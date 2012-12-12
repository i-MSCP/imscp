#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2012 by internet Multi Server Control Panel
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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
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
use Modules::SystemGroup;
use Modules::SystemUser;
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
	# We do not try to establish connection to database since needed data can be unavailable
	iMSCP::Boot->new(mode => 'setup')->init({ nodatabase => 'yes' });

	0;
}

# Load old i-MSCP configuration
#
# Old configuration is simply copy of the current configuration before any modification
#
sub setupLoadOldConfig
{
	# Load old config (only if not already loaded)
	if(! defined %main::imscpOldConfig) {
		%main::imscpOldConfig = %main::imscpConfig;
	}

	0;
}

# Allow any server/addon to register its setup hook functions on the hooks manager before any other tasks
sub setupRegisterHooks()
{
	my ($rs, $file, $class, $item);
	my $hooksManager = iMSCP::HooksManager->getInstance();

	my @servers = iMSCP::Servers::get();

	unless(scalar @servers){
		error('Cannot get servers list');
		return 1;
	}

	for(@servers) {
		s/\.pm//;
		$file = "Servers/$_.pm";
		$class = "Servers::$_";
		require $file;
		$item = $class->factory();
		$rs |= $item->registerSetupHooks($hooksManager) if $item->can('registerSetupHooks');

		last if $rs;
	}

	my @addons = iMSCP::Addons::get();
	unless(scalar @addons){
		error('Cannot get addons list');
		return 1;
	}

	for(@addons) {
		s/\.pm//;
		$file = "Addons/$_.pm";
		$class = "Addons::$_";
		require $file;
		$item = $class->new();
		$rs |= $item->registerSetupHooks($hooksManager) if $item->can('registerSetupHooks');
		last if $rs;
	}

	$rs;
}

# Trigger all dialog subroutines
#
sub setupDialog
{
	my $dialogStack = [];

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupDialog', $dialogStack);

	unshift(
		@$dialogStack,
		(
			\&setupAskServerHostname,
			\&setupAskImscpVhost,
			\&setupAskLocalDnsResolver,
			\&setupAskServerIps,
			\&setupAskSqlDsn,
			\&setupAskImscpDbName,
			\&setupAskDbPrefixSuffix,
			\&setupAskDefaultAdmin,
			\&setupAskAdminEmail,
			\&setupAskPhpMyAdmin,
			\&setupAskTimezone,
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
	my ($state, $nbDialog, $ret) = (0, scalar @$dialogStack, 0);

	while($state != $nbDialog) {
		$ret = $$dialogStack[$state]->($dialog);
		return $ret if $ret && $ret != 30;

		# User asked for step back?
		if($ret == 30) {
			$state != 0 ? $state-- : 0; # We don't allow to step back before first question
			$main::reconfigure = 2 if $main::reconfigure != 1;
		} else {
			$main::reconfigure = 0 if $main::reconfigure == 2;
			$state++;
		}
	}

	#use Data::Dumper;
	#print Dumper(\%main::questions);
	#exit;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupDialog');

	$ret;
}

# Process setup tasks
sub setupTasks
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupTasks');

	my $rs = 0;
	my @steps = (
		[\&setupWriteConfig,				'Write new i-MSCP configuration'],
		[\&setupCreateMasterGroup,			'Creating i-MSCP system master group'],
		[\&setupCreateSystemDirectories,	'Creating system directories'],
		[\&setupServerHostname,				'Setting server hostname'],
		[\&setupLocalResolver,				'Setting local resolver'],
		[\&setupCreateDatabase,				'Creating/updating i-MSCP database'],
		[\&setupSecureSqlAccounts,			'Securing SQL accounts'],
		[\&setupServerIps,					'Setting server ips'],
		[\&setupDefaultAdmin, 				'Creating default admin'],
		[\&setupPhpMyAdmin, 				'Setting PhpMyAdmin'],
		[\&setupPreInstallServers,			'Servers pre-installation'],
		[\&setupPreInstallAddons,			'Addons pre-installation'],
    	[\&setupInstallServers,				'Servers installation'],
    	[\&setupInstallAddons,				'Addons installation'],
    	[\&setupPostInstallServers,			'Servers post-installation'],
    	[\&setupPostInstallAddons,			'Addons post-installation'],
    	[\&setupInitScripts,				'Setting i-MSCP init scripts'],
    	[\&setupRebuildCustomerFiles,		'Rebuilding customers files'],
    	[\&setupBasePermissions,			'Setting base file permissions'],
    	[\&setupRestartServices,			'Restarting services'],
    	[\&setupSaveConfig,					'Saving i-MSCP configuration'],
    	[\&setupAdditionalTasks,			'Processing additional tasks']
	);

	my $step = 1;
	my $nbSteps = @steps;

	for (@steps) {
		$rs |= step($_->[0], $_->[1], $nbSteps, $step);
		last if $rs;
		$step++;
	}

	iMSCP::Dialog->factory()->endGauge() if iMSCP::Dialog->factory()->needGauge();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupTasks');

	$rs;
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
		? (domain_private_tld => qr /^(?:bogus|test)$/)
		: ();
	my ($rs, @labels) = (0, $hostname ? split(/\./, $hostname) : ());

	if($main::reconfigure || ! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($hostname))) {
		if(! $hostname) {
			my $err = undef;

			if (execute("$main::imscpConfig{'CMD_HOSTNAME'} -f", \$hostname, \$err)) {
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

		} while($rs != 30 && ! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($hostname)));

		$dialog->set('no-cancel', undef);
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

	if($main::reconfigure || ! (@labels >= 3 && Data::Validate::Domain->new(%options)->is_domain($vhost))) {

		$vhost = "admin." . setupGetQuestion('SERVER_HOSTNAME') if ! $vhost;

		my $msg = '';

		do {
			($rs, $vhost) = $dialog->inputbox(
				"\nPlease enter the domain name from which i-MSCP must be reachable: $msg",
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

# ask for local DNS resolver
sub setupAskLocalDnsResolver
{
	my $dialog = shift;
	my $localDnsResolver = setupGetQuestion('LOCAL_DNS_RESOLVER');
	$localDnsResolver = lc($localDnsResolver);
	my $rs = 0;

	if($main::reconfigure || $localDnsResolver !~ /^yes|no$/) {
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

	my @serverIpsToKeepOrAdd = setupGetQuestion('SERVER_IPS') ? @{setupGetQuestion('SERVER_IPS')} : ();;
	my %serverIpsToDelete = ();
	my %serverIpsReplMap = ();

	my $ips = iMSCP::IP->new();
	my $rs = $ips->loadIPs();
	return $rs if $rs;

	# Retrieve list of all configured server ips
	my @serverIps = $ips->getIPs();
	if(! @serverIps) {
		error('Unable to retrieve servers ips');
		return 1;
	}

	my $currentServerIps = {};
	my $database = '';

	if(setupGetQuestion('DATABASE_NAME')) {
		# We do not raise error in case we cannot get SQL connection since it's expected on first install
    	$database = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

		if($database) {
			$currentServerIps = $database->doQuery('ip_number', 'SELECT `ip_id`, `ip_number` FROM `server_ips`');

			if(ref $currentServerIps ne 'HASH') {
				error('Cannot retrieve current server ips');
				return 1
			}
		}

		@serverIpsToKeepOrAdd = (@serverIpsToKeepOrAdd, keys %$currentServerIps);
	}

	@serverIps = sort keys %{ { map { $_ => 1 } @serverIps, @serverIpsToKeepOrAdd } };

	if(
		$main::reconfigure ||
		! ($baseServerIp ~~ @serverIps && $baseServerIp ne '127.0.0.1' && $baseServerIp ne $ips->normalize('::1'))
	) {
		do {
			# Ask user for the primary external IP
			($rs, $baseServerIp) = $dialog->radiolist(
				"\nPlease, select the base server IP for i-MSCP:",
				[@serverIps, 'Add new ip'],
				$baseServerIp ? $baseServerIp :  $serverIps[0]
			);
		} while($rs != 30 && ! $baseServerIp);

		# Handle server ip addition
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

				if(@networkCardList > 1) { # Do not ask about network card if not more than one is available					do {
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

		# Handle ip deletion in case the user stepped back
		my $manualBaseServerIp = setupGetQuestion('MANUAL_BASE_SERVER_IP');

		if($manualBaseServerIp && $manualBaseServerIp ne $baseServerIp) {
			$ips->detachIpFromNetCard($manualBaseServerIp);
			$rs = $ips->reset();
			return $rs if $rs;
			@serverIps = grep $_ ne $manualBaseServerIp, @serverIps;
			delete $main::questions{'MANUAL_BASE_SERVER_IP'};
		}

		$main::questions{'MANUAL_BASE_SERVER_IP'} = $baseServerIp if $manualIp;

		# Handle additional i-MSCP addition / deletion
		if($rs != 30) {
			$dialog->set('defaultno', '');

			if(@serverIps > 1 && ! $dialog->yesno("\nDo you want add or remove IP addresses?")) {
				$dialog->set('defaultno', undef);

				@serverIps = grep $_ ne $baseServerIp, @serverIps; # Remove base server ip from the list
				my $sshConnectIp = defined ($ENV{'SSH_CONNECTION'}) ? (split ' ', $ENV{'SSH_CONNECTION'})[2] : undef;

				my $msg = '';

				do {
					($rs, $serverIps) = $dialog->checkbox(
						"\nPlease, select the IP addresses to add into the database and deselect those to delete: $msg",
						[@serverIps],
						@serverIpsToKeepOrAdd
					);

					$msg = '';

					if(defined $sshConnectIp && $sshConnectIp ~~ @serverIps && $serverIps !~ /$sshConnectIp/) {
						$msg = "\n\n\\Z1You cannot remove the IP '$sshConnectIp' to which you are currently connected (ssh).\\Zn\n\nPlease, try again:";
					}

				} while ($rs != 30 && $msg);

				if($rs != 30) {
					$serverIps =~ s/"//g;
					@serverIpsToKeepOrAdd = split ' ', $serverIps; # We retrieve list of ip to add into database
					push @serverIpsToKeepOrAdd, $baseServerIp; # Re-add base ip

					# get list of ip to delete
					my %serverIpsToDelete = ();
					for(@serverIps) {
						$serverIpsToDelete{$$currentServerIps{$_}->{'ip_id'}} = $_
							if(exists $$currentServerIps{$_} && not $_ ~~ @serverIpsToKeepOrAdd);
					}

					if($database) {
						my $resellerIps = $database->doQuery('reseller_ips', 'SELECT `reseller_ips` FROM `reseller_props`');

						if(ref $resellerIps ne 'HASH') {
							error("Cannot retrieve resellers's addresses IP: $resellerIps");
							return 1;
						}

						# Check for server ips already in use and ask user for ip replacement
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
											[@serverIpsToKeepOrAdd],
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
		$main::questions{'SERVER_IPS'} = [@serverIpsToKeepOrAdd];
		$main::questions{'SERVER_IPS_TO_REPLACE'} = {%serverIpsReplMap};
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
			? iMSCP::Crypt->new()->decrypt_db_password(setupGetQuestion('DATABASE_PASSWORD')) : '';
	}

	my $rs = 0;

	my %options = ($main::imscpConfig{'DEBUG'} || iMSCP::Getopt->debug)
		? (domain_private_tld => qr /^(?:bogus|test)$/)
		: ();

	if($main::reconfigure || ! ($dbPass ne '' && ! setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass))) {
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
				do {
					($rs, $dbUser) = $dialog->inputbox(
						"\nPlease, enter an SQL username. This user must exists and have full privileges on SQL server:",
						$dbUser
					);
				} while($rs != 30 && $dbUser eq '');
			}

			# Ask for SQL user password
			if($rs != 30) {
				do {
					($rs, $dbPass) = $dialog->inputbox("\nPlease, enter a password for the '$dbUser' SQL user:", $dbPass);
				} while($rs != 30 && $dbPass eq '');

				$msg =
"
\\Z1Connection to SQL server failed\\Zn

i-MSCP was unable to connect to SQL server with the following data:

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
		$main::questions{'DATABASE_PASSWORD'} = iMSCP::Crypt->new()->encrypt_db_password($dbPass);
	}

	$rs;
}

# Ask for i-MSCP database name
sub setupAskImscpDbName
{
	my $dialog = shift;
	my $dbName = setupGetQuestion('DATABASE_NAME') || 'imscp';
	my $rs = 0;

	if($main::reconfigure || ! setupIsImscpDb($dbName)) {
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

# Ask for database prefix
sub setupAskDbPrefixSuffix
{
	my $dialog = shift;
	my $prefix = setupGetQuestion('MYSQL_PREFIX');
	my $prefixType = setupGetQuestion('MYSQL_PREFIX_TYPE');
	my $rs = 0;

	if(
		$main::reconfigure ||
		! (($prefix eq 'no' && $prefixType eq 'none') || ($prefix eq 'yes' && $prefixType =~ /^infront|behind$/))
	) {

		($rs, $prefix) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuMySQL Database Prefix/Suffix\\Zn

Do you want use a prefix or suffix for customers's SQL databases?

\\Z4Infront:\\Zn A numeric prefix such as '1_' will be added to each customer
         database name.
 \\Z4Behind:\\Zn A numeric suffix such as '_1' will be added to each customer
         database name.
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

# Ask for default admin data
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
    	}
	}

	if($main::reconfigure || $adminLoginName eq '') {

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
				$adminLoginName !~ /^[a-z0-9](:?(?<![-_])(:?-*|[_.])?(?![-_])[a-z0-9]*)*?(?<![-_.])$/
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

# Ask for admin email
sub setupAskAdminEmail
{
	my $dialog = shift;
	my $adminEmail = setupGetQuestion('DEFAULT_ADMIN_ADDRESS');
	my $rs = 0;

	if($main::reconfigure || ! Email::Valid->address($adminEmail)) {
		my $msg = '';

		do {
			($rs, $adminEmail) = $dialog->inputbox("\nPlease, enter admin email address: $msg", $adminEmail);
			$msg = "\n\n\\Z1'$adminEmail' is not a valid email address.\\Zn\n\nPlease, try again:";
		} while( $rs != 30 && ! Email::Valid->address($adminEmail));
	}

	$main::questions{'DEFAULT_ADMIN_ADDRESS'} = $adminEmail if $rs != 30;

	$rs;
}

# Ask for timezone
sub setupAskTimezone
{
	my $dialog = shift;
	my $defaultTimezone = DateTime->new(year => 0, time_zone => 'local')->time_zone->name;
	my $timezone = setupGetQuestion('PHP_TIMEZONE');
	my $rs = 0;

	if($main::reconfigure || ! ($timezone && DateTime::TimeZone->is_valid_name($timezone))) {
		$timezone = $defaultTimezone if ! $timezone;
		my $msg = '';

		do {
			($rs, $timezone) = $dialog->inputbox("\nPlease enter Server`s timezone: $msg", $timezone);
        	$msg = "\n\n\\Z1'$timezone' is not a valid timezone.\\Zn\n\nPlease, try again:";
		} while($rs != 30 && ! DateTime::TimeZone->is_valid_name($timezone));
	}

	$main::questions{'PHP_TIMEZONE'} = $timezone if $rs != 30;

	$rs;
}

# Ask for PhpMyAdmin
# TODO delete old PMA_USER config entry no longer needed
sub setupAskPhpMyAdmin
{
	my $dialog = shift;
	my $wrkFile = "$main::imscpConfig{'CONF_DIR'}/pma/working/config.inc.php";

	my $dbType = setupGetQuestion('DATABASE_TYPE');
	my $dbHost = setupGetQuestion('DATABASE_HOST');
	my $dbPort = setupGetQuestion('DATABASE_PORT');
    my $dbUser = setupGetQuestion('PMA_USER') || 'pma';
    my $dbOldUser = '';
	my $dbPass = setupGetQuestion('PMA_PASSWORD');
	my $dbOldPass = '';
	my $rs = 0;

	if(-f $wrkFile) {
		# Gets user info from current pma configuration file
		$wrkFile = iMSCP::File->new(filename => $wrkFile);
		my $wrkFile = $wrkFile->get();
		return 1 if ! $wrkFile;

		# Retrieve needed values from the working file
		($dbUser, $dbPass) = map { $wrkFile =~ /\['$_'\]\s*=\s*'(.+)'/} qw /controluser controlpass/;
		$dbOldUser = $dbUser;
		$dbOldPass = $dbPass;
	}

	if($main::reconfigure || setupCheckSqlConnect($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)) {
		my $msg = '';

		do {
			($rs, $dbUser) = $dialog->inputbox(
				"\nPlease enter an username for the restricted PhpMyAdmin SQL user: $msg", $dbUser
			);

			if($rs != 30) {
				$msg = "\n\n\\Z1Username cannot be empty.\\Zn\n\nPlease, try again:";

				# i-MSCP SQL user cannot be reused
				if($dbUser eq $main::imscpConfig{'DATABASE_USER'}) {
					$msg = "\n\n\\Z1You cannot reuse the i-MSCP '$main::imscpConfig{'DATABASE_USER'}' SQL user.\\Zn\n\nPlease, try again:";
					$dbUser = '';
				} elsif($dbUser ne $dbOldUser && setupIsSqlUser($dbUser)) {
					$dialog->set('defaultno', '');

					$rs = $dialog->yesno(
"
\\Z1SQL user already exists\\Zn

The '$dbUser' SQL user already exists.

Are you sure you want use this user for PhpMyAdmin? If yes, the '$dbUser' SQL user and all its privileges will be reseted.
"
					);
				}
			}

			$dbUser = '' if $rs;
			$dialog->set('defaultno', undef);

		} while($rs != 30 && $dbUser eq '');

		if($rs != 30) {
			# Ask for PhpMyAdmin SQL user password
			($rs, $dbPass) = $dialog->inputbox(
				'\nPlease, enter a password for the restricted PhpMyAdmin SQL user (blank for autogenerate):', $dbPass
			);

			if($rs != 30) {
				if($dbPass eq '') {
					$dbPass = '';
					my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
					$dbPass .= $allowedChars[rand()*($#allowedChars + 1)]for (1..16);
				}

				$dbPass =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;
				$dialog->msgbox("\nPassword for the restricted PhpMyAdmin SQL user set to: $dbPass");
				$dialog->set('cancel-label');
			}
		}
	}

	if($rs != 30) {
		$main::questions{'PMA_USER'} = $dbUser;
		$main::questions{'PMA_OLD_USER'} = $dbOldUser;
		$main::questions{'PMA_PASSWORD'} = $dbPass;
		$main::questions{'PMA_OLD_PASSWORD'} = $dbOldPass;
	}

	$rs;
}

# Ask for i-MSCP ssl support
sub setupAskSsl
{
	my($dialog, $rs) = (shift, undef);
	my $sslEnabled = setupGetQuestion('SSL_ENABLED');
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my $guiCertDir = $main::imscpConfig{'GUI_CERT_DIR'};
	my $cmdOpenSsl = $main::imscpConfig{'CMD_OPENSSL'};
	my $rs = 0;

	if($main::reconfigure || $sslEnabled !~ /^yes|no$/i) {
		Modules::openssl->new()->{'openssl_path'} = $cmdOpenSsl;
		$rs = setupSslDialog($dialog);
		return $rs if $rs;
	} elsif($sslEnabled eq 'yes') {
		Modules::openssl->new()->{'openssl_path'} = $cmdOpenSsl;
		Modules::openssl->new()->{'cert_path'} = "$guiCertDir/$hostname.pem";
		Modules::openssl->new()->{'intermediate_cert_path'} = "$guiCertDir/$hostname.pem";
		Modules::openssl->new()->{'key_path'} = "$guiCertDir/$hostname.pem";

		if(Modules::openssl->new()->ssl_check_all()){
			iMSCP::Dialog->factory()->msgbox("Certificate is missing or corrupted. Starting recover");
			$rs = setupSslDialog($dialog);
			return $rs if $rs;
		}
	}

	$main::questions{'BASE_SERVER_VHOST_PREFIX'} = 'http://' if $main::imscpConfig{'SSL_ENABLED'} eq 'no';

	$rs;
}

sub setupSslDialog
{
	my ($dialog, $rs, $ret) = (shift, 0, '');
	my $sslEnabled = setupGetQuestion('SSL_ENABLED') || 'no';


	($rs, $sslEnabled) =  $dialog->radiolist(
		"\nDo you want to activate SSL for i-MSCP?", ['no', 'yes'], lc($sslEnabled) eq 'yes' ? 'yes' : 'no'
	);


	if($rs != 30) {
		$main::questions{'SSL_ENABLED'} = $sslEnabled;

		if($sslEnabled eq 'yes') {
			Modules::openssl->new()->{'new_cert_path'} = $main::imscpConfig{'GUI_CERT_DIR'};
			Modules::openssl->new()->{'new_cert_name'} = $main::imscpConfig{'SERVER_HOSTNAME'};

			# TODO how determine default value here?
			($rs, $ret) = $dialog->radiolist( "\nDo you have an SSL certificate?", ['yes', 'no']);

			if($rs != 30) {
				$ret = $ret eq 'yes' ? 1 : 0;

				Modules::openssl->new()->{'cert_selfsigned'} = $ret;
				Modules::openssl->new()->{'vhost_cert_name'} = $main::imscpConfig{'SERVER_HOSTNAME'} if ! $ret;

				if(Modules::openssl->new()->{'cert_selfsigned'}) {
					Modules::openssl->new()->{'intermediate_cert_path'} = '';
					$rs = setupAskCertificateKeyPath($dialog);
					$rs = setupAskIntermediateCertificatePath($dialog) if $rs != 30;
					$rs = setupAskCertificatePath($dialog) if $rs != 30;
				}

				if($rs != 30) {
					$rs = Modules::openssl->new()->ssl_export_all();
					return $rs if $rs;
				}
			}
		}

		if($rs != 30 && $sslEnabled eq 'yes') {
			my $httpPrefix = setupGetQuestion('BASE_SERVER_VHOST_PREFIX');

			($rs, $ret) = $dialog->radiolist(
				"\nPlease, choose the default access mode for i-MSCP",
				['https', 'http'],
				lc($httpPrefix) eq 'http://' ? 'http' : 'https'

			);

			$main::questions{'BASE_SERVER_VHOST_PREFIX'} = "$ret://" if $rs != 30;
		}
	}

	$rs;
}

sub setupAskCertificateKeyPath
{
	my ($dialog, $rs, $ret, $key) = (shift, 0, '', "/root/$main::imscpConfig{'SERVER_HOSTNAME'}.key");

	do {
		($rs, $ret) = $dialog->passwordbox("\nPlease enter password for key if needed:", $ret);

		if($rs != 30) {
			$ret =~ s/(["\$`\\])/\\$1/g;
			Modules::openssl->new()->{'key_pass'} = $ret;

			do {
				($rs, $ret) = $dialog->fselect($key);
			} while($rs != 30 && ! ($ret && -f $ret));

			if($rs != 30) {
				Modules::openssl->new()->{'key_path'} = $ret;
				$key = $ret;
			}
		}
	} while($rs != 30 && Modules::openssl->new()->ssl_check_key());

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

	Modules::openssl->new()->{'intermediate_cert_path'} = $ret if $rs != 30;

	$rs;
}

sub setupAskCertificatePath
{
	my ($dialog, $cert, $rs, $ret) = (shift, "/root/$main::imscpConfig{'SERVER_HOSTNAME'}.crt", 0, '');

	$dialog->msgbox("\nPlease select your certificate:");

	do {
		do {
			($rs, $ret) = $dialog->fselect($cert);
		} while($rs != 30 && ! ($ret && -f $ret));

		if($rs != 30) {
			Modules::openssl->new()->{'cert_path'} = $ret;
			$cert = $ret;
		}
	} while($rs != 30 && Modules::openssl->new()->ssl_check_cert());

	$rs;
}

sub setupAskImscpBackup
{
	my $dialog = shift;
	my $backupImscp = setupGetQuestion('BACKUP_IMSCP');
	$backupImscp = lc($backupImscp);
	my $rs = 0;

	if($main::reconfigure || $backupImscp !~ /^yes|no$/) {
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

sub setupAskDomainBackup
{
	my $dialog = shift;
	my $backupDomains = setupGetQuestion('BACKUP_DOMAINS');
	my $rs = 0;

	if($main::reconfigure || $backupDomains !~ /^yes|no$/) {

		($rs, $backupDomains) = $dialog->radiolist(
"
\\Z4\\Zb\\ZuDomains Backup Feature\\Zn

Do you want activate the backup feature for domains?

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

# Write question answers into imscp.conf file
sub setupWriteConfig
{
	for(keys %main::questions) {
		if(exists $main::imscpConfig{$_}) {
			$main::imscpConfig{$_} = $main::questions{$_};
		}
   	}
}

# Create system master group for imscp
sub setupCreateMasterGroup
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupCreateMasterGroup');

	my $group = Modules::SystemGroup->new();

	$group->{'system'} = 'yes';
	$group->addSystemGroup($main::imscpConfig{'MASTER_GROUP'}) and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCreateMasterGroup');

	0;
}

# Create default directories needed by i-MSCP
sub setupCreateSystemDirectories
{
	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};

	my @systemDirectories  = (
		[$main::imscpConfig{'USER_HOME_DIR'}, $rootUName, $rootGName, 0555],
		[$main::imscpConfig{'LOG_DIR'}, $rootUName,	$rootGName, 0555],
		[$main::imscpConfig{'BACKUP_FILE_DIR'}, $rootUName, $rootGName, 0750]
	);

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupCreateSystemDirectories', \@systemDirectories);

	for (@systemDirectories) {
		iMSCP::Dir->new(dirname => $_->[0])->make({ user => $_->[1], group => $_->[2], mode => $_->[3]}) and return 1;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCreateSystemDirectories');

	0;
}

# Setup server hostname
sub setupServerHostname
{
	my $hostname = setupGetQuestion('SERVER_HOSTNAME');
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my $rs = 0;

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupServerHostname', \$hostname, \$baseServerIp);

	my @labels = split /\./, $hostname;
	my $host = shift(@labels);
	my $hostnameLocal = "$hostname.local";

	my $file = iMSCP::File->new(filename => '/etc/hosts');
	$rs |= $file->copyFile('/etc/hosts.bkp') if !-f '/etc/hosts.bkp';

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

	$rs |= $file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	$file = iMSCP::File->new(filename => '/etc/hostname');
	$rs |= $file->copyFile('/etc/hostname.bkp') if ! -f '/etc/hostname.bkp';
	$content = $host;
	$rs |= $file->set($content);
	$rs |= $file->save();
	$rs |= $file->mode(0644);
	$rs |= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});

	my ($stdout, $stderr);
	$rs |= execute("$main::imscpConfig{'CMD_HOSTNAME'} $host", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	warning("$stderr") if !$rs && $stderr;
	error("$stderr") if $rs && $stderr;
	error("Unable to set server hostname") if $rs && !$stderr;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupServerHostname');

	$rs;
}

# Setup server ips
sub setupServerIps
{
	my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
	my @serverIps = setupGetQuestion('SERVER_IPS') ? @{setupGetQuestion('SERVER_IPS')} : ();
	my $serverIpsToReplace = setupGetQuestion('SERVER_IPS_TO_REPLACE') || {};
	my $serverHostname = setupGetQuestion('SERVER_HOSTNAME');
	my $oldIptoIdMap = {};

	my @serverIps = (
		$main::imscpConfig{'BASE_SERVER_IP'},
		$main::questions{'SERVER_IPS'} ? @{$main::questions{'SERVER_IPS'}} : ()
	);

	iMSCP::HooksManager->getInstance()->trigger(
		'beforeSetupServerIps', \$baseServerIp, \@serverIps, $serverIpsToReplace
	);

	my $database = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));

	if(%$serverIpsToReplace) {
		my $ipsToReplace = join q{,}, map $database->quote($_), keys %$serverIpsToReplace;
		$oldIptoIdMap = $database->doQuery(
			'ip_number', 'SELECT `ip_id`, `ip_number` FROM `server_ips` WHERE `ip_number` IN ('. $ipsToReplace .')'
		);
		if(ref $oldIptoIdMap ne 'HASH') {
			error("Cannot get IDs of server IPs to replace: $oldIptoIdMap");
			return 1;
		}
	}

	my $ips = iMSCP::IP->new();
	my $rs = $ips->loadIPs();
	return $rs if $rs;

	# Process server ips addition

    my ($defaultNetcard) = $ips->getNetCards();

    for (@serverIps) {
    	next if exists $$serverIpsToReplace{$_};
    	my $netCard = $ips->getCardByIP($_) || $defaultNetcard;

    	if($netCard) {
    		my $rs = $database->doQuery(
    			'dummy',
    			'
    				INSERT IGNORE INTO `server_ips` (
    					`ip_number`, `ip_card`, `ip_status`, `ip_id`
    				) VALUES(
    					?, ?, ?, (SELECT `ip_id` FROM `server_ips` as t1 WHERE t1.`ip_number` = ?)
    				)
    			',
    			$_, $netCard, 'toadd', $_
    		);
    		if (ref $rs ne 'HASH') {
    			error("Cannot add/update server address IP '$_': $rs");
    			return 1;
    		}
    	} else {
    		error("Cannot add the '$_' IP into database");
    		return 1;
    	}
    }

	# Setup/update domain name and alias for base server ip

	my ($alias) =  split /\./, $serverHostname;

	$rs = $database->doQuery(
		'dummy',
		'UPDATE `server_ips` SET `ip_domain` = ?, `ip_alias` = ? WHERE `ip_number` = ?',
		$serverHostname,
		$alias,
		$baseServerIp
	);
	return $rs if ref $rs ne 'HASH';

	$rs = $database->doQuery(
		'dummy',
		'UPDATE `server_ips` SET `ip_domain` = NULL, `ip_alias` = NULL WHERE `ip_number` <> ?  AND `ip_domain` = ?',
		$baseServerIp,
		$serverHostname
	);
	return $rs if ref $rs ne 'HASH';

	# Server ips replacement

	if(%$serverIpsToReplace) {
		# for each ip to replace
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
				error("Cannot get ID of the '$newIp' address IP:$newIpId");
				return 1;
			}

			$newIpId = $$newIpId{$newIp}->{'ip_id'};

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

	# Process server ips deletion
	if(@serverIps) {
		my $serverIps = join q{,}, map $database->quote($_), @serverIps;
		my $rs = $database->doQuery(
			'dummy',
			'UPDATE`server_ips` set `ip_status` = ?  WHERE `ip_number` NOT IN(' . $serverIps . ') AND `ip_number` <> ?',
			'delete',
			$baseServerIp
		);
		if (ref $rs ne 'HASH') {
			error("Cannot schedule server IPs deletion: $rs");
			return 1;
		}
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupServerIps');

	0;
}

# Setup local resolver
sub setupLocalResolver
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupLocalResolver');

	my ($err, $file, $content, $out);

	if(-f $main::imscpConfig{'RESOLVER_CONF_FILE'}) {
		$file = iMSCP::File->new(filename => $main::imscpConfig{'RESOLVER_CONF_FILE'});
		$content = $file->get();

		if (! $content){
			$err = "Can't read $main::imscpConfig{'RESOLVER_CONF_FILE'}";
			error("$err");
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
			$file->copyFile("$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp") and return 1;
		}

		# Storing the new file
		$file->set($content) and return 1;
		$file->save() and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		$file->mode(0644) and return 1;
	} else {
		warning("Unable to found the resolv.conf file on your system");
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupLocalResolver');

	0;
}

# Create iMSCP database
sub setupCreateDatabase
{
	my $dbName = setupGetQuestion('DATABASE_NAME');

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupCreateDatabase', \$dbName);

	if(! setupIsImscpDb($dbName)) {
		my ($database, $errStr) = setupGetSqlConnect();
		fatal('Unable to connect to SQL Server: $errStr') if ! $database;

		my $qdbName = $database->quoteIdentifier($dbName);
		my $rs = $database->doQuery('dummy', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
		fatal("Unable to create the '$dbName' SQL database: $rs") if ref $rs ne 'HASH';

		$database->set('DATABASE_NAME', $dbName);
		$rs = $database->connect();
		return $rs if $rs;

		$rs = setupImportSqlSchema($database, "$main::imscpConfig{'CONF_DIR'}/database/database.sql");
		return $rs if $rs;
	}

	# In any case, we ensure we have last db schema by triggering db update
	setupUpdateDatabase() and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCreateDatabase');

	0;
}

#
sub setupImportSqlSchema
{
	my $database = shift;
	my $file = shift;

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupImportSqlSchema', \$file);

	my $content = iMSCP::File->new(filename => $file)->get();
	$content =~ s/^(--[^\n]{0,})?\n//mg;
	my @queries = (split /;\n/, $content);

	my $title = "Executing " . @queries . " queries:";

	startDetail();

	my $step = 1;
	for (@queries) { # TODO Must be fixed: first query is never show here
		my $rs = $database->doQuery('dummy', $_);
		return $rs if (ref $rs ne 'HASH');

		my $msg = $queries[$step] ? "$title\n$queries[$step]" : $title;
		step('', $msg, scalar @queries, $step);
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupImportSqlSchema');

	0;
}

# Update i-MSCP database schema
sub setupUpdateDatabase
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupUpdateDatabase');

	my ($rs, $stdout, $stderr);
	my $file = iMSCP::File->new(filename => "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php");

	my $content	= $file->get();
	return 1 if(!$content);

	if($content =~ s/{GUI_ROOT_DIR}/$main::imscpConfig{'GUI_ROOT_DIR'}/) {
		$rs = $file->set($content);
		return 1 if($rs != 0);

		$rs = $file->save();
		return 1 if($rs != 0);
	}

	$rs = execute(
		"$main::imscpConfig{'CMD_PHP'} $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \$stdout, \$stderr
	);
	error("$stdout $stderr") if $rs;
	return ($stdout ? "$stdout " : '' ) . $stderr . " exitcode: $rs" if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupUpdateDatabase');

	0;
}

# Secure any SQL account by removing those without password
sub setupSecureSqlAccounts
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupSecureSqlAccounts');

	my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

	my $rdata = $database->doQuery('User', "SELECT `User`, `Host` FROM `mysql`.`user` WHERE `Password` = ''");

	if(ref $rdata ne 'HASH'){
		error("$rdata");
		return 1;
	}

	for (keys %$rdata) {
		my $rs = $database->doQuery('drop', "DROP USER ?@?", $_, $rdata->{$_}->{Host});
		error("$rs") if ref $rs ne 'HASH';
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupSecureSqlAccounts');

	0;
}

# Setup default admin
sub setupDefaultAdmin
{
	my $adminLoginName = setupGetQuestion('ADMIN_LOGIN_NAME');
	my $adminPassword= setupGetQuestion('ADMIN_PASSWORD');
	my $adminEmail= setupGetQuestion('DEFAULT_ADMIN_ADDRESS');

	iMSCP::HooksManager->getInstance()->trigger(
		'beforeSetupDefaultAdmin', \$adminLoginName, \$adminPassword, \$adminEmail
	);

	if($adminLoginName && $adminPassword) {

		$adminPassword = iMSCP::Crypt->new()->crypt_md5_data($adminPassword);

		my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
		fatal('Unable to connect to SQL Server: $errStr') if ! $database;

		my $rs = $database->doQuery(
			'dummy',
			'
				INSERT IGNORE INTO `admin` (
					`admin_name`, `admin_pass`, `admin_type`, `email`
				) VALUES (
					?, ?, ?, ?)
			',
			$adminLoginName, $adminPassword, 'admin', $adminEmail
		);
		return $rs if ref $rs ne 'HASH';

		$rs = $database->doQuery(
			'dummy',
			'
				INSERT IGNORE INTO `user_gui_props` (
					`user_id`, `lang`, `layout_color`
				) VALUES (
					LAST_INSERT_ID(), ?, ?
				)
			',
			'en_GB', 'red'
		);
		return $rs if ref $rs ne 'HASH';
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupDefaultAdmin');

	0;
}

# Create PhpMyAdmin conffile and SQL user with its privileges
sub setupPhpMyAdmin
{
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/pma";
    my $bkpDir = "$cfgDir/backup";
    my $wrkDir = "$cfgDir/working";
    my $prodDir	= "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma";
	my $dbHost = setupGetQuestion('DATABASE_HOST');
	my $dbUser = setupGetQuestion('PMA_USER');
	my $dbOldUser = setupGetQuestion('PMA_OLD_USER');
	my $dbPass = setupGetQuestion('PMA_PASSWORD');
	my $dbOldPass = setupGetQuestion('PMA_OLD_PASSWORD');

	iMSCP::HooksManager->getInstance()->trigger('beforeSetupPhpMyAdmin', \$dbUser, \$dbOldUser, \$dbPass, \$dbOldPass);

	if($dbUser ne $dbOldUser || $dbPass ne $dbOldPass) {

    	my ($rs, $file, $cfgFile) = (0, undef, undef);

		# Save current production file if it exists
		if(-f "$prodDir/config.inc.php") {
			$file = iMSCP::File->new(
				filename => "$prodDir/config.inc.php")->copyFile("$bkpDir/config.inc.php." . time
			) and return 1;
		}

		# Delete old PhpMyAdmin restricted SQL user and all it privileges (if any)
		if($dbOldUser ne $dbUser) {
			$rs = setupDeleteSqlUser($dbOldUser, $main::imscpOldConfig{'DATABASE_HOST'});
			error("Unable to remove the old PhpMyAdmin '$dbOldUser' restricted SQL user: $rs") if $rs;
			return 1 if $rs;
		}

		# Ensure new PhpMyAdmin restricted SQL user do not already exists by deleting it
		$rs = setupDeleteSqlUser($dbUser, $dbHost);
		error("Unable to delete the PhpMyAdmin '$dbUser' restricted SQL user: $rs") if $rs;
		return 1 if $rs;

		#
		## Setup new PhpMyAdmin restricted SQL user
		#

		# Get SQL connection with full privileges
        my ($database, $errStr) = setupGetSqlConnect();
        fatal('Unable to connect to SQL Server: $errStr') if ! $database;

		# Add USAGE privilege on the mysql database (also create PhpMyadmin user)
		$rs = $database->doQuery('dummy', 'GRANT USAGE ON `mysql`.* TO ?@? IDENTIFIED BY ?', $dbUser, $dbHost, $dbPass);
		if(ref $rs ne 'HASH') {
			error("Failed to add USAGE privilege on the 'mysql' database for the '$dbUser' SQL user: $rs");
			return 1;
		}

		# Add SELECT privilege on the mysql.db table
		$rs = $database->doQuery('dummy', 'GRANT SELECT ON `mysql`.`db` TO ?@?', $dbUser, $dbHost);
		if(ref $rs ne 'HASH') {
        	error("Failed to add SELECT privilege on the 'mysql.db' table for the '$dbUser' SQL user: $rs");
        	return 1;
        }

		# Add SELECT privilege on many columns of the mysql.user table
		$rs = $database->doQuery(
			'dummy',
			'
				GRANT SELECT (Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv, Create_priv, Drop_priv,
					Reload_priv, Shutdown_priv, Process_priv, File_priv, Grant_priv, References_priv, Index_priv,
					Alter_priv, Show_db_priv, Super_priv, Create_tmp_table_priv, Lock_tables_priv, Execute_priv,
					Repl_slave_priv, Repl_client_priv)
				ON `mysql`.`user`
				TO ?@?
			',
			$dbUser,
			$dbHost
		);
		if(ref $rs ne 'HASH') {
        	error("Failed to add SELECT privileges on columns of the 'mysql.user' table for the '$dbUser' SQL user: $rs");
        	return 1;
        }

		# Add SELECT privilege on the mysql.host table
		$rs = $database->doQuery('dummy', 'GRANT SELECT ON `mysql`.`host` TO ?@?', $dbUser, $dbHost);
		if(ref $rs ne 'HASH') {
        	error("Failed to add SELECT privilege on the 'mysql.host' table for the '$dbUser' SQL user: $rs");
        	return 1;
        }

		# Add SELECT privilege on many columns of the mysql.tables_priv table
		$rs = $database->doQuery(
			'dummy',
			'
				GRANT SELECT (`Host`, `Db`, `User`, `Table_name`, `Table_priv`, `Column_priv`)
				ON `mysql`.`tables_priv`
				TO?@?
			',
			$dbUser,
			$dbHost
		);
		if(ref $rs ne 'HASH') {
        	error("Failed to add SELECT privilege on columns of the 'mysql.tables_priv' table for the '$dbUser' SQL user: $rs");
        	return 1;
        }

		#
		## Build new PhpMyAdmin config.inc.php file
		#

		# Generate blowfish secret
		my $blowfishSecret	= '';
		my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');
		$blowfishSecret .= $allowedChars[rand()*($#allowedChars + 1)] for (1..31);

		# Get the template file
		$file = iMSCP::File->new(filename => "$cfgDir/config.inc.tpl");
		$cfgFile = $file->get();
		return 1 if ! $cfgFile;

		$cfgFile = process(
			{
				PMA_USER => $dbUser,
				PMA_PASS => $dbPass,
				HOSTNAME => $dbHost,
				UPLOADS_DIR	=> "$main::imscpConfig{'GUI_ROOT_DIR'}/data/uploads",
				TMP_DIR => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp",
				BLOWFISH => $blowfishSecret
			},
			$cfgFile
		);
		return 1 if ! $cfgFile;

		# Store new file in working directory
		$file = iMSCP::File->new(filename => "$wrkDir/config.inc.php");
		$file->set($cfgFile) and return 1;
		$file->save() and return 1;
		$file->mode(0640) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	}

	# Install working file in production directory
    my $file = iMSCP::File->new(filename => "$wrkDir/config.inc.php");
    $file->copyFile("$prodDir/") and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPhpMyAdmin');

	0;
}

# Setup crontab
# TODO: awstats part should be done via awstats installer
sub setupCrontab
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupCrontab');

	my ($rs, $cfgTpl, $err);

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
		iMSCP::File->new(filename => "$prodDir/imscp")->copyFile("$bkpDir/imscp." . time) and return 1;
	}

	## Building new configuration file

	# Loading the template from /etc/imscp/cron.d/imscp
	$cfgTpl = iMSCP::File->new(filename => "$cfgDir/imscp")->get();
	return 1 if (!$cfgTpl);

	# Awstats cron task preparation (On|Off) according status in imscp.conf
	if ($main::imscpConfig{'AWSTATS_ACTIVE'} !~ /^yes/i || $main::imscpConfig{'AWSTATS_MODE'} eq '1') {
		$awstats = '#';
	}

	# Search and cleaning path for rkhunter and chkrootkit programs
	# @todo review this s...
	($rkhunter = `which rkhunter`) =~ s/\s$//g;
	($chkrootkit = `which chkrootkit`) =~ s/\s$//g;

	# Building the new file
	$cfgTpl = iMSCP::Templator::process(
		{
			LOG_DIR				=> $main::imscpConfig{'LOG_DIR'},
			CONF_DIR			=> $main::imscpConfig{'CONF_DIR'},
			QUOTA_ROOT_DIR		=> $main::imscpConfig{'QUOTA_ROOT_DIR'},
			TRAFF_ROOT_DIR		=> $main::imscpConfig{'TRAFF_ROOT_DIR'},
			TOOLS_ROOT_DIR		=> $main::imscpConfig{'TOOLS_ROOT_DIR'},
			BACKUP_ROOT_DIR		=> $main::imscpConfig{'BACKUP_ROOT_DIR'},
			RKHUNTER_LOG		=> $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG		=> $main::imscpConfig{'CHKROOTKIT_LOG'},
			AWSTATS_ROOT_DIR	=> $main::imscpConfig{'AWSTATS_ROOT_DIR'},
			AWSTATS_ENGINE_DIR	=> $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			'AW-ENABLED'		=> $awstats,
			'RK-ENABLED'		=> !length($rkhunter) ? '#' : '',
			RKHUNTER			=> $rkhunter,
			'CR-ENABLED'		=> !length($chkrootkit) ? '#' : '',
			CHKROOTKIT			=> $chkrootkit
		},
		$cfgTpl
	);
	return 1 if ! $cfgTpl;

	# Store new file in working directory
	my $file = iMSCP::File->new(filename => "$wrkDir/imscp");
	$file->set($cfgTpl);
	$file->save() and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	# Install new file in production directory
	$file->copyFile("$prodDir/") and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupCrontab');

	0;
}

# Setup i-MSCP init scripts
sub setupInitScripts
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupInitScripts');

	my ($rs, $rdata, $fileName, $stdout, $stderr);

	# Odering is important here.
	# Service imscp_network has to be enabled to start service imscp_daemon. It's a
	# dependency added to be sure that if an admin adds an new IP through the GUI,
	# the traffic will always be correctly computed. When we'll switch to mutli-server,
	# the traffic logger will be review to avoid this dependency
	for ($main::imscpConfig{'CMD_IMSCPN'}, $main::imscpConfig{'CMD_IMSCPD'}) {
		# Do not process if the service is disabled
		next if(/^no$/i);

		($fileName) = /.*\/([^\/]*)$/;

		my $file = iMSCP::File->new(filename => $_);
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		$file->mode(0755) and return 1;

		# Services installation / update (Debian, Ubuntu)
		$rs = execute("/usr/sbin/update-rc.d -f $fileName remove", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $rs;

		# Fix for #119: Defect - Error when adding IP's
		# We are now using dependency based boot sequencing (insserv)
		# See http://wiki.debian.org/LSBInitScripts ; Must be read carrefully
		$rs = execute("/usr/sbin/update-rc.d $fileName defaults", \$stdout, \$stderr);
		debug("$stdout") if $stdout;
		error("$stderr") if $rs;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupInitScripts');

	0;
}

# Setup i-MSCP base permissions
sub setupBasePermissions
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupBasePermissions');

	my $rootUName = $main::imscpConfig{'ROOT_USER'};
	my $rootGName = $main::imscpConfig{'ROOT_GROUP'};
	my $masterUName = $main::imscpConfig{'MASTER_GROUP'};
	my $CONF_DIR = $main::imscpConfig{'CONF_DIR'};
	my $ROOT_DIR = $main::imscpConfig{'ROOT_DIR'};
	my $LOG_DIR = $main::imscpConfig{'LOG_DIR'};
	my $rs = 0;

	$rs |= setRights("$CONF_DIR", {user => $rootUName, group => $masterUName, mode => '0770'});
	$rs |= setRights("$CONF_DIR/imscp.conf", {user => $rootUName, group => $masterUName, mode => '0660'});
	$rs |= setRights("$CONF_DIR/imscp-db-keys", {user => $rootUName, group => $masterUName, mode => '0640'});
	$rs |= setRights("$ROOT_DIR/engine", {user => $rootUName, group => $masterUName, mode => '0755', recursive => 'yes'});
	$rs |= setRights($LOG_DIR, {user => $rootUName, group => $masterUName, mode => '0750'});

	iMSCP::HooksManager->getInstance()->trigger('afterSetupBasePermissions');

	0;
}

sub setupRkhunter
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupRkhunter');

	my ($rs, $rdata);

	# Deleting any existent log files
	my $file = iMSCP::File->new (filename => $main::imscpConfig{'RKHUNTER_LOG'});
	$file->set();
	$file->save() and return 1;
	$file->owner('root', 'adm');
	$file->mode(0644);

	# Updates the rkhunter configuration provided by Debian like distributions
	# to disable the default cron task (i-MSCP provides its own cron job for rkhunter)
	if(-e '/etc/default/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new (filename => '/etc/default/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Disable default cron task
		$rdata =~ s/CRON_DAILY_RUN="(yes)?"/CRON_DAILY_RUN="no"/gmi;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	# Updates the logrotate configuration provided by Debian like distributions
	# to modify right
	if(-e '/etc/logrotate.d/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new (filename => '/etc/logrotate.d/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Disable cron task default
		$rdata =~ s/create 640 root adm/create 644 root adm/gmi;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	# Update weekly cron task provided by Debian like distributions to avoid
	# creation of unreadable log file
	if(-e '/etc/cron.weekly/rkhunter') {
		# Get the rkhunter file content
		$file = iMSCP::File->new (filename => '/etc/cron.weekly/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Adds `--nolog`option to avoid unreadable log file
		$rdata =~ s/(--versioncheck\s+|--update\s+)(?!--nolog)/$1--nolog /g;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	iMSCP::HooksManager->getInstance()->trigger('afterSetupRkhunter');

	0;
}

# Rebuild all customers's configuration files
sub setupRebuildCustomerFiles
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupRebuildCustomersFiles');

	my $tables = {
		ssl_certs => 'status',
		domain => 'domain_status',
		domain_aliasses => 'alias_status',
		subdomain => 'subdomain_status',
		subdomain_alias => 'subdomain_alias_status',
		mail_users => 'status',
		htaccess => 'status',
		htaccess_groups => 'status',
		htaccess_users => 'status'
	};

	# Set status as 'change'

	my $rs = 0;
	my ($database, $errStr) = setupGetSqlConnect(setupGetQuestion('DATABASE_NAME'));
	fatal('Unable to connect to SQL Server: $errStr') if ! $database;

	while (my ($table, $field) = each %$tables) {
		$rs = $database->doQuery('dummy', "UPDATE `$table` SET `$field` = 'change' WHERE `$field` = 'ok'");
		return $rs if (ref $rs ne 'HASH');
	}

	iMSCP::Boot->new()->unlock();

	my ($stdout, $stderr);
	$rs = execute("perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/imscp-rqst-mngr", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error("Error while rebuilding customers files") if(!$stderr && $rs);

	iMSCP::Boot->new()->lock();
	return $rs if $rs;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupRebuildCustomersFiles');

	0;
}

# Call preinstall method on all i-MSCP server packages
sub setupPreInstallServers
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupPreInstallServers');

	my ($rs, $file, $class, $server, $msg);
	my @servers = iMSCP::Servers::get();

	unless(scalar @servers){
		error('Cannot get servers list');
		return 1;
	}

	my $step = 1;
	startDetail();

	for(@servers){
		s/\.pm//;
		$file	= "Servers/$_.pm";
		$class	= "Servers::$_";
		require $file;
		$server	= $class->factory();
		$msg = "Performing preinstall tasks for " . uc($_) . " server" .
			($main::imscpConfig{uc($_)."_SERVER"} ? ": " . $main::imscpConfig{uc($_) . "_SERVER"} : '');
		$rs |= step(sub{ $server->preinstall() }, $msg, scalar @servers, $step) if $server->can('preinstall');
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPreInstallServers');

	$rs;
}

# Call preinstall method on all i-MSCP addon packages
sub setupPreInstallAddons
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupPreInstallAddons');

	my ($rs, $file, $class, $addons, $msg);
	my @addons = iMSCP::Addons::get();

	unless(scalar @addons){
		error('Cannot get addons list');
		return 1;
	}

	my $step = 1;
	startDetail();

	for(@addons){
		s/\.pm//;
		$file	= "Addons/$_.pm";
		$class	= "Addons::$_";
		require $file;
		$addons	= $class->new();
		$msg = "Performing preinstall tasks for " . uc($_);
		$rs |= step(sub{ $addons->preinstall() }, $msg, scalar @addons, $step) if $addons->can('preinstall');
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPreInstallAddons');

	$rs;
}

# Call install method on all i-MSCP server packages
sub setupInstallServers
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupInstallServers');

	my ($rs, $file, $class, $server, $msg);
	my @servers = iMSCP::Servers::get();

	unless(scalar @servers){
		error('Cannot get servers list');
		return 1;
	}

	my $step = 1;
	startDetail();

	for(@servers){
		s/\.pm//;
		$file	= "Servers/$_.pm";
		$class	= "Servers::$_";
		require $file;
		$server	= $class->factory();
		$msg = "Performing install tasks for " . uc($_) . " server" .
			($main::imscpConfig{uc($_) . "_SERVER"} ? ": " . $main::imscpConfig{uc($_) . "_SERVER"} : '');
		$rs |= step(sub{ $server->install() }, $msg, scalar @servers, $step) if $server->can('install');
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupInstallServers');

	$rs;
}

# Call install method on all i-MSCP addong packages
sub setupInstallAddons
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupInstallAddons');

	my ($rs, $file, $class, $addons, $msg);
	my @addons = iMSCP::Addons::get();

	unless(scalar @addons){
		error('Cannot get addons list');
		return 1;
	}

	my $step = 1;
	startDetail();

	for(@addons){
		s/\.pm//;
		$file	= "Addons/$_.pm";
		$class	= "Addons::$_";
		require $file;
		$addons	= $class->new();
		$msg = "Performing install tasks for ".uc($_);
		$rs |= step(sub{ $addons->install() }, $msg, scalar @addons, $step) if $addons->can('install');
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupInstallAddons');

	$rs;
}

# Call postinstall method on all i-MSCP server packages
sub setupPostInstallServers
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupPostInstallServers');

	my ($rs, $file, $class, $server, $msg);
	my @servers = iMSCP::Servers::get();

	unless(scalar @servers){
		error('Cannot get servers list');
		return 1;
	}

	my $step = 1;
	startDetail();

	for(@servers){
		s/\.pm//;
		$file	= "Servers/$_.pm";
		$class	= "Servers::$_";
		require $file;
		$server	= $class->factory();
		$msg = "Performing postinstall tasks for " . uc($_) . " server" .
			($main::imscpConfig{uc($_)."_SERVER"} ? ": " . $main::imscpConfig{uc($_) . "_SERVER"} : '');
		$rs |= step(sub{ $server->postinstall() }, $msg, scalar @servers, $step) if $server->can('postinstall');
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPostInstallServers');

	$rs;
}

# Call postinstall method on all i-MSCP addon packages
sub setupPostInstallAddons
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupPostInstallAddons');

	my ($rs, $file, $class, $addons, $msg);
	my @addons = iMSCP::Addons::get();

	unless(scalar @addons){
		error('Cannot get addons list');
		return 1;
	}

	my $step = 1;
	startDetail();

	for(@addons){
		s/\.pm//;
		$file	= "Addons/$_.pm";
		$class	= "Addons::$_";
		require $file;
		$addons	= $class->new();
		$msg = "Performing postinstall tasks for ".uc($_);
		$rs |= step(sub{ $addons->postinstall() }, $msg, scalar @addons, $step) if $addons->can('postinstall');
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupPostInstallAddons');

	$rs;
}

# Restart all services needed by i-MSCP
sub setupRestartServices
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupRestartServices');

	startDetail();

	my @services = (
		#['Variable holding command', 'command to execute', 'ignore error if 0 exit on error if 1']
		['CMD_IMSCPN',			'restart',	1],
		['CMD_IMSCPD',			'restart',	1],
		['CMD_CLAMD',			'reload',	1],
		['CMD_POSTGREY',		'restart',	1],
		['CMD_POLICYD_WEIGHT',	'reload',	0],
		['CMD_AMAVIS',			'reload',	1]
	);

	my ($rs, $stdout, $stderr);
	my $count = 1;

	for (@services) {
		if($main::imscpConfig{$_->[0]} && ($main::imscpConfig{$_->[0]} !~ /^no$/i) && -f $main::imscpConfig{$_->[0]}) {

			iMSCP::HooksManager->getInstance()->trigger('beforeSetupRestartService', $_->[0]);

			$rs = step(
				sub { execute("$main::imscpConfig{$_->[0]} $_->[1]", \$stdout, \$stderr)},
				"Restarting $main::imscpConfig{$_->[0]}",
				scalar @services,
				$count
			);
			debug("$main::imscpConfig{$_->[0]} $stdout") if $stdout;
			error("$main::imscpConfig{$_->[0]} $stderr $rs") if ($rs && $_->[2]);
			return $rs if ($rs && $_->[2]);

			iMSCP::HooksManager->getInstance()->trigger('afterSetupRestartService', $_->[0]);
		}

		$count++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterRestartServices');

	0;
}

# Save i-MSCP main configuration file
#
# Save new configuration into the imscp.conf file
# Save old configuration (backup) as imscp.old.conf
#
sub setupSaveConfig
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupSaveConfig');

	#my $file = iMSCP::File->new(filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf");
	#my $cfg = $file->get() or return 1;

	#$file = iMSCP::File->new(filename => "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf");
	#$file->set($cfg) and return 1;
	#$file->save and return 1;
	#$file->mode(0644) and return 1;
	#$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	iMSCP::HooksManager->getInstance()->trigger('afterSetupSaveConfig');

	0;
}

# Run all update additional task such as rkhunter configuration
sub setupAdditionalTasks
{
	iMSCP::HooksManager->getInstance()->trigger('beforeSetupAdditionalTasks');

	startDetail();

	my @steps = (
		[\&setupRkhunter, 'i-MSCP Rkhunter configuration:']
	);
	my $step = 1;
	for (@steps){
		step($_->[0], $_->[1], scalar @steps, $step);
		$step++;
	}

	endDetail();

	iMSCP::HooksManager->getInstance()->trigger('afterSetupAdditionalTasks');

	0;
}

# Retrieve question answer by searching it in the given source or all source
sub setupGetQuestion
{
	my $question = shift;
	my $searchIn = shift || '';

	if(! $searchIn) {
		return $main::questions{$question} || $main::preseed{$question} || $main::imscpConfig{$question} || '';
	} elsif($searchIn = 'questions') {
		return $main::questions{$question} || '';
	} elsif($searchIn eq 'preseed') {
		return $main::preseed{$question} || '';
	} elsif($searchIn eq 'config') {
		return $main::imscpConfig{$question} || '';
	} else {
		fatal('Unknown question source stack');
	}
}

# Check SQL connection
# Return int 0 on success, 1 on failure
sub setupCheckSqlConnect
{
	my ($dbType, $dbName, $dbHost, $dbPort, $dbUser, $dbPass) = (@_);
	my $database = iMSCP::Database->new(db => $dbType)->factory();

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
	my $database = iMSCP::Database->new(db => setupGetQuestion('DATABASE_TYPE'))->factory();

	$database->set('DATABASE_NAME', $dbName);
    $database->set('DATABASE_HOST', setupGetQuestion('DATABASE_HOST') || '');
    $database->set('DATABASE_PORT', setupGetQuestion('DATABASE_PORT') || '');
    $database->set('DATABASE_USER', setupGetQuestion('DATABASE_USER') || '');
    $database->set(
    	'DATABASE_PASSWORD',
    	setupGetQuestion('DATABASE_PASSWORD')
    		? iMSCP::Crypt->new()->decrypt_db_password(setupGetQuestion('DATABASE_PASSWORD'))
    		: ''
    );

	my $rs =  $database->connect();
	my ($ret, $errstr) = ! $rs ? ($database, '') : (0, $rs);

	wantarray ? ($ret, $errstr) : $ret;
}

# Return int - 1 if database exists and look like an i-MSCP database, 0 othewise
sub setupIsImscpDb
{
	my $dbName = shift;
	my $rs = 1;

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
sub setupDeleteSqlUser($)
{
	my $user = shift;
	my $host = shift || '%';

	my ($database, $errstr) = setupGetSqlConnect('mysql');
	fatal("Unable to connect to the SQL server: $errstr") if ! $database;

	# Remove any columns privileges for the given user
	$errstr = $database->doQuery('dummy', "DELETE FROM `columns_priv` WHERE `Host` = ? AND `User` = ?", $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete columns privileges from the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	# Remove any tables privileges for the given user
	$errstr = $database->doQuery('dummy', 'DELETE FROM `tables_priv` WHERE `Host` = ? AND `User` = ?', $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete tables privileges from the '$user\@$host' SQL user: $errstr");
		return 1;
	}

	# Remove any proc privileges for the given user
	$errstr = $database->doQuery('dummy', 'DELETE FROM `procs_priv` WHERE `Host` = ? AND `User` = ?', $host, $user);
	if(ref $errstr ne 'HASH') {
		error("Unable to delete procs privileges from the '$user\@$host' SQL user: $errstr");
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
