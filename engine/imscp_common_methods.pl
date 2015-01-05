#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
#
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2015 by internet Multi Server Control Panel - http://i-mscp.net
#
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "VHCS - Virtual Hosting Control System".
#
# The Initial Developer of the Original Code is moleSoftware GmbH.
# Portions created by Initial Developer are Copyright (C) 2001-2006
# by moleSoftware GmbH. All Rights Reserved.
#
# Portions created by the ispCP Team are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

# Backward compatibility file for script using old engine methods

# Hide the "used only once: possible typo" warnings
no warnings 'once';

# Global variables;

$main::el_sep = "\t#\t";
@main::el = ();
$main::db_host = undef;
$main::db_user = undef;
$main::db_pwd = undef;
$main::db_name = undef;
@main::db_connect = ();
$main::db = undef;
$main::use_crypted_pwd = undef;
%main::cfg = ();
$main::cfg_re = '^[ \t]*([\_A-Za-z0-9]+) *= *([^\n\r]*)[\n\r]';

# License request function must not SIGPIPE;
$SIG{PIPE} = 'IGNORE';
$SIG{HUP} = 'IGNORE';

# Logging subroutines

# Add a new message in the logging stack and print it
#
# Note:  Printing is only done in DEBUG mode
#
# @param arrayref $el Reference to the global logging stack array
# @param scalar $sub_name Subroutine name that cause log message
# @param scalar $msg message to be logged
# @void
#
sub push_el
{
	my ($el, $sub_name, $msg) = @_;

	push @$el, "$sub_name".$main::el_sep."$msg";

	if (defined $main::engine_debug) {
		print STDOUT "[DEBUG] push_el() sub_name: $sub_name, msg: $msg\n";
	}
}

# Print and return the last message from the logging stack
#
# Note: Printing is only done in DEBUG mode.
#
# This subroutine take the last message in the logging stack and print and
# return it. Note that the message is completely removed from the logging stack.
#
# @param arrayref $el Reference to the global logging stack array
# @return mixed Last message from the log stack or undef if the logging stack is
# empty
#
sub pop_el
{
	my ($el) = @_;
	my $data = pop @$el;

	if (!defined $data) {
		if (defined $main::engine_debug) {
			print STDOUT "[DEBUG] pop_el() Empty 'EL' Stack !\n";
		}

		return undef;
	}

	my ($sub_name, $msg) = split(/$main::el_sep/, $data);

	if (defined $main::engine_debug) {
		print STDOUT "[DEBUG] pop_el() sub_name: $sub_name, msg: $msg\n";
	}

    $data;
}

# Dump logging stack
#
# @param arrayref $el Reference to the global Logging stack array
# @param [string $fname Logfile name]
# @return int
#
sub dump_el
{
	my ($el, $fname) = @_;

	if ($fname ne 'stdout' && $fname ne 'stderr') {
		return 0 if ! open(FP, '>', $fname);
	}

	my $el_data;

	while (defined($el_data = pop_el(\@main::el))) {
		my ($sub_name, $msg) = split(/$main::el_sep/, $el_data);

		if ($fname eq 'stdout') {
			printf STDOUT "%-30s | %s\n",  $sub_name, $msg;
		} elsif ($fname eq 'stderr') {
			printf STDERR "%-30s | %s\n",  $sub_name, $msg;
		} else {
			printf FP "%-30s | %s\n",  $sub_name, $msg;
		}
	}

	close FP;
}

# SQL subroutines

sub doSQL
{
	push_el(\@main::el, 'doSQL()', 'Starting...');

	my ($sql) = @_;
	my $qr;

	if (!defined $sql || $sql eq '') {
		push_el(\@main::el, 'doSQL()', '[ERROR] Undefined SQL query');
		return (-1, '');
	}

	if (!defined $main::db || ! ref $main::db) {
		$main::db = DBI->connect(
			@main::db_connect,
			{
				'PrintError' => 0,
				'mysql_auto_reconnect' => 1,
				'mysql_enable_utf8' => 1
			}
		);

		if (!defined $main::db) {
			push_el(
				\@main::el, 'doSQL()', "[ERROR] Unable to connect to SQL server with current DSN: @main::db_connect"
			);
			return (-1, '');
		} else { # FIXME: It is really necessary with the mysql_enable_utf8 option?
			$qr = $main::db->do("SET NAMES 'utf8';");
		}
	}

	if ($sql =~ /select/i) {
		$qr = $main::db->selectall_arrayref($sql);
	} elsif ($sql =~ /show/i) {
		$qr = $main::db->selectall_arrayref($sql);
	} else {
		$qr = $main::db->do($sql);
	}

	if (defined $qr) {
		push_el(\@main::el, 'doSQL()', 'Ending...');
		return (0, $qr);
	} else {
		push_el(\@main::el, 'doSQL()', '[ERROR] Wrong SQL Query: ' . $main::db -> errstr);
		return (-1, '');
	}
}


# Other subroutines

# Get file content in string
#
# @return int 0 on success, -1 otherwise
#
sub get_file
{
	push_el(\@main::el, 'get_file()', 'Starting...');

	my ($fname) = @_;

	if (!defined $fname || $fname eq '') {
		push_el(\@main::el, 'get_file()', "[ERROR] Undefined input data, fname: |$fname| !");
		return 1;
	}

	if (!-e $fname) {
		push_el(\@main::el, 'get_file()', "[ERROR] File '$fname' does not exist !");
		return 1;
	}

	if (!open(F, '<', $fname)) {
		push_el(\@main::el, 'get_file()', "[ERROR] Unable to open '$fname' for reading: $!");
		return 1;
	}

	my @fdata = <F>;
	close(F);

	my $line = join('', @fdata);

	push_el(\@main::el, 'get_file()', 'Ending...');

	return (0, $line);
}

# Delete a file
#
# @param scalar $fname File name to be deleted
# @return 0 on sucess, -1 otherwise
#
sub del_file
{
	push_el(\@main::el, 'del_file()', 'Starting...');

	my ($fname) = @_;

	if (! defined $fname || $fname eq '') {
		push_el(\@main::el, 'del_file()', "[ERROR] Undefined input data, fname: $fname");
		return -1;
	}

	if (! -f $fname) {
		push_el(\@main::el, 'del_file()', "[ERROR] File '$fname' doesn't exist");
		return -1;
	}

	my $res = unlink($fname);

	if ($res != 1) {
		push_el(\@main::el, 'del_file()', "[ERROR] Unable to unlink '$fname' !");
		return -1;
	}

	push_el(\@main::el, 'del_file()', 'Ending...');

	0;
}

# Subroutine for handle external commands

# Get and return external command exit value
#
# This is an merely subroutine to get the external command exit value. If the
# command failed to execute or died with any signal, a negative integer is
# returned. In all other cases, the real exit value from the external command is
# returned.
#
# @return int -1 if the command failed to executed or died with any signal,
# external command exit value otherwise
#
sub getCmdExitValue()
{
	push_el(\@main::el, 'getCmdExitValue()', 'Starting...');

	my $exitValue = -1;

	if ($? == -1) {
 		push_el(\@main::el, 'getCmdExitValue()', "[ERROR] Failed to execute external command: $!");
	} elsif ($? & 127) {
 		push_el(
 		    \@main::el, 'getCmdExitValue()', sprintf "[ERROR] External command died with signal %d, %s coredump",
 		    ($? & 127), ($? & 128) ? 'with' : 'without'
 	    );
	} else {
		$exitValue = $? >> 8;
		push_el(\@main::el, 'getCmdExitValue()', "[DEBUG] External command exited with value $exitValue");
	}

	push_el(\@main::el, 'getCmdExitValue()', 'Ending...');

	$exitValue;
}

# Execute an external command and show
#
# Note:
#
# If you want gets the real exit value from the external command, you must use
# the sys_command_rs() subroutine.
#
# @param string $cmd External command to be executed
# @return int 0 on success, -1 otherwise
#
sub sys_command
{
	push_el(\@main::el, 'sys_command()', 'Starting...');

	my ($cmd) = @_;

	system($cmd);

	my $exit_value = getCmdExitValue();

	if ($exit_value == 0) {
		push_el(\@main::el, "sys_command('$cmd')", 'Ending...');
		0;
	} else {
		push_el(\@main::el, 'sys_command()', "[ERROR] External command '$cmd' exited with value $exit_value !");
		-1;
	}
}

# Execute an external command and return the real exit value
#
# @param string $cmd External command to be executed
# @return int command exit code
#
sub sys_command_rs
{
	my ($cmd) = @_;

	push_el(\@main::el, 'sys_command_rs()', 'Starting...');

	system($cmd);

	push_el(\@main::el, 'sys_command_rs()', 'Ending...');

	getCmdExitValue();
}

sub decrypt_db_password
{
	push_el(\@main::el, 'decrypt_db_password()', 'Starting...');

	my ($pass) = @_;

	if (!defined $pass || $pass eq '') {
		push_el(\@main::el, 'decrypt_db_password()', '[ERROR] Undefined input data...');
		return (1, '');
	}

	if (length($main::db_pass_key) != 32 || length($main::db_pass_iv) != 8) {
		push_el(\@main::el, 'decrypt_db_password()', '[ERROR] KEY or IV has invalid length');
		return (1, '');
	}

	my $cipher = Crypt::CBC -> new(
		{
			'key' => $main::db_pass_key,
			'keysize' => 32,
			'cipher' => 'Blowfish',
			'iv' => $main::db_pass_iv,
			'regenerate_key' => 0,
			'padding' => 'space',
			'prepend_iv' => 0
		}
	);

	my $decoded = decode_base64("$pass\n");
	my $plaintext = $cipher -> decrypt($decoded);

	push_el(\@main::el, 'decrypt_db_password()', 'Ending...');

	return (0, $plaintext);
}

# Setup the global database variables and redefines the DSN
#
# @return int 0
#
sub setup_db_vars
{
	push_el(\@main::el, 'setup_db_vars()', 'Starting...');

	$main::db_host = $main::cfg{'DATABASE_HOST'};
	$main::db_user = $main::cfg{'DATABASE_USER'};
	$main::db_pwd = $main::cfg{'DATABASE_PASSWORD'};
	$main::db_name = $main::cfg{'DATABASE_NAME'};

	if ($main::db_pwd ne '') {
		(my $rs, $main::db_pwd) = decrypt_db_password($main::db_pwd);
		return $rs if $rs;
	}

	# Setup DSN
	@main::db_connect = ("DBI:mysql:$main::db_name:$main::db_host", $main::db_user, $main::db_pwd);
	$main::db = undef;

	push_el(\@main::el, 'setup_db_vars()', 'Ending...');

	0;
}

# Added for BC issue with installers from software packages
*setup_main_vars = *setup_db_vars;

# Load all configuration parameters from a specific configuration file
#
# This subroutine load all configuration parameters from a specific file where
# each of them are represented by a pair of key/value separated by the equal
# sign.
#
# @param [scalar $file_name filename from where the configuration must be loaded]
# Default value is the main i-MSCP configuration file (imscp.conf)
# @return int 0 on success, 1 otherwise
#
sub get_conf
{
	push_el(\@main::el, 'get_conf()', 'Starting...');

	my $file_name = shift || $main::cfg_file;

	my ($rs, $fline) = get_file($file_name);
	return -1 if ($rs != 0);

	my @frows = split(/\n/, $fline);

	my $i = '';

	for ($i = 0; $i < scalar(@frows); $i++) {
		$frows[$i] = "$frows[$i]\n";

		if ($frows[$i] =~ /$main::cfg_re/) {
			$main::cfg{$1} = $2;
		}
	}

	push_el(\@main::el, 'get_conf()', 'Ending...');

	0;
}

sub get_el_error
{
	push_el(\@main::el, 'get_el_error()', 'Starting...');

	my ($fname) = @_;

	my ($rs, $rdata) = get_file($fname);
	return $rs if $rs;

	my @frows = split(/\n/, $rdata);
	my $err_row = "$frows[0]\n";

	$err_row =~ /\|\ *([^\n]+)\n$/;

	$rdata = $1;

	push_el(\@main::el, 'get_el_error()', 'Ending...');

	return (0, $rdata);
}
