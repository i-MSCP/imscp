# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category		i-MSCP
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

use strict;
use warnings;

################################################################################
# User dialog
#
# @return void
#
sub user_dialog {

	use iMSCP::Dialog;

	debug((caller(0))[3].': Starting...');

	iMSCP::Dialog->new()->set('yes-label','CONTINUE');
	iMSCP::Dialog->new()->set('no-label','EXIT');
	if (iMSCP::Dialog->new()->yesno(
					"\n
						Welcome to \\Z1i-MSCP version $main::imscpConfig{'Version'}\\Zn Setup Dialog.

						\\Zu\\Z4[NOTICE]\\Zn
						Make sure you have read and performed all steps from docs/distro/INSTALL document (where distro is your linux distribution).

						\\Zu\\Z4[NOTE]\\Zn
						During the migration process some or all services might require to be shut down or restarted.

						Only services that are not marked with 'NO' in your imscp.conf configuration file will be processed by this program.
						You can stop this process by pushing \\Z1EXIT\\Z0 button
						To continue select \\Z1CONTINUE\\Z0 button"

					)
	){
		iMSCP::Dialog->new()->msgbox(
					"\n
					\\Z1[NOTICE]\\Zn

					The update process was aborted by user..."
		);
		exit 0;
	}

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
#                             Hooks subroutines                                #
################################################################################

################################################################################
# Implements the hook for the maintainers pre-installation scripts
#
# Hook that can be used by distribution maintainers to perform any required
# tasks before that the actions of the main process are executed. This hook
# allow to add a specific script named `preinst` that will be run before the
# both setup and update process actions. This hook is automatically called after
# that all services are shutting down except for the update process where it is
# called after the i-MSCP configuration file processing (loading, updating...).
#
# Note:
#
#  The `preinst` script can be written in PERL, PHP or SHELL (POSIX compliant),
#  and must be copied in the engine/setup directory during the make process. A
#  shared library for the scripts that are written in SHELL is available in the
#  engine/setup directory.
#
# @param string $context Argument that is passed to the maintainer script
# @return int 0 on success, other otherwise
#
sub preinst {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Execute;

	if(-f "$main::imscpConfig{'ROOT_DIR'}/engine/setup/preinst") {

		use File::MimeInfo::Magic;

		my $mime_type = mimetype("$main::imscpConfig{'ROOT_DIR'}/engine/setup/preinst");

		if(!($mime_type =~ /(shell|perl|php)/)){
			error((caller(0))[3].': Unable to determine the mimetype of the `preinst` script!');
			return 1;
		}

		my ($stdout, $stderr, $rs);

		$rs = execute("$main::imscpConfig{'ROOT_DIR'}/engine/setup/preinst", \$stdout, \$stderr);
		debug((caller(0))[3].": Preinstall script returned: $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $rs;
		return $rs if($rs);

	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Implements the hook for the maintainers post-installation scripts
#
# Hook that can be used by distribution maintainers to perform any required
# tasks after that the actions of the main process are executed. This hook
# allow to add a specific script named `postinst` that will be run after the
# both setup and update process actions. This hook is automatically called
# before the set_permissions() subroutine call and so, before that all services
# are restarting.
#
# Note:
#
#  The `postinst` script can be written in PERL, PHP or SHELL (POSIX compliant),
#  and must be copied in the engine/setup directory during the make process. A
#  shared library for the scripts that are written in SHELL is available in the
#  engine/setup directory.
#
# @param string $context Argument that is passed to the maintainer script
# @return int 0 on success, other otherwise
#
sub postinst {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Execute;

	if(-f "$main::imscpConfig{'ROOT_DIR'}/engine/setup/postinst") {

		use File::MimeInfo::Magic;

		my $mime_type = mimetype("$main::imscpConfig{'ROOT_DIR'}/engine/setup/postinst");

		if(!($mime_type =~ /(shell|perl|php)/)){
			error((caller(0))[3].': Unable to determine the mimetype of the `postinst` script!');
			return 1;
		}

		my ($stdout, $stderr, $rs);
		$rs = execute("$main::imscpConfig{'ROOT_DIR'}/engine/setup/postinst", \$stdout, \$stderr);
		debug((caller(0))[3].": Postinstall script returned: $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $rs;
		return $rs if($rs);

	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Load old i-MSCP main configuration file
#
# @return void
#
sub load_old_imscp_cfg {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Config;

	$main::imscpConfigOld = {};

	$main::imscpConfigOld = {};
	my $oldConf = "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf";

	tie %main::imscpConfigOld, 'iMSCP::Config','fileName' => $oldConf if (-f $oldConf);

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Creating i-MSCP database
#
# @return int 0 on success, other on failure
#
sub setup_imscp_database_connection {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Crypt;
	use iMSCP::Dialog;

	my $pass = $main::imscpConfig{'DATABASE_PASSWORD'};
	my $crypt = iMSCP::Crypt->new();

	if(!check_sql_connection(
			$main::imscpConfig{'DATABASE_TYPE'},
			'',
			$main::imscpConfig{'DATABASE_HOST'} || '',
			$main::imscpConfig{'DATABASE_PORT'} || '',
			$main::imscpConfig{'DATABASE_USER'} || '',
			$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
		)
	){
	}elsif($main::imscpConfigOld{'DATABASE_TYPE'} && !check_sql_connection(
			$main::imscpConfigOld{'DATABASE_TYPE'},
			'',
			$main::imscpConfigOld{'DATABASE_HOST'} || '',
			$main::imscpConfigOld{'DATABASE_PORT'} || '',
			$main::imscpConfigOld{'DATABASE_USER'} || '',
			$main::imscpConfigOld{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfigOld{'DATABASE_PASSWORD'}) : ''
		)
	){
		$main::imscpConfig{'DATABASE_TYPE'}		= $main::imscpConfigOld{'DATABASE_TYPE'};
		$main::imscpConfig{'DATABASE_HOST'}		= $main::imscpConfigOld{'DATABASE_HOST'};
		$main::imscpConfig{'DATABASE_PORT'}		= $main::imscpConfigOld{'DATABASE_PORT'};
		$main::imscpConfig{'DATABASE_USER'}		= $main::imscpConfigOld{'DATABASE_USER'};
		$main::imscpConfig{'DATABASE_PASSWORD'}	= $main::imscpConfigOld{'DATABASE_PASSWORD'};
	} else {
		my (
			$dbType,
			$dbHost,
			$dbPort,
			$dbUser,
			$dbPass
		) = (
			'mysql',
			$main::imscpConfig{'DATABASE_HOST'},
			$main::imscpConfig{'DATABASE_PORT'},
			$main::imscpConfig{'DATABASE_USER'}
		);

		use Data::Validate::Domain qw/is_domain/;
		my %options = $main::imscpConfig{'DEBUG'} ? (domain_private_tld => qr /^(?:bogus|test)$/) : ();

		while (check_sql_connection($dbType, '', $dbHost, $dbPort, $dbUser, $dbPass)){
			my $msg = '';
			do{
				$dbHost = iMSCP::Dialog->new()->inputbox( "Please enter database host name (default localhost) $msg", $dbHost);
				$msg = "\n\n$dbHost is not a valid hostname!"
			} while (! (Data::Validate::Domain->new(%options)->is_domain($dbHost)) && $dbHost ne 'localhost');

			$msg = '';
			do{
				$dbPort = iMSCP::Dialog->new()->inputbox("Please enter database port name (default null or 3306) $msg", $dbPort);
				$dbPort =~ s/[^\d]//g;
				$msg = "\n\n$dbPort is not a valid port number!";
			} while ($dbPort && $dbPort !~ /^[\d]*$/);

			$dbUser =  iMSCP::Dialog->new()->inputbox('Please enter database user name (default root)', $dbUser);

			$dbPass =  iMSCP::Dialog->new()->inputbox('Please enter database password','');

		}

		use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;

		if ($main::imscpConfig{'DATABASE_TYPE'} ne $dbType) {$main::imscpConfig{'DATABASE_TYPE'} = $dbType};
		if ($main::imscpConfig{'DATABASE_HOST'} ne idn_to_ascii($dbHost, 'utf-8')) {$main::imscpConfig{'DATABASE_HOST'} = idn_to_ascii($dbHost, 'utf-8');}
		if ($main::imscpConfig{'DATABASE_PORT'} ne $dbPort) {$main::imscpConfig{'DATABASE_PORT'} = $dbPort;}
		if ($main::imscpConfig{'DATABASE_USER'} ne $dbUser) {$main::imscpConfig{'DATABASE_USER'} = $dbUser;}
		if ($main::imscpConfig{'DATABASE_PASSWORD'} ne $crypt->encrypt_db_password($dbPass)) {$main::imscpConfig{'DATABASE_PASSWORD'} = $crypt->encrypt_db_password($dbPass);}

	}
	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# Check Sql connection
#
# This subroutine can be used to check an MySQL server connection with different
# login credentials.
#
# @param string $dbType SQL server type
# [@param string $dbName SQL database to use]
# @param string $dbHost SQL server hostname
# @param string $dbPort SQL server port
# @param string $dbUser SQL username
# @param string $dbPass SQL user password
# @return int 0 on success, error string on failure
#
sub check_sql_connection{

	my ($dbType, $dbName, $dbHost, $dbPort, $dbUser, $dbPass) = (@_);

	debug((caller(0))[3].': Starting...');

	use iMSCP::Database;

	my $database = iMSCP::Database->new(db => $dbType)->factory();
	$database->set('DATABASE_NAME', $dbName);
	$database->set('DATABASE_HOST', $dbHost);
	$database->set('DATABASE_PORT', $dbPort);
	$database->set('DATABASE_USER', $dbUser);
	$database->set('DATABASE_PASSWORD', $dbPass);

	debug((caller(0))[3].': Ending...');
	return $database->connect();
}

################################################################################
# Creating / Update i-MSCP database
#
# @return int 0 on success, other on failure
#
sub setup_imscp_database {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Crypt;
	use iMSCP::Dialog;

	my $crypt = iMSCP::Crypt->new();

	my $dbName = $main::imscpConfig{'DATABASE_NAME'} ? $main::imscpConfig{'DATABASE_NAME'} : ($main::imscpConfigOld{'DATABASE_NAME'} ? $main::imscpConfigOld{'DATABASE_NAME'} : 'imscp');

	if(!$dbName || check_sql_connection(
			$main::imscpConfig{'DATABASE_TYPE'},
			$dbName,
			$main::imscpConfig{'DATABASE_HOST'},
			$main::imscpConfig{'DATABASE_PORT'},
			$main::imscpConfig{'DATABASE_USER'},
			$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
		)
	){

		do{
			$dbName = iMSCP::Dialog->new()->inputbox("Please enter database name (default $dbName)", $dbName);
		}while (!$dbName);

		if (my $error = createDB($dbName, $main::imscpConfig{'DATABASE_TYPE'})){
			error ((caller(0))[3].": $error");
			return 1;
		}

		if ($main::imscpConfig{'DATABASE_NAME'} ne $dbName) {$main::imscpConfig{'DATABASE_NAME'} = $dbName};

	} else {

		$main::imscpConfig{'DATABASE_NAME'} = $main::imscpConfigOld{'DATABASE_NAME'} if(! $main::imscpConfig{'DATABASE_NAME'});

		if (my $error = updateDb()){
			error ((caller(0))[3].": $error");
			return 1;
		}
	}

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# Creating i-MSCP database
#
# @return int 0 on success, other on failure
#
sub createDB{
	my $dbName = shift;
	my $dbType = shift;

	debug((caller(0))[3].': Starting...');

	use iMSCP::Database;

	my $database = iMSCP::Database->new(db => $dbType)->factory();
	$database->set('DATABASE_NAME', '');
	my $error = $database->connect();
	return $error if $error;

	$error = $database->doQuery('dummy', "CREATE DATABASE `$dbName` CHARACTER SET utf8 COLLATE utf8_unicode_ci;");
	return $error if (ref $error ne 'HASH');

	$database->set('DATABASE_NAME', $dbName);
	$error = $database->connect();
	return $error if $error;

	$error = importSQLFile($database, "$main::imscpConfig{'CONF_DIR'}/database/database.sql");
	return $error if ($error);

	debug((caller(0))[3].': Ending...');

	0;
}

sub importSQLFile{
	my $database	= shift;
	my $file		= shift;

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Dialog;
	use iMSCP::Stepper;

	my $content = iMSCP::File->new(filename => $file)->get();
	$content =~ s/^(--[^\n]{0,})?\n//mg;
	my @queries  = (split /;\n/, $content);

	my $title = "Executing ".@queries." queries:";

	startDetail();

	my $step = 1;
	for (@queries){
		my $error =  $database->doQuery('dummy', $_);
		return $error if (ref $error ne 'HASH');
		my $msg = $queries[$step] ?  "$title\n$queries[$step]" : $title;
		step('', $msg, scalar @queries, $step);
		$step++;
	}

	endDetail();

	debug((caller(0))[3].': Ending...');

	0;

}

################################################################################
# Update i-MSCP database schema
#
# @return int 1 on success, other on failure
#
sub updateDb {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	my ($rs);

	my $file	= iMSCP::File->new(filename => "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php");
	my $content	= $file->get();
	return 1 if(!$content);

	if($content =~ s/{GUI_ROOT_DIR}/$main::imscpConfig{'GUI_ROOT_DIR'}/) {
		$rs = $file->set($content);
		return 1 if($rs != 0);
		$rs = $file->save();
		return 1 if($rs != 0);
	}
	my ($stdout);
	$rs = execute("$main::imscpConfig{'CMD_PHP'} $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \$stdout);
	error((caller(0))[3].": $stdout")if($rs != 0);
	return $stdout if($rs != 0);

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Creating default language table
#
# @return int 0 on success, other on failure
# @depracted since r4792
#
#sub setup_default_language_table {
#
#	debug((caller(0))[3].': Starting...');
#
#	use iMSCP::Database;
#
#	my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();
#
#	my $error = importSQLFile($database, "$main::imscpConfig{'CONF_DIR'}/database/languages.sql");
#	return $error if ($error);
#
#	debug((caller(0))[3].': Ending...');
#	0;
#}

################################################################################
# create all directories required by i-MSCP and the managed services
#
# @return int 0 on success, other on failure
#
sub setup_system_dirs {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dir;

	for (
		[$main::imscpConfig{'APACHE_WWW_DIR'},			$main::imscpConfig{'APACHE_USER'},	$main::imscpConfig{'APACHE_GROUP'}],
		[$main::imscpConfig{'APACHE_USERS_LOG_DIR'},	$main::imscpConfig{'APACHE_USER'},	$main::imscpConfig{'APACHE_GROUP'}],
		[$main::imscpConfig{'APACHE_BACKUP_LOG_DIR'},	$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'},	$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$main::imscpConfig{'MTA_VIRTUAL_MAIL_DIR'},	$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$main::imscpConfig{'LOG_DIR'},					$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$main::imscpConfig{'BACKUP_FILE_DIR'},			$main::imscpConfig{'ROOT_USER'},	$main::imscpConfig{'ROOT_GROUP'}],
		[$main::imscpConfig{'PHP_STARTER_DIR'},
														"$main::imscpConfig{'APACHE_SUEXEC_USER_PREF'}$main::imscpConfig{'APACHE_SUEXEC_MIN_UID'}",
																							"$main::imscpConfig{'APACHE_SUEXEC_USER_PREF'}$main::imscpConfig{'APACHE_SUEXEC_MIN_GID'}"
		]
	) {
		iMSCP::Dir->new(dirname => $_->[0])->make({ user => $_->[1], group => $_->[2], mode => 0755}) and return 1;
	}

	askAwstats();

	if ($main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes') {
		iMSCP::Dir->new(dirname => $main::imscpConfig{'AWSTATS_CACHE_DIR'})->make({ user => $main::imscpConfig{'APACHE_USER'}, group => $main::imscpConfig{'APACHE_GROUP'}, mode => 0755}) and return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

sub askAwstats{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	my ($rs, $force);

	if(!$main::imscpConfig{'AWSTATS_ACTIVE'}){
		if($main::imscpConfigOld{'AWSTATS_ACTIVE'} && $main::imscpConfigOld{'AWSTATS_ACTIVE'} =~ /yes|no/){
			$main::imscpConfig{'AWSTATS_ACTIVE'}	= $main::imscpConfigOld{'AWSTATS_ACTIVE'};
		} else {
			while (! ($rs = iMSCP::Dialog->new()->radiolist("Do you want to enable Awstats?", 'yes', 'no'))){}
			if($rs ne $main::imscpConfig{'AWSTATS_ACTIVE'}){
				$main::imscpConfig{'AWSTATS_ACTIVE'} = $rs;
				$force = 'yes';
			}
		}
	}

	if($main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes'){
		unless(!$force && defined $main::imscpConfig{'AWSTATS_MODE'} && $main::imscpConfig{'AWSTATS_MODE'} =~ /0|1/){
			if(!$force && defined $main::imscpConfigOld{'AWSTATS_MODE'} && $main::imscpConfigOld{'AWSTATS_MODE'} =~ /0|1/){
				$main::imscpConfig{'AWSTATS_MODE'}	= $main::imscpConfigOld{'AWSTATS_MODE'};
			} else {
				while (! ($rs = iMSCP::Dialog->new()->radiolist("Select Awstats mode?", 'dynamic', 'static'))){}
				$rs = $rs eq 'dynamic' ? 0 : 1;
				$main::imscpConfig{'AWSTATS_MODE'} = $rs if $rs ne $main::imscpConfig{'AWSTATS_MODE'};
			}
		}
	} else {
		$main::imscpConfig{'AWSTATS_MODE'} = '' if $main::imscpConfig{'AWSTATS_MODE'} ne '';
	}
	debug((caller(0))[3].': Ending...');

	0;
}

sub setup_base_server_IP{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	if($main::imscpConfig{'BASE_SERVER_IP'} && $main::imscpConfig{'BASE_SERVER_IP'} ne '127.0.0.1'){
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	if($main::imscpConfigOld{'BASE_SERVER_IP'} && $main::imscpConfigOld{'BASE_SERVER_IP'} ne '127.0.0.1'){
		$main::imscpConfig{'BASE_SERVER_IP'} = $main::imscpConfigOld{'BASE_SERVER_IP'};
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	my ($out, $err);
	my $ips = {};

	if (execute("$main::imscpConfig{'CMD_IFCONFIG'} -a", \$out, \$err)){
		error((caller(0))[3].": $err");
		return 1 ;
	}

	while($out =~ m/(\S*)[^\n]*\n\s*inet (?:addr:)?([\d.]+).*?cast/sgi){
		if($1 ne 'lo'){
			$ips->{$1} = $2;
		}
	}

	use Data::Validate::IP qw/is_ipv4/;

	while (! ($out = iMSCP::Dialog->new()->radiolist("Please select your external ip:", values %{$ips}, 'none'))){}
	if(! (is_ipv4($out))){
		do{
			while (! ($out = iMSCP::Dialog->new()->inputbox("Please enter your ip:", (values %{$ips})[0]))){}
		}while(! (is_ipv4($out) && $out ne '127.0.0.1') );
		$ips->{'NULL'} = $out;
	}

	$main::imscpConfig{'BASE_SERVER_IP'} = $out if($main::imscpConfig{'BASE_SERVER_IP'} ne $out);

	iMSCP::Dialog->new()->set('yes-label','Yes');
	iMSCP::Dialog->new()->set('no-label','No');

	my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();

	my $other = {};
	my $all = {};
	%$all = %$other = reverse(%$ips);
	delete ($other->{$out});
	%$ips = reverse(%$other);

	my $toSave ='';
	if (scalar(values %{$ips}) > 0 ){
		my $out = iMSCP::Dialog->new()->yesno("\n\n\t\t\tInsert other ips into database?");
		$toSave = iMSCP::Dialog->new()->checkbox("Please select ip`s to be entered to database:", values %{$ips}) if !$out;
		$toSave =~ s/"//g;
	}

	for (split(/ /, $toSave), $out){
		my $error = $database->doQuery(
			'dummy',
			"INSERT IGNORE INTO `server_ips` (`ip_number`, `ip_card`, `ip_status`, `ip_id`)
			VALUES(?, ?, 'toadd', (SELECT `ip_id` FROM `server_ips` as t1 WHERE t1.`ip_number` = ?));", $_, $all->{$_}, $_
		);
		return $error if (ref $error ne 'HASH');
	}

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# Create the system 'hosts' file
#
# @return int 0 on success, other on failure
#
sub setup_hosts {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	my $err = askHostname();
	return 1 if($err);

	my @labels = split /\./, $main::imscpConfig{'SERVER_HOSTNAME'};

	use Net::LibIDN qw/idn_to_ascii/;

	my $host = idn_to_ascii(shift(@labels), 'utf-8');
	my $hostname_local = "$main::imscpConfig{'SERVER_HOSTNAME'}.local";

	my $file = iMSCP::File->new(filename => "/etc/hosts");
	if(!-f '/etc/hosts.bkp') {
		$file->copyFile("/etc/hosts.bkp") and return 1;
	}

	my $content  = "# 'hosts' file configuration.\n\n";

	$content .= "127.0.0.1\t$hostname_local\tlocalhost\n";
	$content .= "$main::imscpConfig{'BASE_SERVER_IP'}\t$main::imscpConfig{'SERVER_HOSTNAME'}\t$host\n";
	$content .= "::ffff:$main::imscpConfig{'BASE_SERVER_IP'}\t$main::imscpConfig{'SERVER_HOSTNAME'}\t$host\n";
	$content .= "::1\tip6-localhost ip6-loopback\n";
	$content .= "fe00::0\tip6-localnet\n";
	$content .= "ff00::0\tip6-mcastprefix\n";
	$content .= "ff02::1\tip6-allnodes\n";
	$content .= "ff02::2\tip6-allrouters\n";
	$content .= "ff02::3\tip6-allhosts\n";

	$file->set($content) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}


sub askHostname{

	debug((caller(0))[3].': Starting...');

	my ($out, $err, $hostname);

	use iMSCP::Dialog;
	use Socket;

	$hostname = gethostbyaddr($main::imscpConfig{'BASE_SERVER_IP'}, &AF_INET);
	if( !$hostname || $hostname !~ /^([\w][\w-]{0,253}[\w])\.([\w][\w-]{0,253}[\w])\.([a-zA-Z]{2,6})$/) {
		if (execute("$main::imscpConfig{'CMD_HOSTNAME'} -f", \$hostname, \$err)){
			error((caller(0))[3].": Can not find hostname (misconfigured?!!): $err");
			$hostname = '';
		}
	}

	chomp($hostname);

	if($hostname && $main::imscpConfig{'SERVER_HOSTNAME'} eq $hostname){
		debug((caller(0))[3].': Ending...');
		return 0;
	}
	if($hostname && $main::imscpConfigOld{'SERVER_HOSTNAME'} && $main::imscpConfigOld{'SERVER_HOSTNAME'} eq $hostname){
		$main::imscpConfig{'SERVER_HOSTNAME'} = $main::imscpConfigOld{'SERVER_HOSTNAME'};
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	use Data::Validate::Domain qw/is_domain/;

	my %options = $main::imscpConfig{'DEBUG'} ? (domain_private_tld => qr /^(?:bogus|test)$/) : ();

	my ($msg, @labels) = ('', ());
	do{
		while (! ($out = iMSCP::Dialog->new()->inputbox( "Please enter a fully qualified hostname (fqdn): $msg", $hostname))){}
		$msg = "\n\n$out is not a valid fqdn!";
		@labels = split(/\./, $out);
	} while (! (Data::Validate::Domain->new(%options)->is_domain($out) && ( @labels >= 3)));

	use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;

	$main::imscpConfig{'SERVER_HOSTNAME'} = idn_to_ascii($out, 'utf-8');

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# Set the local dns resolver
#
# @return int 0 on success, -1 on failure
#
sub setup_resolver {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Dialog;

	my ($err, $file, $content, $out);

	if(-f $main::imscpConfig{'RESOLVER_CONF_FILE'}) {
		$file = iMSCP::File->new(filename => $main::imscpConfig{'RESOLVER_CONF_FILE'});
		$content = $file->get();

		if (! $content){
			$err = "Can't read $main::imscpConfig{'RESOLVER_CONF_FILE'}";
			error((caller(0))[3].": $err");
			return 1;
		}

		if($main::imscpConfig{'LOCAL_DNS_RESOLVER'} !~ /yes|no/i) {
			if($main::imscpConfigOld{'LOCAL_DNS_RESOLVER'} && $main::imscpConfigOld{'LOCAL_DNS_RESOLVER'} =~ /yes|no/i){
				$main::imscpConfig{'LOCAL_DNS_RESOLVER'} = $main::imscpConfigOld{'LOCAL_DNS_RESOLVER'};
			} else {
				while (! ($out = iMSCP::Dialog->new()->radiolist("Do you want allow the system resolver to use the local nameserver?:", ('yes', 'no')))){}
				$main::imscpConfig{'LOCAL_DNS_RESOLVER'} = $out;
			}
		}

		if($main::imscpConfig{'LOCAL_DNS_RESOLVER'} =~ /yes/i) {
			if($content !~ /nameserver 127.0.0.1/i) {
				$content =~ s/(nameserver.*)/nameserver 127.0.0.1\n$1/i;
			}
		} else {
			$content =~ s/nameserver 127.0.0.1//i;
		}

		# Saving the old file if needed
		if(!-f "$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp") {
			$file->copyFile("$main::imscpConfig{'RESOLVER_CONF_FILE'}.bkp") and return 1;
		}

		# Storing the new file
		$file->set($content) and return 1;
		$file->save() and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		$file->mode(0644) and return 1;

	} else {
		error((caller(0))[3]."Unable to found your resolv.conf file!");
		return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP crontab file - (Setup / Update)
#
# This subroutine built, store and install the i-MSCP crontab file
#
sub setup_crontab {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Templator;

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
	if ($main::imscpConfig{'AWSTATS_ACTIVE'} ne 'yes' || $main::imscpConfig{'AWSTATS_MODE'} eq 1) {
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
			AWSTATS_ROOT_DIR	=> $main::imscpConfig{'AWSTATS_ROOT_DIR'},
			RKHUNTER_LOG		=> $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG		=> $main::imscpConfig{'CHKROOTKIT_LOG'},
			AWSTATS_ENGINE_DIR	=> $main::imscpConfig{'AWSTATS_ENGINE_DIR'},
			'AW-ENABLED'		=> $awstats,
			'RK-ENABLED'		=> !length($rkhunter) ? '#' : '',
			RKHUNTER			=> $rkhunter,
			'CR-ENABLED'		=> !length($chkrootkit) ? '#' : '',
			CHKROOTKIT			=> $chkrootkit
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	## Storage and installation of new file

	# Storing new file in the working directory
	my $file = iMSCP::File->new(filename => "$wrkDir/imscp");
	$file->set($cfgTpl);
	$file->save() and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	# Install the new file in production directory
	$file->copyFile("$prodDir/") and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP named main configuration - (Setup / Update)
#
# This subroutine built, store and install the main named configuration file
#
# @return int 0 on success, other on failure
#
sub setup_named {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	# Do not generate configuration files if the service is disabled
	return 0 if($main::imscpConfig{'CMD_NAMED'} =~ /^no$/i);

	my ($rs, $rdata, $cfgTpl, $cfg, $err);

	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/bind";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	if(-f $main::imscpConfig{'BIND_CONF_FILE'} && !-e "$bkpDir/named.conf.system") {
		iMSCP::File->new(filename => $main::imscpConfig{'BIND_CONF_FILE'})->copyFile("$bkpDir/named.conf.system") and return 1;
	}elsif(-f $main::imscpConfig{'BIND_CONF_FILE'}) {
		iMSCP::File->new(filename => $main::imscpConfig{'BIND_CONF_FILE'})->copyFile("$bkpDir/named.conf." . time) and return 1;
	}

	## Building new configuration file

	# Loading the system main configuration file from
	# /etc/imscp/bind/backup/named.conf.system if it exists
	if(-f "$bkpDir/named.conf.system") {
		$cfg = iMSCP::File->new(filename => "$bkpDir/named.conf.system")->get();
		return 1 if(!$cfg);

		# Adjusting the configuration if needed
		$cfg =~ s/listen-on ((.*) )?{ 127.0.0.1; };/listen-on $1 { any; };/;
		$cfg .= "\n";
	# eg. Centos, Fedora did not file by default
	} else {
		warning((caller(0))[3].": Can't find the parent file for named...");
		$cfg = '';
	}

	# Loading the template from /etc/imscp/bind/named.conf
	$cfgTpl = iMSCP::File->new(filename => "$cfgDir/named.conf")->get();
	return 1 if(!$cfgTpl);

	# Building new file
	$cfg .= $cfgTpl;

	## Storage and installation of new file

	# Storing new file in the working directory
	my $file = iMSCP::File->new(filename => "$wrkDir/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	$file->mode(0644) and return 1;

	# Install the new file in the production directory
	$file->copyFile($main::imscpConfig{'BIND_CONF_FILE'}) and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP Apache fastCGI modules configuration - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install all system php related configuration files
#  - Enable required modules and disable unused
#
# @return int 0 on success, other on failure
#
sub setup_fastcgi_modules {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Dialog;
	use iMSCP::Templator;

	# Do not generate configuration files if the service is disabled
	return 0 if($main::imscpConfig{'CMD_HTTPD'} =~ /^no$/i);

	my ($rs, $cfgTpl, $err);

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving the current production file if they exists
	for (qw/fastcgi_imscp.conf fastcgi_imscp.load fcgid_imscp.conf fcgid_imscp.load/) {
		if(-f "$main::imscpConfig{'APACHE_MODS_DIR'}/$_") {
			iMSCP::File->new(filename => "$main::imscpConfig{'APACHE_MODS_DIR'}/$_")->copyFile("$bkpDir/$_." . time) and return 1;
		}
	}

	## Building, storage and installation of new files

	# Tags preparation
	my %tags_hash = (
		fastcgi => {
			APACHE_SUEXEC_MIN_UID	=> $main::imscpConfig{'APACHE_SUEXEC_MIN_UID'},
			APACHE_SUEXEC_MIN_GID	=> $main::imscpConfig{'APACHE_SUEXEC_MIN_GID'},
			APACHE_SUEXEC_USER_PREF	=> $main::imscpConfig{'APACHE_SUEXEC_USER_PREF'},
			PHP_STARTER_DIR			=> $main::imscpConfig{'PHP_STARTER_DIR'},
			PHP_VERSION				=> $main::imscpConfig{'PHP_VERSION'}
		},
		fcgid => {
			PHP_VERSION				=> $main::imscpConfig{'PHP_VERSION'}
		}
	);

	# fastcgi_imscp.conf / fcgid_imscp.conf
	for (qw/fastcgi fcgid/) {
		# Loading the template from the /etc/imscp/apache directory
		my $file = iMSCP::File->new(filename => "$cfgDir/${_}_imscp.conf");
		$cfgTpl = $file->get();
		return 1 if (!$cfgTpl);

		# Building the new configuration file
		$cfgTpl = iMSCP::Templator::process($tags_hash{$_}, $cfgTpl);
		return 1 if (!$cfgTpl);

		# Storing the new file
		$file = iMSCP::File->new(filename => "$wrkDir/${_}_imscp.conf");
		$file->set($cfgTpl) and return 1;
		$file->save() and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		$file->mode(0644) and return 1;

		# Installing the new file
		$file->copyFile("$main::imscpConfig{'APACHE_MODS_DIR'}/") and return 1;
		next if(! -f "$main::imscpConfig{'APACHE_MODS_DIR'}/$_.load");

		# Loading the system configuration file
		$file = iMSCP::File->new(filename => "$main::imscpConfig{'APACHE_MODS_DIR'}/$_.load");
		$cfgTpl = $file->get();
		return 1 if (!$cfgTpl);

		# Building the new configuration file
		$file = iMSCP::File->new(filename => "$wrkDir/${_}_imscp.load");
		$cfgTpl = "<IfModule !mod_$_.c>\n" . $cfgTpl . "</IfModule>\n";
		$file->set($cfgTpl);

		# Store the new file
		$file->save() and return 1;
		$file->mode(0644) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

		# Install the new file
		$file->copyFile("$main::imscpConfig{'APACHE_MODS_DIR'}/") and return 1;
	}

	## Enable required modules and disable unused

	# Debian like distributions only:
	# Note for distributions maintainers:
	# For others distributions, you must use the a post-installation scripts
	if(! -f '/etc/SuSE-release' && -f '/usr/sbin/a2enmod') {
		my( $stdout, $stderr);
		# Disable php4/5 modules if enabled
		execute("/usr/sbin/a2dismod php4 php5", \$stdout, \$stderr);
		debug((caller(0))[3].": stdout $stdout");
		debug((caller(0))[3].": stderr $stderr");

		# Enable actions modules
		$rs = execute("/usr/sbin/a2enmod actions", \$stdout, \$stderr);
		debug((caller(0))[3].": stdout $stdout");
		error((caller(0))[3].": $stderr") if($rs);
		return $rs if($rs);

		if($main::imscpConfig{'PHP_FASTCGI'} !~ /fcgid|fastcgi/i) {
			if($main::imscpConfigOld{'PHP_FASTCGI'} && $main::imscpConfigOld{'PHP_FASTCGI'} =~ /fcgid|fastcgi/i){
				$main::imscpConfig{'PHP_FASTCGI'} = $main::imscpConfigOld{'PHP_FASTCGI'};
			} else {
				my $out;
				while (! ($out = iMSCP::Dialog->new()->radiolist("Please select a Fast CGI module: fcgid or fastcgi", 'fcgid', 'fastcgi'))){}
				$main::imscpConfig{'PHP_FASTCGI'} = $out;
			}
		}

		# Ensures that the unused i-MSCP fcgid module loader is disabled
		my $enable	= $main::imscpConfig{'PHP_FASTCGI'} eq 'fastcgi' ? 'fastcgi_imscp' : 'fcgid_imscp';
		my $disable	= $main::imscpConfig{'PHP_FASTCGI'} eq 'fastcgi' ? 'fcgid_imscp' : 'fastcgi_imscp';

		$rs = execute("/usr/sbin/a2dismod $disable", \$stdout, \$stderr);
		debug((caller(0))[3].": stdout $stdout");
		error((caller(0))[3].": $stderr") if($rs);
		#return only if module exits some morrons do not install fastcgi
		return $rs if($rs && -f "$main::imscpConfig{'APACHE_MODS_DIR'}/$disable.load");

		# Enable fastcgi module
		$rs = execute("/usr/sbin/a2enmod $enable", \$stdout, \$stderr);
		debug((caller(0))[3].": stdout $stdout");
		error((caller(0))[3].": $stderr") if($rs);
		return $rs if($rs);

		# Disable default  fastcgi/fcgid modules loaders to avoid conflicts
		# with i-MSCP loaders
		for (qw/fastcgi fcgid/) {
			$rs = execute("/usr/sbin/a2dismod $_", \$stdout, \$stderr);
			debug((caller(0))[3].": stdout $stdout");
			error((caller(0))[3].": $stderr") if($rs);
			return $rs if($rs && -f "$main::imscpConfig{'APACHE_MODS_DIR'}/$_.load");
		}
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP httpd main vhost - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install i-MSCP main vhost configuration file
#  - Enable required modules (cgid, rewrite, suexec)
#
# @return int 0 on success, other on failure
#
sub setup_httpd_main_vhost {

	debug((caller(0))[3].': Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if $main::imscpConfig{'CMD_HTTPD'} =~ /^no$/i;

	use iMSCP::File;
	use iMSCP::Templator;

	my ($rs, $cfgTpl, $err);

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving the current production file if it exists
	if(-f "$main::imscpConfig{'APACHE_SITES_DIR'}/imscp.conf") {
		iMSCP::File->new(filename => "$main::imscpConfig{'APACHE_SITES_DIR'}/imscp.conf")->copyFile("$bkpDir/imscp.conf.". time) and return 1;
	}

	## Building, storage and installation of new file

	# Using alternative syntax for piped logs scripts when possible
	# The alternative syntax does not involve the Shell (from Apache 2.2.12)
	my $pipeSyntax = '|';

	if(`$main::imscpConfig{'CMD_HTTPD_CTL'} -v` =~ m!Apache/([\d.]+)! &&
		version->new($1) >= version->new('2.2.12')) {
		$pipeSyntax .= '|';
	}

	# Loading the template from /etc/imscp/apache/
	my $file = iMSCP::File->new(filename => "$cfgDir/httpd.conf");
	$cfgTpl = $file->get() or return 1;

	# Building the new file
	$cfgTpl = process(
		{
			APACHE_WWW_DIR	=> $main::imscpConfig{'APACHE_WWW_DIR'},
			ROOT_DIR		=> $main::imscpConfig{'ROOT_DIR'},
			PIPE			=> $pipeSyntax
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Storing the new file in working directory
	$file = iMSCP::File->new(filename => "$wrkDir/imscp.conf");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in production directory
	$file->copyFile("$main::imscpConfig{'APACHE_SITES_DIR'}/") and return 1;

	## Enable required modules

	# Debian like distributions only:
	# Note for distributions maintainers:
	# For others distributions, you must use the a post-installation scripts
	if(! -f '/etc/SuSE-release' && -f '/usr/sbin/a2enmod') {
		my ($stdout, $stderr);
		$rs = execute("/usr/sbin/a2enmod cgid", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout");
		error((caller(0))[3].": $stderr")if($rs);
		return $rs if($rs);

		# Enabling mod rewrite
		$rs = execute("/usr/sbin/a2enmod rewrite", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout");
		error((caller(0))[3].": $stderr")if($rs);
		return $rs if($rs);

		# Enabling mod suexec
		$rs = execute("/usr/sbin/a2enmod suexec", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout");
		error((caller(0))[3].": $stderr")if($rs);
		return $rs if($rs);

		## Enabling main vhost configuration file
		$rs = execute("/usr/sbin/a2ensite imscp.conf", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout");
		error((caller(0))[3].": $stderr")if($rs);
		return $rs if($rs);
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP awstats vhost - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install Awstats vhost configuration file (01_awstats.conf)
#  - Update proxy module configuration file if it exits (proxy.conf)
#  - Enable proxy module
#  - Disable default awstats.conf file
#  - Remove default debian cron task for Awstats
#
# @return int 0 on success, other on failure
#
sub setup_awstats_vhost {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Templator;
	use Servers::httpd;

	# Do not generate configuration files if the service is disabled
	return 0 if($main::imscpConfig{'AWSTATS_ACTIVE'} =~ /^no$/i);

	my ($rs, $path, $file, $cfgTpl, $err);

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/apache";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving some configuration files if they exists
	for (
		map {/(.*\/)([^\/]*)$/ && $1.':'.$2}
		'/etc/logrotate.d/apache',
		'/etc/logrotate.d/apache2',
		"$main::imscpConfig{'APACHE_MODS_DIR'}/proxy.conf"
	) {
		($path, $file) = split /:/;
		next if(!-f $path.$file);

		if(!-f "$bkpDir/$file.system") {
			iMSCP::File->new(filename => "$path$file")->copyFile("$bkpDir/$file.system") and return 1;
		} else {
			iMSCP::File->new(filename => "$path$file")->copyFile("$bkpDir/$file.".time) and return 1;
		}
	}

	my $httpd = Servers::httpd->new()->factory('apache2');

	$rs = $httpd->buildConfFile('01_awstats.conf');
	return $rs if $rs;

	$rs = $httpd->enableSite('01_awstats.conf');
	return $rs if $rs;

	# If Awstats is active and then, dynamic mode is selected
	if ($main::imscpConfig{'AWSTATS_ACTIVE'} eq 'yes' && $main::imscpConfig{'AWSTATS_MODE'} eq 0) {
		## Updating the proxy module configuration file if it exists
		if(-f "$bkpDir/proxy.conf.system") {
			$file = iMSCP::File->new(filename => "$bkpDir/proxy.conf.system");
			$cfgTpl = $file->get();
			return 1 if(!$cfgTpl);

			# Replacing the allowed hosts in mod_proxy if needed
			# @todo Squeeze - All is commented / Check if it work like this
			$cfgTpl =~ s/#Allow from .example.com/Allow from 127.0.0.1/gi;

			# Storing the new file in the working directory
			$file = iMSCP::File->new(filename => "$wrkDir/proxy.conf");
			$file->set($cfgTpl) and return 1;
			$file->save() and return 1;
			$file->mode(0644) and return 1;
			$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

			# Installing the new file in the production directory
			$file->copyFile("$main::imscpConfig{'APACHE_MODS_DIR'}/") and return 1;
		}

		# Debian like distributions only:
		# Note for distributions maintainers:
		# For others distributions, you must use the a post-installation scripts
		if(! -f '/etc/SuSE-release' && -f '/usr/sbin/a2enmod') {
			# Enable required modules
			my ($stdout, $stderr);
			for (qw/proxy proxy_http/){
				$rs = execute("/usr/sbin/a2enmod $_", \$stdout, \$stderr);
				debug((caller(0))[3].": $stdout");
				error((caller(0))[3].": $stderr") if $rs;
				return $rs if $rs;
			}

			# Enable awstats vhost
			$rs = execute("/usr/sbin/a2ensite 01_awstats.conf", \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout");
			error((caller(0))[3].": $stderr") if $rs;
			return $rs if $rs;
		}

		## Update Apache logrotate file

		# If the distribution provides an apache or apache2 log rotation file,
		# update it with the Awstats information. If not, use the i-MSCP file.
		# log rotation should be never executed twice. Therefore it is sane to
		# define it two times in different scopes.
		for (qw/apache apache2/){
			next if(! -f "$bkpDir/$_.system");

			$file = iMSCP::File->new(filename => "$bkpDir/$_.system");
			$cfgTpl = $file->get();
			return 1 if (!$cfgTpl);

			# Add code if not exists
			if ($cfgTpl !~ /awstats_updateall\.pl/i) {
				# Building the new apache logrotate file
				$cfgTpl =~ s/sharedscripts/sharedscripts\n\tprerotate\n\t\t$main::imscpConfig{'AWSTATS_ROOT_DIR'}\/awstats_updateall.pl now -awstatsprog=$main::imscpConfig{'AWSTATS_ENGINE_DIR'}\/awstats.pl &> \/dev\/null\n\tendscript/gi;

				# Storing the new file in the working directory
				$file = iMSCP::File->new(filename => "$wrkDir/$_");
				$file->set($cfgTpl) and return 1;
				$file->save() and return 1;
				$file->mode(0644) and return 1;
				$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

				# Installing the new file in the production directory
				$file->copyFile($main::imscpConfig{'LOGROTATE_CONF_DIR'}) and return 1;
			}
		}
	}

	# Disabling the default awstats.conf file to avoid error such as:
	# Error: SiteDomain parameter not defined in your config/domain file
	# Setup ('/etc/awstats/awstats.conf' file, web server or permissions) may
	# be wrong...
	if(-f "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf") {
		iMSCP::File->new(filename => "$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf")->moveFile("$main::imscpConfig{'AWSTATS_CONFIG_DIR'}/awstats.conf.disabled") and return 1;
	}

	# Removing default Debian Package cron task for awstats
	if(-f "/etc/cron.d/awstats") {
		iMSCP::File->new(filename => "$main::imscpConfig{'CRON_D_DIR'}/awstats")->moveFile("$main::imscpConfig{'CONF_DIR'}/cron.d/backup/awstats.system") and return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP Postfix - (Setup / Update)
#
# This subroutine built, store and install Postfix configuration files:
# - main.cf
# - master.cf
# - aliases, domains, mailboxes, transport, sender-access lookup tables
# - ARPL messenger
#
# @return int 0 on success, other on failure
#
sub setup_mta {

	debug((caller(0))[3].': Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::imscpConfig{'CMD_MTA'} =~ /^no$/i);

	use iMSCP::File;
	use iMSCP::Templator;
	use iMSCP::Execute;

	my ($rs, $cfgTpl, $path, $file, $err);

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/postfix";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";
	my $vrlDir = "$cfgDir/imscp";

	# Saving all system configuration files if they exists
	for (
		map {/(.*\/)([^\/]*)$/ && $1.':'.$2}
		$main::imscpConfig{'POSTFIX_CONF_FILE'},
		$main::imscpConfig{'POSTFIX_MASTER_CONF_FILE'}
	) {
		($path, $file) = split /:/;

		next if (!-f $path.$file || -f "$bkpDir/$file.system");

		iMSCP::File->new(filename => "$path$file")->copyFile("$bkpDir/$file.system") and return 1;
	}

	my $timestamp = time;

	# Saving all current production files
	for (
		map {/(.*\/)([^\/]*)$/ && $1.':'.$2}
		$main::imscpConfig{'POSTFIX_CONF_FILE'},
		$main::imscpConfig{'POSTFIX_MASTER_CONF_FILE'},
		$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}.'/aliases',
		$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}.'/domains',
		$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}.'/mailboxes',
		$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}.'/transport',
		$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}.'/sender-access'
	) {
		($path, $file) = split /:/;

		next if(!-f $path.$file);

		iMSCP::File->new(filename => "$path$file")->copyFile("$bkpDir/$file.$timestamp") and return 1;
	}

	## Building, storage and installation of new file

	# main.cf

	# Loading the template from /etc/imscp/postfix/
	$file	= iMSCP::File->new(filename => "$cfgDir/main.cf");
	$cfgTpl	= $file->get();
	return 1 if (!$cfgTpl);

	# Building the file
	my $hostname = $main::imscpConfig{'SERVER_HOSTNAME'};
	my $gid	= getgrnam($main::imscpConfig{'MTA_MAILBOX_GID_NAME'});
	my $uid	= getpwnam($main::imscpConfig{'MTA_MAILBOX_UID_NAME'});

	$main::imscpConfig{'MTA_MAILBOX_MIN_UID'} = $uid if $main::imscpConfig{'MTA_MAILBOX_MIN_UID'} != $uid;
	$main::imscpConfig{'MTA_MAILBOX_UID'} = $uid if $main::imscpConfig{'MTA_MAILBOX_UID'} != $uid;
	$main::imscpConfig{'MTA_MAILBOX_GID'} = $gid if $main::imscpConfig{'MTA_MAILBOX_GID'} != $gid;

	$cfgTpl = iMSCP::Templator::process(
		{
			MTA_HOSTNAME				=> $hostname,
			MTA_LOCAL_DOMAIN			=> "$hostname.local",
			MTA_VERSION					=> $main::imscpConfig{'Version'},
			MTA_TRANSPORT_HASH			=> $main::imscpConfig{'MTA_TRANSPORT_HASH'},
			MTA_LOCAL_MAIL_DIR			=> $main::imscpConfig{'MTA_LOCAL_MAIL_DIR'},
			MTA_LOCAL_ALIAS_HASH		=> $main::imscpConfig{'MTA_LOCAL_ALIAS_HASH'},
			MTA_VIRTUAL_MAIL_DIR		=> $main::imscpConfig{'MTA_VIRTUAL_MAIL_DIR'},
			MTA_VIRTUAL_DMN_HASH		=> $main::imscpConfig{'MTA_VIRTUAL_DMN_HASH'},
			MTA_VIRTUAL_MAILBOX_HASH	=> $main::imscpConfig{'MTA_VIRTUAL_MAILBOX_HASH'},
			MTA_VIRTUAL_ALIAS_HASH		=> $main::imscpConfig{'MTA_VIRTUAL_ALIAS_HASH'},
			MTA_MAILBOX_MIN_UID			=> $uid,
			MTA_MAILBOX_UID				=> $uid,
			MTA_MAILBOX_GID				=> $gid,
			PORT_POSTGREY				=> $main::imscpConfig{'PORT_POSTGREY'},
			GUI_CERT_DIR				=> $main::imscpConfig{'GUI_CERT_DIR'},
			SSL							=> ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#')
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Storing the new file in working directory
	$file = iMSCP::File->new(filename => "$wrkDir/main.cf");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in production directory
	$file->copyFile($main::imscpConfig{'POSTFIX_CONF_FILE'}) and return 1;

	# master.cf

	# Storing the new file in the working directory
	$file = iMSCP::File->new(filename => "$cfgDir/master.cf");
	$cfgTpl	= $file->get();
	return 1 if (!$cfgTpl);
	$cfgTpl = iMSCP::Templator::process(
		{
			ARPL_USER					=> $main::imscpConfig{'MTA_MAILBOX_UID_NAME'},
			ARPL_GROUP					=> $main::imscpConfig{'MASTER_GROUP'},
			ARPL_PATH					=> $main::imscpConfig{'ROOT_DIR'}."/engine/messenger/imscp-arpl-msgr"
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	$file = iMSCP::File->new(filename => "$wrkDir/master.cf");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in the production dir
	$file->copyFile($main::imscpConfig{'POSTFIX_MASTER_CONF_FILE'}) and return 1;

	## Lookup tables files
	my ($stdout, $stderr);

	for (qw/aliases domains mailboxes transport sender-access/) {
		# Storing the new files in the working directory
		$file = iMSCP::File->new(filename => "$vrlDir/$_");
		$file->copyFile("$wrkDir/") and return 1;

		# Install the files in the production directory
		$file->copyFile("$main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}/") and return 1;

		# Creating/updating Btree databases for all lookup tables
		$rs = execute("$main::imscpConfig{'CMD_POSTMAP'} $main::imscpConfig{'MTA_VIRTUAL_CONF_DIR'}/$_", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout");
		error((caller(0))[3].": $stderr") if($rs);
		return $rs if ($rs);
	}

	# Rebuilding the database for the mail aliases file - Begin
	$rs = execute("$main::imscpConfig{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout");
	error((caller(0))[3].": $stderr") if($rs);
	return $rs if ($rs);

	## Setting ARPL messenger owner, group and permissions
	$file = iMSCP::File->new(filename => "$main::imscpConfig{'ROOT_DIR'}/engine/messenger/imscp-arpl-msgr");
	$file->mode(0755) and return 1;
	$file->owner($main::imscpConfig{'MTA_MAILBOX_UID_NAME'}, $main::imscpConfig{'MTA_MAILBOX_GID_NAME'}) and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP Courier - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install Courier, related configuration files
#  - Creates userdb.dat file from the contents of the userdb file
#
# @return int 0 on success, other on failure
#
sub setup_po {

	debug((caller(0))[3].': Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::imscpConfig{'CMD_AUTHD'} =~ /^no$/i);

	use iMSCP::File;
	use iMSCP::Execute;

	my ($rs, $rdata, $err, $file);

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/courier";
	my $bkpDir ="$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	# Saving all system configuration files if they exists
	for (qw/authdaemonrc userdb/) {
		if(-f "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_" && !-f "$bkpDir/$_.system") {
			iMSCP::File->new(filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_")->copyFile("$bkpDir/$_.system") and return 1;
		}
	}

	my $timestamp = time;

	# Saving all current production files if they exist
	for (qw/authdaemonrc userdb/) {
		next if(!-f "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_");
		iMSCP::File->new(filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_")->copyFile("$bkpDir/$_.$timestamp") and return 1;
	}

	## Building, storage and installation of new file

	# Saving all current production files if they exist
	for (($main::imscpConfig{'COURIER_IMAP_SSL'}, $main::imscpConfig{'COURIER_POP_SSL'})) {
		$file = iMSCP::File->new(filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_");
		next if(!-f "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/$_");
		if(!-f "$bkpDir/$_.system"){
			$file->copyFile("$bkpDir/$_.system") and return 1;
		} else {
			$file->copyFile("$bkpDir/$_.$timestamp") and return 1;
		}
		$rdata = $file->get();
		return 1 if (!$rdata);
		if($rdata =~ m/^TLS_CERTFILE=/msg){
			$rdata =~ s!^TLS_CERTFILE=.*$!TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem!mg;
		} else {
			$rdata .= "TLS_CERTFILE=$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		}
		$file = iMSCP::File->new(filename => "$wrkDir/$_");
		$file->set($rdata) and return 1;
		$file->save() and return 1;
		$file->mode(0644) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
		# Installing the new file in the production directory
		$file->copyFile("$main::imscpConfig{'AUTHLIB_CONF_DIR'}/") and return 1;
	}

	# authdaemonrc file

	# Loading the system file from /etc/imscp/backup
	$file = iMSCP::File->new(filename => "$bkpDir/authdaemonrc.system");
	$rdata = $file->get();
	return 1 if (!$rdata);

	# Building the new file (Adding the authuserdb module if needed)
	if($rdata !~ /^\s*authmodulelist="(?:.*)?authuserdb.*"$/gm) {
		$rdata =~ s/(authmodulelist=")/$1authuserdb /gm;
	}

	# Storing the new file in the working directory
	$file = iMSCP::File->new(filename => "$wrkDir/authdaemonrc");
	$file->set($rdata) and return 1;
	$file->save() and return 1;
	$file->mode(0660) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in the production directory
	$file->copyFile("$main::imscpConfig{'AUTHLIB_CONF_DIR'}/") and return 1;

	# userdb file

	# Storing the new file in the working directory
	iMSCP::File->new(filename => "$cfgDir/userdb")->copyFile("$wrkDir/") and return 1;

	# After build this file is world readable which is is bad
	# Permissions are inherited by production file
	$file = iMSCP::File->new(filename => "$wrkDir/userdb");
	$file->mode(0600) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the new file in the production directory
	$file->copyFile("$main::imscpConfig{'AUTHLIB_CONF_DIR'}/") and return 1;

	$file = iMSCP::File->new(filename => "$main::imscpConfig{'AUTHLIB_CONF_DIR'}/userdb");
	$file->mode(0600) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Creating/Updating userdb.dat file from the contents of the userdb file
	my ($stdout, $stderr);
	$rs = execute($main::imscpConfig{'CMD_MAKEUSERDB'}, \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if ($stdout);
	error((caller(0))[3].": $stderr") if ($stderr && $rs);
	return $rs if $rs;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP Proftpd - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Built, store and install Proftpd main configuration files
#  - Create Ftpd SQL account if needed
#
# @return int 0 on success, other on failure
#
sub setup_ftpd {

	debug((caller(0))[3].': Starting...');

	# Do not generate configuration files if the service is disabled
	return 0 if($main::imscpConfig{'CMD_FTPD'} =~ /^no$/i);

	use iMSCP::File;
	use iMSCP::Execute;
	use iMSCP::Templator;
	use iMSCP::Dir;
	use iMSCP::Dialog;
	use iMSCP::Crypt;

	my ($rs, $rdata, $cfgTpl, $file, $err);

	my $wrkFile;
	my ($dbType, $dbName, $dbHost, $dbPort, $dbUser, $dbPass);
	my $connData;

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/proftpd";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	## Sets the path to the configuration file

	$main::imscpConfig{'FTPD_CONF_FILE'} = '/etc/proftpd/proftpd.conf' if (!-f $main::imscpConfig{'FTPD_CONF_FILE'});

	my $timestamp = time;

	# Saving the system configuration file if it exist
	if(-f $main::imscpConfig{'FTPD_CONF_FILE'} && !-f "$bkpDir/proftpd.conf.system") {
		iMSCP::File->new(filename => $main::imscpConfig{'FTPD_CONF_FILE'})->copyFile("$bkpDir/proftpd.conf.system") and return 1;
	}elsif(-f $main::imscpConfig{'FTPD_CONF_FILE'}) {
		iMSCP::File->new(filename => $main::imscpConfig{'FTPD_CONF_FILE'})->copyFile("$bkpDir/proftpd.conf.$timestamp") and return 1;
	}

	## Get the current user and password for SQL connection and check it

	if(-f "$wrkDir/proftpd.conf" ) {
		$wrkFile = "$wrkDir/proftpd.conf";
	} elsif(-f "$main::imscpConfig{'CONF_DIR'}/proftpd/backup/proftpd.conf.imscp") {
		$wrkFile = "$main::imscpConfig{'CONF_DIR'}/proftpd/backup/proftpd.conf.imscp";
	} elsif(-f '/etc/proftpd.conf.bak') {
		$wrkFile = '/etc/proftpd.conf.bak';
	}

	if($wrkFile && -f $wrkFile) {

		# Loading working configuration file from /etc/imscp/working/
		$file = iMSCP::File->new(filename => $wrkFile);
		$rdata = $file->get();
		return 1 if(!$rdata);

		if($rdata =~ /SQLBackend(?:[ \t])+?([^ \t\n]+)(?:.{0,})SQLConnectInfo(?: |\t)+(.*?)(?:@([^:]+?))?(?:\:([\d]+?))?(?: |\t)+(.*?)(?:(?: |\t)+(.*?))?\n/ims) {
			# Check the database connection with current ids
			$err = check_sql_connection($1 || '', $2 || '', $3 || '', $4 || '', $5 || '', $6 || '');

			# If the connection is successful, we can use these identifiers
			if(!$err) {
				$connData = 'yes';
				$dbUser = $5;
				$dbPass = $6
			} else {
				iMSCP::Dialog->new()->msgbox(
					"\n
					\\Z1[WARNING]\\Zn

					Unable to connect to the database with authentication information found in your proftpd.conf file!

					We will create a new Ftpd Sql account.
					");
			}
			#restore defaul connection
			my $crypt = iMSCP::Crypt->new();

			$err = check_sql_connection(
				$main::imscpConfig{'DATABASE_TYPE'},
				$main::imscpConfig{'DATABASE_NAME'},
				$main::imscpConfig{'DATABASE_HOST'},
				$main::imscpConfig{'DATABASE_PORT'},
				$main::imscpConfig{'DATABASE_USER'},
				$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
			);
			if ($err){
				error((caller(0))[3].": $err");
				return 1;
			}

		}
	} else {
		iMSCP::Dialog->new()->msgbox("
					\n
					\\Z1[WARNING]\\Zn

					Unable to find the Proftpd configuration file!

					The program will create a new."
		);
	}


	# We ask the database ftp user and password, and we create new SQL ftp
	# user account if needed
	if(!$connData) {

		# Ask for proftpd SQL username
		$dbUser = 'vftp';
		do{
			$dbUser = iMSCP::Dialog->new()->inputbox("Please enter database user name (default vftp)", $dbUser);
		} while (!$dbUser || $main::imscpConfig{'DATABASE_USER'} eq $dbUser);
		iMSCP::Dialog->new()->set('cancel-label','Autogenerate');

		# Ask for proftpd SQL user password
		$dbPass = iMSCP::Dialog->new()->inputbox("Please enter database password (leave blank for autogenerate)", $dbPass);
		if(!$dbPass){
			$dbPass = iMSCP::Crypt::randomString(8);
		}
		$dbPass =~ s/('|"|`|#|;|\s)/_/g;
		iMSCP::Dialog->new()->msgbox("Your password is '".$dbPass."' (we have stripped not allowed chars)");
		iMSCP::Dialog->new()->set('cancel-label');

		## Setup of new SQL ftp user
		my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();

		## We ensure that new data doesn't exist in database
		$err = $database->doQuery(
			'dummy',
			"
				DELETE FROM
					`mysql`.`tables_priv`
				WHERE
					`Host` = ?
				AND
					`Db` = ?
				AND
					`User` = ?;
			", $main::imscpConfig{'SERVER_HOSTNAME'}, $main::imscpConfig{'DATABASE_NAME'}, $dbUser
		);
		return $err if (ref $err ne 'HASH');

		$err = $database->doQuery(
			'dummy',
			"
				DELETE FROM
					`mysql`.`user`
				WHERE
					`Host` = ?
				AND
					`User` = ?;
			", $main::imscpConfig{'SERVER_HOSTNAME'}, $dbUser
		);
		return $err if (ref $err ne 'HASH');


		$err = $database->doQuery('dummy', 'FLUSH PRIVILEGES');
		return $err if (ref $err ne 'HASH');

		## Inserting new data into the database

		for (qw/ftp_group ftp_users quotalimits quotatallies/) {
			$err = $database->doQuery(
				'dummy',
				"
					GRANT SELECT,INSERT,UPDATE,DELETE ON `$main::imscpConfig{'DATABASE_NAME'}`.`$_`
					TO ?@?
					IDENTIFIED BY ?;
				", $dbUser, $main::imscpConfig{'DATABASE_HOST'}, $dbPass
			);
			return $err if (ref $err ne 'HASH');
		}
	}

	## Building, storage and installation of new file

	# Loading the template from /etc/imscp/proftpd/
	$file = iMSCP::File->new(filename => "$cfgDir/proftpd.conf");
	$cfgTpl = $file->get();
	return 1 if (!$cfgTpl);

	# Building the new file
	$cfgTpl = iMSCP::Templator::process(
		{
		HOST_NAME		=> $main::imscpConfig{'SERVER_HOSTNAME'},
		DATABASE_NAME	=> $main::imscpConfig{'DATABASE_NAME'},
		DATABASE_HOST	=> $main::imscpConfig{'DATABASE_HOST'},
		DATABASE_PORT	=> $main::imscpConfig{'DATABASE_PORT'},
		DATABASE_USER	=> $dbUser,
		DATABASE_PASS	=> $dbPass,
		FTPD_MIN_UID	=> $main::imscpConfig{'APACHE_SUEXEC_MIN_UID'},
		FTPD_MIN_GID	=> $main::imscpConfig{'APACHE_SUEXEC_MIN_GID'},
		GUI_CERT_DIR	=> $main::imscpConfig{'GUI_CERT_DIR'},
		SSL				=> ($main::imscpConfig{'SSL_ENABLED'} eq 'yes' ? '' : '#')
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Store the new file in working directory
	$file = iMSCP::File->new(filename => "$wrkDir/proftpd.conf");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0600) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the new file in production directory
	$file->copyFile($main::imscpConfig{'FTPD_CONF_FILE'}) and return 1;

	## To fill ftp_traff.log file with something
	if (! -f "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd") {
		debug((caller(0))[3].": Create dir $main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd");
		my $dir = iMSCP::Dir->new(dirname => "$main::imscpConfig{'TRAFF_LOG_DIR'}/proftpd");
		$dir->make({ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0755}) and return 1;
	}

	if(! -f "$main::imscpConfig{'TRAFF_LOG_DIR'}$main::imscpConfig{'FTP_TRAFF_LOG'}") {
		$file = iMSCP::File->new(filename => "$main::imscpConfig{'TRAFF_LOG_DIR'}$main::imscpConfig{'FTP_TRAFF_LOG'}");
		$file->set("\n") and return 1;
		$file->save() and return 1;
		$file->mode(0644) and return 1;
		$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP Daemon, network - (Setup / Update)
#
# This subroutine install or update the i-MSCP daemon and network init scripts
#
# @return int 0 on success, other on failure
#
sub setup_imscp_daemon_network {

	debug((caller(0))[3].': Starting...');

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

		if(-x '/usr/sbin/update-rc.d') {
			$rs = execute("/usr/sbin/update-rc.d -f $fileName remove", \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout") if $stdout;
			error((caller(0))[3].": $stderr") if $rs;

			# Fix for #119: Defect - Error when adding IP's
			# We are now using dependency based boot sequencing (insserv)
			# See http://wiki.debian.org/LSBInitScripts ; Must be read carrefully
			$rs = execute("/usr/sbin/update-rc.d $fileName defaults", \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout") if $stdout;
			error((caller(0))[3].": $stderr") if $rs;

			# imscp_network should be stopped before the MySQL server (due to the
			# interfaces deletion process)
			#if($fileName eq 'imscp_network') {
			#	$rs = execute("/usr/sbin/update-rc.d $fileName defaults 99 20", \$stdout, \$stderr);
			#	debug((caller(0))[3].": $stdout") if $stdout;
			#	error((caller(0))[3].": $stderr") if $rs;
			#} else {
			#	$rs = execute("/usr/sbin/update-rc.d $fileName defaults 99", \$stdout, \$stderr);
			#	debug((caller(0))[3].": $stdout") if $stdout;
			#	error((caller(0))[3].": $stderr") if $rs;
			#}
		}

#		# LSB 3.1 Core section 20.4 compatibility (ex. OpenSUSE > 10.1)
#		} elsif(-x '/usr/lib/lsb/install_initd') {
#			# Update task
#			if(-x '/usr/lib/lsb/remove_initd') {
#				$rs = execute("/usr/lib/lsb/remove_initd $_", \$stdout, \$stderr);
#				debug((caller(0))[3].": $stdout") if $stdout;
#				error((caller(0))[3].": $stderr") if $rs;
#			}
#
#			$rs = execute("/usr/lib/lsb/install_initd $_", \$stdout, \$stderr);
#			debug((caller(0))[3].": $stdout") if $stdout;
#			error((caller(0))[3].": $stderr") if $rs;
#		}
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Set engine and gui permissions
#
# @return int 0 on success, other on failure
#
sub set_permissions {

	debug((caller(0))[3].': Starting...');

	my ($rs, $stdout, $stderr);

	for (qw/engine gui/) {

		#Use $stderr because this script does not exit on error?!!
		$rs = execute("$main::imscpConfig{'ROOT_DIR'}/engine/setup/set-$_-permissions.sh", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
		return $stderr if $stderr;
	}

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Restart services
#
# This subroutines restart all the services managed by i-MSCP.
#
sub restart_services {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	use iMSCP::Stepper;

	startDetail();

	#iMSCP::Dialog->new()->startGauge('Restarting service', 0) if iMSCP::Dialog->new()->needGauge();

	my @services = (
		#['Variable holding command', 'command to execute', 'ignore error if 0 exit on error if 1']
		['CMD_IMSCPN',			'restart',	1],
		['CMD_IMSCPD',			'restart',	1],
		['CMD_NAMED',			'reload',	1],
		['CMD_HTTPD',			'reload',	1],
		['CMD_FTPD',			'restart',	1],			# must be restarted
		['CMD_CLAMD',			'reload',	1],
		['CMD_POSTGREY',		'reload',	1],
		['CMD_POLICYD_WEIGHT',	'reload',	0],
		['CMD_AMAVIS',			'reload',	1],
		['CMD_MTA',				'reload',	1],
		['CMD_AUTHD',			'restart',	1],
		['CMD_POP',				'restart',	1],
		['CMD_POP_SSL',			'restart',	1],
		['CMD_IMAP',			'restart',	1],
		['CMD_IMAP_SSL',		'restart',	1],
	);

	my ($rs, $stdout, $stderr);
	my $count = 1;

	for (@services) {
		if($main::imscpConfig{$_->[0]} && ($main::imscpConfig{$_->[0]} !~ /^no$/i) && -f $main::imscpConfig{$_->[0]}) {
			$rs = step(
				sub { execute("$main::imscpConfig{$_->[0]} $_->[1]", \$stdout, \$stderr)},
				"Restarting $main::imscpConfig{$_->[0]}",
				scalar @services,
				$count,
				'no'
			);
			debug((caller(0))[3].": $main::imscpConfig{$_->[0]} $stdout") if $stdout;
			error((caller(0))[3].": $main::imscpConfig{$_->[0]} $stderr $rs") if ($rs && $_->[2]);
			return $rs if ($rs && $_->[2]);
		}
		$count++;
	}

	iMSCP::Dialog->new()->endGauge()  if iMSCP::Dialog->new()->needGauge();

	endDetail();

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# Setup i-MSCP database default data
#
# Default data are:
#
# - Data for the first i-MSCP administrator is none exists
# - Data for the first Ip
#
# @return int 0 on success, other on failure
#
sub setup_default_sql_data {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Crypt;
	use iMSCP::Database;

	my ($error);

	my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();
	my $admins = $database->doQuery(
						'admin_id',
						'SELECT
							*
						FROM
							`admin`
						WHERE
							`admin_type` = \'admin\'
						'
	);
	return 1 if (ref $admins ne 'HASH');

	my $msg = '';
	if( ! scalar keys %{$admins} ){
		my ($admin, $pass, $rpass, $msg, $admin_email) = ('admin');
		while(!($admin	= iMSCP::Dialog->new()->inputbox('Please enter administrator login name', $admin))){};
		do{
			while(!($pass	= iMSCP::Dialog->new()->passwordbox("Please enter administrator password ". ($msg ? $msg : ''),''))){};
			while(!($rpass	= iMSCP::Dialog->new()->passwordbox('Please repeat administrator password',''))){};
			$msg = "\n\n\\Z1Password do not match\\Zn.\n\nPlease try again";
		}while($pass ne $rpass);
		$pass = iMSCP::Crypt->new()->crypt_md5_data($pass);
		$admin_email = askAdminEmail();
		my $error = $database->doQuery(
			'dummy',
			"INSERT INTO `admin` (`admin_name`, `admin_pass`, `admin_type`, `email`)
			VALUES (?, ?, 'admin', ?);", $admin, $pass, $admin_email
		);
		return $error if (ref $error ne 'HASH');

		$error = $database->doQuery(
			'dummy',
			"INSERT INTO `user_gui_props` (`user_id`) values (LAST_INSERT_ID());"
		);
		return $error if (ref $error ne 'HASH');
	} else {
		askAdminEmail();
	}

	## First Ip data - Begin

	debug((caller(0))[3].': Inserting primary Ip data...');

	$error = $database->doQuery(
		'dummy',
		"
		UPDATE
			`server_ips`
		SET
			`ip_domain` = ?
		 WHERE
			`ip_number` = ?
		", $main::imscpConfig{'SERVER_HOSTNAME'}, $main::imscpConfig{'BASE_SERVER_IP'}
	);
	return $error if (ref $error ne 'HASH');

	$error = $database->doQuery(
		'dummy',
		"
		UPDATE
			`server_ips`
		SET
			`ip_domain` = NULL
		 WHERE
			`ip_number` != ?
		AND
			`ip_domain` = ?
		 ", $main::imscpConfig{'BASE_SERVER_IP'}, $main::imscpConfig{'SERVER_HOSTNAME'}
	);
	return $error if (ref $error ne 'HASH');

	askMYSQLPrefix();

	debug((caller(0))[3].': Ending...');
	0;
}

sub askMYSQLPrefix{
	debug((caller(0))[3].': Starting...');
	my $useprefix	= $main::imscpConfig{'MYSQL_PREFIX'} ?  $main::imscpConfig{'MYSQL_PREFIX'} : ($main::imscpConfigOld{'MYSQL_PREFIX'} ? $main::imscpConfigOld{'MYSQL_PREFIX'} : '');
	my $prefix		= $main::imscpConfig{'MYSQL_PREFIX_TYPE'} ?  $main::imscpConfig{'MYSQL_PREFIX_TYPE'} : ($main::imscpConfigOld{'MYSQL_PREFIX_TYPE'} ? $main::imscpConfigOld{'MYSQL_PREFIX_TYPE'} : '');
	while(!$useprefix || !$prefix){
		my $prefix = $prefix = iMSCP::Dialog->new()->radiolist("Use MySQL Prefix? Possible values:", 'do not use', 'infront', 'after');
		if($prefix eq 'do not use'){
			$useprefix	= 'no';
			$prefix		= 'none';
		} elsif($prefix =~ /^(infront|after)$/){
			$useprefix	= 'yes';
		}
	}
	$main::imscpConfig{'MYSQL_PREFIX'} = $useprefix if($main::imscpConfig{'MYSQL_PREFIX'} ne $useprefix);
	$main::imscpConfig{'MYSQL_PREFIX_TYPE'} = $prefix if($main::imscpConfig{'MYSQL_PREFIX_TYPE'} ne $prefix);
	debug((caller(0))[3].': Ending...');
}

sub askAdminEmail{

	my $admin_email = $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} ?  $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} : ($main::imscpConfigOld{'DEFAULT_ADMIN_ADDRESS'} ? $main::imscpConfigOld{'DEFAULT_ADMIN_ADDRESS'} : '');
	use Email::Valid;
	my $msg = '';
	while(!$admin_email){
		$admin_email = iMSCP::Dialog->new()->inputbox("Please enter administrator e-mail address .$msg");
		$admin_email = '' if(!Email::Valid->address($admin_email));
		$msg = "\n\n\\Z1Email is not valid\\Zn.\n\nPlease try again";
	}
	$main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} = $admin_email if($main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'} ne $admin_email);

	$admin_email;
}

################################################################################
# i-MSCP GUI pma configuration file and pma SQL control user - (Setup / Update)
#
# This subroutine built, store and install the PhpMyAdmin configuration file
#
# @return int 0 on success, -1 otherwise
#
sub setup_gui_pma {

	debug((caller(0))[3].': Starting...');

	my $cfgDir	= "$main::imscpConfig{'CONF_DIR'}/pma";
	my $bkpDir	= "$cfgDir/backup";
	my $wrkDir	= "$cfgDir/working";
	my $prodDir	= "$main::imscpConfig{'GUI_ROOT_DIR'}/tools/pma";
	my $dbType	= $main::imscpConfig{'DATABASE_TYPE'};
	my $dbHost	= $main::imscpConfig{'DATABASE_HOST'};
	my $dbPort	= $main::imscpConfig{'DATABASE_PORT'};
	my $dbName	= $main::imscpConfig{'DATABASE_NAME'};

	my ($error, $blowfishSecret, $ctrlUser, $ctrlUserPwd, $cfgFile, $file, $rebuild);

	# Saving the current production file if it exists
	if(-f "$prodDir/config.inc.php") {
		$file = iMSCP::File->new(filename => "$prodDir/config.inc.php")->copyFile("$bkpDir/config.inc.php." . time) and return 1;
	}

	if(-f "$wrkDir/config.inc.php") {
		# Gets the pma configuration file
		$file = iMSCP::File->new(filename => "$cfgDir/working/config.inc.php");
		$cfgFile = $file->get();
		return 1 if (!$cfgFile);

		# Retrieving the needed values from the working file
		($blowfishSecret, $ctrlUser, $ctrlUserPwd) = map {
			$cfgFile =~ /\['$_'\]\s*=\s*'(.+)'/
		} qw /blowfish_secret controluser controlpass/;
		$rebuild = check_sql_connection($dbType, '', $dbHost, $dbPort, $ctrlUser || '', $ctrlUserPwd || '');

		my $crypt = iMSCP::Crypt->new();

		my $err = check_sql_connection(
			$main::imscpConfig{'DATABASE_TYPE'},
			$main::imscpConfig{'DATABASE_NAME'},
			$main::imscpConfig{'DATABASE_HOST'},
			$main::imscpConfig{'DATABASE_PORT'},
			$main::imscpConfig{'DATABASE_USER'},
			$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
		);
		if ($err){
			error((caller(0))[3].": $err");
			return 1;
		}

	} else {
		$rebuild = 'yes';
	}

	# Getting blowfish secret
	if(!defined $blowfishSecret) {
		$blowfishSecret = iMSCP::Crypt::randomString(31);
		$blowfishSecret =~ s/'/\\'/gi;
	}

	if($rebuild){
		iMSCP::Dialog->new()->msgbox("
							\n\\Z1[WARNING]\\Zn

							Unable to found your working PMA configuration file !

							A new one will be created.
						"
		);

		$ctrlUser = $ctrlUser ? $ctrlUser : ($main::imscpConfig{'PMA_USER'} ? $main::imscpConfig{'PMA_USER'} : ($main::imscpConfigOld{'PMA_USER'} ? $main::imscpConfigOld{'PMA_USER'} : 'pma'));

		do{
			$ctrlUser = iMSCP::Dialog->new()->inputbox("Please enter database user name", $ctrlUser);
		} while (!$ctrlUser || ($main::imscpConfig{'DATABASE_USER'} eq $ctrlUser));
		iMSCP::Dialog->new()->set('cancel-label','Autogenerate');

		# Ask for proftpd SQL user password
		$ctrlUserPwd = iMSCP::Dialog->new()->inputbox("Please enter database password (leave blank for autogenerate)", '');
		if(!$ctrlUserPwd){
			$ctrlUserPwd = iMSCP::Crypt::randomString(16);
		}
		$ctrlUserPwd =~ s/('|"|`|#|;|\\)/_/g;
		iMSCP::Dialog->new()->msgbox("Your password is '".$ctrlUserPwd."'");
		iMSCP::Dialog->new()->set('cancel-label');

		my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();

		## We ensure that new data doesn't exist in database
		$error = $database->doQuery(
			'dummy',"
				DELETE FROM `mysql`.`tables_priv`
				WHERE `Host` = ?
				AND `Db` = 'mysql' AND `User` = ?;
			", $dbHost, $ctrlUser
		);
		return $error if (ref $error ne 'HASH');

		$error = $database->doQuery(
			'dummy',"
				DELETE FROM `mysql`.`user`
				WHERE `Host` = ?
				AND `User` = ?;
			", $dbHost, $ctrlUser
		);
		return $error if (ref $error ne 'HASH');

		$error = $database->doQuery(
			'dummy',"
				DELETE FROM `mysql`.`columns_priv`
				WHERE `Host` = ?
				AND `User` = ?;
			", $dbHost, $ctrlUser
		);
		return $error if (ref $error ne 'HASH');

		# Flushing privileges
		$error = $database->doQuery('dummy','FLUSH PRIVILEGES');
		return $error if (ref $error ne 'HASH');

		# Adding the new pma control user
		$error = $database->doQuery(
			'dummy',"
				GRANT USAGE ON
					`mysql`.*
				TO
					?@?
				IDENTIFIED BY
					?
			", $ctrlUser, $dbHost, $ctrlUserPwd
		);
		return $error if (ref $error ne 'HASH');

		## Sets the rights for the pma control user

		$error = $database->doQuery(
			'dummy',"
				GRANT SELECT ON `mysql`.`db` TO ?;
			", $ctrlUser."@".$dbHost
		);
		return $error if (ref $error ne 'HASH');

		$error = $database->doQuery(
			'dummy',"
				GRANT SELECT (
					Host, User, Select_priv, Insert_priv, Update_priv, Delete_priv,
					Create_priv, Drop_priv, Reload_priv, Shutdown_priv, Process_priv,
					File_priv, Grant_priv, References_priv, Index_priv, Alter_priv,
					Show_db_priv, Super_priv, Create_tmp_table_priv,
					Lock_tables_priv, Execute_priv, Repl_slave_priv,
					Repl_client_priv
				)
				ON `mysql`.`user`
				TO ?;
			", $ctrlUser."@".$dbHost
		);
		return $error if (ref $error ne 'HASH');

		$error = $database->doQuery(
			'dummy',"GRANT SELECT ON `mysql`.`host` TO ?;", $ctrlUser."@".$dbHost
		);
		return $error if (ref $error ne 'HASH');

		$error = $database->doQuery(
			'dummy',"
				GRANT SELECT
					(`Host`, `Db`, `User`, `Table_name`, `Table_priv`, `Column_priv`)
				ON
					`mysql`.`tables_priv`
				TO
					?;
			", $ctrlUser."@".$dbHost
		);
		return $error if (ref $error ne 'HASH');

		$main::imscpConfig{'PMA_USER'} = $ctrlUser if($main::imscpConfig{'PMA_USER'} ne $ctrlUser);
	}

	## Building the new file

	# Getting the template file
	$file = iMSCP::File->new(filename => "$cfgDir/config.inc.tpl");
	$cfgFile = $file->get();
	return 1 if (!$cfgFile);

	$cfgFile = process(
		{
			PMA_USER => $ctrlUser,
			PMA_PASS => $ctrlUserPwd,
			HOSTNAME => $dbHost,
			TMP_DIR  => "$main::imscpConfig{'GUI_ROOT_DIR'}/phptmp",
			BLOWFISH => $blowfishSecret
		},
		$cfgFile
	);
	return 1 if (!$cfgFile);

	# Storing the file in the working directory
	$file = iMSCP::File->new(filename => "$cfgDir/working/config.inc.php");
	$file->set($cfgFile) and return 1;
	$file->save() and return 1;
	$file->mode(0640) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Installing the file in the production directory
	# Note: permission are set by the set-gui-permissions.sh script
	$file->copyFile("$prodDir/") and return 1;

	#restore defaul connection
	my $crypt = iMSCP::Crypt->new();

	$error = check_sql_connection(
		$main::imscpConfig{'DATABASE_TYPE'},
		$main::imscpConfig{'DATABASE_NAME'},
		$main::imscpConfig{'DATABASE_HOST'},
		$main::imscpConfig{'DATABASE_PORT'},
		$main::imscpConfig{'DATABASE_USER'},
		$main::imscpConfig{'DATABASE_PASSWORD'} ? $crypt->decrypt_db_password($main::imscpConfig{'DATABASE_PASSWORD'}) : ''
	);
	return $error if ($error);


	debug((caller(0))[3].': Ending...');

	0;
}


################################################################################
# i-MSCP GUI apache vhost - (Setup / Update)
#
# This subroutine built, store and install i-MSCP GUI vhost configuration file.
#
# @return int 0 on success, other on failure
#
sub setup_gui_httpd {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Templator;
	use iMSCP::Execute;
	use Servers::httpd;

	my ($rs, $cfgTpl, $file);

	my $httpd = Servers::httpd->new()->factory('apache2');

	$rs = $httpd->disableSite('000-default');
	return $rs if $rs;

	# Disable the default NameVirtualHost directive
	# (Debian like distributions)
	if(-f '/etc/apache2/ports.conf') {
		# Loading the file
		$file = iMSCP::File->new(filename => '/etc/apache2/ports.conf');
		my $rdata = $file->get();
		return $rdata if(!$rdata);

		# Disable the default NameVirtualHost directive
		$rdata =~ s/^NameVirtualHost \*:80/#NameVirtualHost \*:80/gmi;

		# Saving the modified file
		$file->set($rdata) and return 1;
		$file->save() and return 1;
	}

	my $adminEmailAddress = $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'};
	my ($user, $domain) = split /@/, $adminEmailAddress;
	use Net::LibIDN qw/idn_to_ascii/;
	$adminEmailAddress = "$user@".idn_to_ascii($domain, 'utf-8');

	$httpd->{DEFAULT_ADMIN_ADDRESS}	= $adminEmailAddress;
	$httpd->{WWW_DIR}				= $main::imscpConfig{'ROOT_DIR'};
	$httpd->{DMN_NAME}				= 'gui';

	$rs = $httpd->buildConfFile($httpd->{masterConf});
	return $rs if $rs;

	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes'){
		$rs = $httpd->buildConfFile($httpd->{masterSSLConf});
		return $rs if $rs;
	}

	$rs = $httpd->enableSite($httpd->{masterConf});
	return $rs if $rs;

	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes'){
		$rs = $httpd->enableSite($httpd->{masterSSLConf});
		return $rs if $rs;
		$rs = $httpd->enableMod('ssl');
		return $rs if $rs;
	}


	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP GUI PHP configuration files - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Create the master fcgi directory
#  - Built, store and install gui php related files (starter script, php.ini...)
#
# @return int 0 on success, other on failure
#
sub setup_gui_php {

	debug((caller(0))[3].': Starting...');

	my ($rs, $cfgTpl, $file);

	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/fcgi";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";

	my $timestamp = time;

	# Saving files if they exists
	for ('php5-fcgi-starter', 'php5/php.ini', 'php5/browscap.ini') {
		if(-f "$main::imscpConfig{'PHP_STARTER_DIR'}/master/$_") {
			my (undef, $name) = split('/');
			$name = $_ if(!defined $name);
			my $file = iMSCP::File->new(filename => "$main::imscpConfig{'PHP_STARTER_DIR'}/master/$_");
			$file->copyFile("$bkpDir/master.$name.$timestamp") and return 1;
		}
	}

	## Create the fcgi directories tree for the GUI if it doesn't exists
	my $dir = iMSCP::Dir->new(dirname => "$main::imscpConfig{'PHP_STARTER_DIR'}/master/php5");
	$dir->make({user=>$main::imscpConfig{'ROOT_USER'}, group =>$main::imscpConfig{'ROOT_GROUP'}, mode => 0755}) and return 1;

	## PHP5 Starter script

	# Loading the template from /etc/imscp/fcgi/parts/master
	$cfgTpl = iMSCP::File->new(filename => "$cfgDir/parts/master/php5-fcgi-starter.tpl")->get();
	return 1 if (!$cfgTpl);

	# Building the new file
	$cfgTpl = process(
		{
			PHP_STARTER_DIR		=> $main::imscpConfig{'PHP_STARTER_DIR'},
			PHP5_FASTCGI_BIN	=> $main::imscpConfig{'PHP5_FASTCGI_BIN'},
			GUI_ROOT_DIR		=> $main::imscpConfig{'GUI_ROOT_DIR'},
			DMN_NAME			=> 'master'
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Storing the new file in the working directory
	$file = iMSCP::File->new(filename => "$wrkDir/master.php5-fcgi-starter");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0755) and return 1;
	$file->owner($main::imscpConfig{'APACHE_SUEXEC_USER_PREF'} . $main::imscpConfig{'APACHE_SUEXEC_MIN_UID'},$main::imscpConfig{'APACHE_SUEXEC_USER_PREF'} . $main::imscpConfig{'APACHE_SUEXEC_MIN_GID'}) and return 1;

	# Install the new file
	$file->copyFile("$main::imscpConfig{'PHP_STARTER_DIR'}/master/php5-fcgi-starter") and return 1;

	## PHP5 php.ini file

	# Loading the template from /etc/imscp/fcgi/parts/master/php5
	$cfgTpl = iMSCP::File->new(filename => "$cfgDir/parts/master/php5/php.ini")->get();
	return 1 if (!$cfgTpl);
	askPHPTimezone();
	# Building the new file
	$cfgTpl = process(
		{
			WWW_DIR				=> $main::imscpConfig{'ROOT_DIR'},
			DMN_NAME			=> 'gui',
			MAIL_DMN			=> $main::imscpConfig{'BASE_SERVER_VHOST'},
			CONF_DIR			=> $main::imscpConfig{'CONF_DIR'},
			MR_LOCK_FILE		=> $main::imscpConfig{'MR_LOCK_FILE'},
			PEAR_DIR			=> $main::imscpConfig{'PEAR_DIR'},
			RKHUNTER_LOG		=> $main::imscpConfig{'RKHUNTER_LOG'},
			CHKROOTKIT_LOG		=> $main::imscpConfig{'CHKROOTKIT_LOG'},
			OTHER_ROOTKIT_LOG	=> ($main::imscpConfig{'OTHER_ROOTKIT_LOG'} ne '') ? ":$main::imscpConfig{'OTHER_ROOTKIT_LOG'}" : '',
			PHP_STARTER_DIR		=> $main::imscpConfig{'PHP_STARTER_DIR'},
			PHP_TIMEZONE		=> $main::imscpConfig{'PHP_TIMEZONE'}
		},
		$cfgTpl
	);
	return 1 if (!$cfgTpl);

	# Store the new file in working directory
	$file = iMSCP::File->new(filename => "$wrkDir/master.php.ini");
	$file->set($cfgTpl) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'APACHE_SUEXEC_USER_PREF'} . $main::imscpConfig{'APACHE_SUEXEC_MIN_UID'},$main::imscpConfig{'APACHE_SUEXEC_USER_PREF'} . $main::imscpConfig{'APACHE_SUEXEC_MIN_GID'}) and return 1;

	# Install the new file
	$file->copyFile("$main::imscpConfig{'PHP_STARTER_DIR'}/master/php5/php.ini") and return 1;


	## PHP Browser Capabilities support file

	# Store the new file in working directory
	iMSCP::File->new(filename => "$cfgDir/parts/master/php5/browscap.ini")->copyFile("$wrkDir/browscap.ini") and return 1;

	$file = iMSCP::File->new(filename => "$wrkDir/browscap.ini");
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'APACHE_SUEXEC_USER_PREF'} . $main::imscpConfig{'APACHE_SUEXEC_MIN_UID'}, $main::imscpConfig{'APACHE_SUEXEC_USER_PREF'} . $main::imscpConfig{'APACHE_SUEXEC_MIN_GID'}) and return 1;

	# Install the new file
	$file->copyFile("$main::imscpConfig{'PHP_STARTER_DIR'}/master/php5/browscap.ini") and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}
sub askPHPTimezone{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	my $dt;

	if($main::imscpConfig{'PHP_TIMEZONE'}){
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	if($main::imscpConfigOld{'PHP_TIMEZONE'}){
		$main::imscpConfig{'PHP_TIMEZONE'} = $main::imscpConfigOld{'PHP_TIMEZONE'};
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	use DateTime;
	use DateTime::TimeZone;

	$dt = DateTime->new(year => 0, time_zone  => 'local')->time_zone->name;

	my $msg = '';
	do{
		while (! ($dt = iMSCP::Dialog->new()->inputbox( "Please enter Server`s Timezone $msg", $dt))){}
		$msg = "$dt is not a valid timezone! The continent and the city, both must start with a capital letter, e.g. Europe/London'";
	} while (! DateTime::TimeZone->is_valid_name($dt));

	$main::imscpConfig{'PHP_TIMEZONE'} = $dt;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# i-MSCP Gui named configuration - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Add Gui named cfg data in main Bind9 configuration file
#  - Built GUI named DNS record's file
#
# @return int 0 on success, other on failure
#
sub setup_gui_named {

	debug((caller(0))[3].': Starting...');

	setup_gui_named_cfg_data() and return 1;
	setup_gui_named_db_data() and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}


sub askVHOST{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	if($main::imscpConfig{'BASE_SERVER_VHOST'}){
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	if($main::imscpConfigOld{'BASE_SERVER_VHOST'}){
		$main::imscpConfig{'BASE_SERVER_VHOST'} = $main::imscpConfigOld{'BASE_SERVER_VHOST'};
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	use Data::Validate::Domain qw/is_domain/;

	my $hostname = "admin.$main::imscpConfig{'SERVER_HOSTNAME'}";

	my %options = $main::imscpConfig{'DEBUG'} ? (domain_private_tld => qr /^(?:bogus|test)$/) : ();

	my ($msg, @labels) = ('', ());
	do{
		while (! ($hostname = iMSCP::Dialog->new()->inputbox( "Please enter the domain name where i-MSCP will be reachable on:  $msg", $hostname))){}
		$msg = "\n\n$hostname is not a valid fqdn!";
		@labels = split(/\./, $hostname);
	} while (! (Data::Validate::Domain->new(%options)->is_domain($hostname) && ( @labels >= 3)));

	use Net::LibIDN qw/idn_to_ascii/;

	$main::imscpConfig{'BASE_SERVER_VHOST'} = idn_to_ascii($hostname, 'utf-8');

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# i-MSCP Gui named cfg file - (Setup / Update)
#
# This subroutine do the following tasks:
#  - Add Gui named cfg data in main configuration file
#
# @return int 0 on success, other on failure
#
sub setup_gui_named_cfg_data {

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;
	use iMSCP::Templator;

	my ($rs, $rdata, $cfg, $file);

	# Named directories paths
	my $cfgDir	= $main::imscpConfig{'CONF_DIR'};
	my $tpl_dir	= "$cfgDir/bind/parts";
	my $bkpDir	= "$cfgDir/bind/backup";
	my $wrkDir	= "$cfgDir/bind/working";
	my $dbDir	= $main::imscpConfig{'BIND_DB_DIR'};

	# Saving the current production file if it exists
	if(-f $main::imscpConfig{'BIND_CONF_FILE'}){
		$file = iMSCP::File->new(filename => $main::imscpConfig{'BIND_CONF_FILE'});
		if(! -f "$bkpDir/named.conf.system") {
			$file->copy("$bkpDir/named.conf.system") and return 1;
		} else {
			$file->copy("$bkpDir/named.conf." . time) and return 1;
		}
	}

	## Building of new configuration file

	# Loading all needed templates from /etc/imscp/bind/parts
	my ($entry_b, $entry_e, $entry) = ('', '', '');
	$entry_b	= iMSCP::File->new(filename => "$tpl_dir/cfg_entry_b.tpl")->get();
	$entry_e	= iMSCP::File->new(filename => "$tpl_dir/cfg_entry_e.tpl")->get();
	$entry		= iMSCP::File->new(filename => "$tpl_dir/cfg_entry.tpl")->get();
	return 1 if(!defined $entry_b ||!defined $entry_e ||!defined $entry);

	# Preparation tags
	my $tags_hash	= {DMN_NAME => $main::imscpConfig{'BASE_SERVER_VHOST'}, DB_DIR => $dbDir};
	my $entry_b_val	= process($tags_hash, $entry_b);
	my $entry_e_val	= process($tags_hash, $entry_e);
	my $entry_val	= process($tags_hash, $entry);


	# Loading working file from /etc/imscp/bind/working/named.conf
	$file		= iMSCP::File->new(filename => "$wrkDir/named.conf");
	$cfg		= $file->get();
	return 1 if (!$cfg);

	# Building the new configuration file
	my $entry_repl = "$entry_b_val$entry_val$entry_e_val$entry_b$entry_e";

	#delete old if exist
	$cfg = replaceBloc($entry_b_val, $entry_e_val, '', $cfg, 0);
	#add new
	$cfg = replaceBloc($entry_b, $entry_e, $entry_repl, $cfg, 0);

	## Storage and installation of new file - Begin

	# Store the new builded file in the working directory
	$file = iMSCP::File->new(filename => "$wrkDir/named.conf");
	$file->set($cfg) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the new file in the production directory
	$file->copyFile($main::imscpConfig{'BIND_CONF_FILE'}) and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}

sub askSecondaryDNS{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	my $out;

	if($main::imscpConfig{'SECONDARY_DNS'}){
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	if($main::imscpConfigOld{'SECONDARY_DNS'}){
		$main::imscpConfig{'SECONDARY_DNS'} = $main::imscpConfigOld{'SECONDARY_DNS'};
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	while (! ($out = iMSCP::Dialog->new()->radiolist("Enable secondary DNS server address IP?", 'no', 'yes'))){}
	if($out eq 'no'){
		$main::imscpConfig{'SECONDARY_DNS'} = 'no';
		debug((caller(0))[3].': Ending...');
		return 0;
	}

	use Data::Validate::IP qw/is_ipv4/;

	do{
		while (! ($out = iMSCP::Dialog->new()->inputbox("Please enter secondary DNS server address IP"))){}
	}while(! (is_ipv4($out) && $out ne '127.0.0.1') );

	$main::imscpConfig{'SECONDARY_DNS'} = $out;

	debug((caller(0))[3].': Ending...');
	0;
}

################################################################################
# i-MSCP Gui named dns record's - (Setup / Update)
#
# This subroutine does the following tasks:
#  - Build GUI named dns record's file
#
# @return int 0 on success, other on failure
#
sub setup_gui_named_db_data {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	use iMSCP::File;
	use iMSCP::Templator;

	my ($rs, $wrkFileContent, $entries, $file);

	# Slave DNS  - Address IP
	askSecondaryDNS();

	my $secDnsIp	= $main::imscpConfig{'SECONDARY_DNS'};
	my $baseIp		= $main::imscpConfig{'BASE_SERVER_IP'};
	my $baseVhost	= $main::imscpConfig{'BASE_SERVER_VHOST'};

	# Directories paths
	my $cfgDir = "$main::imscpConfig{'CONF_DIR'}/bind";
	my $bkpDir = "$cfgDir/backup";
	my $wrkDir = "$cfgDir/working";
	my $dbDir = $main::imscpConfig{'BIND_DB_DIR'};

	# Zone file name
	my $dbFname = "$baseVhost.db";

	# Named zone files paths
	my $sysCfg = "$dbDir/$dbFname";
	my $wrkCfg = "$wrkDir/$dbFname";
	my $bkpCfg = "$bkpDir/$dbFname";

	## Dedicated tasks for Install or Updates process

	#Saving the current production file if it exists
	if(-f $sysCfg) {
		iMSCP::File->new(filename => $sysCfg)->copyFile("$bkpCfg." . time) and return 1;
	}

	# Load the current working db file

	if(!-f $wrkCfg) {
		iMSCP::Dialog->new()->msgbox(
						"\\Z1[WARNING]\\Zn

						$main::imscpConfig{'BASE_SERVER_VHOST'}: Working db file not found!.
						Re-creation from scratch is needed..."
		);
	} else {
		$wrkFileContent = iMSCP::File->new(filename => $wrkCfg)->get();
	}

	## Building new configuration file

	# Loading the template from /etc/imscp/bind/parts
	$entries = iMSCP::File->new(filename => "$cfgDir/parts/db_master_e.tpl")->get();
	return 1 if (!$entries);

	my $tags = {
		DMN_NAME			=> $baseVhost,
		DMN_IP				=> $baseIp,
		BASE_SERVER_IP		=> $baseIp,
		SECONDARY_DNS_IP	=> ($secDnsIp ne 'no') ? $secDnsIp : $baseIp
	};

	# Replacement tags
	$entries = process($tags, $entries);
	return 1 if (!$entries);

	# Create or Update serial number according RFC 1912
	my $bTag = process($tags, iMSCP::File->new(filename => "$cfgDir/parts/db_time_b.tpl")->get());
	my $eTag = process($tags, iMSCP::File->new(filename =>"$cfgDir/parts/db_time_e.tpl")->get());
	return 1 if(!$bTag || !$eTag);
	my $timestamp = getBloc($bTag, $eTag, ($wrkFileContent ? $wrkFileContent : $entries));
	my $regExp = '[\s](?:(\d{4})(\d{2})(\d{2})(\d{2})|(\{TIMESTAMP\}))';
	my (undef, undef, undef, $day, $mon, $year) = localtime;
	if((my $tyear, my $tmon, my $tday, my $nn, my $setup) = ($timestamp =~ /$regExp/)) {
		if($setup){
			$timestamp = sprintf '%04d%02d%02d00', $year+1900, $mon+1, $day;
		} else {
			$nn++;
			if($nn >= 99){
				$nn = 0;
				$tday++;
			}
			$timestamp = ((($year+1900)*10000+($mon+1)*100+$day) > ($tyear*10000 +  $tmon*100 + $tday)) ? (sprintf '%04d%02d%02d00', $year+1900, $mon+1, $day) : (sprintf '%04d%02d%02d%02d', $tyear, $tmon, $tday, $nn);
		}
		$entries = process({ TIMESTAMP => $timestamp}, $entries);
	} else {
		error((caller(0))[3].': Can not find timestamp for base vhost');
		return 1;
	}

	## Store and install

	# Store the file in the working directory
	$file = iMSCP::File->new(filename => $wrkCfg);
	$file->set($entries) and return 1;
	$file->save() and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	# Install the file in the production directory
	$file->copyFile("$dbDir/") and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Save old i-MSCP main configuration file to preserve curent settings,
# because current will be overwritten on update
#
# @return int 0
#
sub save_conf{

	debug((caller(0))[3].': Starting...');

	use iMSCP::File;

	my$file = iMSCP::File->new(filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf");
	my $cfg = $file->get() or return 1;

	$file = iMSCP::File->new(filename => "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf");
	$file->set($cfg) and return 1;
	$file->save and return 1;
	$file->mode(0644) and return 1;
	$file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}) and return 1;

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Update i-MSCP main configuration file
#
# @return int 0
#
sub update_imscp_cfg {

	debug((caller(0))[3].': Starting...');
	for(qw/
		ZIP
		APACHE_SUEXEC_USER_PREF
		APACHE_SUEXEC_MIN_GID
		APACHE_SUEXEC_MAX_GID
		APACHE_SUEXEC_MIN_UID
		APACHE_SUEXEC_MAX_UID
		APACHE_USER
		APACHE_GROUP
		MTA_MAILBOX_MIN_UID
		MTA_MAILBOX_UID
		MTA_MAILBOX_UID_NAME
		MTA_MAILBOX_GID
		MTA_MAILBOX_GID_NAME
		BACKUP_HOUR
		BACKUP_MINUTE
		USER_INITIAL_THEME
		FTP_USERNAME_SEPARATOR
		DATE_FORMAT
		HTACCESS_USERS_FILE_NAME
		HTACCESS_GROUPS_FILE_NAME
		GUI_EXCEPTION_WRITERS
		DEBUG
	/){
		if($main::imscpConfigOld{$_} && $main::imscpConfigOld{$_} ne $main::imscpConfig{$_}){
			$main::imscpConfig{$_} = $main::imscpConfigOld{$_};
		}
	}
#??????????????????????????????????????????????

#?????????????????????????????????????????????????????

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Disable access to panel during update
#
# @return int 0 on success, other on failure
#
sub disableGUI{

	debug((caller(0))[3].': Starting...');

	if($main::imscpConfig{'CMD_HTTPD'} !~ /^no$/i){

		my ($rs, $stdout, $stderr);

		# Debian like distributions only:
		# Note for distributions maintainers:
		# For others distributions, you must use the a post-installation scripts

		if(! -f '/etc/SuSE-release' && -f '/usr/sbin/a2dissite') {

			## Enabling main vhost configuration file
			my $rs = execute("/usr/sbin/a2dissite imscp.conf", \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout");
			warning((caller(0))[3].": $stderr") if $stderr;

		}

		# Reload apache config
		$rs = execute("$main::imscpConfig{'CMD_HTTPD'} reload", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if $stderr;
	}

	debug((caller(0))[3].': Ending...');

	0;

}

################################################################################
# Create users and groups for different services
#
# @return int 0 on success, other on failure
#
sub setup_system_users{


	debug((caller(0))[3].': Starting...');
	my (@errors, $stdout, $stderr, $rs, $cmd);

	## SYSTEM USER
	my $panelGrName = $main::imscpConfig{'APACHE_SUEXEC_USER_PREF'}.$main::imscpConfig{'APACHE_SUEXEC_MIN_GID'};
	my $panelUName = $main::imscpConfig{'APACHE_SUEXEC_USER_PREF'}.$main::imscpConfig{'APACHE_SUEXEC_MIN_UID'};
	if(!getgrnam($panelGrName)){
		debug ((caller(0))[3].": Create group $panelGrName:");
		$rs = execute("$main::imscpConfig{'CMD_GROUPADD'} $panelGrName", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}
	if(!getpwnam($panelUName)){
		debug ((caller(0))[3].": Create user $panelUName");
		##TODO CHECK FROR BSD
		$cmd = ($main::imscpConfig{'ROOT_GROUP'} eq 'wheel') ?
			"$main::imscpConfig{'CMD_USERADD'} $panelUName".
			" -d $main::imscpConfig{'PHP_STARTER_DIR'}/master -m -c vu-master".
			" -g $panelGrName -s /bin/false"
		:
			"$main::imscpConfig{'CMD_USERADD'} -d $main::imscpConfig{'PHP_STARTER_DIR'}/master".
			" -m -c vu-master -g $panelGrName -s /bin/false $panelUName"
		;
		$rs = execute($cmd, \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	## MAIL USERS
	my $mailGrName	= $main::imscpConfig{'MTA_MAILBOX_GID_NAME'};
	my $mailUName	= $main::imscpConfig{'MTA_MAILBOX_UID_NAME'};
	if(!getgrnam($mailGrName)){
		debug ((caller(0))[3].": Create group $mailGrName:");
		$rs = execute("$main::imscpConfig{'CMD_GROUPADD'} $mailGrName", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}
	if(!getpwnam($mailUName)){
		debug ((caller(0))[3].": Create user $mailGrName:");
		$cmd = ($main::imscpConfig{'ROOT_GROUP'} eq 'wheel') ?
			"$main::imscpConfig{'CMD_USERADD'} $mailUName -c vmail-user -s /bin/false"
		:
			"$main::imscpConfig{'CMD_USERADD'} -c vmail-user -g $mailGrName -s /bin/false -r $mailUName"
		;
		$rs = execute($cmd, \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	##MASTER GROUP
	my $masterGrName = $main::imscpConfig{'MASTER_GROUP'};
	if(!getgrnam($masterGrName)){
		debug ((caller(0))[3].": Create group $masterGrName:");
		$rs = execute("$main::imscpConfig{'CMD_GROUPADD'} $masterGrName", \$stdout, \$stderr);
		debug((caller(0))[3].": $stdout") if $stdout;
		error((caller(0))[3].": $stderr") if ($stderr && $rs);
		warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
		return $rs if $rs;
	}

	(undef, undef, undef, my $gUsers) = getgrnam($masterGrName);
	$gUsers =~ s/\s/,/g;

	debug((caller(0))[3].": Users in $masterGrName |$gUsers|");

	for($mailUName, $panelUName){
		if(!$gUsers || $_ !~ m/$gUsers/){
			$cmd = ($main::imscpConfig{'ROOT_GROUP'} eq 'wheel') ?
				"$main::imscpConfig{'CMD_USERGROUP'} $_ -G $masterGrName"
			:
				"$main::imscpConfig{'CMD_USERGROUP'} -G $masterGrName $_"
			;
			$rs = execute($cmd, \$stdout, \$stderr);
			debug((caller(0))[3].": $stdout") if $stdout;
			error((caller(0))[3].": $stderr") if ($stderr && $rs);
			warning((caller(0))[3].": $stderr") if ($stderr && !$rs);
			return $rs if $rs;
		}
	}

	debug((caller(0))[3].': Ending...');

	0;
}

sub askBackup{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	my $BACKUP_IMSCP	= $main::imscpConfig{'BACKUP_IMSCP'} ?  $main::imscpConfig{'BACKUP_IMSCP'} : ($main::imscpConfigOld{'BACKUP_IMSCP'} ? $main::imscpConfigOld{'BACKUP_IMSCP'} : '');
	my $BACKUP_DOMAINS	= $main::imscpConfig{'BACKUP_DOMAINS'} ?  $main::imscpConfig{'BACKUP_DOMAINS'} : ($main::imscpConfigOld{'BACKUP_DOMAINS'} ? $main::imscpConfigOld{'BACKUP_DOMAINS'} : '');

	if (!$BACKUP_IMSCP){
		while (! ($BACKUP_IMSCP = iMSCP::Dialog->new()->radiolist("Do you want to enable backup for iMSCP configuration?", 'yes', 'no'))){}
	}
	if($BACKUP_IMSCP ne $main::imscpConfig{'BACKUP_IMSCP'}){ $main::imscpConfig{'BACKUP_IMSCP'} = $BACKUP_IMSCP; }

	if (!$BACKUP_DOMAINS){
		while (! ($BACKUP_DOMAINS = iMSCP::Dialog->new()->radiolist("Do you want to enable backup for domains?", 'yes', 'no'))){}
	}
	if($BACKUP_DOMAINS ne $main::imscpConfig{'BACKUP_DOMAINS'}){ $main::imscpConfig{'BACKUP_DOMAINS'} = $BACKUP_DOMAINS; }

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Run all update additional task such as rkhunter configuration
#
# @return void
#
sub additional_tasks{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Stepper;

	my @steps = (
		[\&setup_rkhunter, 'i-MSCP Rkhunter configuration:']
	);
	my $step = 1;
	for (@steps){
		step($_->[0], $_->[1], scalar @steps, $step);
		$step++;
	}
	iMSCP::Dialog->new()->endGauge()  if iMSCP::Dialog->new()->needGauge();

	debug((caller(0))[3].': Ending...');

	0;
}


################################################################################
# Setup rkhunter - (Setup / Update)
#
# This subroutine process the following tasks:
#
#  - update rkhunter database files (only during setup process)
#  - Debian specific: Updates the configuration file and cron task, and
#  remove default unreadable created log file
#
# @return int 0 on success, other on failure
#
sub setup_rkhunter {

	debug((caller(0))[3].': Starting...');

	my ($rs, $rdata);

	# Deleting any existent log files
	my $file = iMSCP::File->new (filename => $main::imscpConfig{'RKHUNTER_LOG'}."*");
	$file->delFile() and return 1;

	# Updates the rkhunter configuration provided by Debian like distributions
	# to disable the default cron task (i-MSCP provides its own cron job for rkhunter)
	if(-e '/etc/default/rkhunter') {
		# Get the file as a string
		$file = iMSCP::File->new (filename => '/etc/default/rkhunter');
		$rdata = $file->get();
		return 1 if(!$rdata);

		# Disable cron task default
		$rdata =~ s/CRON_DAILY_RUN="(yes)?"/CRON_DAILY_RUN="no"/gmi;

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

	debug((caller(0))[3].': Ending...');

	0;
}

################################################################################
# Rebuild all customers configuration files
#
# @return int 1 on success, other on failure
#
sub rebuild_customers_cfg {

	debug((caller(0))[3].': Starting...');

	use iMSCP::Boot;

	my $tables = {
		domain => 'domain_status', domain_aliasses => 'alias_status',
		subdomain => 'subdomain_status', subdomain_alias => 'subdomain_alias_status',
		mail_users => 'status', htaccess => 'status', htaccess_groups => 'status',
		htaccess_users => 'status'
	};

	# Set status as 'change'
	my $error;
	my $database = iMSCP::Database->new(db => $main::imscpConfig{'DATABASE_TYPE'})->factory();
	while (my ($table, $field) = each %$tables) {
		$error =  $database->doQuery('dummy',
			"
				UPDATE
					$table
				SET
					$field = 'change'
				WHERE
					$field = 'ok'
				;
			"
		);
		return $error if (ref $error ne 'HASH');
	}


	iMSCP::Boot->new()->unlock();

	my ($stdout, $stderr, $rs);
	$rs = execute("perl $FindBin::Bin/../imscp-rqst-mngr update", \$stdout, \$stderr);
	debug((caller(0))[3].": $stdout") if $stdout;
	error((caller(0))[3].": $stderr") if $stderr;
	return $rs if $rs;

	iMSCP::Boot->new()->lock();

	debug((caller(0))[3].': Ending...');

	0;
}

sub setup_ssl{

	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;

	my $rs;

	$main::imscpConfig{'SSL_ENABLED'} = $main::imscpConfigOld{'SSL_ENABLED'} if(!$main::imscpConfig{'SSL_ENABLED'} && $main::imscpConfigOld{'SSL_ENABLED'});

	if(!$main::imscpConfig{'SSL_ENABLED'}){
		Modules::openssl->new()->{openssl_path} = $main::imscpConfig{'CMD_OPENSSL'};
		$rs = sslDialog();
		return $rs if $rs;
	} elsif($main::imscpConfig{'SSL_ENABLED'} eq 'yes') {
		Modules::openssl->new()->{openssl_path}				= $main::imscpConfig{'CMD_OPENSSL'};
		Modules::openssl->new()->{cert_path}				= "$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		Modules::openssl->new()->{intermediate_cert_path}	= "$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		Modules::openssl->new()->{key_path}					= "$main::imscpConfig{'GUI_CERT_DIR'}/$main::imscpConfig{'SERVER_HOSTNAME'}.pem";
		if(Modules::openssl->new()->ssl_check_all()){
			iMSCP::Dialog->new()->msgbox("Certificate is missing or corrupt. Starting recover");
			$rs = sslDialog();
			return $rs if $rs;
		}
	}

	if($main::imscpConfig{'SSL_ENABLED'} ne 'yes'){
		$main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} = "http://";
		my $httpd = Servers::httpd->new()->factory('apache2');
		$httpd->disableSite($httpd->{masterSSLConf});
	};

	debug((caller(0))[3].': Ending...');

	0;
}

sub ask_certificate_key_path{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	use Modules::openssl;

	my $rs;
	my $key = "/root/$main::imscpConfig{'SERVER_HOSTNAME'}.key";
	my $pass = '';

	do{
		$rs = iMSCP::Dialog->new()->passwordbox("Please enter password for key if needed:", $pass);
		$rs =~s/(["\$`\\])/\\$1/g;
		Modules::openssl->new()->{key_pass} = $rs;
		do{
			while (! ($rs = iMSCP::Dialog->new()->fselect($key))){}
		}while (! -f $rs);
		Modules::openssl->new()->{key_path} = $rs;
		$key = $rs;
		$rs = Modules::openssl->new()->ssl_check_key();
	}while($rs);

	debug((caller(0))[3].': Ending...');
	0;
}

sub ask_intermediate_certificate_path{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	use Modules::openssl;

	my $rs;
	my $cert = '/root/';

	iMSCP::Dialog->new()->set('yes-label');
	iMSCP::Dialog->new()->set('no-label');
	return 0 if(iMSCP::Dialog->new()->yesno('Do you have an intermediate certificate?'));
	do{
		while (! ($rs = iMSCP::Dialog->new()->fselect($cert))){}
	}while ($rs && !-f $rs);
	Modules::openssl->new()->{intermediate_cert_path} = $rs;

	debug((caller(0))[3].': Ending...');
	0;
}

sub ask_certificate_path{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	use Modules::openssl;

	my $rs;
	my $cert = "/root/$main::imscpConfig{'SERVER_HOSTNAME'}.crt";

	iMSCP::Dialog->new()->msgbox('Please select certificate');
	do{
		do{
			while (! ($rs = iMSCP::Dialog->new()->fselect($cert))){}
		}while (! -f $rs);
		Modules::openssl->new()->{cert_path} = $rs;
		$cert = $rs;
		$rs = Modules::openssl->new()->ssl_check_cert();
	}while($rs);

	debug((caller(0))[3].': Ending...');
	0;
}

sub sslDialog{
	debug((caller(0))[3].': Starting...');

	use iMSCP::Dialog;
	use Modules::openssl;

	my $rs;

	while (! ($rs = iMSCP::Dialog->new()->radiolist("Do you want to activate SSL?", 'no', 'yes'))){}
	if($rs ne $main::imscpConfig{'SSL_ENABLED'}){ $main::imscpConfig{'SSL_ENABLED'} = $rs; }
	if($rs eq 'yes'){
		Modules::openssl->new()->{new_cert_path} = $main::imscpConfig{'GUI_CERT_DIR'};
		Modules::openssl->new()->{new_cert_name} = $main::imscpConfig{'SERVER_HOSTNAME'};
		while (! ($rs = iMSCP::Dialog->new()->radiolist('Select method', 'Create a self signed certificate', 'I already have a signed certificate'))){}
		$rs = $rs eq 'Create a self signed certificate' ? 0 : 1;
		Modules::openssl->new()->{cert_selfsigned} = $rs;
		Modules::openssl->new()->{vhost_cert_name} = $main::imscpConfig{'SERVER_HOSTNAME'} if ( !$rs );

		if( Modules::openssl->new()->{cert_selfsigned}){
			Modules::openssl->new()->{intermediate_cert_path} = '';
			ask_certificate_key_path();
			ask_certificate_path();
			ask_intermediate_certificate_path();
		}
		$rs = Modules::openssl->new()->ssl_export_all();
		return $rs if $rs;
	}
	if($main::imscpConfig{'SSL_ENABLED'} eq 'yes'){
		while (! ($rs = iMSCP::Dialog->new()->radiolist("Select default access mode for master domain?", 'https', 'http'))){}
		$main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} = "$rs://";
	}

	debug((caller(0))[3].': Ending...');
	0;
}

1;
