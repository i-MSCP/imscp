#!/usr/bin/perl

# VHCS 2.4.7.1 to ispCP Omega migration script
# Copyright (c) 2007 by Raphael Geissert <atomo64@gmail.com> (original author)
# Copyright (c) 2007 by isp Control Panel
# http://isp-control.net
#
# License:
#  This library is free software; you can redistribute it and/or
#  modify it under the terms of the GNU Lesser General Public
#  License as published by the Free Software Foundation; either
#  version 2.1 of the License, or (at your option) any later version.
#
#  This library is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
#  Lesser General Public License for more details.
#
#  You should have received a copy of the GNU Lesser General Public
#  License along with this library; if not, write to the Free Software
#  Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
#
#  On Debian systems, the complete text of the GNU Lesser General
#  Public License can be found in `/usr/share/common-licenses/LGPL'.
#
#
# The ispCP ω Home Page is at:
#
#    http://isp-control.net
#

use FindBin;
use lib "$FindBin::Bin/..";
require 'ispcp_common_methods.pl';

use strict;

use warnings;

sub stop_services {

    my ($lock_file) = @_;

    if (-e $lock_file) {

        exit_werror("\tVHCS2's backups engine is currently running. Aborting...");

    }

    print STDOUT "\t";

    if ( -e "/etc/init.d/vhcs2_daemon" ) {

        sys_command("/etc/init.d/vhcs2_daemon stop");

        print STDOUT "\t";

    }

    if ( -e "/etc/init.d/ispcp_daemon" ) {

        sys_command("/etc/init.d/ispcp_daemon stop");

        print STDOUT "\t";

    }

    if ( -e "/etc/init.d/ispcp_network" ) {

        sys_command("/etc/init.d/ispcp_network stop");

        print STDOUT "\t";

    }

    print STDOUT "\n\tBlocking access to /etc/vhcs2/vhcs2.conf...";

    if (sys_command("chmod a-r /etc/vhcs2/vhcs2.conf") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";

    return 0;
}

sub start_services {

    print STDOUT "\tAllowing access to /etc/vhcs2/vhcs2.conf ...";

    if (sys_command("chmod u+r /etc/vhcs2/vhcs2.conf") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }
    print STDOUT "done\n";

    sys_command("$main::cfg{'CMD_ISPCPD'} start");
    sys_command("$main::cfg{'CMD_ISPCPN'} start");
    sleep(2);

    print STDOUT "\tDisabling vhcs2's apache2 sites ...";

    if (-e "/etc/apache2/sites-enabled/vhcs2.conf" &&
        sys_command("unlink /etc/apache2/sites-enabled/vhcs2.conf") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }
    print STDOUT "done\n";

    #Restart servers to make them use the newly generated config
    sys_command("$main::cfg{'CMD_HTTPD'} restart");
    sleep(2);
    sys_command("$main::cfg{'CMD_MTA'} restart");
    sleep(2);
    if (-e "$main::cfg{'CMD_NAMED'}") {
        sys_command("$main::cfg{'CMD_NAMED'} restart");
        sleep(2);
    }
    sys_command("$main::cfg{'CMD_POP'} restart");
    sleep(2);
    if (-e "$main::cfg{'CMD_POP_SSL'}") {
        sys_command("$main::cfg{'CMD_POP_SSL'} restart");
        sleep(2);
    }
    sys_command("$main::cfg{'CMD_IMAP'} restart");
    sleep(2);
    if (-e "$main::cfg{'CMD_IMAP_SSL'}") {
        sys_command("$main::cfg{'CMD_IMAP_SSL'} restart");
        sleep(2);
    }
    sys_command("$main::cfg{'CMD_FTPD'} restart");
    sleep(2);
    sys_command("$main::cfg{'CMD_AUTHD'} restart");

    return 0;
}

sub exit_werror {

    my ($msg, $code) = @_;

    if (!defined($code) || $code <= 0 ) {
        $code = 1;
    }

    if (defined($msg) && $msg ne '' ) {
        print STDERR "\n$msg\n";
    }

    exit $code;

}

sub upgrade_database {

    my ($rdata, $rs, $sql) = (undef, undef, undef);

    print STDOUT "\tDropping ispcp table...";

    ($rs, $rdata) = doSQL("DROP DATABASE IF EXISTS `ispcp`;");

    if ($rs != 0) {
        print STDOUT "failed!\n";
        exit_werror($rdata, $rs);
    }

    print STDOUT "done\n";

    print STDOUT "\tCreating new database...";

    if (sys_command("mysqladmin -u\'$main::db_user\' -p\'$main::db_pwd\' create ispcp ") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";

    print STDOUT "\tCopying database...";

    if (sys_command("mysqldump --opt -u\'$main::db_user\' -p\'$main::db_pwd\' $main::db_name | mysql -u\'$main::db_user\' -p\'$main::db_pwd\' ispcp") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";

    print STDOUT "\tUpgrading database structure...";

    if (sys_command("mysql -u\'$main::db_user\' -p\'$main::db_pwd\' < vhcs2.4.7-ispcp.sql") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";

    return 0;
}

sub install_language {

    if (sys_command("mysql -u\'$main::db_user\' -p\'$main::db_pwd\' ispcp < $main::cfg{'CONF_DIR'}/database/languages.sql") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";

    return 0;
}

my $rs = 0;

my $welcome_message = <<MSG;

\tWelcome to the VHCS 2.4.7.1 to ispCP Omega migration script.
\tThis program will try to convert your existing VHCS system to ispCP.
\tPlease make sure you have a backup of your server data.


\tNOTE: During the migration process some or all the services might require to be
\t shutdown or restarted.
MSG

print STDOUT $welcome_message;

# Make sure we are in the right dir
if ( ! -e "./ispcp-setup") {
    print STDOUT "Please first change to the directory where this script is";
    exit 1;
}

print STDOUT "\tVHCS2 must first be partially removed from the system\n";
print STDOUT "\tDo you want me to run the remover (it will only remove the config files)? (yes|skip|abort) [abort]: ";
my $cont = readline(\*STDIN);
chop($cont);

if (!defined($cont) || $cont eq 'abort') {

    exit_werror("Script was aborted by user");

}

if ( $cont eq 'yes' ) {
    # Remove unecessary VHCS2 stuff
    $rs = sys_command_rs("./vhcs2-remover.pl");
    exit_werror(undef, $rs) if ($rs != 0);
}

print STDOUT "\n\tispCP should now be setup\n";
print STDOUT "\tDo you want me to run the setup program? (yes|skip|abort) [yes]: ";
$cont = readline(\*STDIN);
chop($cont);

if ($cont eq 'abort') {

    exit_werror("Script was aborted by user");

}

if ( $cont ne 'skip' ) {
    print STDOUT "\n\tRemember to use 'ispcp' as the database name so the migration script works correctly\n";
    # Now let's setup ispCP
    $rs = sys_command("./ispcp-setup");
    exit_werror($rs) if ($rs != 0);
}

# Now we load the config before we lock the system

$main::cfg_file = '/etc/vhcs2/vhcs2.conf';

# First call won't connect to DB because the keys haven't been loaded yet
get_conf();

require $main::cfg{'ROOT_DIR'} . '/engine/vhcs2-db-keys.pl';

# Let's connect to the database
setup_main_vars();

print STDOUT "\nVHCS2's services will now be stopped:\n";

stop_services("/tmp/vhcs2-backup-all.lock");

print STDOUT "\nVHCS2's database will now be converted:\n";

upgrade_database();

print STDOUT "\nInstalling default language...";

# Now let's load the new config
$main::cfg_file = '/etc/ispcp/ispcp.conf';

# Load new config
get_conf();

require $main::cfg{'ROOT_DIR'} . '/engine/ispcp-db-keys.pl';

# Now we connect
setup_main_vars();

install_language();

if ($main::cfg{'DATABASE_NAME'} ne 'ispcp') {

    print STDOUT "\nIMPORTANT: you have installed ispCP in a non-default database";
    print STDOUT "\n\tThe migration script has converted your old VHCS database";
    print STDOUT "\n\tin the new database called 'ispcp'; please rename this database";
    print STDOUT "\n\twith the one you chosen at ispCP install time: $main::cfg{'DATABASE_NAME'}\n";
    print STDOUT "\nAfter that you should run the requests manager: $main::cfg{'ROOT_DIR'}/engine/ispcp-rqst-mngr\n";

} else {

    print STDOUT "\nRunning ispCP's requests manager...";

    sys_command("$main::cfg{'ROOT_DIR'}/engine/ispcp-rqst-mngr");

    print STDOUT "done\n";

}

print STDOUT "\nStarting ispCP's services:\n";

start_services();

my $bye_message = <<MSG;

The migration script has finished.
To update the email templates to the new ones clear their content via the GUI and save the changes, it will then use the new ones.

\tHave a nice day
--
\tVHCS 2.4.7.1 to ispCP Omega migration script
\t\t- Copyright (C) 2007 Raphael Geissert
This program makes use of software copyrighted by moleSoftware GmbH, and isp Control Panel.
MSG

print STDOUT $bye_message;

exit 0;
