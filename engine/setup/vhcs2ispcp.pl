#!/usr/bin/perl

# VHCS 2.4.7.1 to ispCP Omega migration script
# Copyright (c) 2007 by Raphael Geissert <atomo64@gmail.com>
#
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
    
    if (sys_command("/etc/init.d/vhcs2_daemon stop") != 0) {
        exit_werror();
    }
    
    # The daemon and the network traffic logger should not be running.
    #  Here we make sure about that
    if (sys_command("/etc/init.d/ispcp_daemon stop") != 0) {
        exit_werror();
    }
    
    if (sys_command("/etc/init.d/ispcp_network stop") != 0) {
        exit_werror();
    }
    
    print STDOUT "\tBlocking access to /etc/vhcs2/vhcs2.conf...";
    
    if (sys_command("chmod a-r /etc/vhcs2/vhcs2.conf") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";
    
    return 0;
}

sub start_services {

    my ($lock_file) = @_;
    
    print STDOUT "\tAllowing access to /etc/vhcs2/vhcs2.conf ...";

    if (sys_command("chmod u+r /etc/vhcs2/vhcs2.conf") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }
	print STDOUT "done\n";
    
    sys_command("/etc/init.d/ispcp_daemon start");
    sys_command("/etc/init.d/ispcp_network start");
    
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

    print STDOUT "\tCopying database...";
    
    if (sys_command("mysqladmin -u\'$main::cfg{'DATABASE_USER'}\' -p\'$main::cfg{'DATABASE_PASSWORD'}\' create ispcp ") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }
    
    if (sys_command("mysqldump --opt -u\'$main::cfg{'DATABASE_USER'}\' -p\'$main::cfg{'DATABASE_PASSWORD'}\' $main::cfg{'DATABASE_NAME'} | mysql -u\'$main::cfg{'DATABASE_USER'}\' -p\'$main::cfg{'DATABASE_PASSWORD'}\' ispcp") != 0) {
        print STDOUT "failed!\n";
        exit_werror();
    }

    print STDOUT "done\n";

    print STDOUT "\tUpgrading database structure...";
    
    ($rs, $sql) = get_file('vhcs2ispcp.sql');
    
    exit_werror("SQL structure changes file couldn't be loaded", $rs) if ($rs != 0);
    
    ($rs, $rdata) = doSQL($sql);
    
    if ($rs != 0) {
        print STDOUT "failed!\n";
        exit_werror($rdata, $rs);
    }

    print STDOUT "done\n";

    return 0;
}

sub install_language {
    
    my ($rs, $sql, $rdata) = (undef, undef, undef);
    
    ($rs, $sql) = get_file("$main::db{'CONF_DIR'}/languages.sql");
    
    exit_werror("languages SQL file couldn't be retrieved", $rs) if ($rs != 0);
    
    ($rs, $rdata) = doSQL($sql);
    
    if ($rs != 0) {
        print STDOUT "failed!\n";
        exit_werror($rdata, $rs);
    }

    print STDOUT "done\n";

    return 0;
}

my $rs = undef;

my $welcome_message = <<MSG;

\tWelcome to the VHCS 2.4.7.1 to ispCP Omega migration script.
\tThis program will try to convert your existing VHCS system to ispCP.
\tPlease make sure you have a backup of your server data.


\tNOTE: During the migration process some or all the services might require to be 
\t shutdown or restarted.
MSG

print STDOUT $welcome_message;

print STDOUT "\tAre you sure you want to proceed? (type 'yes') [no]: ";

my $cont = readline(\*STDIN);
chop($cont);

if (!defined($cont) || $cont ne 'yes') {

    exit_werror("Script was aborted by user");

}

# Now we load the config before we lock the system

$main::cfg_file = '/etc/vhcs2/vhcs2.conf';

# First call won't connect to DB because the keys haven't been loaded yet
get_conf();

require $main::cfg{'ROOT_DIR'} . '/ispcp-db-keys.pl';

# This time we connect to the database
get_conf();

print STDOUT "\nVHCS2's services will now be stopped:\n";

$rs = stop_services("/tmp/vhcs2-backup-all.lock");

print STDOUT "\nVHCS2's database will now be converted:\n";

upgrade_database();

print STDOUT "\nInstalling default language...";

# Now let's load the new config
$main::cfg_file = '/etc/ispcp/ispcp.conf';

# Load new config and connect to the database as the new user
get_conf();

install_language();

if($main::cfg{'DATABASE_NAME'} ne 'ispcp') {

    print STDOUT "\nIMPORTANT: you have installed ispCP in a non-default directory";
    print STDOUT "\n\tThe migration script has converted your old VHCS database";
    print STDOUT "\n\tin the new database called 'ispcp'; please rename this database";
    print STDOUT "\n\twith the one you choose at install time: $main::cfg{'DATABASE_NAME'}\n";

}

print STDOUT "\nispCP's services will now be started:";

start_services();

my $bye_message = <<MSG;

The migration script has finished.

\tHave a nice day
--
\tVHCS 2.4.7.1 to ispCP Omega migration script 
\t\t- Copyright (C) 2007 Raphael Geissert
This program makes use of software copyrighted by moleSoftware GmbH, and isp Control Panel.
MSG

print STDOUT $bye_message;

exit 0;
