#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use iMSCP::Crypt qw/md5 encryptBlowfishCBC decryptBlowfishCBC/;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::EventManager;
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

# Boot
sub setupBoot
{
	# We do not try to establish connection to the database since needed data can be unavailable
	iMSCP::Bootstrapper->getInstance()->boot({ mode => 'setup', nodatabase => 'yes' });

	unless(%main::imscpOldConfig) {
		%main::imscpOldConfig = ();

		my $oldConfig = "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf";
		tie %main::imscpOldConfig, 'iMSCP::Config', fileName => $oldConfig, readonly => 1 if -f $oldConfig;
	}

	0;
}

# Allow any server/package to register its setup event listeners before any other task
sub setupRegisterListeners
{
	my ($eventManager, $rs) = (iMSCP::EventManager->getInstance(), 0);

	for(iMSCP::Servers->getInstance()->get()) {
		next if $_ eq 'noserver';

		my $package = "Servers::$_";

		eval "require $package";

		unless($@) {
			my $instance = $package->factory();
			$rs = $instance->registerSetupListeners($eventManager) if $instance->can('registerSetupListeners');
		} else {
			error($@);
        	$rs = 1;
		}

		return $rs if $rs;
	}

	for(iMSCP::Packages->getInstance()->get()) {
		my $package = "Package::$_";

		eval "require $package";

		unless($@) {
			my $instance = $package->getInstance();
			$rs = $instance->registerSetupListeners($eventManager) if $instance->can('registerSetupListeners');
		} else {
			error($@);
        	$rs = 1;
		}

		return $rs if $rs;
	}

	$rs;
}

# Trigger all dialog subroutines
sub setupDialog
{
	my $dialogStack = [];

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupDialog', $dialogStack);
	return $rs if $rs;

	unshift(
		@$dialogStack,
		(
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
		)
	);

	my $dialog = iMSCP::Dialog->getInstance();

	$dialog->set('ok-label', 'Ok');
	$dialog->set('yes-label', 'Yes');
	$dialog->set('no-label', 'No');
	$dialog->set('cancel-label', 'Back');

	# Implements a simple state machine (backup capability)
	# Any dialog subroutine *should* allow user to step back by returning 30 when 'back' button is pushed
	my ($state, $nbDialog) = (0, scalar @{$dialogStack});

	while($state != $nbDialog) {
		$rs = $$dialogStack[$state]->($dialog);
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

# Process setup tasks
sub setupTasks
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupTasks') and return 1;
	return $rs if $rs;

	my @steps = (
		[\&setupSaveOldConfig,              'Saving old configuration file'],
		[\&setupWriteNewConfig,             'Writing new configuration file'],
		[\&setupCreateMasterGroup,          'Creating system master group'],
		[\&setupCreateSystemDirectories,    'Creating system directories'],
		[\&setupServerHostname,             'Setting server hostname'],
		[\&setupCreateDatabase,             'Creating/updating i-MSCP database'],
		[\&setupSecureSqlInstallation,      'Securing SQL installation'],
		[\&setupServerIps,                  'Setting server IP addresses'],
		[\&setupDefaultAdmin,               'Creating/updating default admin account'],
		[\&setupServices,                   'Setup i-MSCP services'],
		[\&setupServiceSsl,                 'Setup SSL for i-MSCP services'],
		[\&setupPreInstallServers,          'Servers pre-installation'],
		[\&setupPreInstallPackages,         'Packages pre-installation'],
		[\&setupInstallServers,             'Servers installation'],
		[\&setupInstallPackages,            'Packages installation'],
		[\&setupPostInstallServers,         'Servers post-installation'],
		[\&setupPostInstallPackages,        'Packages post-installation'],
		[\&setupSetPermissions,             'Setting permissions'],
		[\&setupRebuildCustomerFiles,       'Rebuilding customers files'],
		[\&setupRestartServices,            'Restarting services']
	);

	my $step = 1;
	my $nbSteps = @steps;

	for (@steps) {
		$rs = step($_->[0], $_->[1], $nbSteps, $step);
		return $rs if $rs;

		$step++;
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupTasks');
}

#
## Dialog subroutines
#

# Ask for server hostname
sub setupAskServerHostname
{
	my $dialog = $_[0];

	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my %options = (domain_private_tld => qr /.*/);
	my ($rs, @labels) = (0, $hostname ? split(/\./, $hostname) : ());

	if(
		$main::reconfigure ~~ ['system_hostname', 'hostnames', 'all', 'forced'] ||
		! (@labels >= 3 && is_domain($hostname, \%options))
	) {
		unless($hostname) {
			my $err = undef;

			if (execute('hostname -f', \$hostname, \$err)) {
				error("Unable to find server hostname (server misconfigured?): $err");
			} else {
				chomp($hostname);
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

		} while($rs != 30 && ! (@labels >= 3 && is_domain($hostname, \%options)));

		$dialog->set('no-cancel', undef);
	}

	setupSetQuestion('SERVER_HOSTNAME', $hostname) if $rs != 30;

	$rs;
}

# Ask for server ips
sub setupAskServerIps
{
	my $dialog = $_[0];

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
	unless(@serverIps) {
		error('Unable to retrieve servers IPs');
		return 1;
	}

	my $currentServerIps = { };
	my $database = '';
	my $msg = '';

	if(setupGetQuestion('DATABASE_NAME')) {
		# We do not raise error in case we cannot get SQL connection since it's expected in some contexts
		$database = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

		if($database) {
			$currentServerIps = $database->doQuery('ip_number', 'SELECT ip_id, ip_number FROM server_ips');

			unless(ref $currentServerIps eq 'HASH') {
				error("Unable to retrieve server IPs: $currentServerIps");
				return 1
			}
		}

		@{$serverIpsToAdd} = (@{$serverIpsToAdd}, keys %{$currentServerIps});
	}

	@serverIps = sort keys %{ { map { $_ => 1 } @serverIps, @{$serverIpsToAdd} } };

	if(
		$main::reconfigure ~~ ['ips', 'all', 'forced'] || ! ($baseServerIp ~~ @serverIps) ||
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

				@serverIps = grep $_ ne $baseServerIp, @serverIps; # Remove the base server IP from the list

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

					if($database) {
						# Get list of IP addresses to delete
						%serverIpsToDelete = ();

						for(@serverIps) {
							if(exists $currentServerIps->{$_} && not $_ ~~ @{$serverIpsToAdd}) {
								$serverIpsToDelete{$currentServerIps->{$_}->{'ip_id'}} = $_;
							}
						}

						# Check for server IP addresses already in use and ask for replacement
						my $resellerIps = $database->doQuery('reseller_ips', 'SELECT reseller_ips FROM reseller_props');

						if(ref $resellerIps ne 'HASH') {
							error("Cannot retrieve resellers's addresses IP: $resellerIps");
							return 1;
						}

						for(keys %$resellerIps){
							my @resellerIps = split ';';

							for(@resellerIps) {
								if(exists $serverIpsToDelete{$_} && ! exists $serverIpsReplMap{$serverIpsToDelete{$_}}) {
									my $ret = '';

									do {
										($rs, $ret) = $dialog->radiolist(
"
The IP address '$serverIpsToDelete{$_}' is already in use. Please, choose an IP to replace it:
",
											$serverIpsToAdd,
											$baseServerIp
										);
									} while($rs != 30 && ! $ret);

									$serverIpsReplMap{$serverIpsToDelete{$_}} = $ret;
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

# Ask for Sql DSN and SQL username/password
sub setupAskSqlDsn
{
	my $dialog = $_[0];

	my $dbType = setupGetQuestion('DATABASE_TYPE') || 'mysql';
	my $dbHost = setupGetQuestion('DATABASE_HOST') || 'localhost';
	my $dbPort = setupGetQuestion('DATABASE_PORT') || '3306';
	my $dbUser = setupGetQuestion('DATABASE_USER') || 'root';
	my $dbPass;

	if(iMSCP::Getopt->preseed) {
		$dbPass = setupGetQuestion('DATABASE_PASSWORD');
	} else {
		$dbPass = setupGetQuestion('DATABASE_PASSWORD');
		$dbPass = ($dbPass) ? decryptBlowfishCBC($main::imscpDBKey, $main::imscpDBiv, $dbPass) : '';
	}

	if($dbPass ne '' && setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)) {
		# Following a decryptBlowfishCBC() failure,  ensure no special chars are present in password string
		# If we don't, dialog will not let user set new password
		$dbPass = '';
	}

	my $rs = 0;
	my %options = (domain_private_tld => qr /.*/);

	if($main::reconfigure ~~ [ 'sql', 'servers', 'all', 'forced' ] || $dbPass eq '') {
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
					$dbHost eq 'localhost' || is_domain($dbHost, \%options) ||
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
		setupSetQuestion('DATABASE_PASSWORD', encryptBlowfishCBC($main::imscpDBKey, $main::imscpDBiv, $dbPass));
	}

	$rs;
}

# Ask for hosts from which SQL users are allowed to connect from
sub setupAskSqlUserHost
{
	my $dialog = $_[0];

	my $host = setupGetQuestion('DATABASE_USER_HOST');
	$host = ($host eq '127.0.0.1') ? 'localhost' : $host;

	my $rs = 0;

	if(setupGetQuestion('DATABASE_HOST') ne 'localhost') { # Remote MySQL server
		if($main::reconfigure ~~ ['sql', 'servers', 'all', 'forced'] || ! $host) {
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

# Ask for i-MSCP database name
sub setupAskImscpDbName
{
	my $dialog = $_[0];

	my $dbName = setupGetQuestion('DATABASE_NAME') || 'imscp';
	my $rs = 0;

	if(
		$main::reconfigure ~~ ['sql', 'servers', 'all', 'forced'] ||
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

# Ask for database prefix/suffix
sub setupAskDbPrefixSuffix
{
	my $dialog = $_[0];

	my $prefix = setupGetQuestion('MYSQL_PREFIX');
	my $prefixType = setupGetQuestion('MYSQL_PREFIX_TYPE');
	my $rs = 0;

	if(
		$main::reconfigure ~~ ['sql', 'servers', 'all', 'forced'] ||
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
			['infront', 'behind', 'none'],
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

# Ask for default administrator
sub setupAskDefaultAdmin
{
	my $dialog = $_[0];

	my ($adminLoginName, $password, $rpassword) = ('', '', '');
	my ($rs, $msg) = (0, '');

	my $database = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

	if(iMSCP::Getopt->preseed) {
		$adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME');
		$password = setupGetQuestion('ADMIN_PASSWORD');
		$adminLoginName = '' if $password eq '';
	} elsif($database) {
		my $defaultAdmin = $database->doQuery(
			'created_by',
			'
				SELECT
					`admin_name`, `created_by`
				FROM
					`admin` WHERE `created_by` = ?
				AND
					`admin_type` = ?
				LIMIT
					1
			',
			'0',
			'admin'
		);
		unless(ref $defaultAdmin eq 'HASH') {
			error($defaultAdmin);
			return 1;
		}

		if(%{$defaultAdmin}) {
			$adminLoginName = $$defaultAdmin{'0'}->{'admin_name'};
		}
	}

	setupSetQuestion('ADMIN_OLD_LOGIN_NAME', $adminLoginName);

	if($main::reconfigure ~~ ['admin', 'all', 'forced'] || $adminLoginName eq '') {

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
			} elsif($database) {
				my $rdata = $database->doQuery(
					'admin_id',
					'SELECT `admin_id` FROM `admin` WHERE `admin_name` = ? AND `created_by` <> 0 LIMIT 1',
					$adminLoginName
				);
				unless(ref $rdata eq 'HASH') {
					error($rdata);
					return 1;
				} elsif(%{$rdata}) {
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
					} while($rs != 30 &&  $rpassword ne $password);
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

# Ask for administrator email
sub setupAskAdminEmail
{
	my $dialog = $_[0];

	my $adminEmail = setupGetQuestion('DEFAULT_ADMIN_ADDRESS');
	my $rs = 0;

	if($main::reconfigure ~~ ['admin', 'all', 'forced'] || ! Email::Valid->address($adminEmail)) {
		my $msg = '';

		do {
			($rs, $adminEmail) = $dialog->inputbox("\nPlease, enter admin email address: $msg", $adminEmail);
			$msg = "\n\n\\Z1'$adminEmail' is not a valid email address.\\Zn\n\nPlease, try again:";
		} while( $rs != 30 && ! Email::Valid->address($adminEmail));
	}

	setupSetQuestion('DEFAULT_ADMIN_ADDRESS', $adminEmail) if $rs != 30;

	$rs;
}

# Ask for timezone
sub setupAskTimezone
{
	my $dialog = $_[0];

	my $defaultTimezone = DateTime->new(year => 0, time_zone => 'local')->time_zone->name;
	my $timezone = setupGetQuestion('TIMEZONE');
	my $rs = 0;

	if($main::reconfigure ~~ [ 'timezone', 'all', 'forced' ] || ! ($timezone && DateTime::TimeZone->is_valid_name($timezone))) {
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

# Ask for services SSL
sub setupAskServicesSsl
{
	my($dialog) = $_[0];

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
			['no', 'yes'],
			($sslEnabled eq 'yes') ? 'yes' : 'no'
		);

		if($sslEnabled eq 'yes' && $rs != 30) {
			# Ask for self-signed certificate
			($rs, $selfSignedCertificate) = $dialog->radiolist(
				"\nDo you have an SSL certificate for the $domainName domain?",
				['yes', 'no'],
				($selfSignedCertificate ~~ ['yes', 'no']) ? (($selfSignedCertificate eq 'yes') ? 'no' : 'yes') : 'no'
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

# Ask for i-MSCP backup feature
sub setupAskImscpBackup
{
	my $dialog = $_[0];

	my $backupImscp = setupGetQuestion('BACKUP_IMSCP');
	my $rs = 0;

	if($main::reconfigure ~~ ['backup', 'all', 'forced'] || $backupImscp !~ /^yes|no$/) {
		($rs, $backupImscp) = $dialog->radiolist(
"
\\Z4\\Zb\\Zui-MSCP Backup Feature\\Zn

Do you want activate the backup feature for i-MSCP?

The backup feature for i-MSCP allows the daily save of all i-MSCP
configuration files and its database. It's greatly recommended to
activate this feature.
",
			['yes', 'no'],
			$backupImscp ne 'no' ? 'yes' : 'no'
		);
	}

	setupSetQuestion('BACKUP_IMSCP', $backupImscp) if $rs != 30;

	$rs;
}

# Ask for customer backup feature
sub setupAskDomainBackup
{
	my $dialog = $_[0];

	my $backupDomains = setupGetQuestion('BACKUP_DOMAINS');
	my $rs = 0;

	if($main::reconfigure ~~ ['backup', 'all', 'forced'] || $backupDomains !~ /^yes|no$/) {
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
			['yes', 'no'],
			$backupDomains ne 'no' ? 'yes' : 'no'
		);
	}

	setupSetQuestion('BACKUP_DOMAINS', $backupDomains) if $rs != 30;

	$rs;
}

#
## Setup subroutines
#

# Save old i-MSCP main configuration file
#
sub setupSaveOldConfig
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSaveOldConfig');
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.conf");

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("Unable to read $main::imscpConfig{'CONF_DIR'}/imscp.conf");
		return 1;
	}

	$file = iMSCP::File->new('filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupSaveOldConfig');
}

# Write question answers into imscp.conf file
sub setupWriteNewConfig
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupWriteNewConfig');
	return $rs if $rs;

	for(keys %main::questions) {
		if(exists $main::imscpConfig{$_}) {
			$main::imscpConfig{$_} = $main::questions{$_};
		}
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupWriteNewConfig');
}

# Create system master group for imscp
sub setupCreateMasterGroup
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateMasterGroup');
	return $rs if $rs;

	$rs = iMSCP::SystemGroup->getInstance()->addSystemGroup($main::imscpConfig{'IMSCP_GROUP'}, 1);
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupCreateMasterGroup');
}

# Create default directories needed by i-MSCP
sub setupCreateSystemDirectories
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my @systemDirectories  = (
		#[$main::imscpConfig{'USER_WEB_DIR'}, $rootUName, $rootGName, 0555],
		#[$main::imscpConfig{'LOG_DIR'}, $rootUName, $rootGName, 0555],
		[$main::imscpConfig{'BACKUP_FILE_DIR'}, $rootUName, $rootGName, 0750]
	);

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateSystemDirectories', \@systemDirectories);
	return $rs if $rs;

	for (@systemDirectories) {
		$rs = iMSCP::Dir->new( dirname => $_->[0] )->make({ 'user' => $_->[1], 'group' => $_->[2], 'mode' => $_->[3]});
		return $rs if $rs;
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupCreateSystemDirectories');
}

# Setup server hostname
sub setupServerHostname
{
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupServerHostname', \$hostname, \$baseServerIp);
	return $rs if $rs;

	my @labels = split /\./, $hostname;
	my $host = shift(@labels);
	my $hostnameLocal = "$hostname.local";

	my $file = iMSCP::File->new('filename' => '/etc/hosts');
	$rs = $file->copyFile('/etc/hosts.bkp') if ! -f '/etc/hosts.bkp';
	return $rs if $rs;

	my $net = iMSCP::Net->getInstance();
	my $content = "# 'hosts' file configuration.\n\n";

	$content .= "127.0.0.1\t$hostnameLocal\tlocalhost\n";
	$content .= "$baseServerIp\t$hostname\t$host\n";
	$content .= "::ffff:$baseServerIp\t$hostname\t$host\n" if $net->getAddrVersion($baseServerIp) eq 'ipv4';
	$content .= "::1\tip6-localhost\tip6-loopback\n" if $net->getAddrVersion($baseServerIp) eq 'ipv4';
	$content .= "::1\tip6-localhost\tip6-loopback\t$host\n" if $net->getAddrVersion($baseServerIp) eq 'ipv6';
	$content .= "fe00::0\tip6-localnet\n";
	$content .= "ff00::0\tip6-mcastprefix\n";
	$content .= "ff02::1\tip6-allnodes\n";
	$content .= "ff02::2\tip6-allrouters\n";
	$content .= "ff02::3\tip6-allhosts\n";

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => '/etc/hostname');

	$rs = $file->copyFile('/etc/hostname.bkp') if ! -f '/etc/hostname.bkp';
	return $rs if $rs;

	$content = $host;

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute("hostname $host", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if ! $rs && $stderr;
	error($stderr) if $rs && $stderr;
	error('Unable to set server hostname') if $rs && ! $stderr;
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupServerHostname');
}

# Setup server ips
sub setupServerIps
{
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my $serverIpsToReplace = setupGetQuestion('SERVER_IPS_TO_REPLACE') || { };
	my $serverIpsToDelete = setupGetQuestion('SERVER_IPS_TO_DELETE') || [];
	my $serverHostname = setupGetQuestion('SERVER_HOSTNAME');
	my $oldIptoIdMap = { };

	my @serverIps = (
		$baseServerIp,
		($main::questions{'SERVER_IPS'}) ? @{$main::questions{'SERVER_IPS'}} : ()
	);

	my $rs = iMSCP::EventManager->getInstance()->trigger(
		'beforeSetupServerIps', \$baseServerIp, \@serverIps, $serverIpsToReplace
	);
	return $rs if $rs;

	# Ensure promoting of secondary IP addresses in case a PRIMARY addresse is being deleted
	# Note we are ignoring return value here (eg for vps)
	my ($stdout, $stderr);
	execute("sysctl -q -w net.ipv4.conf.all.promote_secondaries=1", \$stdout, \$stderr);

	my ($database, $errstr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	unless($database) {
		error("Unable to connect to the SQL database: $errstr");
		return 1;
	}

	# Get IDs of IP addresses to replace
	if(%{$serverIpsToReplace}) {
		my $ipsToReplace = join q{,}, map $database->quote($_), keys %{$serverIpsToReplace};
		$oldIptoIdMap = $database->doQuery(
			'ip_number', 'SELECT ip_id, ip_number FROM server_ips WHERE ip_number IN ('. $ipsToReplace .')'
		);
		if(ref $oldIptoIdMap ne 'HASH') {
			error("Unable to get IDs of server IPs to replace: $oldIptoIdMap");
			return 1;
		}
	}

	my $net = iMSCP::Net->getInstance();

	# Process server IPs addition

	my $defaultNetcard = (grep { $_ ne 'lo' } $net->getDevices())[0];

	for (@serverIps) {
		next if exists $serverIpsToReplace->{$_};
		my $netCard = ($net->isKnownAddr($_)) ? $net->getAddrDevice($_) || $defaultNetcard : $defaultNetcard;

		if($netCard) {
			my $rs = $database->doQuery(
				'dummy',
				'INSERT IGNORE INTO server_ips (ip_number, ip_card, ip_status) VALUES(?, ?, ?)',
				$_,
				$netCard,
				'toadd'
			);
			if (ref $rs ne 'HASH') {
				error("Unable to add/update the $_ IP address: $rs");
				return 1;
			}
		} else {
			error("Unable to add the $_ IP address into database: Unknown network card");
			return 1;
		}
	}

	# Server IPs replacement

	if(%{$serverIpsToReplace}) {
		# for each IP to replace
		for(keys %$serverIpsToReplace) {
			my $newIp = $serverIpsToReplace->{$_}; # New IP
			my $oldIpId = $oldIptoIdMap->{$_}->{'ip_id'}; # Old IP ID

			# Get IP IDs of resellers to which the IP to replace is currently assigned
			my $resellerIps = $database->doQuery(
				'id',
				'SELECT id, reseller_ips FROM reseller_props WHERE reseller_ips REGEXP ?',
				"(^|[^0-9]$oldIpId;)"
			);
			unless(ref $resellerIps eq 'HASH') {
				error($resellerIps);
				return 1;
			}

			# Get new IP ID
			my $newIpId = $database->doQuery(
				'ip_number', 'SELECT ip_id, ip_number FROM server_ips WHERE ip_number = ?', $newIp
			);
			unless(ref $newIpId eq 'HASH') {
				error($newIpId);
				return 1;
			}

			$newIpId = $newIpId->{$newIp}->{'ip_id'};

			for(keys %$resellerIps) {
				my $ips = $resellerIps->{$_}->{'reseller_ips'};

				if($ips !~ /(?:^|[^0-9])$newIpId;/) {
					$ips =~ s/((?:^|[^0-9]))$oldIpId;?/$1$newIpId;/;
					$rs = $database->doQuery(
						'dummy', 'UPDATE reseller_props SET reseller_ips = ? WHERE id = ?', $ips, $_
					);
					unless(ref $rs eq 'HASH') {
						error($rs);
						return 1;
					}
				}
			}

			# Update IP id of customer domains if needed
			$rs = $database->doQuery(
				'dummy', 'UPDATE domain SET domain_ip_id = ? WHERE domain_ip_id = ?', $newIpId, $oldIpId
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
			
			# Update IP id of customer domain aliases if needed
			$rs = $database->doQuery(
				'dummy', 'UPDATE domain_aliasses SET alias_ip_id = ? WHERE alias_ip_id = ?', $newIpId, $oldIpId
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}
	}

	# Process IP deletion
	if(@{$serverIpsToDelete}) {
		my $serverIpsToDelete = join q{,}, map $database->quote($_), @{$serverIpsToDelete};
		my $rs = $database->doQuery(
			'dummy',
			'UPDATE server_ips set ip_status = ?  WHERE ip_number IN(' . $serverIpsToDelete . ') AND ip_number <> ?',
			'todelete',
			$baseServerIp
		);
		unless (ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupServerIps');
}

# Create iMSCP database
sub setupCreateDatabase
{
	my $dbName = setupGetQuestion('DATABASE_NAME');

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateDatabase', \$dbName);
	return $rs if $rs;

	unless(setupIsImscpDb($dbName)) {
		my ($database, $errStr) = setupGetSqlConnect();

		unless($database) {
			error("Unable to connect to SQL server: $errStr");
			return 1;
		}

		my $qdbName = $database->quoteIdentifier($dbName);
		my $rs = $database->doQuery('dummy', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");

		if(ref $rs ne 'HASH') {
			error("Unable to create the '$dbName' SQL database: $rs");
			return 1;
		}

		$database->set('DATABASE_NAME', $dbName);
		$rs = $database->connect();
		return $rs if $rs;

		$rs = setupImportSqlSchema($database, "$main::imscpConfig{'CONF_DIR'}/database/database.sql");
		return $rs if $rs;
	}

	# In all cases, we process database update. This is important because sometime some developer forget to update the
	# database revision in the main database.sql file.
	$rs = setupUpdateDatabase();
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupCreateDatabase');
}

# Convenience method allowing to create or update a database schema
sub setupImportSqlSchema
{
	my ($database, $file) = @_;

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupImportSqlSchema', \$file);

	my $content = iMSCP::File->new('filename' => $file)->get();
	unless(defined $content) {
		error("Unable to read $file");
		return 1;
	}

	$content =~ s/^(--[^\n]{0,})?\n//gm;
	my @queries = (split /;\n/, $content);

	my $title = "Executing " . @queries . " queries:";

	startDetail();

	my $step = 1;

	for (@queries) {
		my $rs = $database->doQuery('dummy', $_);
		unless(ref $rs eq 'HASH') {
			error("Unable to execute SQL query: $rs");
			return 1;
		}

		my $msg = $queries[$step] ? "$title\n$queries[$step]" : $title;
		step('', $msg, scalar @queries, $step);
		$step++;
	}

	endDetail();

	iMSCP::EventManager->getInstance()->trigger('afterSetupImportSqlSchema');
}

# Update i-MSCP database schema
sub setupUpdateDatabase
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupUpdateDatabase');
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php");

	my $content = $file->get();
	unless(defined $content) {
		error("Unable to read $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php");
		return 1;
	}

	if($content =~ s/\{GUI_ROOT_DIR\}/$main::imscpConfig{'GUI_ROOT_DIR'}/) {
		$rs = $file->set($content);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	my ($stdout, $stderr);
	$rs = execute("php $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupUpdateDatabase');
}

# Secure any SQL account by removing those without password
#
# Basically, this method do same job as the mysql_secure_installation script
# - Remove anonymous users
# - Remove remote sql root user (only for local server)
# - Remove test database if any
# - Reload privileges tables
sub setupSecureSqlInstallation
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSecureSqlInstallation');
	return $rs if $rs;

	my ($database, $errStr) = setupGetSqlConnect();

	unless($database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	# Remove anonymous users
	$errStr = $database->doQuery('dummy', "DELETE FROM mysql.user WHERE User = ''");
	unless(ref $errStr eq 'HASH') {
		error("Unable to delete anonymous users: $errStr");
		return 1;
	}

	# Remove test database if any
	$errStr = $database->doQuery('dummy', 'DROP DATABASE IF EXISTS `test`');
	unless(ref $errStr eq 'HASH') {
		error("Unable to remove database test : $errStr"); # Not critical, keep moving...
		return 1;
	}

	# Remove privileges on test database
	$errStr = $database->doQuery('dummy', "DELETE FROM mysql.db WHERE Db = 'test' OR Db = 'test\\_%'");
	unless(ref $errStr eq 'HASH') {
		error("Unable to remove privileges on test database: $errStr");
		return 1;
	}

	# Disallow remote root login
	if($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
		$errStr = $database->doQuery(
			'dummy', "DELETE FROM mysql.user WHERE User = 'root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
		);
		unless(ref $errStr eq 'HASH'){
			error("Unable to remove remote root users: $errStr");
			return 1;
		}
	}

	# Reload privilege tables
	$errStr = $database->doQuery('dummy', 'FLUSH PRIVILEGES');
	unless(ref $errStr eq 'HASH') {
		debug("Unable to reload privileges tables: $errStr");
		return 1;
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupSecureSqlInstallation');
}

# Setup default admin
sub setupDefaultAdmin
{
	my $adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME');
	my $adminOldLoginName = setupGetQuestion('ADMIN_OLD_LOGIN_NAME');
	my $adminPassword= setupGetQuestion('ADMIN_PASSWORD');
	my $adminEmail= setupGetQuestion('DEFAULT_ADMIN_ADDRESS');

	my $rs = iMSCP::EventManager->getInstance()->trigger(
		'beforeSetupDefaultAdmin', \$adminLoginName, \$adminPassword, \$adminEmail
	);
	return $rs if $rs;

	if($adminLoginName && $adminPassword) {
		$adminPassword = md5($adminPassword);

		my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
		unless($database) {
			error("Unable to connect to SQL server: $errStr");
			return 1;
		}

		my $rs = $database->doQuery(
			'admin_name',
			'SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_name` = ? LIMIT 1',
			$adminOldLoginName
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		unless(%{$rs}) {
			$rs = $database->doQuery(
				'dummy',
				'INSERT INTO `admin` (`admin_name`, `admin_pass`, `admin_type`, `email`) VALUES (?, ?, ?, ?)',
				$adminLoginName, $adminPassword, 'admin', $adminEmail
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}

			$rs = $database->doQuery(
				'dummy',
				'
					INSERT IGNORE INTO `user_gui_props` (
						`user_id`, `lang`, `layout`, `layout_color`, `logo`, `show_main_menu_labels`
					) VALUES (
						LAST_INSERT_ID(), ?, ?, ?, ?, ?
					)
				',
				'auto', 'default', 'black', '', '1'
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		} else {
			$rs = $database->doQuery(
				'dummy',
				'UPDATE `admin` SET `admin_name` = ?, `admin_pass` = ?, `email` = ? WHERE `admin_id` = ?',
				$adminLoginName, $adminPassword, $adminEmail, $rs->{$adminOldLoginName}->{'admin_id'}
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}
	}

	iMSCP::EventManager->getInstance()->trigger('afterSetupDefaultAdmin');
}

# Setup SSL for i-MSCP services
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
				'certificate_chains_storage_dir' =>  $main::imscpConfig{'CONF_DIR'},
				'certificate_chain_name' => 'imscp_services'
			)->createSelfSignedCertificate($domainName);
			return $rs if $rs;
		} else {
			my $rs = iMSCP::OpenSSL->new(
				'certificate_chains_storage_dir' =>  $main::imscpConfig{'CONF_DIR'},
				'certificate_chain_name' => 'imscp_services',
				'private_key_container_path' => $privateKeyPath,
				'private_key_passphrase' => $passphrase,
				'certificate_container_path' => $certificatePath,
				'ca_bundle_container_path' => $caBundlePath
			)->createCertificateChain();
			return $rs if $rs;
		}
	}

	0;
}

# Setup i-MSCP services
sub setupServices
{
	# Be sure that legacy boot ordering is not enabled
	#if(-f "/etc/init.d/.legacy-bootordering") {
	#	my $rs = iMSCP::File->new( filename => '/etc/init.d/.legacy-bootordering' )->delFile();
	#	return $rs if $rs;
	#}

	my $serviceMngr = iMSCP::Service->getInstance();

	if($serviceMngr->isUpstart()) {
		# Work around https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=780641
		$serviceMngr->getProvider('sysvinit')->remove('imscp_network');
	}

	$serviceMngr->enable($_) for 'imscp_daemon', 'imscp_network';

	0;
}

# Set Permissions
sub setupSetPermissions
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSetPermissions');
	return $rs if $rs;

	my $debug = $main::imscpConfig{'DEBUG'} || 0;
	$main::imscpConfig{'DEBUG'} = (iMSCP::Getopt->debug) ? 1 : 0;

	for my $script ('set-engine-permissions.pl', 'set-gui-permissions.pl') {
		startDetail();

		my $stderr;
		$rs = executeNoWait(
			"perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script --setup",
			sub { my $str = shift; while ($$str =~ s/^(.*)\t(.*)\t(.*)\n//) { step(undef, $1, $2, $3); } },
			sub { my $str = shift; while ($$str =~ s/^(.*\n)//) { $stderr .= $1; } }
		);

		endDetail();

		error("Error while setting permissions: $stderr") if $stderr && $rs;
		error('Error while setting permissions: Unknown error') if $rs && ! $stderr;
		return $rs if $rs;
	}

	$main::imscpConfig{'DEBUG'} = $debug;

	iMSCP::EventManager->getInstance()->trigger('afterSetupSetPermissions');
}

# Rebuild all customer's configuration files
sub setupRebuildCustomerFiles
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupRebuildCustomersFiles');
	return $rs if $rs;

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

	my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	unless($database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	my $rawDb = $database->startTransaction();

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
	};

	if($@) {
		$rawDb->rollback();
		$database->endTransaction();
		error("Unable to execute SQL query: $@");
		return 1;
	}

	$database->endTransaction();

	iMSCP::Bootstrapper->getInstance()->unlock();

	my $debug = $main::imscpConfig{'DEBUG'} || 0;
	$main::imscpConfig{'DEBUG'} = (iMSCP::Getopt->debug) ? 1 : 0;

	startDetail();

	my $stderr;
	$rs = executeNoWait(
		"perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-rqst-mngr --setup",
		sub { my $str = shift; while ($$str =~ s/^(.*)\t(.*)\t(.*)\n//) { step(undef, $1, $2, $3); } },
		sub { my $str = shift; while ($$str =~ s/^(.*\n)//) { $stderr .= $1; } }
	);

	endDetail();

	iMSCP::Bootstrapper->getInstance()->lock();

	$main::imscpConfig{'DEBUG'} = $debug;

	error("\nError while rebuilding customers files: $stderr") if $stderr && $rs;
	error('Error while rebuilding customers files: Unknown error') if $rs && ! $stderr;
	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupRebuildCustomersFiles');
}

# Call preinstall method on all i-MSCP server packages
sub setupPreInstallServers
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPreInstallServers');
	return $rs if $rs;

	my @servers = iMSCP::Servers->getInstance()->get();
	my $nbServers = scalar @servers;
	my $step = 1;

	startDetail();

	for(@servers) {
		next if $_ eq 'noserver';

		my $package = "Servers::$_";

		eval "require $package";

		unless($@) {
			my $server = $package->factory();

			if($server->can('preinstall')) {
				$rs = step(
					sub { $server->preinstall() },
					sprintf("Running %s preinstall tasks...", ref $server),
					$nbServers,
					$step
				);

				last if $rs;
			}
		} else {
			error($@);
			$rs = 1;
			last;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupPreInstallServers');
}

# Call preinstall method on all i-MSCP packages
sub setupPreInstallPackages
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPreInstallPackages');
	return $rs if $rs;

	my @packages = iMSCP::Packages->getInstance()->get();
	my $nbPackages = scalar @packages;
	my $step = 1;

	startDetail();

	for(@packages) {
		my $package = "Package::$_";

		eval "require $package";

		unless($@) {
			my $package = $package->getInstance();

			if($package->can('preinstall')) {
				$rs = step(
					sub { $package->preinstall() },
					sprintf("Running %s preinstall tasks...", ref $package),
					$nbPackages,
					$step
				);

				last if $rs;
			}
		} else {
			error($@);
			$rs = 1;
			last;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupPreInstallPackages');
}

# Call install method on all i-MSCP server packages
sub setupInstallServers
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupInstallServers');
	return $rs if $rs;

	my @servers = iMSCP::Servers->getInstance()->get();
	my $nbServers = scalar @servers;
	my $step = 1;

	startDetail();

	for(@servers) {
		my $package = "Servers::$_";

		eval "require $package";

		unless($@) {
			next if $_ eq 'noserver';

			my $server = $package->factory();

			if($server->can('install')) {
				$rs = step(
					sub { $server->install() },
					sprintf("Running %s install tasks...", ref $server),
					$nbServers,
					$step
				);

				last if $rs;
			}
		} else {
			error($@);
			$rs = 1;
			last;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupInstallServers');
}

# Call install method on all i-MSCP packages
sub setupInstallPackages
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupInstallPackages');
	return $rs if $rs;

	my @packages = iMSCP::Packages->getInstance()->get();
	my $nbPackages = scalar @packages;
	my $step = 1;

	startDetail();

	for(@packages) {
		my $package = "Package::$_";

		eval "require $package";

		unless($@) {
			my $package = $package->getInstance();

			if($package->can('install')) {
				$rs = step(
					sub { $package->install() },
					sprintf("Running %s install tasks...", ref $package),
					$nbPackages,
					$step
				);

				last if $rs;
			}
		} else {
			error($@);
			$rs = 1;
			last;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupInstallPackages');
}

# Call postinstall method on all i-MSCP server packages
sub setupPostInstallServers
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPostInstallServers');
	return $rs if $rs;

	my @servers = iMSCP::Servers->getInstance()->get();
	my $nbServers = scalar @servers;
	my $step = 1;

	startDetail();

	for(@servers) {
		next if $_ eq 'noserver';

		my $package = "Servers::$_";

		eval "require $package";

		unless($@) {
			my $server = $package->factory();

			if($server->can('postinstall')) {
				$rs = step(
					sub { $server->postinstall() },
					sprintf("Running %s postinstall tasks...", ref $server),
					$nbServers,
					$step
				);

				last if $rs;
			}
		} else {
			error($@);
			$rs = 1;
			last;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupPostInstallServers');
}

# Call postinstall method on all i-MSCP packages
sub setupPostInstallPackages
{
	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPostInstallPackages');
	return $rs if $rs;

	my @packages = iMSCP::Packages->getInstance()->get();
	my $nbPackages = scalar @packages;
	my $step = 1;

	startDetail();

	for(@packages) {
		my $package = "Package::$_";

		eval "require $package";

		unless($@) {
			my $package = $package->getInstance();

			if($package->can('postinstall')) {
				$rs = step(
					sub { $package->postinstall() },
					sprintf("Running %s postinstall tasks...", ref $package),
					$nbPackages,
					$step
				);

				last if $rs;
			}
		} else {
			error($@);
			$rs = 1;
			last;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::EventManager->getInstance()->trigger('afterSetupPostInstallPackages');
}

# Restart all services
sub setupRestartServices
{
	my @services = ();

	my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupRestartServices', \@services);
	return $rs if $rs;

	my $serviceMngr = iMSCP::Service->getInstance();

	unshift @services, (
		[ sub { $serviceMngr->restart('imscp_network'); 0; }, 'i-MSCP Network' ],
		[ sub { $serviceMngr->restart('imscp_daemon'); 0; }, 'i-MSCP Daemon' ]
	);

	my $nbSteps = @services;
	my $step = 1;

	startDetail();

	for (@services) {
		$rs = step($_->[0], "Restarting $_->[1] service...", $nbSteps, $step);
		return $rs if $rs;
		$step++;
	}

	endDetail();

	iMSCP::EventManager->getInstance()->trigger('afterSetupRestartServices');
}

#
## Low level subroutines
#

# Retrieve question answer
sub setupGetQuestion
{
	my ($qname, $default) = @_;
	$default ||= '';

	return (exists $main::questions{$qname})
		? $main::questions{$qname}
		: (
			(exists $main::imscpConfig{$qname} && $main::imscpConfig{$qname} ne '')
				? $main::imscpConfig{$qname}
				: $default
		);
}

sub setupSetQuestion
{
	$main::questions{$_[0]} = $_[1];
}

# Check SQL connection
# Return int 0 on success, error string on failure
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

# Return database connection
#
# Param string [OPTIONAL] Database name to use (default none)
# Return ARRAY [iMSCP::Database|0, errstr] or SCALAR iMSCP::Database|0
sub setupGetSqlConnect
{
	my $dbName = $_[0] || '';

	my $db = iMSCP::Database->factory();

	$db->set('DATABASE_NAME', $dbName);
	$db->set('DATABASE_HOST', setupGetQuestion('DATABASE_HOST') || '');
	$db->set('DATABASE_PORT', setupGetQuestion('DATABASE_PORT') || '');
	$db->set('DATABASE_USER', setupGetQuestion('DATABASE_USER') || '');
	$db->set(
		'DATABASE_PASSWORD',
		setupGetQuestion('DATABASE_PASSWORD')
			? decryptBlowfishCBC($main::imscpDBKey, $main::imscpDBiv, setupGetQuestion('DATABASE_PASSWORD')) : ''
	);

	my $rs = $db->connect();
	my ($ret, $errstr) = ! $rs ? ($db, '') : (0, $rs);

	wantarray ? ($ret, $errstr) : $ret;
}

# Return int - 1 if database exists and look like an i-MSCP database, 0 othewise
sub setupIsImscpDb
{
	my $dbName = $_[0];

	my ($db, $errstr) = setupGetSqlConnect();
	fatal("Unable to connect to SQL server: $errstr") unless $db;

	my $rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $dbName);
	fatal("SQL query failed: $rs") if ref $rs ne 'HASH';

	return 0 if ! %$rs;

	($db, $errstr) = setupGetSqlConnect($dbName);
	fatal("Unable to connect to SQL database: $errstr") unless $db;

	$rs = $db->doQuery('1', 'SHOW TABLES');
	fatal("SQL query failed: $rs") if ref $rs ne 'HASH';

	for (qw/server_ips user_gui_props reseller_props/) {
		return 0 if ! exists $$rs{$_};
	}

	1;
}

# Return int - 1 if the given SQL user exists, 0 otherwise
sub setupIsSqlUser($)
{
	my $sqlUser = $_[0];

	my ($db, $errstr) = setupGetSqlConnect('mysql');
	fatal("Unable to connect to the SQL Server: $errstr") unless $db;

	my $rs = $db->doQuery('1', 'SELECT EXISTS(SELECT 1 FROM `user` WHERE `user` = ?)', $sqlUser);
	fatal($rs) if ref $rs ne 'HASH';

	$$rs{1} ? 1 : 0;
}

# Delete the give Sql user and all its privileges
#
# Return int 0 on success, 1 on error
sub setupDeleteSqlUser
{
	my ($user, $host) = @_;
	$host ||= '%';

	my ($db, $errstr) = setupGetSqlConnect('mysql');
	fatal("Unable to connect to the mysql database: $errstr") unless $db;

	# Remove any columns privileges for the given user
	$errstr = $db->doQuery('dummy', "DELETE FROM `columns_priv` WHERE `Host` = ? AND `User` = ?", $host, $user);
	unless(ref $errstr eq 'HASH') {
		error("Unable to remove columns privileges: $errstr");
		return 1;
	}

	# Remove any tables privileges for the given user
	$errstr = $db->doQuery('dummy', 'DELETE FROM `tables_priv` WHERE `Host` = ? AND `User` = ?', $host, $user);
	unless(ref $errstr eq 'HASH') {
		error("Unable to remove tables privileges: $errstr");
		return 1;
	}

	# Remove any proc privileges for the given user
	$errstr = $db->doQuery('dummy', 'DELETE FROM `procs_priv` WHERE `Host` = ? AND `User` = ?', $host, $user);
	unless(ref $errstr eq 'HASH') {
		error("Unable to remove procs privileges: $errstr");
		return 1;
	}

	# Remove any database privileges for the given user
	$errstr = $db->doQuery('dummy', 'DELETE FROM `db` WHERE `Host` = ? AND `User` = ?', $host, $user);
	unless(ref $errstr eq 'HASH') {
		error("Unable to remove privileges: $errstr");
		return 1;
	}

	# Remove any global privileges for the given user and the user itself
	$errstr = $db->doQuery('dummy', "DELETE FROM `user` WHERE `Host` = ? AND `User` = ?", $host, $user);
	unless(ref $errstr eq 'HASH') {
		error("Unable to delete SQL user: $errstr");
		return 1;
	}

	# Reload privileges
	$errstr = $db->doQuery('dummy','FLUSH PRIVILEGES');
	unless(ref $errstr eq 'HASH') {
		error("Unable to flush SQL privileges: $errstr");
		return 1;
	}

	0;
}

1;
