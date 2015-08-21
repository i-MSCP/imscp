#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
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

# Backward compatibility file for scripts using VHCS engine

no warnings 'once';

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

sub push_el
{
	my ($el, $sub_name, $msg) = @_;

	push @$el, "$sub_name".$main::el_sep."$msg";

	if (defined $main::engine_debug) {
		print STDOUT "[DEBUG] push_el() sub_name: $sub_name, msg: $msg\n";
	}
}

sub pop_el
{
	my ($el) = @_;
	my $data = pop @$el;

	unless (defined $data) {
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

sub dump_el
{
	my ($el, $fname) = @_;

	if ($fname ne 'stdout' && $fname ne 'stderr') {
		return 0 unless open(my $fh, '>', $fname);
	}

	my $el_data;
	while (defined($el_data = pop_el(\@main::el))) {
		my ($sub_name, $msg) = split(/$main::el_sep/, $el_data);

		if ($fname eq 'stdout') {
			printf STDOUT "%-30s | %s\n",  $sub_name, $msg;
		} elsif ($fname eq 'stderr') {
			printf STDERR "%-30s | %s\n",  $sub_name, $msg;
		} else {
			printf {$fh} "%-30s | %s\n",  $sub_name, $msg;
		}
	}
}

sub doSQL
{
	push_el(\@main::el, 'doSQL()', 'Starting...');
	my ($sql) = @_;
	my $qr;

	unless (defined $sql && $sql ne '') {
		push_el(\@main::el, 'doSQL()', '[ERROR] Undefined SQL query');
		return (-1, '');
	}

	unless (defined $main::db && ref $main::db) {
		$main::db = DBI->connect( @main::db_connect, {
			PrintError => 0,
			mysql_auto_reconnect => 1,
			mysql_enable_utf8 => 1
		});

		unless (defined $main::db) {
			push_el(\@main::el, 'doSQL()', "[ERROR] Unable to connect to SQL server with current DSN: @main::db_connect");
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
	}

	push_el(\@main::el, 'doSQL()', '[ERROR] Wrong SQL Query: ' . $main::db -> errstr);
	return (-1, '');
}

sub get_file
{
	push_el(\@main::el, 'get_file()', 'Starting...');
	my ($fname) = @_;

	unless (defined $fname && $fname ne '') {
		push_el(\@main::el, 'get_file()', "[ERROR] Undefined input data, fname: |$fname| !");
		return 1;
	}

	unless (-f $fname) {
		push_el(\@main::el, 'get_file()', "[ERROR] File '$fname' does not exist !");
		return 1;
	}

	unless (open(my $fh, '<', $fname)) {
		push_el(\@main::el, 'get_file()', "[ERROR] Unable to open '$fname' for reading: $!");
		return 1;
	}

	my @fdata = <$fh>;
	close($fh);
	my $line = join('', @fdata);
	push_el(\@main::el, 'get_file()', 'Ending...');
	return (0, $line);
}

sub del_file
{
	push_el(\@main::el, 'del_file()', 'Starting...');
	my ($fname) = @_;

	unless (defined $fname && $fname ne '') {
		push_el(\@main::el, 'del_file()', "[ERROR] Undefined input data, fname: $fname");
		return -1;
	}

	unless (-f $fname) {
		push_el(\@main::el, 'del_file()', "[ERROR] File '$fname' doesn't exist");
		return -1;
	}

	unless (unlink($fname)) {
		push_el(\@main::el, 'del_file()', "[ERROR] Unable to unlink '$fname' !");
		return -1;
	}

	push_el(\@main::el, 'del_file()', 'Ending...');
	0;
}

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
	unless (defined $pass && $pass ne '') {
		push_el(\@main::el, 'decrypt_db_password()', '[ERROR] Undefined input data...');
		return (1, '');
	}

	if (length($main::db_pass_key) != 32 || length($main::db_pass_iv) != 16) {
		push_el(\@main::el, 'decrypt_db_password()', '[ERROR] KEY or IV has invalid length');
		return (1, '');
	}

	my $plaintext = Crypt::CBC->new(
		-cipher => 'Crypt::Rijndael', -key => $key, -literal_key => 1, -iv => $main::db_pass_iv, -header => 'none',
		-padding => 'null'
	)->decrypt(decode_base64($pass));

	push_el(\@main::el, 'decrypt_db_password()', 'Ending...');
	return (0, $plaintext);
}

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

	@main::db_connect = ("DBI:mysql:$main::db_name:$main::db_host", $main::db_user, $main::db_pwd);
	$main::db = undef;
	push_el(\@main::el, 'setup_db_vars()', 'Ending...');
	0;
}

# Added for BC issue with installers from software packages
*setup_main_vars = *setup_db_vars;

sub get_conf
{
	push_el(\@main::el, 'get_conf()', 'Starting...');
	my $file_name = shift || $main::cfg_file;
	my ($rs, $fline) = get_file($file_name);
	return -1 if $rs;

	my @frows = split(/\n/, $fline);
	for (my $i = 0; $i < scalar(@frows); $i++) {
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
