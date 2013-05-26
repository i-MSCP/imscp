#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
#
# @category		i-MSCP
# @copyright	2010-2013 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @author		Laurent Declercq <l.declercq@nuxwin.com>
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

use strict;
use warnings;

use FindBin;
use iMSCP::HooksManager;
use DateTime;
use DateTime::TimeZone;
use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;
use Data::Validate::Domain qw/is_domain/;
use IPC::Open3;
use Symbol qw/gensym/;
use File::Basename;
use iMSCP::LsbRelease;
use iMSCP::Debug;
use iMSCP::IP;
use iMSCP::Boot;
use iMSCP::Dialog;
use iMSCP::Stepper;
use iMSCP::Crypt;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::HooksManager;
use iMSCP::Rights;
use iMSCP::Templator;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use Modules::openssl;
use Email::Valid;
use iMSCP::Servers;
use iMSCP::Addons;
use iMSCP::Getopt;

# Global variable that holds some questions
%main::questions = ();

# Boot
sub setupBoot
{
	# We do not try to establish connection to the database since needed data can be unavailable
	iMSCP::Boot->getInstance()->boot({ 'mode' => 'setup', 'nodatabase' => 'yes' });

	my $oldConfig = "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf";

	%main::imscpOldConfig = ();

	tie %main::imscpOldConfig, 'iMSCP::Config', 'fileName' => $oldConfig, 'readonly' => 1 if -f $oldConfig;

	0;
}

# Allow any server/addon to register its setup hook functions on the hooks manager before any other tasks
sub setupRegisterHooks()
{
	my $rs = 0;
	my ($file, $class, $instance);
	my $hooksManager = iMSCP::HooksManager->getInstance();

	for(iMSCP::Servers->getInstance()->get()) {
		s/\.pm//;
		$file = "Servers/$_.pm";
		$class = "Servers::$_";
		require $file;

		$instance = $class->factory();
		$rs = $instance->registerSetupHooks($hooksManager) if $instance->can('registerSetupHooks');
		return $rs if $rs;
	}

	for(iMSCP::Addons->getInstance()->get()) {
		s/\.pm//;
		$file = "Addons/$_.pm";
		$class = "Addons::$_";

		require $file;
		$instance = $class->getInstance();
		$rs = $instance->registerSetupHooks($hooksManager) if $instance->can('registerSetupHooks');
		return $rs if $rs;
	}

	$rs;
}

# Trigger all dialog subroutines
sub setupDialog
{
	my $dialogStack = [];

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupDialog', $dialogStack) and return 1;

	unshift(
		@$dialogStack,
		(
			\&setupAskServerHostname,
			\&setupAskImscpVhost,
			\&setupAskLocalDnsResolver,
			\&setupAskServerIps,
			\&setupAskSqlDsn,
			\&setupAskSqlUserHost,
			\&setupAskImscpDbName,
			\&setupAskDbPrefixSuffix,
			\&setupAskDefaultAdmin,
			\&setupAskAdminEmail,
			\&setupAskPhpTimezone,
			\&setupAskSsl,
			\&setupAskImscpBackup,
			\&setupAskDomainBackup
		)
	);

	my $dialog = iMSCP::Dialog->factory();

	$dialog->resetLabels();
	$ENV{'DIALOGOPTS'} = "--ok-label Ok --yes-label Yes --no-label No --cancel-label Back";

	# We want get 30 as exit code for both ESC and CANCEL events (ESC will be handled in different way later)
	$ENV{'DIALOG_CANCEL'} = 30;
	$ENV{'DIALOG_ESC'} = 30;

	# Implements a simple state machine (backup capability)
	# Any dialog subroutine *should* allow user to step back by returning 30 when 'back' button is pushed
	my ($state, $nbDialog, $rs) = (0, scalar @$dialogStack, 0);

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

	iMSCP::HooksManager->getInstance()->trigger('afterSetupDialog');
}

# Process setup tasks
sub setupTasks
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupTasks') and return 1;

	my $rs;

	my @steps = (
		[\&setupSaveOldConfig,				'Saving old i-MSCP main configuration file'],
		[\&setupWriteNewConfig,				'Write new i-MSCP main configuration file'],
		[\&setupCreateMasterGroup,			'Creating i-MSCP system master group'],
		[\&setupCreateSystemDirectories,	'Creating system directories'],
		[\&setupServerHostname,				'Setting server hostname'],
		[\&setupLocalResolver,				'Setting local resolver'],
		[\&setupCreateDatabase,				'Creating/updating i-MSCP database'],
		[\&setupSecureSqlInstallation,		'Securing SQL installation'],
		[\&setupServerIps,					'Setting server ips'],
		[\&setupDefaultAdmin, 				'Creating default admin'],
		[\&setupPreInstallServers,			'Servers pre-installation'],
		[\&setupPreInstallAddons,			'Addons pre-installation'],
		[\&setupInstallServers,				'Servers installation'],
		[\&setupInstallAddons,				'Addons installation'],
		[\&setupPostInstallServers,			'Servers post-installation'],
		[\&setupPostInstallAddons,			'Addons post-installation'],
		[\&setupCron,						'Setup cron tasks'],
		[\&setupInitScripts,				'Setting i-MSCP init scripts'],
		[\&setupRebuildCustomerFiles,		'Rebuilding customers files'],
		[\&setupSetPermissions,				'Setting permissions'],
		[\&setupRestartServices,			'Restarting services'],
		[\&setupAdditionalTasks,			'Processing additional tasks']
	);

	my $step = 1;
	my $nbSteps = @steps;

	for (@steps) {
		$rs = step($_->[0], $_->[1], $nbSteps, $step);
		return $rs if $rs;
		$step++;
	}

	iMSCP::Dialog->factory()->endGauge() if iMSCP::Dialog->factory()->hasGauge();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupTasks');
}

#
## Dialog subroutines
#

# Ask for server hostname
sub setupAskServerHostname
{
	my $dialog = shift;
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my %options = ($main::imscpConfig{'DEBUG'} || iMSCP::Getopt->debug)
		? (domain_private_tld => qr /^(?:bogus|test)$/) : ();
	my ($rs, @labels) = (0, $hostname ? split(/\./, $hostname) : ());

	if(
		$main::reconfigure ~~ ['hostname', 'all', 'forced'] ||
		! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($hostname))
	) {
		if(! $hostname) {
			my $err = undef;

			if (execute("$main::imscpConfig{'CMD_HOSTNAME'} -f", \$hostname, \$err)) {
				error("Unable to find server hostname (server misconfigured?): $err");
			} else {
				chomp($hostname);
			}
		}

		my $msg = '';
		#$dialog->set('no-cancel', '');

		do {
			($rs, $hostname) = $dialog->inputbox(
				"\nPlease enter a fully-qualified hostname (FQHN): $msg", idn_to_unicode($hostname, 'utf-8')
			);
			$msg = "\n\n\\Z1'$hostname' is not a valid fully-qualified host name.\\Zn\n\nPlease, try again:";
			$hostname = idn_to_ascii($hostname, 'utf-8');
			@labels = split(/\./, $hostname);

		} while($rs != 30 && ! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($hostname)));

		#$dialog->set('no-cancel', undef);
	}

	$main::questions{'SERVER_HOSTNAME'} = $hostname if $rs != 30;

	$rs;
}

# Ask for i-MSCP frontend vhost
sub setupAskImscpVhost
{
	my $dialog = shift;
	my $vhost = setupGetQuestion('BASE_SERVER_VHOST');
	my %options = ($main::imscpConfig{'DEBUG'} || iMSCP::Getopt->debug)
		? (domain_private_tld => qr /^(?:bogus|test)$/) : ();

	my ($rs, @labels) = (0, $vhost ? split(/\./, $vhost) : ());

	if(
		$main::reconfigure ~~ ['hostname', 'all', 'forced'] ||
		! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($vhost))
	) {

		$vhost = 'admin.' . setupGetQuestion('SERVER_HOSTNAME') if ! $vhost;

		my $msg = '';

		do {
			($rs, $vhost) = $dialog->inputbox(
				"\nPlease enter the domain name from which i-MSCP frontEnd must be reachable: $msg",
				idn_to_unicode($vhost, 'utf-8')
			);
			$msg = "\n\n\\Z1'$vhost' is not a fully-qualified domain name (FQDN).\\Zn\n\nPlease, try again:";
			$vhost = idn_to_ascii($vhost, 'utf-8');
			@labels = split(/\./, $vhost);
		} while($rs != 30 && ! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($vhost)));
	}

	$main::questions{'BASE_SERVER_VHOST'} = $vhost if $rs != 30;

	$rs;
}

# Ask for local DNS resolver
sub setupAskLocalDnsResolver
{
	my $dialog = shift;
	my $localDnsResolver = setupGetQuestion('LOCAL_DNS_RESOLVER');
	$localDnsResolver = lc($localDnsResolver);
	my $rs = 0;

	if($main::reconfigure ~~ ['resolver', 'all', 'forced'] || $localDnsResolver !~ /^yes|no$/) {
		($rs, $localDnsResolver) = $dialog->radiolist(
			"\nDo you want allow the system resolver to use the local nameserver?",
			['yes', 'no'],
			$localDnsResolver ne 'no' ? 'yes' : 'no'
		);
	}

	$main::questions{'LOCAL_DNS_RESOLVER'} = $localDnsResolver if $rs != 30;

	$rs;
}

# Ask for server ips
sub setupAskServerIps
{
	my $dialog = shift;
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my $manualIp = 0;
	my $serverIps = '';

	my @serverIpsToAdd = setupGetQuestion('SERVER_IPS') ? @{setupGetQuestion('SERVER_IPS')} : ();
	my %serverIpsToDelete = ();
	my %serverIpsReplMap = ();

	my $ips = iMSCP::IP->new();
	my $rs = $ips->loadIPs();
	return $rs if $rs;

	# Retrieve list of all configured IP addresses
	my @serverIps = $ips->getIPs();
	if(! @serverIps) {
		error('Unable to retrieve servers ips');
		return 1;
	}

	my $currentServerIps = {};
	my $database = '';

	if(setupGetQuestion('DATABASE_NAME')) {
		# We do not raise error in case we cannot get SQL connection since it's expected in some context
    	$database = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

		if($database) {
			$currentServerIps = $database->doQuery('ip_number', 'SELECT `ip_id`, `ip_number` FROM `server_ips`');

			if(ref $currentServerIps ne 'HASH') {
				error('Cannot retrieve current server ips');
				return 1
			}
		}

		@serverIpsToAdd = (@serverIpsToAdd, keys %{$currentServerIps});
	}

	@serverIps = sort keys %{ { map { $_ => 1 } @serverIps, @serverIpsToAdd } };

	if(
		$main::reconfigure ~~ ['ips', 'all', 'forced'] ||
		! ($baseServerIp ~~ @serverIps && $baseServerIp ne '127.0.0.1' && $baseServerIp ne $ips->normalize('::1'))
	) {
		do {
			# Ask user for the server base IP
			($rs, $baseServerIp) = $dialog->radiolist(
				"\nPlease, select the base server IP for i-MSCP:",
				[@serverIps, 'Add new ip'],
				$baseServerIp ? $baseServerIp :  $serverIps[0]
			);
		} while($rs != 30 && ! $baseServerIp);

		# Handle server IP addresses addition
		if($rs != 30 && $baseServerIp eq 'Add new ip') {
			$baseServerIp = '';
			my $msg = '';
			do {
				($rs, $baseServerIp) = $dialog->inputbox("\nPlease, enter an IP address: $msg", $baseServerIp);
				$msg = "\n\n\\Z1Invalid or unallowed IP address.\\Zn\n\nPlease, try again:";
			} while(
				$rs != 30 &&
				! (
					$baseServerIp ne '127.0.0.1' && $baseServerIp ne $ips->normalize('::1') &&
					$ips->isValidIp($baseServerIp)
				)
            );

			if($rs != 30 && ! ($baseServerIp ~~ @serverIps)) {
				my $networkCard = undef;
				my @networkCardList = $ips->getNetCards();

				if(@networkCardList > 1) { # Do not ask about network card if not more than one is available
					($rs, $networkCard) = $dialog->radiolist(
                    	"\nPlease, select the network card on which you want to add the IP address:", @networkCardList
                    );
				} else {
					$networkCard = pop(@networkCardList);
				}

				if($rs != 30) {
					$ips->attachIpToNetCard($networkCard, $baseServerIp);
					$rs = $ips->reset();
					return $rs if $rs;
					$manualIp = 1;
				}
			}
		}

		# Handle IP deletion in case the user stepped back
		my $manualBaseServerIp = setupGetQuestion('MANUAL_BASE_SERVER_IP');

		if($manualBaseServerIp && $manualBaseServerIp ne $baseServerIp) {
			$ips->detachIpFromNetCard($manualBaseServerIp);
			$rs = $ips->reset();
			return $rs if $rs;
			@serverIps = grep $_ ne $manualBaseServerIp, @serverIps;
			delete $main::questions{'MANUAL_BASE_SERVER_IP'};
		}

		$main::questions{'MANUAL_BASE_SERVER_IP'} = $baseServerIp if $manualIp;

		# Handle additional IP addition / deletion
		if($rs != 30) {
			$dialog->set('defaultno', '');

			if(@serverIps > 1 && ! $dialog->yesno("\nDo you want add or remove IP addresses?")) {
				$dialog->set('defaultno', undef);

				@serverIps = grep $_ ne $baseServerIp, @serverIps; # Remove the base server IP from the list

				# Retrieve IP to which the user is currently connected (SSH)
				my $sshConnectIp = defined ($ENV{'SSH_CONNECTION'}) ? (split ' ', $ENV{'SSH_CONNECTION'})[2] : undef;

				my $msg = '';

				do {
					($rs, $serverIps) = $dialog->checkbox(
						"\nPlease, select the IP addresses to add into the database and deselect those to delete: $msg",
						[@serverIps],
						@serverIpsToAdd
					);

					$msg = '';

					if(defined $sshConnectIp && $sshConnectIp ~~ @serverIps && $serverIps !~ /$sshConnectIp/) {
						$msg = "\n\n\\Z1You cannot remove the IP '$sshConnectIp' to which you are currently connected (SSH).\\Zn\n\nPlease, try again:";
					}

				} while ($rs != 30 && $msg);

				if($rs != 30) {
					$serverIps =~ s/"//g;
					@serverIpsToAdd = split ' ', $serverIps; # Retrieve list of IP to add into database
					push @serverIpsToAdd, $baseServerIp; # Re-add base ip

					if($database) {
						# Get list of IP addresses to delete
						%serverIpsToDelete = ();
						for(@serverIps) {
							$serverIpsToDelete{$currentServerIps->{$_}->{'ip_id'}} = $_
								if(exists $currentServerIps->{$_} && not $_ ~~ @serverIpsToAdd);
						}

						# Check for server IP addresses already in use and ask for replacement
						my $resellerIps = $database->doQuery('reseller_ips', 'SELECT `reseller_ips` FROM `reseller_props`');

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
											[@serverIpsToAdd],
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
		$main::questions{'BASE_SERVER_IP'} = $baseServerIp;
		$main::questions{'SERVER_IPS'} = [@serverIpsToAdd];
		$main::questions{'SERVER_IPS_TO_REPLACE'} = {%serverIpsReplMap};
		$main::questions{'SERVER_IPS_TO_DELETE'} = [values %serverIpsToDelete];
	}

	$rs;
}

# Ask for Sql DSN and SQL username/password
sub setupAskSqlDsn
{
	my $dialog = shift;
	my $dbType = setupGetQuestion('DATABASE_TYPE') || 'mysql';
	my $dbHost = setupGetQuestion('DATABASE_HOST') || 'localhost';
	my $dbPort = setupGetQuestion('DATABASE_PORT') || '3306';
	my $dbUser = setupGetQuestion('DATABASE_USER') || 'root';

	my $dbPass = '';

	if(setupGetQuestion('DATABASE_PASSWORD', 'preseed')) {
		$dbPass = setupGetQuestion('DATABASE_PASSWORD', 'preseed');
	} else {
		$dbPass = setupGetQuestion('DATABASE_PASSWORD')
			? iMSCP::Crypt->getInstance()->decrypt_db_password(setupGetQuestion('DATABASE_PASSWORD')) : '';
	}

	my $rs = 0;

	my %options = ($main::imscpConfig{'DEBUG'} || iMSCP::Getopt->debug)
		? (domain_private_tld => qr /^(?:bogus|test)$/) : ();

	if(
		$main::reconfigure ~~ ['sql', 'servers', 'all', 'forced'] ||
		! ($dbPass ne '' && ! setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass))
	) {
		my $msg = '';

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
					$dbHost eq 'localhost' || Data::Validate::Domain->new(%options)->is_domain($dbHost) ||
					iMSCP::IP->new()->isValidIp($dbHost)
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

					if(length $dbUser > 16) {
						$msg = "\n\n\\Z1MySQL user names can be up to 16 characters long.\\Zn\n\nPlease, try again:";
						$dbUser = '';
					}
				} while($rs != 30 && ! $dbUser);
			}

			# Ask for SQL user password
			if($rs != 30) {
				do {
					($rs, $dbPass) = $dialog->inputbox("\nPlease, enter a password for the '$dbUser' SQL user:", $dbPass);
				} while($rs != 30 && $dbPass eq '');

				$msg =
"
\\Z1Connection to SQL server failed\\Zn

i-MSCP was unable to connect to the SQL server with the following data:

\\Z4Host:\\Zn		$dbHost
\\Z4Port:\\Zn		$dbPort
\\Z4Username:\\Zn	$dbUser
\\Z4Password:\\Zn	$dbPass

Please, try again.
";
			}

		} while($rs != 30 && setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass));
	}

	if($rs != 30) {
		$main::questions{'DATABASE_TYPE'} = $dbType;
		$main::questions{'DATABASE_HOST'} = $dbHost;
		$main::questions{'DATABASE_PORT'} = $dbPort;
		$main::questions{'DATABASE_USER'} = $dbUser;
		$main::questions{'DATABASE_PASSWORD'} = iMSCP::Crypt->getInstance()->encrypt_db_password($dbPass);
	}

	$rs;
}

# Ask for hosts from which SQL users are allowed to connect from
sub setupAskSqlUserHost
{
	my $dialog = shift;
	my $host = setupGetQuestion('DATABASE_USER_HOST');
	my $domain = Data::Validate::Domain->new();
	my $ip = iMSCP::IP->new();
	my $rs = 0;

	if(
		$main::reconfigure ~~ ['sql', 'servers', 'all', 'forced'] ||
		(
			$host ne 'localhost' && $host ne '127.0.0.1' && $host ne '%' &&
			! $domain->is_domain($host) && ! $ip->isValidIp($host)
		)
	) {
		my $msg = '';

		$host = (setupGetQuestion('SQL_SERVER') ne 'remote_server')
			? 'localhost' : setupGetQuestion('BASE_SERVER_IP') if ! $host;

		do {
			($rs, $host) = $dialog->inputbox(
"
Please, enter the host from which SQL users created by i-MSCP should be allowed to connect to your SQL server:

Allowed values are:

 - Fully qualified hostname or localhost
 - IPv4 or IPv6 addresses
 - The percent character '%' for any host

 This dialog is mostly for remote MySQL server usage. If you are using a local server, default value should be fine.
",
				$host
			);

			$msg = '';

			if($rs != 30) {
				if(
					$host ne 'localhost' && $host ne '127.0.0.1' && $host ne '%' && ! $domain->is_domain($host) &&
					! $ip->isValidIp($host)
				) {
					$msg = "\n\n\\Z1 Invalid host found.\\z\n\n Please, try again:";
				}
			}
		} while($rs != 30 && $msg);
	}

	$main::questions{'DATABASE_USER_HOST'} = $host if $rs != 30;

	$rs;
}

# Ask for i-MSCP database name
sub setupAskImscpDbName
{
	my $dialog = shift;
	my $dbName = setupGetQuestion('DATABASE_NAME') || 'imscp';
	my $rs = 0;

	if(
		$main::reconfigure ~~ ['sql', 'servers', 'all', 'forced'] ||
		(! setupGetQuestion('DATABASE_NAME', 'preseed') && ! setupIsImscpDb($dbName))
	) {
		my $msg = '';

		do {
			($rs, $dbName) = $dialog->inputbox("\nPlease, enter a database name for i-MSCP: $msg", $dbName);
			$msg = '';

			if(! $dbName) {
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

\\Z4Note:\\Zn If the database you want to create already exists, nothing
      will happen.
"
				);

				$dialog->set('defaultno', undef);
			}
		}
	}

	$main::questions{'DATABASE_NAME'} = $dbName if $rs != 30;

	$rs;
}

# Ask for database prefix/suffix
sub setupAskDbPrefixSuffix
{
	my $dialog = shift;
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

Do you want use a prefix or suffix for customers's SQL databases?

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
		$main::questions{'MYSQL_PREFIX'} = $prefix;
		$main::questions{'MYSQL_PREFIX_TYPE'} = $prefixType;
	}

	$rs;
}

# Ask for default administrator
sub setupAskDefaultAdmin
{
	my $dialog = shift;
	my ($adminLoginName, $password, $rpassword) = ('', '', '');
	my ($rs, $msg) = (0, '');

	my $database = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

	if(setupGetQuestion('ADMIN_LOGIN_NAME', 'preseed')) {
		$adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME', 'preseed');
		$password = setupGetQuestion('ADMIN_PASSWORD', 'preseed');
	} elsif($database) {
		my $defaultAdmin = $database->doQuery(
			'created_by',
			'
				SELECT
					`admin_name`, `created_by`
				FROM
					`admin` WHERE `created_by` = ? AND `admin_type` = ?
				LIMIT
					1
			',
			'0',
			'admin'
		);

		if(ref $defaultAdmin eq 'HASH' && %{$defaultAdmin}) {
			$adminLoginName = $$defaultAdmin{'0'}->{'admin_name'};
			$main::questions{'ADMIN_OLD_LOGIN_NAME'} = $adminLoginName;
		}
	}

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
			}
		} while($rs != 30 &&  $msg);

		if($rs != 30) {
			$msg = '';

			do {
				# Ask for administrator password
				do {
					($rs, $password) = $dialog->inputbox("\nPlease, enter admin password: $msg", $password);
					$msg = '\n\n\\Z1The password must be at least 6 characters long.\\Zn\n\nPlease, try again:';
				} while($rs != 30 && length $password < 6);

				# Ask for administrator password confirmation
				if($rs != 30) {
					$msg = '';

					do {
						($rs, $rpassword) = $dialog->inputbox("\nPlease, confirm admin password: $msg", '');
						$msg = "\n\n\\Z1Passwords do not match.\\Zn\n\nPlease try again:";
					} while($rs != 30 &&  $rpassword ne $password);
				}
			} while($rs != 30 && $password ne $rpassword);
		}
	}

	if($rs != 30) {
		$main::questions{'ADMIN_LOGIN_NAME'} = $adminLoginName;
		$main::questions{'ADMIN_PASSWORD'} = $password;
	}

	$rs;
}

# Ask for administrator email
sub setupAskAdminEmail
{
	my $dialog = shift;
	my $adminEmail = setupGetQuestion('DEFAULT_ADMIN_ADDRESS');
	my $rs = 0;

	if($main::reconfigure ~~ ['admin', 'all', 'forced'] || ! Email::Valid->address($adminEmail)) {
		my $msg = '';

		do {
			($rs, $adminEmail) = $dialog->inputbox("\nPlease, enter admin email address: $msg", $adminEmail);
			$msg = "\n\n\\Z1'$adminEmail' is not a valid email address.\\Zn\n\nPlease, try again:";
		} while( $rs != 30 && ! Email::Valid->address($adminEmail));
	}

	$main::questions{'DEFAULT_ADMIN_ADDRESS'} = $adminEmail if $rs != 30;

	$rs;
}

# Ask for PHP timezone
sub setupAskPhpTimezone
{
	my $dialog = shift;
	my $defaultTimezone = DateTime->new(year => 0, time_zone => 'local')->time_zone->name;
	my $timezone = setupGetQuestion('PHP_TIMEZONE');
	my $rs = 0;

	if(
		$main::reconfigure ~~ ['php', 'all', 'forced'] ||
		! ($timezone && DateTime::TimeZone->is_valid_name($timezone))
	) {
		$timezone = $defaultTimezone if ! $timezone;
		my $msg = '';

		do {
			($rs, $timezone) = $dialog->inputbox("\nPlease enter timezone for PHP: $msg", $timezone);
			$msg = "\n\n\\Z1'$timezone' is not a valid timezone.\\Zn\n\nPlease, try again:";
		} while($rs != 30 && ! DateTime::TimeZone->is_valid_name($timezone));
	}

	$main::questions{'PHP_TIMEZONE'} = $timezone if $rs != 30;

	$rs;
}

# Ask for i-MSCP ssl support
sub setupAskSsl
{
	my($dialog) = shift;

	my $sslEnabled = setupGetQuestion('SSL_ENABLED');
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my $guiCertDir = $main::imscpConfig{'GUI_CERT_DIR'};
	my $cmdOpenSsl = $main::imscpConfig{'CMD_OPENSSL'};
	my $openSSL = Modules::openssl->getInstance();

	my $rs = 0;

	if($main::reconfigure ~~ ['ssl', 'all', 'forced'] || $sslEnabled !~ /^yes|no$/i) {
		$openSSL->{'openssl_path'} = $cmdOpenSsl;
		$rs = setupSslDialog($dialog);
		return $rs if $rs;
	} elsif(setupGetQuestion('SSL_ENABLED', 'preseed') eq 'yes') { # We are in preseed mode
		$main::questions{'SSL_ENABLED'} = $sslEnabled;

		$openSSL->{'openssl_path'} = $cmdOpenSsl;
		$openSSL->{'new_cert_path'} = $main::imscpConfig{'GUI_CERT_DIR'};
		$openSSL->{'new_cert_name'} = setupGetQuestion('SERVER_HOSTNAME');
		$openSSL->{'cert_selfsigned'} = setupGetQuestion('SELFSIGNED_CERTIFICATE');

		if(! $openSSL->{'cert_selfsigned'}) {
			$openSSL->{'key_path'} = setupGetQuestion('CERTIFICATE_KEY_PATH');
			$openSSL->{'key_pass'} = setupGetQuestion('CERTIFICATE_KEY_PASSWORD');
			$openSSL->{'intermediate_cert_path'} = setupGetQuestion('INTERMEDIATE_CERTIFICATE_PATH');
			$openSSL->{'cert_path'} = setupGetQuestion('CERTIFICATE_PATH');

			$rs = $openSSL->ssl_check_all();
		} else {
			$openSSL->{'vhost_cert_name'} = setupGetQuestion('SERVER_HOSTNAME')
		}

		if($rs) { # In preseed mode, will cause fatal error and it's expected
			$rs = setupSslDialog($dialog);
        	return $rs if $rs;
        } else {
        	$rs = $openSSL->ssl_export_all();
        	return $rs if $rs;
        }
	} elsif($sslEnabled eq 'yes') {
		$openSSL->{'openssl_path'} = $cmdOpenSsl;
		$openSSL->{'key_path'} = "$guiCertDir/$hostname.pem";
		$openSSL->{'cert_path'} = "$guiCertDir/$hostname.pem";
		$openSSL->{'intermediate_cert_path'} = "$guiCertDir/$hostname.pem";

		if($openSSL->ssl_check_all()){
			iMSCP::Dialog->factory()->msgbox("Certificate is missing or corrupted. Starting recover");
			$rs = setupSslDialog($dialog);
			return $rs if $rs;
		}
	} else {
		$main::questions{'SSL_ENABLED'} = 'no';
	}

	$main::questions{'BASE_SERVER_VHOST_PREFIX'} = 'http://' if $main::imscpConfig{'SSL_ENABLED'} eq 'no';

	$rs;
}

sub setupSslDialog
{
	my ($dialog, $rs, $ret) = (shift, 0, '');

	my $sslEnabled = setupGetQuestion('SSL_ENABLED') || 'no';

	($rs, $sslEnabled) = $dialog->radiolist(
		"\nDo you want to activate SSL for i-MSCP?", ['no', 'yes'], lc($sslEnabled) eq 'yes' ? 'yes' : 'no'
	);

	if($rs != 30) {
		$main::questions{'SSL_ENABLED'} = $sslEnabled;

		if($sslEnabled eq 'yes') {
			my $openSSL = Modules::openssl->getInstance();

			$openSSL->{'new_cert_path'} = $main::imscpConfig{'GUI_CERT_DIR'};
			$openSSL->{'new_cert_name'} = setupGetQuestion('SERVER_HOSTNAME');

			# TODO determine default value here
			($rs, $ret) = $dialog->radiolist( "\nDo you have an SSL certificate?", ['yes', 'no'], 'no');

			if($rs != 30) {
				$ret = $ret eq 'yes' ? 1 : 0;

				$openSSL->{'cert_selfsigned'} = 1 if ! $ret;
				$openSSL->{'vhost_cert_name'} = setupGetQuestion('SERVER_HOSTNAME') if ! $ret;

				if(! $openSSL->{'cert_selfsigned'}) {
					$rs = setupAskCertificateKeyPath($dialog);
					$rs = setupAskIntermediateCertificatePath($dialog) if $rs != 30;
					$rs = setupAskCertificatePath($dialog) if $rs != 30;
				}

				if($rs != 30) {
					$rs = $openSSL->ssl_export_all();
					return $rs if $rs;
				}
			}
		}

		if($rs != 30 && $sslEnabled eq 'yes') {
			my $httpPrefix = setupGetQuestion('BASE_SERVER_VHOST_PREFIX');

			($rs, $ret) = $dialog->radiolist(
				"\nPlease, choose the default access mode for i-MSCP",
				['https', 'http'],
				lc($httpPrefix) eq 'https://' ? 'https' : 'http'

			);

			$main::questions{'BASE_SERVER_VHOST_PREFIX'} = "$ret://" if $rs != 30;
		}
	}

	$rs;
}

sub setupAskCertificateKeyPath
{
	my ($dialog, $rs, $ret1, $ret2, $msg) = (shift, 0, '', '', '');

	my $key = '/root/' . setupGetQuestion('SERVER_HOSTNAME') . '.key';
	my $openSSL = Modules::openssl->getInstance();

	do {
		($rs, $ret1) = $dialog->passwordbox("\nPlease enter the password for your private key if needed:$msg", $ret1);

		if($rs != 30) {
			$ret1 =~ s/(["\$`\\])/\\$1/g;
			$openSSL->{'key_pass'} = $ret1;

			do {
				($rs, $ret2) = $dialog->fselect($key);
			} while($rs != 30 && ! ($ret2 && -f $ret2));

			if($rs != 30) {
				$openSSL->{'key_path'} = $ret2;
				$key = $ret2;
			}
		}

		if($openSSL->ssl_check_key()) {
			$msg = "\n\n\\Z1Wrong private key or password.\\Zn\n\nPlease try again:";
		} else {
			$msg = '';
		}

	} while($rs != 30 && $msg);

	$rs;
}

sub setupAskIntermediateCertificatePath
{
	my ($dialog, $cert, $rs, $ret) = (shift, '/root/', 0, '');

	$rs = $dialog->yesno("\nDo you have an intermediate certificate?");
	return 0 if $rs;

	do {
		($rs, $ret) = $dialog->fselect($cert);
	} while($rs != 30 && ! ($ret && -f $ret));

	Modules::openssl->getInstance()->{'intermediate_cert_path'} = $ret if $rs != 30;

	$rs;
}

sub setupAskCertificatePath
{
	my ($dialog, $rs, $ret) = (shift, 0, '');

	my $cert = '/root/' . setupGetQuestion('SERVER_HOSTNAME') . '.crt';
	my $openSSL = Modules::openssl->getInstance();

	$dialog->msgbox("\nPlease select your certificate:");

	do {
		do {
			($rs, $ret) = $dialog->fselect($cert);
		} while($rs != 30 && ! ($ret && -f $ret));

		if($rs != 30) {
			$openSSL->{'cert_path'} = $ret;
			$cert = $ret;
		}
	} while($rs != 30 && $openSSL->ssl_check_cert());

	$rs;
}

# Ask for i-MSCP backup feature
sub setupAskImscpBackup
{
	my $dialog = shift;
	my $backupImscp = setupGetQuestion('BACKUP_IMSCP');
	$backupImscp = lc($backupImscp);
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

	$main::questions{'BACKUP_IMSCP'} = $backupImscp if $rs != 30;

	$rs;
}

# Ask for customer backup feature
sub setupAskDomainBackup
{
	my $dialog = shift;
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
			$backupDomains ne 'yes' ? 'no' : 'yes'
		);
	}

	$main::questions{'BACKUP_DOMAINS'} = $backupDomains if $rs != 30;

	$rs;
}

#
## Setup subroutines
#

# Save old i-MSCP main configuration file
#
sub setupSaveOldConfig
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupSaveOldConfig');
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.conf");

	my $cfg = $file->get();
	unless(defined $cfg) {
		error("$main::imscpConfig{'CONF_DIR'}/imscp.conf");
		return 1;
	}

	$file = iMSCP::File->new('filename' => "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf");

	$rs = $file->set($cfg);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupSaveOldConfig');
}

# Write question answers into imscp.conf file
sub setupWriteNewConfig
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupWriteNewConfig');
	return $rs if $rs;

	for(keys %main::questions) {
		if(exists $main::imscpConfig{$_}) {
			$main::imscpConfig{$_} = $main::questions{$_};
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupWriteNewConfig');
}

# Create system master group for imscp
sub setupCreateMasterGroup
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupCreateMasterGroup');
	return $rs if $rs;

	my $group = iMSCP::SystemGroup->new();

	$group->{'system'} = 'yes';
	$rs = $group->addSystemGroup($main::imscpConfig{'MASTER_GROUP'});
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCreateMasterGroup');
}

# Create default directories needed by i-MSCP
sub setupCreateSystemDirectories
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my @systemDirectories  = (
		#[$main::imscpConfig{'USER_WEB_DIR'}, $rootUName, $rootGName, 0555],
		#[$main::imscpConfig{'LOG_DIR'}, $rootUName,	$rootGName, 0555],
		[$main::imscpConfig{'BACKUP_FILE_DIR'}, $rootUName, $rootGName, 0750]
	);

	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupCreateSystemDirectories', \@systemDirectories);
	return $rs if $rs;

	for (@systemDirectories) {
		$rs = iMSCP::Dir->new('dirname' => $_->[0])->make({ user => $_->[1], group => $_->[2], mode => $_->[3]});
		return $rs if $rs;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCreateSystemDirectories');
}

# Setup server hostname
sub setupServerHostname
{
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');

	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupServerHostname', \$hostname, \$baseServerIp);
	return $rs if $rs;

	my @labels = split /\./, $hostname;
	my $host = shift(@labels);
	my $hostnameLocal = "$hostname.local";

	my $file = iMSCP::File->new('filename' => '/etc/hosts');
	$rs = $file->copyFile('/etc/hosts.bkp') if ! -f '/etc/hosts.bkp';
	return $rs if $rs;

	my $content = "# 'hosts' file configuration.\n\n";

	$content .= "127.0.0.1\t$hostnameLocal\tlocalhost\n";
	$content .= "$baseServerIp\t$hostname\t$host\n";
	$content .= "::ffff:$baseServerIp\t$hostname\t$host\n" if iMSCP::IP->new()->getIpType($baseServerIp) eq 'ipv4';
	$content .= "::1\tip6-localhost\tip6-loopback\n" if iMSCP::IP->new()->getIpType($baseServerIp) eq 'ipv4';
	$content .= "::1\tip6-localhost\tip6-loopback\t$host\n" if iMSCP::IP->new()->getIpType($baseServerIp) ne 'ipv4';
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
	$rs = execute("$main::imscpConfig{'CMD_HOSTNAME'} $host", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	warning($stderr) if ! $rs && $stderr;
	error($stderr) if $rs && $stderr;
	error('Unable to set server hostname') if $rs && ! $stderr;
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupServerHostname');
}

# Setup server ips
sub setupServerIps
{
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my $serverIpsToReplace = setupGetQuestion('SERVER_IPS_TO_REPLACE') || {};
	my $serverIpsToDelete = setupGetQuestion('SERVER_IPS_TO_DELETE') || [];
	my $serverHostname = setupGetQuestion('SERVER_HOSTNAME');
	my $oldIptoIdMap = {};

	my @serverIps = (
		$main::imscpConfig{'BASE_SERVER_IP'},
		$main::questions{'SERVER_IPS'} ? @{$main::questions{'SERVER_IPS'}} : ()
	);

	my $rs = iMSCP::HooksManager->getInstance()->trigger(
		'beforeSetupServerIps', \$baseServerIp, \@serverIps, $serverIpsToReplace
	);
	return $rs if $rs;

	my ($database, $errstr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	if(! $database) {
		error("Unable to connect to the SQL database: $errstr");;
		return 1;
	}

	# Get IDs of IP addresses to replace
	if(%{$serverIpsToReplace}) {
		my $ipsToReplace = join q{,}, map $database->quote($_), keys %{$serverIpsToReplace};
		$oldIptoIdMap = $database->doQuery(
			'ip_number', 'SELECT `ip_id`, `ip_number` FROM `server_ips` WHERE `ip_number` IN ('. $ipsToReplace .')'
		);
		if(ref $oldIptoIdMap ne 'HASH') {
			error("Unable to get IDs of server IPs to replace: $oldIptoIdMap");
			return 1;
		}
	}

	my $ips = iMSCP::IP->new();
	$rs = $ips->loadIPs();
	return $rs if $rs;

	# Process server IP addresses addition

	my ($defaultNetcard) = $ips->getNetCards();

	for (@serverIps) {
		next if exists $serverIpsToReplace->{$_};
		my $netCard = $ips->getCardByIP($_) || $defaultNetcard;

		if($netCard) {
			my $rs = $database->doQuery(
				'dummy',
				'
					INSERT IGNORE INTO `server_ips` (
						`ip_number`, `ip_card`, `ip_status`, `ip_id`
					) VALUES(
						?, ?, ?, (SELECT `ip_id` FROM `server_ips` AS `t1` WHERE `t1`.`ip_number` = ?)
					)
				',
				$_, $netCard, 'toadd', $_
			);
			if (ref $rs ne 'HASH') {
				error("Unable to add/update server address IP '$_': $rs");
				return 1;
			}
		} else {
			error("Unable to add the '$_' IP into database");
			return 1;
		}
	}

	# Setup/update domain name and alias for the base server IP

	my ($alias) =  split /\./, $serverHostname;

	$rs = $database->doQuery(
		'dummy', 'UPDATE `server_ips` SET `ip_domain` = ?, `ip_alias` = ? WHERE `ip_number` = ?',
		$serverHostname, $alias, $baseServerIp
	);
	return $rs if ref $rs ne 'HASH';

	$rs = $database->doQuery(
		'dummy',
		'UPDATE `server_ips` SET `ip_domain` = NULL, `ip_alias` = NULL WHERE `ip_number` <> ?  AND `ip_domain` = ?',
		$baseServerIp, $serverHostname
	);
	return $rs if ref $rs ne 'HASH';

	# Server ips replacement

	if(%{$serverIpsToReplace}) {
		# for each IP to replace
		for(keys %$serverIpsToReplace) {
			my $newIp = $serverIpsToReplace->{$_}; # New IP
			my $oldIpId = $oldIptoIdMap->{$_}->{'ip_id'}; # Old IP ID

			# Get IP IDs of resellers to which the IP to replace is currently assigned
			my $resellerIps = $database->doQuery(
				'id',
				'SELECT `id`, `reseller_ips` FROM `reseller_props` WHERE `reseller_ips` REGEXP ?',
				"(^|[^0-9]$oldIpId;)"
			);
			if(ref $resellerIps ne 'HASH') {
				error("Query failed: $resellerIps");
				return 1;
			}

			# Get new IP ID
			my $newIpId = $database->doQuery(
				'ip_number', 'SELECT `ip_id`, `ip_number` FROM `server_ips` WHERE `ip_number` = ?', $newIp
			);
			if(ref $newIpId ne 'HASH') {
				error("Unable to get ID of the '$newIp' address IP:$newIpId");
				return 1;
			}

			$newIpId = $newIpId->{$newIp}->{'ip_id'};

			for(keys %$resellerIps) {
				my $ips = $resellerIps->{$_}->{'reseller_ips'};

				if($ips !~ /(?:^|[^0-9])$newIpId;/) {
					$ips =~ s/((?:^|[^0-9]))$oldIpId;?/$1$newIpId;/;
					$rs = $database->doQuery(
						'dummy', 'UPDATE `reseller_props` SET `reseller_ips` = ? WHERE `id` = ?', $ips, $_
					);
					if(ref $rs ne 'HASH') {
						error("Unable to update reseller IP list: $rs");
						return 1;
					}
				}
			}
		}
	}

	# Process IP deletion
	if(@{$serverIpsToDelete}) {
		my $serverIpsToDelete = join q{,}, map $database->quote($_), @{$serverIpsToDelete};
		my $rs = $database->doQuery(
			'dummy',
			'UPDATE`server_ips` set `ip_status` = ?  WHERE `ip_number` IN(' . $serverIpsToDelete . ') AND `ip_number` <> ?',
			'delete',
			$baseServerIp
		);
		if (ref $rs ne 'HASH') {
			error("Unable to schedule server IPs deletion: $rs");
			return 1;
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupServerIps');
}

# Setup local resolver
sub setupLocalResolver
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupLocalResolver');
	return $rs if $rs;

	my ($err, $file, $content, $out);

	if(-f $main::imscpConfig{'RESOLVER_CONF_FILE'}) {
		$file = iMSCP::File->new(filename => $main::imscpConfig{'RESOLVER_CONF_FILE'});
		$content = $file->get();

		unless (defined $content){
			$err = "Unable to read $main::imscpConfig{'RESOLVER_CONF_FILE'}";
			error($err);
			return 1;
		}

		if(setupGetQuestion('LOCAL_DNS_RESOLVER') =~ /^yes$/i) {
			if($content !~ /nameserver 127.0.0.1/i) {
				$content =~ s/(nameserver.*)/nameserver 127.0.0.1\n$1/i;
			}
		} else {
			$content =~ s/nameserver 127.0.0.1//i;
		}

		$content =~ s/\n+/\n/g; # Remove any empty line

		# Saving the old file if needed
		if(! -f "$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp") {
			$rs = $file->copyFile("$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp");
			return $rs if $rs;
		}

		# Storing the new file
		$rs = $file->set($content);
		return $rs if $rs;

		$rs = $file->save() ;
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;
	} else {
		warning("Unable to found the resolv.conf file on your system");
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupLocalResolver');
}

# Create iMSCP database
sub setupCreateDatabase
{
	my $dbName = setupGetQuestion('DATABASE_NAME');

	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupCreateDatabase', \$dbName);
	return $rs if $rs;

	if(! setupIsImscpDb($dbName)) {
		my ($database, $errStr) = setupGetSqlConnect();
		if(! $database) {
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

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCreateDatabase');
}

# Convenience method allowing to create or update a database schema
sub setupImportSqlSchema
{
	my $database = shift;
	my $file = shift;

	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupImportSqlSchema', \$file);

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
		if(ref $rs ne 'HASH') {
			error("Unable to execute SQL query: $rs");
			return 1;
		}

		my $msg = $queries[$step] ? "$title\n$queries[$step]" : $title;
		step('', $msg, scalar @queries, $step);
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupImportSqlSchema');
}

# Update i-MSCP database schema
sub setupUpdateDatabase
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupUpdateDatabase');
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php");

	my $content	= $file->get();
	unless(defined $content) {
		error("Unable to read $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php");
		return 1;
	}

	if($content =~ s/{GUI_ROOT_DIR}/$main::imscpConfig{'GUI_ROOT_DIR'}/) {
		$rs = $file->set($content);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	my ($stdout, $stderr);
	$rs = execute(
		"$main::imscpConfig{'CMD_PHP'} $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupUpdateDatabase');
}

# Secure any SQL account by removing those without password
# Basically, this method do same job as the mysql_secure_installation script
# - Remove anonymous users
# - Remove users without password set
# - Remove remote sql root user
# - Remove test database if any
# - Reload privileges tables
sub setupSecureSqlInstallation
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupSecureSqlInstallation');
	return $rs if $rs;

	my ($database, $errStr) = setupGetSqlConnect();
	if(! $database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	# Remove anonymous users
	$errStr = $database->doQuery('dummy', "DELETE FROM `mysql`.`user` WHERE `User` = '';");
	if(ref $errStr ne 'HASH') {
		error("Unable to delete anonymous users: $errStr");
		return 1;
	}

	# Remove user without password set
	my $rdata = $database->doQuery('User', "SELECT `User`, `Host` FROM `mysql`.`user` WHERE `Password` = '';");

	for (keys %{$rdata}) {
		$errStr = $database->doQuery('dummy', "DROP USER ?@?", $_, $rdata->{$_}->{'Host'});
		if(ref $errStr ne 'HASH') {
			error("Unable to remove SQL user $_\\@$rdata->{$_}->{'Host'}: $errStr");
			return 1;
		}
	}

    # Remove test database if any
    $errStr = $database->doQuery('dummy', 'DROP DATABASE `test`;');
   	if(ref $errStr ne 'HASH'){
    	debug("Unable to remove database test (not critical): $errStr"); # Not critical, keep moving...
    }

    # Remove privileges on test database
    $errStr = $database->doQuery('dummy', "DELETE FROM `mysql`.`db` WHERE `Db` = 'test' OR `Db` = 'test\\_%';");
   	if(ref $errStr ne 'HASH'){
    	debug("Unable to remove privilege on test database (not critical): $errStr"); # Not critical, keep moving...
    }

	# Disallow remote root login
	if($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
		$errStr = $database->doQuery(
			'dummy',
			"DELETE FROM `mysql`.`user` WHERE `User` = 'root' AND `Host` NOT IN ('localhost', '127.0.0.1', '::1');"
		);
   		if(ref $errStr ne 'HASH'){
    		error("Unable to remove remote root user: $errStr");
    		return 1;
    	}
    }

	# Reload privilege tables
    $errStr = $database->doQuery('dummy', 'FLUSH PRIVILEGES;');
   	if(ref $errStr ne 'HASH') {
    	debug("Unable to reload privileges tables: $errStr");
    	return 1;
    }

	iMSCP::HooksManager->getInstance()->trigger('afterSetupSecureSqlInstallation');
}

# Setup default admin
sub setupDefaultAdmin
{
	my $adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME');
	my $adminOldLoginName = setupGetQuestion('ADMIN_OLD_LOGIN_NAME');
	my $adminPassword= setupGetQuestion('ADMIN_PASSWORD');
	my $adminEmail= setupGetQuestion('DEFAULT_ADMIN_ADDRESS');

	my $rs = iMSCP::HooksManager->getInstance()->trigger(
		'beforeSetupDefaultAdmin', \$adminLoginName, \$adminPassword, \$adminEmail
	);
	return $rs if $rs;

	if($adminLoginName && $adminPassword) {

		$adminPassword = iMSCP::Crypt->getInstance()->crypt_md5_data($adminPassword);

		my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
		if(! $database) {
			error("Unable to connect to SQL server: $errStr");
			return 1;
		}

		my $rs = $database->doQuery(
			'dummy', 'DELETE FROM `admin` WHERE `admin_name` = ? OR `admin_name` = ?',
			$adminLoginName, $adminOldLoginName
		);
		return $rs if ref $rs ne 'HASH';

		$rs = $database->doQuery(
			'dummy',
			'
				INSERT INTO `admin` (
					`admin_name`, `admin_pass`, `admin_type`, `email`
				) VALUES (
					?, ?, ?, ?
				)
			',
			$adminLoginName, $adminPassword, 'admin', $adminEmail
		);
		return $rs if ref $rs ne 'HASH';

		$rs = $database->doQuery('admin_id', 'SELECT `admin_id` FROM `admin` WHERE `admin_type` = ?', 'reseller');
		return $rs if ref $rs ne 'HASH';

		if(%{$rs}) {
			$rs = $database->doQuery(
				'dummy',
				'
					UPDATE
						`admin` SET `created_by` = LAST_INSERT_ID()
					WHERE
						`admin_type` = ?
					AND
						`created_by` NOT IN (' . join(',', keys %{$rs}) . ')
				',
				'reseller'
			);
			return $rs if ref $rs ne 'HASH';
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
			'en_GB', 'default', 'black', '', '1'
		);
		return $rs if ref $rs ne 'HASH';

		# Remove any orphaned user properties
		$rs = $database->doQuery(
			'dummy', 'DELETE FROM `user_gui_props` WHERE `user_id` NOT IN (SELECT `admin_id` FROM `admin`)'
		);
		return $rs if ref $rs ne 'HASH';
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupDefaultAdmin');
}

# Setup crontab
# TODO: awstats part should be done via awstats installer
sub setupCron
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupCron');
	return $rs if $rs;

	my ($cfgTpl, $err);

	my $awstats = '';
	my ($rkhunter, $chkrootkit);

	# Directories paths
	my $cfgDir = $main::imscpConfig{'CONF_DIR'} . '/cron.d';
	my $bkpDir = $cfgDir . '/backup';
	my $wrkDir = $cfgDir . '/working';

	# Retrieving production directory path
	my $prodDir = ($^O =~ /bsd$/ ? '/usr/local/etc/cron.daily/imscp' : '/etc/cron.d');

	# Saving the current production file if it exists
	if(-f "$prodDir/imscp") {
		$rs = iMSCP::File->new('filename' => "$prodDir/imscp")->copyFile("$bkpDir/imscp." . time);
		return $rs if $rs;
	}

	## Building new configuration file

	# Loading the template from /etc/imscp/cron.d/imscp
	$cfgTpl = iMSCP::File->new('filename' => "$cfgDir/imscp")->get();
	unless(defined $cfgTpl) {
		error("Unable to read $cfgDir/imscp");
		return 1;
	}

	# Awstats cron task preparation (On|Off) according status in imscp.conf
	if ($main::imscpConfig{'WEBSTATS_ADDON'} ne 'Awstats' || $main::imscpConfig{'AWSTATS_MODE'} eq '1') {
		$awstats = '#';
	}

	# Search and cleaning path for rkhunter and chkrootkit programs
	# @todo review this s...
	($rkhunter = `which rkhunter`) =~ s/\s$//g;
	($chkrootkit = `which chkrootkit`) =~ s/\s$//g;

	# Building the new file
	$cfgTpl = iMSCP::Templator::process(
		{
			'LOG_DIR' => $main::imscpConfig{'LOG_DIR'},
			'CONF_DIR' => $main::imscpConfig{'CONF_DIR'},
			'QUOTA_ROOT_DIR' => $main::imscpConfig{'QUOTA_ROOT_DIR'},
			'TRAFF_ROOT_DIR' => $main::imscpConfig{'TRAFF_ROOT_DIR'},
			'TOOLS_ROOT_DIR' => $main::imscpConfig{'TOOLS_ROOT_DIR'},
			'BACKUP_ROOT_DIR' => $main::imscpConfig{'BACKUP_ROOT_DIR'},
			'RKHUNTER_LOG' => $main::imscpConfig{'RKHUNTER_LOG'},
			'CHKROOTKIT_LOG' => $main::imscpConfig{'CHKROOTKIT_LOG'},
			'AWSTATS_ROOT_DIR' => $main::imscpConfig{'AWSTATS_ROOT_DIR'},
			'AWSTATS_ENGINE_DIR' => $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			'AW-ENABLED' => $awstats,
			'RK-ENABLED' => ! length($rkhunter) ? '#' : '',
			'RKHUNTER' => $rkhunter,
			'CR-ENABLED' => ! length($chkrootkit) ? '#' : '',
			'CHKROOTKIT' => $chkrootkit
		},
		$cfgTpl
	);
	return 1 if ! defined $cfgTpl;

	# Store new file in working directory
	my $file = iMSCP::File->new('filename' => "$wrkDir/imscp");

	$rs = $file->set($cfgTpl);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install new file in production directory
	$rs = $file->copyFile("$prodDir/");
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCron');

	0;
}

# Setup i-MSCP init scripts
# TODO review
sub setupInitScripts
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupInitScripts');
	return $rs if $rs;

	my ($rdata, $service, $stdout, $stderr);

	for ($main::imscpConfig{'CMD_IMSCPN'}, $main::imscpConfig{'CMD_IMSCPD'}) {
		# Do not process if the service is disabled
		next if(/^no$/i);

		if(! -f $_) {
			error("File '$_' is missing");
			return 1;
		}

		my $file = iMSCP::File->new('filename' => $_);

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0755);
		return $rs if $rs;

		$service = fileparse($_);

		# Services installation / update (Debian, Ubuntu)
		$rs = execute("/usr/sbin/update-rc.d -f $service remove", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("/usr/sbin/update-rc.d $service defaults", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupInitScripts');
}

# Set Permissions
sub setupSetPermissions
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupSetPermissions');
	return $rs if $rs;


	my $backtrace = $main::imscpConfig{'BACKTRACE'} || 0;
	$main::imscpConfig{'BACKTRACE'} = (iMSCP::Getopt->backtrace) ? 1 : 0;

	my $debug = $main::imscpConfig{'DEBUG'} || 0;
	$main::imscpConfig{'DEBUG'} = (iMSCP::Getopt->debug) ? 1 : 0;

	for my $script ('set-engine-permissions.pl', 'set-gui-permissions.pl') {

		startDetail();

		my $pid = open3(gensym, \*CATCHOUT, \*CATCHERR, "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script setup");

		while(<CATCHOUT>) {
			chomp;
			step(undef, $1, $2, $3) if /^(.*)\t(.*)\t(.*)$/;
		}

		my $stderr = do { local $/; <CATCHERR> };

		waitpid($pid, 0) if $pid;

		$rs = getExitCode($?);

		endDetail();

		error($stderr) if $stderr && $rs;
		error("Error while setting permissions") if $rs && ! $stderr;
		return $rs if $rs;
	}

	$main::imscpConfig{'BACKTRACE'} = $backtrace;
    $main::imscpConfig{'DEBUG'} = $debug;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupSetPermissions');
}

# TODO should be an addon
sub setupRkhunter
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupRkhunter');
	return $rs if $rs;

	my $rdata;

	# Deleting any existent log files
	my $file = iMSCP::File->new ('filename' => $main::imscpConfig{'RKHUNTER_LOG'});

	$rs = $file->set('');
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner('root', 'adm');
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Updates the rkhunter configuration provided by Debian like distributions
	# to disable the default cron task (i-MSCP provides its own cron job for rkhunter)
	if(-f '/etc/default/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new ('filename' => '/etc/default/rkhunter');
		$rdata = $file->get();
		unless(defined $rdata) {
			error("Unable to read /etc/default/rkhunter");
			return 1;
		}

		# Disable default cron task
		$rdata =~ s/CRON_DAILY_RUN="(yes)?"/CRON_DAILY_RUN="no"/gmi;

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Updates the logrotate configuration provided by Debian like distributions to modify rights
	if(-f '/etc/logrotate.d/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new ('filename' => '/etc/logrotate.d/rkhunter');
		$rdata = $file->get();
		unless(defined $rdata) {
			error("Unable to read /etc/logrotate.d/rkhunter");
			return 1;
		}

		# Disable cron task default
		$rdata =~ s/create 640 root adm/create 644 root adm/gmi;

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Update weekly cron task provided by Debian like distributions to avoid creation of unreadable log file
	if(-f '/etc/cron.weekly/rkhunter') {
		# Get the rkhunter file content
		$file = iMSCP::File->new('filename' => '/etc/cron.weekly/rkhunter');
		$rdata = $file->get();
		unless(defined $rdata) {
			error("Unable to read /etc/cron.weekly/rkhunter");
			return 1;
		}

		# Adds `--nolog`option to avoid unreadable log file
		$rdata =~ s/(--versioncheck\s+|--update\s+)(?!--nolog)/$1--nolog /g;

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupRkhunter');
}

# Rebuild all customers's configuration files
sub setupRebuildCustomerFiles
{
	my $rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupRebuildCustomersFiles');
	return $rs if $rs;

	my $tables = {
		ssl_certs => 'status',
		admin => ['admin_status', "AND `admin_type` = 'user'"],
		domain => 'domain_status',
		domain_aliasses => 'alias_status',
		subdomain => 'subdomain_status',
		subdomain_alias => 'subdomain_alias_status',
		mail_users => 'status',
		htaccess => 'status',
		htaccess_groups => 'status',
		htaccess_users => 'status'
	};

	my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	if(! $database) {
		error("Unable to connect to SQL server: $errStr");
		return 1;
	}

	my $aditionalCondition;

	while (my ($table, $field) = each %$tables) {
		if(ref $field eq 'ARRAY') {
			$aditionalCondition = $field->[1];
			$field = $field->[0];
		} else {
			$aditionalCondition = ''
		}

		# Matching only on 'ok' status is not sufficient since if setup fail for any reason, next execution
		# will not change error status to 'change'
		$rs = $database->doQuery(
			'dummy',
			"
				UPDATE
					`$table`
				SET
					`$field` = 'change'
				WHERE
					`$field` NOT IN('toadd', 'delete', 'disabled', 'ordered')
				$aditionalCondition
			"
		);
		unless(ref $rs eq 'HASH') {
			error("Unable to execute SQL query: $rs");
			return 1;
		}
	}

	iMSCP::Boot->getInstance()->unlock();

	my $backtrace = $main::imscpConfig{'BACKTRACE'} || 0;
	$main::imscpConfig{'BACKTRACE'} = (iMSCP::Getopt->backtrace) ? 1 : 0;

	my $debug = $main::imscpConfig{'DEBUG'} || 0;
	$main::imscpConfig{'DEBUG'} = (iMSCP::Getopt->debug) ? 1 : 0;

	startDetail();

	my $pid = open3(gensym, \*CATCHOUT, \*CATCHERR, "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-rqst-mngr setup");

	while(<CATCHOUT>) {
		# "$type\t$status\t$name\t$id\t$total\t$i\n"
		chomp;
		step(undef, "Processing $1 ($2) tasks: $3 (ID $4)", $5, $6) if /^(.*)\t(.*)\t(.*)\t(.*)\t(.*)\t(.*)$/;
	}

	my $stderr = do { local $/; <CATCHERR> };

	waitpid($pid, 0) if $pid;

	$rs = getExitCode($?);

	endDetail();

	iMSCP::Boot->getInstance()->lock();

	$main::imscpConfig{'DEBUG'} = $debug;
	$main::imscpConfig{'BACKTRACE'} = $backtrace;
	error($stderr) if $stderr && $rs;
	error("Error while rebuilding customers files.") if $rs && ! $stderr;
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupRebuildCustomersFiles');
}

# Call preinstall method on all i-MSCP server packages
sub setupPreInstallServers
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupPreInstallServers');
	return $rs if $rs;

	my ($file, $class, $server, $msg);
	my @servers = iMSCP::Servers->getInstance()->get();

	my $step = 1;
	startDetail();

	for(@servers) {
		s/\.pm//;
		$file = "Servers/$_.pm";
		$class = "Servers::$_";
		require $file;
		$server	= $class->factory();

		if($server->can('preinstall')) {
			$msg = "Performing preinstall tasks for $_ server" .
				($main::imscpConfig{uc($_)."_SERVER"} ? ": " . $main::imscpConfig{uc($_) . "_SERVER"} : '');
			$rs = step(sub{ $server->preinstall() }, $msg, scalar @servers, $step);
			last if $rs;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPreInstallServers');
}

# Call preinstall method on all i-MSCP addon packages
sub setupPreInstallAddons
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupPreInstallAddons');
	return $rs if $rs;

	my ($file, $class, $addons, $msg);
	my @addons = iMSCP::Addons->getInstance()->get();

	my $step = 1;
	startDetail();

	for(@addons) {
		s/\.pm//;
		$file = "Addons/$_.pm";
		$class = "Addons::$_";
		require $file;
		$addons = $class->getInstance();

		if($addons->can('preinstall')) {
			$msg = "Performing preinstall tasks for $_ addon";
			$rs = step(sub{ $addons->preinstall() }, $msg, scalar @addons, $step);
			last if $rs;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPreInstallAddons');
}

# Call install method on all i-MSCP server packages
sub setupInstallServers
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupInstallServers');
	return $rs if $rs;

	my ($file, $class, $server, $msg);
	my @servers = iMSCP::Servers->getInstance()->get();

	my $step = 1;
	startDetail();

	for(@servers) {
		s/\.pm//;
		$file = "Servers/$_.pm";
		$class = "Servers::$_";
		require $file;
		$server = $class->factory();

		if($server->can('install')) {
			$msg = "Performing install tasks for $_ server" .
				($main::imscpConfig{uc($_) . "_SERVER"} ? ": " . $main::imscpConfig{uc($_) . "_SERVER"} : '');
			$rs = step(sub{ $server->install() }, $msg, scalar @servers, $step);
			last if $rs;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupInstallServers');
}

# Call install method on all i-MSCP addong packages
sub setupInstallAddons
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupInstallAddons');
	return $rs if $rs;

	my ($file, $class, $addons, $msg);
	my @addons = iMSCP::Addons->getInstance()->get();

	my $step = 1;
	startDetail();

	for(@addons) {
		s/\.pm//;
		$file = "Addons/$_.pm";
		$class = "Addons::$_";
		require $file;
		$addons = $class->getInstance();

		if($addons->can('install')) {
			$msg = "Performing install tasks for $_ addon";
			$rs =step(sub{ $addons->install() }, $msg, scalar @addons, $step);
			last if $rs;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupInstallAddons');
}

# Call postinstall method on all i-MSCP server packages
sub setupPostInstallServers
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupPostInstallServers');
	return $rs if $rs;

	my ($file, $class, $server, $msg);
	my @servers = iMSCP::Servers->getInstance()->get();

	my $step = 1;
	startDetail();

	for(@servers) {
		s/\.pm//;
		$file = "Servers/$_.pm";
		$class = "Servers::$_";
		require $file;
		$server = $class->factory();

		if($server->can('postinstall')) {
			$msg = "Performing postinstall tasks for $_ server" .
				($main::imscpConfig{uc($_)."_SERVER"} ? ": " . $main::imscpConfig{uc($_) . "_SERVER"} : '');
			$rs = step(sub{ $server->postinstall() }, $msg, scalar @servers, $step);
			last if $rs;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPostInstallServers');
}

# Call postinstall method on all i-MSCP addon packages
sub setupPostInstallAddons
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupPostInstallAddons');
	return $rs if $rs;

	my ($file, $class, $addons, $msg);
	my @addons = iMSCP::Addons->getInstance()->get();

	my $step = 1;
	startDetail();

	for(@addons) {
		s/\.pm//;
		$file = "Addons/$_.pm";
		$class = "Addons::$_";
		require $file;
		$addons = $class->getInstance();

		if($addons->can('postinstall')) {
			$msg = "Performing postinstall tasks for $_ addon";
			$rs = step(sub{ $addons->postinstall() }, $msg, scalar @addons, $step);
			last if $rs;
		}

		$step++;
	}

	endDetail();

	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPostInstallAddons');
}

# Restart all services needed by i-MSCP
sub setupRestartServices
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupRestartServices');
	return $rs if $rs;

	my @services = (
		#['Variable holding command', 'command to execute', 'ignore error if 0 exit on error if 1']
		['CMD_IMSCPN', 'restart', 1],
		['CMD_IMSCPD', 'restart', 1],
		['CMD_CLAMD', 'reload', 1],
		['CMD_POSTGREY', 'restart', 1],
		['CMD_POLICYD_WEIGHT', 'reload', 0],
		['CMD_AMAVIS', 'reload', 1]
	);

	my ($stdout, $stderr);
	my $totalItems = @services;
	my $counter = 1;

	startDetail();

	for (@services) {
		if(
			exists $main::imscpConfig{$_->[0]} && lc($main::imscpConfig{$_->[0]}) ne 'no' &&
			-f $main::imscpConfig{$_->[0]}
		) {
			$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupRestartService', $_->[0]);
			return $rs if $rs;

			$rs = step(
				sub { execute("$main::imscpConfig{$_->[0]} $_->[1]", \$stdout, \$stderr)},
				"Restarting $main::imscpConfig{$_->[0]} service",
				$totalItems,
				$counter
			);
			debug($stdout) if $stdout;
			error($stderr) if $rs && $_->[2];
			$rs = 0 unless $rs && $_->[2];
			return $rs if $rs;

			$rs = iMSCP::HooksManager->getInstance()->trigger('afterSetupRestartService', $_->[0]);
			return $rs if $rs;
		}

		$counter++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupRestartServices');
}

# Run all update additional task such as rkhunter configuration
sub setupAdditionalTasks
{
	my $rs = 0;

	$rs = iMSCP::HooksManager->getInstance()->trigger('beforeSetupAdditionalTasks');
	return $rs if $rs;

	startDetail();

	my @steps = (
		[\&setupRkhunter, 'Setup Rkhunter']
	);

	my $step = 1;

	for (@steps) {
		$rs = step($_->[0], $_->[1], scalar @steps, $step);
		return $rs if $rs;
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupAdditionalTasks');
}

#
## Low level subroutines
#

# Retrieve question answer by searching it in the given source or all sources
sub setupGetQuestion
{
	my $question = shift;
	my $searchIn = shift;

	if(! $searchIn) {
		return $main::questions{$question} if exists $main::questions{$question};
		return $main::preseed{$question} if exists $main::preseed{$question};
		return exists $main::imscpConfig{$question} ? $main::imscpConfig{$question} : '';
	} elsif($searchIn eq 'questions') {
		return exists $main::questions{$question} ? $main::questions{$question} : '';
	} elsif($searchIn eq 'preseed') {
		return exists $main::preseed{$question} ? $main::preseed{$question} : '';
	} elsif($searchIn eq 'config') {
		return exists $main::imscpConfig{$question} ? $main::imscpConfig{$question} : '';
	} else {
		fatal('Unknown question source stack');
	}
}

# Check SQL connection
# Return int 0 on success, 1 on failure
sub setupCheckSqlConnect
{
	my ($dbType, $dbName, $dbHost, $dbPort, $dbUser, $dbPass) = (@_);
	my $database = iMSCP::Database->new('db' => $dbType)->factory();

	$database->set('DATABASE_NAME', $dbName);
	$database->set('DATABASE_HOST', $dbHost);
	$database->set('DATABASE_PORT', $dbPort);
	$database->set('DATABASE_USER', $dbUser);
	$database->set('DATABASE_PASSWORD', $dbPass);

	$database->connect() ? 1 : 0;
}

# Return database connection
#
# Param string [OPTIONAL] Database name to use (default none)
# Return ARRAY [iMSCP::Database|0, errstr] or SCALAR iMSCP::Database|0
sub setupGetSqlConnect
{
	my $dbName = shift || '';
	my $database = iMSCP::Database->new('db' => setupGetQuestion('DATABASE_TYPE'))->factory();

	$database->set('DATABASE_NAME', $dbName);
	$database->set('DATABASE_HOST', setupGetQuestion('DATABASE_HOST') || '');
	$database->set('DATABASE_PORT', setupGetQuestion('DATABASE_PORT') || '');
	$database->set('DATABASE_USER', setupGetQuestion('DATABASE_USER') || '');
	$database->set(
		'DATABASE_PASSWORD',
		setupGetQuestion('DATABASE_PASSWORD')
			? iMSCP::Crypt->getInstance()->decrypt_db_password(setupGetQuestion('DATABASE_PASSWORD'))
			: ''
	);

	my $rs = $database->connect();
	my ($ret, $errstr) = ! $rs ? ($database, '') : (0, $rs);

	wantarray ? ($ret, $errstr) : $ret;
}

# Return int - 1 if database exists and look like an i-MSCP database, 0 othewise
sub setupIsImscpDb
{
	my $dbName = shift;
	my $rs;

	my ($database, $errstr) = setupGetSqlConnect();
	fatal("Unable to connect to the SQL Server: $errstr") if ! $database;

	$rs = $database->doQuery('1', 'SHOW DATABASES LIKE ?', $dbName);
	fatal('SQL query failed: $rs') if ref $rs ne 'HASH';

	return 0 if ! %$rs;

	($database, $errstr) = setupGetSqlConnect($dbName);
	fatal("Unable to connect to the '$dbName' SQL database: $errstr") if ! $database;

	$rs = $database->doQuery('1', 'SHOW TABLES');
	fatal("SQL query failed: $rs") if ref $rs ne 'HASH';

	for (qw/server_ips user_gui_props reseller_props/) {
		return 0 if ! exists $$rs{$_};
	}

	1;
}

# Return int - 1 if the given SQL user exists, 0 otherwise
sub setupIsSqlUser($)
{
	my $sqlUser = shift;

	my ($database, $errstr) = setupGetSqlConnect('mysql');
	fatal("Unable to connect to the SQL Server: $errstr") if ! $database;

	my $rs = $database->doQuery('1', 'SELECT EXISTS(SELECT 1 FROM `user` WHERE `user` = ?)', $sqlUser);
	fatal("SQL query failed: $rs") if ref $rs ne 'HASH';

	$$rs{1} ? 1 : 0;
}

# Delete an SQL user and all its privileges
#
# Return int 0 on success, 1 on error
sub setupDeleteSqlUser
{
	my $user = shift;
	my $host = shift || '%';

	my ($database, $errstr) = setupGetSqlConnect('mysql');
	fatal("Unable to connect to the mysql database: $errstr") if ! $database;

	# Remove any columns privileges for the given user
	$errstr = $database->doQuery('dummy', "DELETE FROM `columns_priv` WHERE `Host` = ? AND `User` = ?", $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete columns privileges for the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	# Remove any tables privileges for the given user
	$errstr = $database->doQuery('dummy', 'DELETE FROM `tables_priv` WHERE `Host` = ? AND `User` = ?', $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete tables privileges for the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	# Remove any proc privileges for the given user
	$errstr = $database->doQuery('dummy', 'DELETE FROM `procs_priv` WHERE `Host` = ? AND `User` = ?', $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete procs privileges for the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	# Remove any database privileges for the given user
	$errstr = $database->doQuery('dummy', 'DELETE FROM `db` WHERE `Host` = ? AND `User` = ?', $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete database privileges from the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	# Remove any global privileges for the given user and the user itself
	$errstr = $database->doQuery('dummy', "DELETE FROM `user` WHERE `Host` = ? AND `User` = ?", $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	$errstr = $database->doQuery('dummy','FLUSH PRIVILEGES');
	if(ref $errstr ne 'HASH') {
		error("Unable to flush SQL privileges: $errstr");
		return 1;
	}

	0;
}

1;
