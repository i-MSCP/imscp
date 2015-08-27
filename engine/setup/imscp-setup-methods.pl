#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use FindBin;
use DateTime;
use DateTime::TimeZone;
use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;
use Data::Validate::Domain qw/is_domain/;
use Scalar::Util qw(openhandle);
use File::Basename;
use iMSCP::LsbRelease;
use iMSCP::Debug;
use iMSCP::Net;
use iMSCP::Bootstrapper;
use iMSCP::Dialog;
use iMSCP::Stepper;
use iMSCP::Crypt qw/bcrypt decryptRijndaelCBC encryptRijndaelCBC/;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::Mount 'umount';
use iMSCP::Rights;
use iMSCP::TemplateParser;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::OpenSSL;
use Email::Valid;
use iMSCP::Servers;
use iMSCP::Packages;
use iMSCP::Getopt;
use iMSCP::Service;
use iMSCP::DbTasksProcessor;

sub setupBoot
{
	iMSCP::Bootstrapper->getInstance()->boot({ mode => 'setup', nodatabase => 1 });

	unless(%main::imscpOldConfig) {
		%main::imscpOldConfig = ();

		my $oldConfig = "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf";
		if(-f $oldConfig) {
			tie %main::imscpOldConfig, 'iMSCP::Config', fileName => $oldConfig, readonly => 1;
		}
	}

	0;
}

sub setupRegisterListeners
{
	my $eventManager = iMSCP::EventManager->getInstance();

	for my $server(iMSCP::Servers->getInstance()->getFull()) {
		eval "require $server" or die(sprintf('Could not load %s package: %s', $server, $@));
		my $obj = $server->factory();

		if(my $ref = $obj->can('registerSetupListeners')) {
			my $rs = $ref->($obj, $eventManager);
			return $rs if $rs;
		}
	}

	for my $package(iMSCP::Packages->getInstance()->getFull()) {
		eval "require $package" or die(sprintf('Could not load %s package: %s', $package, $@));
		my $obj = $package->getInstance();

		if(my $ref = $obj->can('registerSetupListeners')) {
			my $rs = $ref->($obj, $eventManager);
			return $rs if $rs;
		}
	}

	0;
}

sub setupDialog
{
	my $dialogStack = [];

	iMSCP::EventManager->getInstance()->trigger('beforeSetupDialog', $dialogStack);

	unshift(@$dialogStack, (
		\&setupAskServerHostname,
		\&setupAskServerIps,
		\&setupAskSqlDsn,
		\&setupAskSqlUserHost,
		\&setupAskImscpDbName,
		\&setupAskDbPrefixSuffix,
		\&setupAskDefaultAdmin,
		\&setupAskAdminEmail,
		\&setupAskTimezone,
		\&setupAskServicesSsl,
		\&setupAskImscpBackup,
		\&setupAskDomainBackup
	));

	my $dialog = iMSCP::Dialog->getInstance();
	$dialog->set('ok-label', 'Ok');
	$dialog->set('yes-label', 'Yes');
	$dialog->set('no-label', 'No');
	$dialog->set('cancel-label', 'Back');

	# Implements a simple state machine (backup capability)
	# Any dialog subroutine *should* allow user to step back by returning 30 when 'back' button is pushed
	my ($state, $nbDialog) = (0, scalar @{$dialogStack});

	while($state != $nbDialog) {
		my $rs = $$dialogStack[$state]->($dialog);
		return $rs if $rs && $rs != 30;

		# User asked for step back?
		if($rs == 30) {
			$state != 0 ? $state-- : 0; # We don't allow to step back before first question
			$main::reconfigure = 'forced' if $main::reconfigure eq 'none';
		} else {
			$main::reconfigure = 'none' if $main::reconfigure eq 'forced';
			$state++;
		}
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupDialog');
}

sub setupTasks
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupTasks');

	# Umount any filesystem under /var/www/virtual for safety reasons
	umount($main::imscpConfig{'USER_WEB_DIR'});

	my @steps = (
		[ \&setupSaveOldConfig,           'Saving old configuration file' ],
		[ \&setupWriteNewConfig,          'Writing new configuration file' ],
		[ \&setupCreateMasterGroup,       'Creating system master group' ],
		[ \&setupCreateSystemDirectories, 'Creating system directories' ],
		[ \&setupServerHostname,          'Setting server hostname' ],
		[ \&setupCreateDatabase,          'Creating/updating i-MSCP database' ],
		[ \&setupSecureSqlInstallation,   'Securing SQL installation' ],
		[ \&setupServerIps,               'Setting server IP addresses' ],
		[ \&setupDefaultAdmin,            'Creating/updating default admin account' ],
		[ \&setupServices,                'Setup services' ],
		[ \&setupServiceSsl,              'Setup SSL for i-MSCP services' ],
		[ \&setupServersAndPackages,      'Setup servers/packages' ],
		[ \&setupRebuildCustomerFiles,    'Rebuilding customers files' ],
		[ \&setupSetPermissions,          'Setting permissions' ],
		[ \&setupRestartServices,         'Restarting services' ]
	);

	my ($nStep, $nSteps) = (0, scalar @steps);
	step(@{ $steps[$nStep] }, $nSteps, ++$nStep) for @steps;
	iMSCP::EventManager->getInstance()->trigger('afterSetupTasks');
}

#
## Dialog subroutines
#

sub setupAskServerHostname
{
	my $dialog = shift;

	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my %options = (domain_private_tld => qr /.*/);
	my ($rs, @labels) = (0, $hostname ? split(/\./, $hostname) : ());

	if(
		$main::reconfigure ~~ [ 'system_hostname', 'hostnames', 'all', 'forced' ] ||
		! (@labels >= 3 && is_domain($hostname, { domain_private_tld => qr /.*/ }))
	) {
		unless($hostname) {
			if (execute('hostname -f', \my $stdout, \my $stderr)) {
				error(sprintf('Could not find server hostname (server misconfigured?): %s', $stderr)) if $stderr;
				$stderr or error('Could not find server hostname (server misconfigured?)');
			} else {
				chomp($hostname = $stdout);
			}
		}

		my $msg = '';
		$dialog->set('no-cancel', '');

		do {
			($rs, $hostname) = $dialog->inputbox(
				"\nPlease enter a fully-qualified hostname (FQHN): $msg", idn_to_unicode($hostname, 'utf-8')
			);
			$msg = "\n\n\\Z1'$hostname' is not a valid fully-qualified host name.\\Zn\n\nPlease, try again:";
			$hostname = idn_to_ascii($hostname, 'utf-8');
			@labels = split(/\./, $hostname);

		} while($rs != 30 && ! (@labels >= 3 && is_domain($hostname, { domain_private_tld => qr /.*/ })));

		$dialog->set('no-cancel', undef);
	}

	setupSetQuestion('SERVER_HOSTNAME', $hostname) if $rs != 30;

	$rs;
}

sub setupAskServerIps
{
	my $dialog = shift;

	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my $baseServerPublicIp = setupGetQuestion('BASE_SERVER_PUBLIC_IP');
	my $serverIps = '';

	my $serverIpsToAdd = setupGetQuestion('SERVER_IPS', []);
	my %serverIpsToDelete = ();
	my %serverIpsReplMap = ();

	my $net = iMSCP::Net->getInstance();
	my $rs = 0;

	# Retrieve list of all configured IP addresses
	my @serverIps = $net->getAddresses();
	@serverIps or die('Could not retrieve servers IPs');

	my $currentServerIps = { };
	my $db = '';
	my $msg = '';

	if(setupGetQuestion('DATABASE_NAME', undef)) {
		# We do not raise error in case we cannot get SQL connection since it's expected in some contexts
		$db = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

		if($db) {
			$currentServerIps = $db->doQuery('ip_number', 'SELECT ip_id, ip_number FROM server_ips');
			ref $currentServerIps eq 'HASH' or die(sprintf('Could not retrieve server IPs: %s', $currentServerIps));
		}

		@{$serverIpsToAdd} = (@{$serverIpsToAdd}, keys %{$currentServerIps});
	}

	@serverIps = sort keys %{ { map { $_ => 1 } @serverIps, @{$serverIpsToAdd} } };

	if(
		$main::reconfigure ~~ [ 'ips', 'all', 'forced' ] || ! ($baseServerIp ~~ @serverIps) ||
		! ($net->isValidAddr($baseServerIp) && $net->isValidAddr($baseServerPublicIp))
	) {
		do {
			# Ask user for the server base IP
			($rs, $baseServerIp) = $dialog->radiolist(
				"\nPlease, select the base server IP for i-MSCP:",
				[@serverIps],
				($baseServerIp && $baseServerIp ~~ @serverIps) ? $baseServerIp : $serverIps[0]
			);
		} while($rs != 30 && ! $baseServerIp);

		if($rs != 30) {
			# Server inside private LAN?
			if($net->getAddrType($baseServerIp) ne 'PUBLIC') {
				if (! $net->isValidAddr($baseServerPublicIp) || $net->getAddrType($baseServerPublicIp) ne 'PUBLIC') {
					$baseServerPublicIp = '';
				}

				$msg = '';

				do {
					($rs, $baseServerPublicIp) = $dialog->inputbox(
"
The system has detected that your server is inside a private LAN.

Please enter your public IP:$msg

\\ZbNote:\\Zn Leave blank to force usage of the $baseServerIp IP address.
",
						$baseServerPublicIp
					);

					if($baseServerPublicIp) {
						unless($net->isValidAddr($baseServerPublicIp)) {
							$msg = "\n\n\\Z1Invalid or unallowed IP address.\\Zn\n\nPlease, try again:";
						} elsif($net->getAddrType($baseServerPublicIp) ne 'PUBLIC') {
							$msg = "\n\n\\Z1Unallowed IP address. The IP address must be public.\\Zn\n\nPlease, try again:";
						} else {
							$msg = '';
						}
					} else {
						$baseServerPublicIp = $baseServerIp;
						$msg = ''
					}
				} while($rs != 30 && $msg);
			} else {
				$baseServerPublicIp = $baseServerIp
			}
		}

		# Handle additional IP addition / deletion
		if($rs != 30) {
			$dialog->set('defaultno', '');

			if(@serverIps > 1) {
				$dialog->set('defaultno', undef);

				@serverIps = grep { $_ ne $baseServerIp } @serverIps; # Remove the base server IP from the list

				# Retrieve IP to which the user is currently connected (SSH)
				my $sshConnectionIp = defined ($ENV{'SSH_CONNECTION'}) ? (split ' ', $ENV{'SSH_CONNECTION'})[2] : undef;
				$msg = '';

				do {
					($rs, $serverIps) = $dialog->checkbox(
						"\nPlease, select additional IP addresses to add into the database and deselect those to delete: $msg",
						[@serverIps],
						@{$serverIpsToAdd}
					);

					$msg = '';

					if(defined $sshConnectionIp && $sshConnectionIp ~~ @serverIps && not $sshConnectionIp ~~ $serverIps) {
						$msg = "\n\n\\Z1You cannot remove the $sshConnectionIp IP to which you are currently connected " .
						"through SSH.\\Zn\n\nPlease, try again:";
					}
				} while ($rs != 30 && $msg);

				if($rs != 30) {
					@{$serverIpsToAdd} = @{$serverIps}; # Retrieve list of IP to add into database
					push @{$serverIpsToAdd}, $baseServerIp; # Re-add base ip

					if($db) {
						# Get list of IP addresses to delete
						%serverIpsToDelete = ();

						for my $ipAddr(@serverIps) {
							if(exists $currentServerIps->{$ipAddr} && not $_ ~~ @{$serverIpsToAdd}) {
								$serverIpsToDelete{$currentServerIps->{$ipAddr}->{'ip_id'}} = $ipAddr;
							}
						}

						# Check for server IP addresses already in use and ask for replacement
						my $resellerIps = $db->doQuery('reseller_ips', 'SELECT reseller_ips FROM reseller_props');
						ref $resellerIps eq 'HASH' or die(sprintf(
							"Could not retrieve resellers's addresses IP: %s", $resellerIps
						));

						for my $ipAddrs(keys %{$resellerIps}){
							my @resellerIps = split ';', $ipAddrs;

							for my $ipAddr(@resellerIps) {
								if(
									exists $serverIpsToDelete{$ipAddr} &&
									! exists $serverIpsReplMap{$serverIpsToDelete{$ipAddr}}
								) {
									my $ret = '';

									do {
										($rs, $ret) = $dialog->radiolist(
"
The  $serverIpsToDelete{$ipAddr} IP address is already in use. Please, choose an IP to replace it:
",
											$serverIpsToAdd,
											$baseServerIp
										);
									} while($rs != 30 && ! $ret);

									$serverIpsReplMap{$serverIpsToDelete{$ipAddr}} = $ret;
								}

								last if $rs;
							}

							last if $rs;
						}
					}
				}
			}

			$dialog->set('defaultno', undef);
		}
	}

	if($rs != 30) {
		setupSetQuestion('BASE_SERVER_IP', $baseServerIp);
		setupSetQuestion('BASE_SERVER_PUBLIC_IP', $baseServerPublicIp);
		setupSetQuestion('SERVER_IPS', $serverIpsToAdd);
		setupSetQuestion('SERVER_IPS_TO_REPLACE', {%serverIpsReplMap});
		setupSetQuestion('SERVER_IPS_TO_DELETE', [values %serverIpsToDelete]);
	}

	$rs;
}

# Fixme: Should me moved in SQLD server installer
sub setupAskSqlDsn
{
	my $dialog = shift;

	my $dbType = setupGetQuestion('DATABASE_TYPE') || 'mysql';
	my $dbHost = setupGetQuestion('DATABASE_HOST') || 'localhost';
	my $dbPort = setupGetQuestion('DATABASE_PORT') || '3306';
	my $dbUser = setupGetQuestion('DATABASE_USER') || 'root';
	my $dbPass;

	if(iMSCP::Getopt->preseed) {
		$dbPass = setupGetQuestion('DATABASE_PASSWORD');
	} else {
		$dbPass = setupGetQuestion('DATABASE_PASSWORD');
		$dbPass = defined $dbPass && $dbPass ne ''
			? decryptRijndaelCBC($main::imscpConfig{'DB_KEY'}, $main::imscpConfig{'DB_IV'}, $dbPass)
			: '';
	}

	my $rs = 0;

	if(
		$main::reconfigure ~~ [ 'sql', 'servers', 'all', 'forced' ] ||
		($dbPass eq '' || setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass))
	) {
		my $msg = my $dbError = '';

		do {
			$dialog->msgbox($msg) if $msg;
			$msg = '';

			# Ask for SQL server hostname (Accept both hostname and Ip)
			do {
				($rs, $dbHost) = $dialog->inputbox(
					"\nPlease enter a hostname or IP for the SQL server: $msg", idn_to_unicode($dbHost, 'utf-8')
				);
				$msg = "\n\n\\Z1'$dbHost' is not a valid hostname nor a valid ip.\\Zn\n\nPlease, try again:";
				$dbHost = idn_to_ascii($dbHost, 'utf-8');
			} while (
				$rs != 30 &&
				! (
					$dbHost eq 'localhost' || is_domain($dbHost, { domain_private_tld => qr /.*/ }) ||
					iMSCP::Net->getInstance()->isValidAddr($dbHost)
				)
			);

			if($rs != 30) {
				$msg = '';

				# Ask for SQL server port only if needed (socket vs tcp)
				if($dbHost ne 'localhost' || ! ($dbPort =~ /^[\d]+$/ && int($dbPort) > 1024 && int($dbPort) < 65536)) {
					do {
						($rs, $dbPort) = $dialog->inputbox("\nPlease enter a port for the SQL server: $msg", $dbPort);
						$msg  = "\n\n\\Z1'$dbPort' is not a valid port number or is out of allowed range.\\Zn\n\nPlease, try again:";
					} while($rs != 30 && ! ($dbPort =~ /^[\d]+$/ && int($dbPort) > 1024 && int($dbPort) < 65536));
				} else { # Simply put the default port even if not used
					$dbPort = '3306';
				}
			}

			# Ask for SQL username
			if($rs != 30) {
				$msg = '';

				do {
					($rs, $dbUser) = $dialog->inputbox(
						"\nPlease, enter an SQL username. This user must exists and have full privileges on SQL server:$msg",
						$dbUser
					);
				} while($rs != 30 && ! $dbUser);
			}

			# Ask for SQL user password
			if($rs != 30) {
				do {
					($rs, $dbPass) = $dialog->passwordbox(
						"\nPlease, enter a password for the '$dbUser' SQL user:$msg", $dbPass
					);

					$msg = "\n\n\\Z1Password cannot be empty.\\Zn\n\nPlease, try again:"
				} while($rs != 30 && $dbPass eq '');

				$msg = '';

				if(($dbError = setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass))) {
					$msg =
"
\\Z1Connection to SQL server failed\\Zn

i-MSCP was unable to connect to the SQL server using the following data:

\\Z4Host:\\Zn $dbHost
\\Z4Port:\\Zn $dbPort
\\Z4Username:\\Zn $dbUser
\\Z4Password:\\Zn $dbPass

Error was: $dbError

Please, try again.
";
				}
			}

		} while($rs != 30 && $msg);
	}

	if($rs != 30) {
		setupSetQuestion('DATABASE_TYPE', $dbType);
		setupSetQuestion('DATABASE_HOST', $dbHost);
		setupSetQuestion('DATABASE_PORT', $dbPort);
		setupSetQuestion('DATABASE_USER', $dbUser);
		setupSetQuestion('DATABASE_PASSWORD', encryptRijndaelCBC(
			$main::imscpConfig{'DB_KEY'}, $main::imscpConfig{'DB_IV'}, $dbPass)
		);
	}

	$rs;
}

# Fixme: Should me moved in SQLD server installer
sub setupAskSqlUserHost
{
	my $dialog = shift;

	my $host = setupGetQuestion('DATABASE_USER_HOST');
	$host = ($host eq '127.0.0.1') ? 'localhost' : $host;
	my $rs = 0;

	if(setupGetQuestion('DATABASE_HOST') ne 'localhost') { # Remote MySQL server
		if($main::reconfigure ~~ [ 'sql', 'servers', 'all', 'forced' ] || ! $host) {
			do {
				($rs, $host) = $dialog->inputbox(
"
Please, enter the host from which SQL users created by i-MSCP must be allowed to connect to your SQL server:

Important: No check is made on the entered value. Please refer to the following document for allowed values.

	http://dev.mysql.com/doc/refman/5.5/en/account-names.html

Note that 127.0.0.7 is always mapped to 'localhost'.
",
					$host // setupGetQuestion('BASE_SERVER_IP')
				);
			} while($rs != 30 && $host eq '');

			# map 127.0.0.1 to localhost for consistency reasons
			$host = 'localhost' if $host eq '127.0.0.1';
		}

		setupSetQuestion('DATABASE_USER_HOST', $host) if $rs != 30;
	} else {
		setupSetQuestion('DATABASE_USER_HOST', 'localhost');
	}

	$rs;
}

# Fixme: Should me moved in SQLD server installer
sub setupAskImscpDbName
{
	my $dialog = shift;

	my $dbName = setupGetQuestion('DATABASE_NAME') || 'imscp';
	my $rs = 0;

	if(
		$main::reconfigure ~~ [ 'sql', 'servers', 'all', 'forced' ] ||
		(! iMSCP::Getopt->preseed && ! setupIsImscpDb($dbName))
	) {
		my $msg = '';

		do {
			($rs, $dbName) = $dialog->inputbox("\nPlease, enter a database name for i-MSCP: $msg", $dbName);
			$msg = '';

			unless($dbName) {
				$msg = "\n\n\\Z1Database name cannot be empty.\\Zn\n\nPlease, try again:";
			} elsif($dbName =~ /[:;]/) {
				$msg = "\n\n\\Z1Database name contain illegal characters ':' and/or ';'.\\Zn\n\nPlease, try again:";
			} elsif(setupGetSqlConnect($dbName) && ! setupIsImscpDb($dbName)) {
				$msg = "\n\n\\Z1Database '$dbName' exists but do not look like an i-MSCP database.\\Zn\n\nPlease, try again:";
			}
		} while ($rs != 30 && $msg);

		if($rs != 30) {
			my $oldDbName = setupGetQuestion('DATABASE_NAME');

			if($oldDbName && $dbName ne $oldDbName && setupIsImscpDb($oldDbName)) {
				$dialog->set('defaultno', '');

				$dbName = setupGetQuestion('DATABASE_NAME') if $dialog->yesno(
"
\\Z1An i-MSCP database has been found\\Zn

A database '$main::imscpConfig{'DATABASE_NAME'}' for i-MSCP already exists.

Are you sure you want to create a new database?

Keep in mind that the new database will be free of any reseller and customer data.

\\Z4Note:\\Zn If the database you want to create already exists, nothing will happen.
"
				);

				$dialog->set('defaultno', undef);
			}
		}
	}

	setupSetQuestion('DATABASE_NAME', $dbName) if $rs != 30;

	$rs;
}

# Fixme: Should me moved in SQLD server installer
sub setupAskDbPrefixSuffix
{
	my $dialog = shift;

	my $prefix = setupGetQuestion('MYSQL_PREFIX');
	my $prefixType = setupGetQuestion('MYSQL_PREFIX_TYPE');
	my $rs = 0;

	if(
		$main::reconfigure ~~ [ 'sql', 'servers', 'all', 'forced' ] ||
		! (($prefix eq 'no' && $prefixType eq 'none') || ($prefix eq 'yes' && $prefixType =~ /^infront|behind$/))
	) {

		($rs, $prefix) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuMySQL Database Prefix/Suffix\\Zn

Do you want use a prefix or suffix for customer's SQL databases?

\\Z4Infront:\\Zn A numeric prefix such as '1_' will be added to each customer
         SQL user and database name.
 \\Z4Behind:\\Zn A numeric suffix such as '_1' will be added to each customer
         SQL user and database name.
   \\Z4None\\Zn: Choice will be let to customer.
",
			[ 'infront', 'behind', 'none' ],
			$prefixType =~ /^infront|behind$/ ? $prefixType : 'none'
		);

		if($prefix eq 'none') {
			$prefix = 'no';
			$prefixType = 'none';
		} else {
			$prefixType = $prefix;
			$prefix = 'yes';
		}
	}

	if($rs != 30) {
		setupSetQuestion('MYSQL_PREFIX', $prefix);
		setupSetQuestion('MYSQL_PREFIX_TYPE', $prefixType);
	}

	$rs;
}

# Fixme: Should me moved in frontEnd package installer
sub setupAskDefaultAdmin
{
	my $dialog = shift;

	my ($adminLoginName, $password, $rpassword) = ('', '', '');
	my ($rs, $msg) = (0, '');

	my $db = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

	if(iMSCP::Getopt->preseed) {
		$adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME');
		$password = setupGetQuestion('ADMIN_PASSWORD');
		$adminLoginName = '' if $password eq '';
	} elsif($db) {
		my $defaultAdmin = $db->doQuery(
			'created_by',
			"SELECT admin_name, created_by FROM admin WHERE created_by = 0 AND admin_type = 'admin' LIMIT 1",
		);
		ref $defaultAdmin eq 'HASH' or die($defaultAdmin);

		if(%{$defaultAdmin}) {
			$adminLoginName = $$defaultAdmin{'0'}->{'admin_name'};
		}
	}

	setupSetQuestion('ADMIN_OLD_LOGIN_NAME', $adminLoginName);

	if($main::reconfigure ~~ [ 'admin', 'all', 'forced' ] || $adminLoginName eq '') {

		# Ask for administrator login name
		do {
			($rs, $adminLoginName) = $dialog->inputbox(
				"\nPlease, enter admin login name: $msg", $adminLoginName || 'admin'
			);

			$msg = '';

			if($adminLoginName eq '') {
				$msg = '\n\n\\Z1Admin login name cannot be empty.\\Zn\n\nPlease, try again:';
			} elsif(
				length $adminLoginName <= 2 ||
				$adminLoginName !~ /^[a-z0-9](:?(?<![-_])(:?-*|[_.])?(?![-_])[a-z0-9]*)*?(?<![-_.])$/i
			) {
				$msg = '\n\n\\Z1Bad admin login name syntax or length.\\Zn\n\nPlease, try again:'
			} elsif($db) {
				my $rdata = $db->doQuery(
					'admin_id',
					'SELECT admin_id FROM admin WHERE admin_name = ? AND created_by <> 0 LIMIT 1',
					$adminLoginName
				);
				ref $rdata eq 'HASH' or die($rdata);

				if(%{$rdata}) {
					$msg = '\n\n\\Z1This login name already exists.\\Zn\n\nPlease, try again:'
				}
			}
		} while($rs != 30 &&  $msg);

		if($rs != 30) {
			$msg = '';

			do {
				# Ask for administrator password
				do {
					($rs, $password) = $dialog->passwordbox("\nPlease, enter admin password: $msg", $password);
					$msg = '\n\n\\Z1The password must be at least 6 characters long.\\Zn\n\nPlease, try again:';
				} while($rs != 30 && length $password < 6);

				# Ask for administrator password confirmation
				if($rs != 30) {
					$msg = '';

					do {
						($rs, $rpassword) = $dialog->passwordbox("\nPlease, confirm admin password: $msg", '');
						$msg = "\n\n\\Z1Passwords do not match.\\Zn\n\nPlease try again:";
					} while($rs != 30 && $rpassword ne $password);
				}
			} while($rs != 30 && $password ne $rpassword);
		}
	}

	if($rs != 30) {
		setupSetQuestion('ADMIN_LOGIN_NAME', $adminLoginName);
		setupSetQuestion('ADMIN_PASSWORD', $password);
	}

	$rs;
}

sub setupAskAdminEmail
{
	my $dialog = shift;

	my $adminEmail = setupGetQuestion('DEFAULT_ADMIN_ADDRESS');
	my $rs = 0;

	if($main::reconfigure ~~ [ 'admin', 'all', 'forced' ] || ! Email::Valid->address($adminEmail)) {
		my $msg = '';

		do {
			($rs, $adminEmail) = $dialog->inputbox("\nPlease, enter admin email address: $msg", $adminEmail);
			$msg = "\n\n\\Z1'$adminEmail' is not a valid email address.\\Zn\n\nPlease, try again:";
		} while( $rs != 30 && ! Email::Valid->address($adminEmail));
	}

	setupSetQuestion('DEFAULT_ADMIN_ADDRESS', $adminEmail) if $rs != 30;

	$rs;
}

sub setupAskTimezone
{
	my $dialog = shift;

	my $defaultTimezone = DateTime->new(year => 0, time_zone => 'local')->time_zone->name;
	my $timezone = setupGetQuestion('TIMEZONE');
	my $rs = 0;

	if(
		$main::reconfigure ~~ [ 'timezone', 'all', 'forced' ] ||
		!($timezone && DateTime::TimeZone->is_valid_name($timezone))
	) {
		$timezone = $defaultTimezone unless $timezone;
		my $msg = '';

		do {
			($rs, $timezone) = $dialog->inputbox("\nPlease enter your timezone: $msg", $timezone);
			$msg = "\n\n\\Z1'$timezone' is not a valid timezone.\\Zn\n\nPlease, try again:";
		} while($rs != 30 && ! DateTime::TimeZone->is_valid_name($timezone));
	}

	setupSetQuestion('TIMEZONE', $timezone) if $rs != 30;

	$rs;
}

# Fixme: We must allow one SSL certificate per service
sub setupAskServicesSsl
{
	my($dialog) = shift;

	my $domainName = setupGetQuestion('SERVER_HOSTNAME');
	my $sslEnabled = setupGetQuestion('SERVICES_SSL_ENABLED');
	my $selfSignedCertificate = setupGetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'no');
	my $privateKeyPath = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH', '/root/');
	my $passphrase = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE');
	my $certificatPath = setupGetQuestion('SERVICES_SSL_CERTIFICATE_PATH', "/root/");
	my $caBundlePath = setupGetQuestion('SERVICES_SSL_CA_BUNDLE_PATH', '/root/');
	my $openSSL = iMSCP::OpenSSL->new();
	my $rs = 0;

	if(
		$main::reconfigure ~~ [ 'services_ssl', 'ssl', 'all', 'forced' ] || not $sslEnabled ~~ [ 'yes', 'no' ] ||
		($sslEnabled eq 'yes' &&  $main::reconfigure ~~ [ 'system_hostname', 'hostnames' ])
	) {
		SSL_DIALOG:

		# Ask for SSL
		($rs, $sslEnabled) = $dialog->radiolist(
			"\nDo you want to activate SSL for the i-MSCP services (ftp, smtp...)?",
			[ 'no', 'yes' ],
			($sslEnabled eq 'yes') ? 'yes' : 'no'
		);

		if($sslEnabled eq 'yes' && $rs != 30) {
			# Ask for self-signed certificate
			($rs, $selfSignedCertificate) = $dialog->radiolist(
				"\nDo you have an SSL certificate for the $domainName domain?",
				[ 'yes', 'no' ],
				($selfSignedCertificate ~~ [ 'yes', 'no' ]) ? (($selfSignedCertificate eq 'yes') ? 'no' : 'yes') : 'no'
			);

			$selfSignedCertificate = ($selfSignedCertificate eq 'no') ? 'yes' : 'no';

			if($selfSignedCertificate eq 'no' && $rs != 30) {
				# Ask for private key
				my $msg = '';

				do {
					$dialog->msgbox("$msg\nPlease select your private key in next dialog.");

					# Ask for private key container path
					do {
						($rs, $privateKeyPath) = $dialog->fselect($privateKeyPath);
					} while($rs != 30 && ! ($privateKeyPath && -f $privateKeyPath));

					if($rs != 30) {
						($rs, $passphrase) = $dialog->passwordbox(
							"\nPlease enter the passphrase for your private key if any:", $passphrase
						);
					}

					if($rs != 30) {
						$openSSL->{'private_key_container_path'} = $privateKeyPath;
						$openSSL->{'private_key_passphrase'} = $passphrase;

						if($openSSL->validatePrivateKey()) {
							$msg = "\n\\Z1Wrong private key or passphrase. Please try again.\\Zn\n\n";
						} else {
							$msg = '';
						}
					}
				} while($rs != 30 && $msg);

				# Ask for the CA bundle
				if($rs != 30) {
					# The codes used for "Yes" and "No" match those used for "OK" and "Cancel", internally no
					# distinction is made... Therefore, we override the Cancel value temporarly
					$ENV{'DIALOG_CANCEL'} = 1;
					$rs = $dialog->yesno("\nDo you have any SSL intermediate certificate(s) (CA Bundle)?");

					unless($rs) { # backup feature still available through ESC
						do {
							($rs, $caBundlePath) = $dialog->fselect($caBundlePath);
						} while($rs != 30 && ! ($caBundlePath && -f $caBundlePath));

						$openSSL->{'ca_bundle_container_path'} = $caBundlePath if $rs != 30;
					}else {
						$openSSL->{'ca_bundle_container_path'} = '';
					}

					$ENV{'DIALOG_CANCEL'} = 30;
				}

				if($rs != 30) {
					$dialog->msgbox("\nPlease select your SSL certificate in next dialog.");

					$rs = 1;

					do {
						$dialog->msgbox("\n\\Z1Wrong SSL certificate. Please try again.\\Zn\n\n") if ! $rs;

						do {
							($rs, $certificatPath) = $dialog->fselect($certificatPath);
						} while($rs != 30 && ! ($certificatPath && -f $certificatPath));

						$openSSL->{'certificate_container_path'} = $certificatPath if $rs != 30;
					} while($rs != 30 && $openSSL->validateCertificate());
				}
			}
		}
	} elsif($sslEnabled eq 'yes' && ! iMSCP::Getopt->preseed) {
		$openSSL->{'private_key_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
		$openSSL->{'ca_bundle_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
		$openSSL->{'certificate_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";

		if($openSSL->validateCertificateChain()) {
			iMSCP::Dialog->getInstance()->msgbox("\nYour SSL certificate for the services is missing or invalid.");
			goto SSL_DIALOG;
		}

		# In case the certificate is valid, we do not generate it again
		setupSetQuestion('SERVICES_SSL_SETUP', 'no');
	}

	if($rs != 30) {
		setupSetQuestion('SERVICES_SSL_ENABLED', $sslEnabled);
		setupSetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate);
		setupSetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH', $privateKeyPath);
		setupSetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase);
		setupSetQuestion('SERVICES_SSL_CERTIFICATE_PATH', $certificatPath);
		setupSetQuestion('SERVICES_SSL_CA_BUNDLE_PATH', $caBundlePath);
	}

	$rs;
}

# Fixme: Should be configurable through GUI only, or by editing conffile manually
sub setupAskImscpBackup
{
	my $dialog = shift;

	my $backupImscp = setupGetQuestion('BACKUP_IMSCP');
	my $rs = 0;

	if($main::reconfigure ~~ [ 'backup', 'all', 'forced' ] || $backupImscp !~ /^yes|no$/) {
		($rs, $backupImscp) = $dialog->radiolist(
"
\\Z4\\Zb\\Zui-MSCP Backup Feature\\Zn

Do you want activate the backup feature for i-MSCP?

The backup feature for i-MSCP allows the daily save of all i-MSCP
configuration files and its database. It's greatly recommended to
activate this feature.
",
			[ 'yes', 'no' ],
			$backupImscp ne 'no' ? 'yes' : 'no'
		);
	}

	setupSetQuestion('BACKUP_IMSCP', $backupImscp) if $rs != 30;

	$rs;
}

# Fixme: Should be configurable through GUI only, or by editing conffile manually
sub setupAskDomainBackup
{
	my $dialog = shift;

	my $backupDomains = setupGetQuestion('BACKUP_DOMAINS');
	my $rs = 0;

	if($main::reconfigure ~~ [ 'backup', 'all', 'forced' ] || $backupDomains !~ /^yes|no$/) {
		($rs, $backupDomains) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuDomains Backup Feature\\Zn

Do you want activate the backup feature for customers?

This feature allows resellers to propose backup options to their customers such as:

 - Full (domains and SQL databases)
 - Domains only (Web files)
 - SQL databases only
 - None (no backup)
",
			[ 'yes', 'no' ],
			$backupDomains ne 'no' ? 'yes' : 'no'
		);
	}

	setupSetQuestion('BACKUP_DOMAINS', $backupDomains) if $rs != 30;

	$rs;
}

#
## Setup subroutines
#

sub setupSaveOldConfig
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupSaveOldConfig');

	my $file = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf" );
	my $cfg = $file->get();

	$file = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf" );
	$file->set($cfg);
	$file->save();

	iMSCP::EventManager->getInstance()->trigger('afterSetupSaveOldConfig');
}

sub setupWriteNewConfig
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupWriteNewConfig');

	while(my($question, $value) = each(%main::questions)) {
		if(exists $main::imscpConfig{$question}) {
			$main::imscpConfig{$question} = $value;
		}
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupWriteNewConfig');
}

sub setupCreateMasterGroup
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateMasterGroup');

	my $rs = iMSCP::SystemGroup->getInstance()->addSystemGroup($main::imscpConfig{'IMSCP_GROUP'}, 1);
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupCreateMasterGroup');
}

# Fixme Should be removed - Any directory should be created through layout file
sub setupCreateSystemDirectories
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my @systemDirectories = ( [ $main::imscpConfig{'BACKUP_FILE_DIR'}, $rootUName, $rootGName, 0750 ] );

	iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateSystemDirectories', \@systemDirectories);

	for my $dir(@systemDirectories) {
		iMSCP::Dir->new( dirname => $dir->[0] )->make({ user => $dir->[1], group => $dir->[2], mode => $dir->[3] });
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupCreateSystemDirectories');
}

sub setupServerHostname
{
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');

	# /etc/host file

	my $file = iMSCP::File->new( filename  => '/etc/hosts' );
	$file->copyFile('/etc/hosts.bkp') unless -f '/etc/hosts.bkp';

	my $eventManager = iMSCP::EventManager->getInstance();
	$eventManager->trigger('beforeSetupServerHostsFile', \$hostname, \$baseServerIp);

	my @labels = split /\./, $hostname;
	my $host = shift @labels;
	my $localHostname = $hostname . '.local';
	my $net = iMSCP::Net->getInstance();

	my $fileContent = "# 'hosts' file configuration.\n\n";
	$fileContent .= "127.0.0.1\t$localHostname\tlocalhost\n";
	$fileContent .= "$baseServerIp\t$hostname\t$host\n";
	$fileContent .= "::ffff:$baseServerIp\t$hostname\t$host\n" if $net->getAddrVersion($baseServerIp) eq 'ipv4';
	$fileContent .= "::1\tip6-localhost\tip6-loopback\n" if $net->getAddrVersion($baseServerIp) eq 'ipv4';
	$fileContent .= "::1\tip6-localhost\tip6-loopback\t$host\n" if $net->getAddrVersion($baseServerIp) eq 'ipv6';
	$fileContent .= "fe00::0\tip6-localnet\n";
	$fileContent .= "ff00::0\tip6-mcastprefix\n";
	$fileContent .= "ff02::1\tip6-allnodes\n";
	$fileContent .= "ff02::2\tip6-allrouters\n";
	$fileContent .= "ff02::3\tip6-allhosts\n";

	$eventManager->trigger('afterSetupServerHostsFile', \$fileContent);

	$file->set($fileContent);
	$file->save();
	$file->mode(0644);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	# /etc/hostname file
	$file = iMSCP::File->new( filename => '/etc/hostname' );
	$file->copyFile('/etc/hostname.bkp') unless -f '/etc/hostname.bkp';

	$eventManager->trigger('beforeSetupServerHostnameFile', \$fileContent, $hostname);
	$fileContent = $host;
	$eventManager->trigger('afterSetupServerHostnameFile', \$fileContent, $hostname);

	$file->set($fileContent);
	$file->save();
	$file->mode(0644);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	my $rs = execute("hostname $host", \my $stdout, \my $stderr);
	error($stderr) if $rs && $stderr;
	error('Could not set server hostname') if $rs && ! $stderr;
	$rs;
}

sub setupServerIps
{
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my $serverIpsToReplace = setupGetQuestion('SERVER_IPS_TO_REPLACE') || { };
	my $serverIpsToDelete = setupGetQuestion('SERVER_IPS_TO_DELETE') || [];
	my $oldIptoIdMap = { };
	my @serverIps = ( $baseServerIp, ($main::questions{'SERVER_IPS'}) ? @{$main::questions{'SERVER_IPS'}} : () );

	iMSCP::EventManager->getInstance()->trigger(
		'beforeSetupServerIps', \$baseServerIp, \@serverIps, $serverIpsToReplace
	);

	# Ensure promoting of secondary IP addresses in case a PRIMARY addresse is being deleted
	# Note we are ignoring return value here (eg for vps)
	execute("sysctl -q -w net.ipv4.conf.all.promote_secondaries=1", \my $stdout, \my $stderr);

	my ($db, $errstr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	$db or die(sprintf('Could not connect to the SQL database: %s', $errstr));

	# Get IDs of IP addresses to replace
	if(%{$serverIpsToReplace}) {
		my $ipsToReplace = join q{,}, map $db->quote($_), keys %{$serverIpsToReplace};
		$oldIptoIdMap = $db->doQuery(
			'ip_number', 'SELECT ip_id, ip_number FROM server_ips WHERE ip_number IN ('. $ipsToReplace .')'
		);
		ref $oldIptoIdMap eq 'HASH' or die(sprintf('Could not get IDs of server IPs to replace: %s', $oldIptoIdMap));
	}

	my $net = iMSCP::Net->getInstance();

	# Process server IPs addition

	my $defaultNetcard = (grep { $_ ne 'lo' } $net->getDevices())[0];

	for my $ipAddr (@serverIps) {
		next if exists $serverIpsToReplace->{$ipAddr};
		my $netCard = ($net->isKnownAddr($ipAddr)) ? $net->getAddrDevice($ipAddr) || $defaultNetcard : $defaultNetcard;

		if($netCard) {
			my $rs = $db->doQuery(
				'i', 'INSERT IGNORE INTO server_ips (ip_number, ip_card, ip_status) VALUES(?, ?, ?)', $ipAddr, $netCard,
				'toadd'
			);
			ref $rs eq 'HASH' or die(sprintf('Could not add/update the %s IP address: %s', $ipAddr, $rs));
		} else {
			die(sprintf('Could not add the %s IP address into database: Unknown network card', $ipAddr));
		}
	}

	# Server IPs replacement

	if(%{$serverIpsToReplace}) {
		# for each IP to replace
		for(keys %$serverIpsToReplace) {
			my $newIp = $serverIpsToReplace->{$_}; # New IP
			my $oldIpId = $oldIptoIdMap->{$_}->{'ip_id'}; # Old IP ID

			# Get IP IDs of resellers to which the IP to replace is currently assigned
			my $resellerIps = $db->doQuery(
				'id', 'SELECT id, reseller_ips FROM reseller_props WHERE reseller_ips REGEXP ?', "(^|[^0-9]$oldIpId;)"
			);
			ref $resellerIps eq 'HASH' or die($resellerIps);

			# Get new IP ID
			my $newIpId = $db->doQuery(
				'ip_number', 'SELECT ip_id, ip_number FROM server_ips WHERE ip_number = ?', $newIp
			);
			ref $newIpId eq 'HASH' or die($newIpId);

			$newIpId = $newIpId->{$newIp}->{'ip_id'};

			for(keys %$resellerIps) {
				my $ips = $resellerIps->{$_}->{'reseller_ips'};

				if($ips !~ /(?:^|[^0-9])$newIpId;/) {
					$ips =~ s/((?:^|[^0-9]))$oldIpId;?/$1$newIpId;/;
					my $rs = $db->doQuery('u', 'UPDATE reseller_props SET reseller_ips = ? WHERE id = ?', $ips, $_);
					ref $rs eq 'HASH' or die($rs);
				}
			}

			# Update IP id of customer domains if needed
			my $rs = $db->doQuery('u', 'UPDATE domain SET domain_ip_id = ? WHERE domain_ip_id = ?', $newIpId, $oldIpId);
			ref $rs eq 'HASH' or die($rs);
			
			# Update IP id of customer domain aliases if needed
			$rs = $db->doQuery(
				'u', 'UPDATE domain_aliasses SET alias_ip_id = ? WHERE alias_ip_id = ?', $newIpId, $oldIpId
			);
			ref $rs eq 'HASH' or die($rs);
		}
	}

	# Process IP deletion
	if(@{$serverIpsToDelete}) {
		my $serverIpsToDelete = join q{,}, map $db->quote($_), @{$serverIpsToDelete};
		my $rs = $db->doQuery(
			'u',
			'UPDATE server_ips set ip_status = ?  WHERE ip_number IN(' . $serverIpsToDelete . ') AND ip_number <> ?',
			'todelete',
			$baseServerIp
		);
		ref $rs eq 'HASH' or die($rs);
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupServerIps');
}

# Fixme Should be moved in SQLD server installer
sub setupCreateDatabase
{
	my $dbName = setupGetQuestion('DATABASE_NAME');

	iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateDatabase', \$dbName);

	unless(setupIsImscpDb($dbName)) {
		my ($db, $errStr) = setupGetSqlConnect();
		$db or die(sprintf('Could not connect to SQL server: %s', $errStr));

		my $qdbName = $db->quoteIdentifier($dbName);
		my $rs = $db->doQuery('dummy', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
		ref $rs eq 'HASH' or die(sprintf("Could not create the '%s' SQL database: %s", $dbName, $rs));

		$db->set('DATABASE_NAME', $dbName);
		$rs = $db->connect();
		return $rs if $rs;

		setupImportSqlSchema($db, "$main::imscpConfig{'CONF_DIR'}/database/database.sql");
	}

	# In all cases, we process database update. This is important because sometime some developer forget to update the
	# database revision in the main database.sql file.
	my $rs = setupUpdateDatabase();
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupCreateDatabase');
}

# Convenience method allowing to create or update a database schema
# Fixme: Should be a method provided by the SQLD server
sub setupImportSqlSchema
{
	my ($db, $file) = @_;

	iMSCP::EventManager->getInstance()->trigger('beforeSetupImportSqlSchema', \$file);

	my $content = iMSCP::File->new( filename => $file )->get();

	$content =~ s/^(--[^\n]{0,})?\n//gm;
	my @queries = split /;\n/, $content;

	for my $query(@queries) {
		my $rs = $db->doQuery('d', $query);
		ref $rs eq 'HASH' or die(sprintf('Could not execute SQL query: %s', $rs));
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupImportSqlSchema');
}

# Fixme: Should be moved in SQLD server installer
sub setupUpdateDatabase
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupUpdateDatabase');

	my $file = iMSCP::File->new( filename => "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php" );
	my $content = $file->get();

	if($content =~ s/\{GUI_ROOT_DIR\}/$main::imscpConfig{'GUI_ROOT_DIR'}/) {
		$file->set($content);
		$file->save();
	}

	my $rs = execute("php $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupUpdateDatabase');
}

# Secure any SQL account by removing those without password
# Basically, this method do same job as the mysql_secure_installation script
# - Remove anonymous users
# - Remove users without password set
# - Remove remote sql root user (only for local server)
# - Remove test database if any
# - Reload privileges tables
# Fixme: Should be moved in SQLD server installer
sub setupSecureSqlInstallation
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupSecureSqlInstallation');

	my ($db, $errStr) = setupGetSqlConnect();
	$db or die(sprintf('Could not connect to SQL server: %s', $errStr));

	# Remove anonymous users
	$errStr = $db->doQuery('d', "DELETE FROM mysql.user WHERE User = ''");
	ref $errStr eq 'HASH' or die(sprintf('Could not delete anonymous users: %s', $errStr));

	# Remove user without password set
	my $rdata = $db->doQuery('User', "SELECT User, Host FROM mysql.user WHERE Password = ''");

	for my $user(keys %{$rdata}) {
		$errStr = $db->doQuery('d', "DROP USER ?@?", $user, $rdata->{$user}->{'Host'});
		ref $errStr eq 'HASH' or die(sprintf(
			'Could not remove SQL user %s@%s: %s', $user, $rdata->{$user}->{'Host'}, $errStr
		));
	}

	# Remove test database if any
	$errStr = $db->doQuery('d', 'DROP DATABASE IF EXISTS test');
	ref $errStr eq 'HASH' or die(sprintf('Could not remove test database: %s',  $errStr));

	# Remove privileges on test database
	$errStr = $db->doQuery('d', "DELETE FROM mysql.db WHERE Db = 'test' OR Db = 'test\\_%'");
	ref $errStr eq 'HASH' or die(sprintf('Could not remove privileges on test database: %s', $errStr));

	# Disallow remote root login
	if($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
		$errStr = $db->doQuery(
			'd', "DELETE FROM mysql.user WHERE User = 'root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
		);
		ref $errStr eq 'HASH' or die(sprintf('Could not remove remote root users: %s', $errStr));
	}

	# Reload privilege tables
	$errStr = $db->doQuery('f', 'FLUSH PRIVILEGES');
	ref $errStr eq 'HASH' or die(sprintf('Could not reload privileges tables: %s', $errStr));

	iMSCP::EventManager->getInstance()->trigger('afterSetupSecureSqlInstallation');
}

# Fixme: Should be moved in frontEnd package installer
sub setupDefaultAdmin
{
	my $adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME');
	my $adminOldLoginName = setupGetQuestion('ADMIN_OLD_LOGIN_NAME');
	my $adminPassword = setupGetQuestion('ADMIN_PASSWORD');
	my $adminEmail = setupGetQuestion('DEFAULT_ADMIN_ADDRESS');

	iMSCP::EventManager->getInstance()->trigger(
		'beforeSetupDefaultAdmin', \$adminLoginName, \$adminPassword, \$adminEmail
	);

	if($adminLoginName && $adminPassword) {
		$adminPassword = bcrypt($adminPassword);

		my ($db, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
		$db or die(sprintf('Could not connect to SQL server: %s', $errStr));

		my $rs = $db->doQuery(
			'admin_name', 'SELECT admin_id, admin_name FROM admin WHERE admin_name = ? LIMIT 1', $adminOldLoginName
		);
		ref $rs eq 'HASH' or die($rs);

		unless(%{$rs}) {
			$rs = $db->doQuery(
				'i',
				'INSERT INTO admin (admin_name, admin_pass, admin_type, email) VALUES (?, ?, ?, ?)',
				$adminLoginName, $adminPassword, 'admin', $adminEmail
			);
			ref $rs eq 'HASH' or die($rs);

			$rs = $db->doQuery(
				'i',
				'
					INSERT IGNORE INTO `user_gui_props` (
						`user_id`, `lang`, `layout`, `layout_color`, `logo`, `show_main_menu_labels`
					) VALUES (
						LAST_INSERT_ID(), ?, ?, ?, ?, ?
					)
				',
				'auto', 'default', 'black', '', '1'
			);
			ref $rs eq 'HASH' or die($rs);
		} else {
			$rs = $db->doQuery(
				'u',
				'UPDATE admin SET admin_name = ?, admin_pass = ?, email = ? WHERE admin_id = ?',
				$adminLoginName, $adminPassword, $adminEmail, $rs->{$adminOldLoginName}->{'admin_id'}
			);
			ref $rs eq 'HASH' or die($rs);
		}
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupDefaultAdmin');
}

sub setupServiceSsl
{
	my $domainName = setupGetQuestion('SERVER_HOSTNAME');
	my $selfSignedCertificate = (setupGetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE') eq 'yes') ? 1 : 0;
	my $privateKeyPath = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH');
	my $passphrase = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE');
	my $certificatePath = setupGetQuestion('SERVICES_SSL_CERTIFICATE_PATH');
	my $caBundlePath = setupGetQuestion('SERVICES_SSL_CA_BUNDLE_PATH');
	my $sslEnabled = setupGetQuestion('SERVICES_SSL_ENABLED');

	if($sslEnabled eq 'yes' && setupGetQuestion('SERVICES_SSL_SETUP', 'yes') eq 'yes') {
		if($selfSignedCertificate) {
			my $rs = iMSCP::OpenSSL->new(
				certificate_chains_storage_dir =>  $main::imscpConfig{'CONF_DIR'},
				certificate_chain_name => 'imscp_services'
			)->createSelfSignedCertificate($domainName);
			return $rs if $rs;
		} else {
			my $rs = iMSCP::OpenSSL->new(
				certificate_chains_storage_dir =>  $main::imscpConfig{'CONF_DIR'},
				certificate_chain_name => 'imscp_services',
				private_key_container_path => $privateKeyPath,
				private_key_passphrase => $passphrase,
				certificate_container_path => $certificatePath,
				ca_bundle_container_path => $caBundlePath
			)->createCertificateChain();
			return $rs if $rs;
		}
	}

	0;
}

sub setupServices
{
	# Be sure that legacy boot ordering is not enabled
	#if(-f "/etc/init.d/.legacy-bootordering") {
	#	iMSCP::File->new( filename => '/etc/init.d/.legacy-bootordering' )->delFile();
	#}

	my $serviceMngr = iMSCP::Service->getInstance();

	if($serviceMngr->isUpstart()) {
		# Work around https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=780641
		$serviceMngr->getProvider('sysvinit')->remove('imscp_mountall');
		$serviceMngr->getProvider('sysvinit')->remove('imscp_network');
	}

	# Service imscp_network has to be enabled first to enable service imscp_mountall
	$serviceMngr->enable($_) for 'imscp_daemon', 'imscp_network', 'imscp_mountall';

	0;
}

# Set Permissions
sub setupSetPermissions
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupSetPermissions');
	my $debug = $main::imscpConfig{'DEBUG'} || 0;
	$main::imscpConfig{'DEBUG'} = (iMSCP::Getopt->debug) ? 1 : 0;

	for my $script ('set-engine-permissions.pl', 'set-gui-permissions.pl') {
		startDetail();

		my $stderr;
		my $rs = executeNoWait(
			"perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script --setup",
			sub { my $str = shift; while ($$str =~ s/^(.*)\t(.*)\t(.*)\n//) { step(sub { 0 }, $1, $2, $3) } },
			sub { my $str = shift; while ($$str =~ s/^(.*\n)//) { $stderr .= $1 } }
		);

		endDetail();

		error(sprintf('Error while setting permissions: %s', $stderr)) if $rs && $stderr;
		error('Error while setting permissions: Unknown error') if $rs && !$stderr;
		return $rs if $rs;
	}

	$main::imscpConfig{'DEBUG'} = $debug;
	iMSCP::EventManager->getInstance()->trigger('afterSetupSetPermissions');
}

sub setupRebuildCustomerFiles
{
	iMSCP::EventManager->getInstance()->trigger('beforeSetupRebuildCustomersFiles');

	my $tables = {
		ssl_certs => 'status',
		admin => ['admin_status', "AND `admin_type` = 'user'"],
		domain => 'domain_status',
		domain_aliasses => 'alias_status',
		#subdomain => 'subdomain_status', # This is now automatically done by the domain module
		#subdomain_alias => 'subdomain_alias_status', # This is now automatically done by the alias module
		#domain_dns => 'domain_dns_status', # This is now automatically done by the domain and alias modules
		mail_users => 'status',
		htaccess => 'status',
		htaccess_groups => 'status',
		htaccess_users => 'status'
	};

	my ($db, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	die(sprintf('Could not connect to SQL server: %s', $errStr)) unless $db;

	my $rawDb = $db->startTransaction();

	eval {
		my $aditionalCondition;

		while (my ($table, $field) = each %{$tables}) {
			if(ref $field eq 'ARRAY') {
				$aditionalCondition = $field->[1];
				$field = $field->[0];
			} else {
				$aditionalCondition = ''
			}

			$rawDb->do(
				"
					UPDATE
						$table
					SET
						$field = 'tochange'
					WHERE
						$field NOT IN('toadd', 'torestore', 'todisable', 'disabled', 'ordered', 'todelete')
					$aditionalCondition
				"
			);

			$rawDb->do("UPDATE $table SET $field = 'todisable' WHERE $field = 'disabled' $aditionalCondition");
		}

		$rawDb->do(
			"
				UPDATE
					plugin
				SET
					plugin_status = 'tochange', plugin_error = NULL
				WHERE
					plugin_status IN ('tochange', 'enabled')
				AND
					plugin_backend = 'yes'
			"
		);

		$rawDb->commit();
		$db->endTransaction();
		1;
	} or do {
		$rawDb->rollback();
		$db->endTransaction();
		die(sprintf('Could not execute SQL query: %s', $@));
	};

	startDetail();
	iMSCP::DbTasksProcessor->getInstance( mode => 'setup' )->process();
	endDetail();

	iMSCP::EventManager->getInstance()->trigger('afterSetupRebuildCustomersFiles');
}

sub setupServersAndPackages
{
	my $eventManager = iMSCP::EventManager->getInstance();
	my @srvs = iMSCP::Servers->getInstance()->getFull();
	my @pkgs = iMSCP::Packages->getInstance()->getFull();
	my $nSteps = @srvs + @pkgs;

	for my $task(qw/PreInstall Install PostInstall/) {
		my $lcTask = lc($task);

		$eventManager->trigger('beforeSetup' . $task . 'Servers');
		startDetail();
		my $nStep = 1;

		for my $srv(@srvs) {
			my $obj = $srv->factory();
			if(my $ref = $obj->can($lcTask)) {
				step(sub { $ref->($obj) }, sprintf("Running %s %s tasks...", $srv, $lcTask), $nSteps, $nStep);
			}
			$nStep++;
		}

		$eventManager->trigger('afterSetup' . $task . 'Servers');
		$eventManager->trigger('beforeSetup' . $task . 'Packages');

		for my $pkg(@pkgs) {
			my $obj = $pkg->getInstance();
			if(my $ref = $obj->can($lcTask)) {
				step(sub { $ref->($obj) }, sprintf("Running %s %s tasks...", $pkg, $lcTask), $nSteps, $nStep);
			}
			$nStep++;
		}

		endDetail();
		$eventManager->trigger('afterSetup' . $task . 'Packages');
	}

	0;
}

# Fixme: We should move this into dedicated provider iMSCP::Provider::Network, iMSCP::Provider::Daemon ...
sub setupRestartServices
{
	my @services = ();
	iMSCP::EventManager->getInstance()->trigger('beforeSetupRestartServices', \@services);
	my $serviceMngr = iMSCP::Service->getInstance();

	unshift @services, (
		[ sub { $serviceMngr->restart('imscp_network'); 0 }, 'i-MSCP Network' ],
		[ sub { $serviceMngr->restart('imscp_daemon'); 0 }, 'i-MSCP Daemon' ]
	);

	startDetail();
	my ($nService, $nServices) = (0, scalar @services);
	step(@{ $services[$nService] }, $nServices, ++$nService) for @services;
	endDetail();

	iMSCP::EventManager->getInstance()->trigger('afterSetupRestartServices');
}

#
## Low level subroutines
#

# Fixme: We should have dedicated container to manage Questions
sub setupGetQuestion
{
	my ($qname, $default) = (shift, shift || '');

	exists $main::questions{$qname} ? $main::questions{$qname} : (
		exists $main::imscpConfig{$qname} && $main::imscpConfig{$qname} ne '' ? $main::imscpConfig{$qname} : $default
	);
}

sub setupSetQuestion
{
	$main::questions{$_[0]} = $_[1];
}

# Fixme: Should be a method of the SQLD server
sub setupCheckSqlConnect
{
	my ($dbType, $dbName, $dbHost, $dbPort, $dbUser, $dbPass) = @_;

	my $db = iMSCP::Database->factory();
	$db->set('DATABASE_NAME', $dbName);
	$db->set('DATABASE_HOST', $dbHost);
	$db->set('DATABASE_PORT', $dbPort);
	$db->set('DATABASE_USER', $dbUser);
	$db->set('DATABASE_PASSWORD', $dbPass);
	$db->connect();
}

sub setupGetSqlConnect
{
	my $dbName = $_[0] || '';

	my $dbPass = setupGetQuestion('DATABASE_PASSWORD', undef);

	my $db = iMSCP::Database->factory();
	$db->set('DATABASE_NAME', $dbName);
	$db->set('DATABASE_HOST', setupGetQuestion('DATABASE_HOST') || '');
	$db->set('DATABASE_PORT', setupGetQuestion('DATABASE_PORT') || '');
	$db->set('DATABASE_USER', setupGetQuestion('DATABASE_USER') || '');
	$db->set(
		'DATABASE_PASSWORD',
		$dbPass ? decryptRijndaelCBC($main::imscpConfig{'DB_KEY'}, $main::imscpConfig{'DB_IV'}, $dbPass) : ''
	);

	my $rs = $db->connect();
	my ($ret, $errstr) = ! $rs ? ($db, '') : (0, $rs);

	wantarray ? ($ret, $errstr) : $ret;
}

# Fixme: Should be a method of the SQLD server
sub setupIsImscpDb
{
	my $dbName = shift;

	my ($db, $errstr) = setupGetSqlConnect();
	$db or die(sprintf('Could not connect to SQL server: %s', $errstr));

	my $rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $dbName);
	ref $rs eq 'HASH' or die(sprintf('SQL query failed: %s', $rs));
	return 0 unless %{$rs};

	($db, $errstr) = setupGetSqlConnect($dbName);
	$db or die(sprintf('Could not connect to SQL database: %s', $errstr));

	$rs = $db->doQuery('1', 'SHOW TABLES');
	ref $rs eq 'HASH' or die(sprintf('SQL query failed: %s', $rs));

	for my $table(qw/server_ips user_gui_props reseller_props/) {
		return 0 unless $rs->{$table};
	}

	1;
}

sub setupIsSqlUser($)
{
	my $sqlUser = shift;

	my ($db, $errstr) = setupGetSqlConnect('mysql');
	$db or die(sprintf('Could not connect to the SQL Server: %s', $errstr));

	my $rs = $db->doQuery('1', 'SELECT EXISTS(SELECT 1 FROM user WHERE user = ?)', $sqlUser);
	ref $rs eq 'HASH' or die($rs);

	$$rs{1} ? 1 : 0;
}

# Fixme: Should be a method of the SQLD server
sub setupDeleteSqlUser
{
	my ($user, $host) = (shift, shift || '%');

	my ($db, $errstr) = setupGetSqlConnect('mysql');
	$db or die(sprintf('Could not connect to the SQL Server: %s', $errstr));

	# Remove any columns privileges for the given user
	$errstr = $db->doQuery('d', "DELETE FROM columns_priv WHERE Host = ? AND User = ?", $host, $user);
	ref $errstr eq 'HASH' or die(sprintf('Could not remove columns privileges: %s', $errstr));

	# Remove any tables privileges for the given user
	$errstr = $db->doQuery('d', 'DELETE FROM tables_priv WHERE Host = ? AND User = ?', $host, $user);
	ref $errstr eq 'HASH' or die(sprintf('Could not remove tables privileges: %s', $errstr));

	# Remove any proc privileges for the given user
	$errstr = $db->doQuery('d', 'DELETE FROM procs_priv WHERE Host = ? AND User = ?', $host, $user);
	ref $errstr eq 'HASH' or die(sprintf('Could not remove procs privileges: %s', $errstr));

	# Remove any database privileges for the given user
	$errstr = $db->doQuery('d', 'DELETE FROM db WHERE Host = ? AND User = ?', $host, $user);
	ref $errstr eq 'HASH' or die(sprintf('Could not remove privileges: %s', $errstr));

	# Remove any global privileges for the given user and the user itself
	$errstr = $db->doQuery('d', "DELETE FROM user WHERE Host = ? AND User = ?", $host, $user);
	ref $errstr eq 'HASH' or die(sprintf('Could not delete SQL user: %s', $errstr));

	# Reload privileges
	$errstr = $db->doQuery('d', 'FLUSH PRIVILEGES');
	ref $errstr eq 'HASH' or die(sprintf('Could not flush SQL privileges: %s', $errstr));

	0;
}

1;
