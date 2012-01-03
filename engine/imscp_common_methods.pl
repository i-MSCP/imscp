#!/usr/bin/perl

# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2001-2006 by moleSoftware GmbH - http://www.molesoftware.com
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
#
# Version: $Id$
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
# Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#


# Hide the "used only once: possible typo" warnings
no warnings 'once';

# Global variables;

$main::cc_stdout = '/tmp/imscp-cc.stdout';

$main::cc_stderr = '/tmp/imscp-cc.stderr';

$main::el_sep = "\t#\t";

# Initialize the stack that will contain all logging messages
@main::el = ();

%main::domain_id_name = ();

%main::domain_name_id = ();

%main::domain_id_ipid = ();

%main::sub_id_name = ();

%main::sub_name_id = ();

%main::sub_id_parentid = ();

%main::als_id_name = ();

%main::als_name_id = ();

%main::als_id_parentid = ();

%main::ip_id_num = ();

%main::ip_num_id = ();

$main::db_host = undef;

$main::db_user = undef;

$main::db_pwd = undef;

$main::db_name = undef;

@main::db_connect = ();

$main::db = undef;

$main::use_crypted_pwd = undef;

$main::master_name = 'imscp-rqst-mngr';

%main::cfg = ();

%main::cfg_reg = ();

$main::cfg_re = '^[ \t]*([\_A-Za-z0-9]+) *= *([^\n\r]*)[\n\r]';

# License request function must not SIGPIPE;
$SIG{PIPE} = 'IGNORE';

$SIG{HUP} = 'IGNORE';

################################################################################
#                                Logging subroutines                           #
################################################################################

################################################################################
# Add a new message in the logging stack and print it
#
# Note:  Printing is only done in DEBUG mode
#
# @param arrayref $el Reference to the global logging stack array
# @param scalar $sub_name Subroutine name that cause log message
# @param scalar $msg message to be logged
# @void
#
sub push_el {

	my ($el, $sub_name, $msg) = @_;

	push @$el, "$sub_name".$main::el_sep."$msg";

	if (defined $main::engine_debug) {
        print STDOUT "[DEBUG] push_el() sub_name: $sub_name, msg: $msg\n";
    }
}

################################################################################
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
sub pop_el {

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

    return $data;
}

################################################################################
# Dump the logging stack
#
# @param arrayref $el Reference to the global Logging stack array
# @param [string $fname Logfile name]
# @return int
#
sub dump_el {

	my ($el, $fname) = @_;


	if ($fname ne 'stdout' && $fname ne 'stderr') {
		return 0 if(!open(FP, '>', $fname));
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

################################################################################
#                                SQL subroutines                               #
################################################################################

sub doSQL {

	push_el(\@main::el, 'doSQL()', 'Starting...');

	my ($sql) = @_;
	my $qr = undef;

	if (!defined $sql || $sql eq '') {
		push_el(\@main::el, 'doSQL()', '[ERROR] Undefined SQL query !');

		return (-1, '');
	}

	if (!defined $main::db || !ref $main::db) {
		$main::db = DBI->connect(@main::db_connect, {PrintError => 0});

		if (!defined $main::db) {

			push_el(
				\@main::el, 'doSQL()',
				"[ERROR] Unable to connect SQL server with current DSN: @main::db_connect"
			);

			return (-1, '');

		# DB: use always UTF8
		} elsif ($main::cfg{'DATABASE_UTF8'} eq 'yes' ) {
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
		push_el(
			\@main::el, 'doSQL()',
			'[ERROR] Incorrect SQL Query -> ' . $main::db -> errstr
		);

		return (-1, '');
	}
}

sub doHashSQL {

	push_el(\@main::el, 'doHashSQL()', 'Starting...');

	my ($sql, $kField) = @_;
	my $qr;

	if (!defined $sql || $sql eq '') {
		push_el(\@main::el, 'doHashSQL()', '[ERROR] Undefined SQL query !');

		return (-1, '');
	}

	if (!defined $main::db || !ref $main::db) {
		$main::db = DBI -> connect(@main::db_connect, {PrintError => 0});

		if (!defined $main::db) {

		push_el(
			\@main::el, 'doHashSQL()', '[ERROR] Unable to connect SQL server !'
		);

		return (-1, '');

		} elsif ($main::cfg{'DATABASE_UTF8'} eq 'yes' ) { # DB: use always UTF8
			$qr = $main::db -> do("SET NAMES 'utf8';");
		}
	}

	if (defined $kField && $kField ne '' && $sql =~ /^[\s]*?(select|show)/i) {
		$qr = $main::db->selectall_hashref($sql, $kField);
	} else {
		$qr = $main::db->do($sql);
	}

	if (defined $qr) {
		push_el(\@main::el, 'doHashSQL()', 'Ending...');

		return (0, $qr);

	} else {
		push_el(
			\@main::el, 'doHashSQL()',
			'[ERROR] Incorrect SQL Query -> '.$main::db -> errstr
		);

		return (-1, '');
	}
}

################################################################################
#                                Other subroutines                             #
################################################################################

################################################################################
# set file user, group and permissions
#
# Note:
#
# If $fgroup is set to 'null' this subroutine will get the GID from /etc/passwd.
#
# @author   VHCS/ispCP Team
# @author	Benedikt Heintel
# @version	1.1
# @access	public
# @param	scalar $fname File or Folder Name
# @param	mixed $fuser Linux User or UserID
# @param	mixed $fgroup Linux Group, GroupID or 'null'
# @param	int $fperms	Linux Permissions
# @return	int on success, -1 otherwise
#
sub setfmode {

	push_el(\@main::el, 'setfmode()', 'Starting...');

	my ($fname, $fuser, $fgroup, $fperms) = @_;

	if (!defined $fname || !defined $fuser || !defined $fperms || $fname eq ''
		|| $fname eq '' || $fgroup eq '' || $fperms eq '') {

		push_el(
			\@main::el, 'setfmode()',
			"[ERROR] Undefined input data, fname: |$fname|, fuid: |$fuser|, " .
				"fgid: |$fgroup|, fperms: |$fperms| !"
		);

		return -1;
	}

	if (!-e $fname) {
		push_el(
			\@main::el, 'setfmode()', "[ERROR] File '$fname' does not exist !"
		);

		return -1;
	}

	my @udata = ();
	my @gdata = ();

	my ($uid, $gid);

	# get UID of user
	if ($fuser =~ /^\d+$/) {
		$uid = $fuser;
	} elsif ($fuser ne '-1') {
		@udata = getpwnam($fuser);

		if (scalar(@udata) == 0) {
			push_el(\@main::el, 'setfmode()', "[ERROR] Unknown user '$fuser' !");

			return -1;
		}

		$uid = $udata[2];
	}

	# get GID of user
	if ($fgroup =~ /^\d+$/) {
		$gid = $fgroup;
	} elsif ($fgroup eq 'null') {
		$gid = $udata[3];
	} elsif ($fgroup ne '-1') {
		@gdata = getgrnam($fgroup);

		if (scalar(@udata) == 0) {
			push_el(
				\@main::el, 'setfmode()', "[ERROR] Unknown user '$fgroup' !"
			);

			return -1;
		}

		$gid = $gdata[2];
	}

	my $res = chmod($fperms, $fname);

	if ($res != 1) {
		push_el(
			\@main::el, 'setfmode()',
			"[ERROR] cannot change permissions of file '$fname' !"
		);

		return -1;
	}

	$res = chown($uid, $gid, $fname);

	if ($res != 1) {
		push_el(
			\@main::el, 'setfmode()',
			"[ERROR] cannot change user/group of file '$fname' !"
		);

		return -1;
	}

	push_el(\@main::el, 'setfmode()', 'Ending...');

	0;
}

################################################################################
# Get file content in string
#
# @return int 0 on success, -1 otherwise
#
sub get_file {

	push_el(\@main::el, 'get_file()', 'Starting...');

	my ($fname) = @_;

	if (!defined $fname || $fname eq '') {
		push_el(
			\@main::el, 'get_file()',
			"[ERROR] Undefined input data, fname: |$fname| !"
		);

		return 1;
	}

	if (!-e $fname) {
		push_el(
			\@main::el, 'get_file()', "[ERROR] File '$fname' does not exist !"
		);

		return 1;
	}

	if (!open(F, '<', $fname)) {
		push_el(
			\@main::el, 'get_file()', "[ERROR] Can't open '$fname' for reading: $!"
		);

		return 1;
	}

	my @fdata = <F>;
	close(F);

	my $line = join('', @fdata);

	push_el(\@main::el, 'get_file()', 'Ending...');

	return (0, $line);
}

################################################################################
# Stores a string in a file
#
# This subroutine allow to store a string in a file. If the file already exit,
# all the current content will be replaced by the new one from the string.
#
# Note:
#
# The file is created with rights and permissions defined during the call. If
# the group is not defined, he will be get from the /etc/passwd file.
#
# @author		VHCS/ispCP Team
# @copyright 	2006-2009 by ispCP | http://isp-control.net
# @version		1.1
#
# @access	public
# @param	scalar $fname File Name
# @param	scalar $fdata Data to write to file
# @param	mixed $fuser Linux User or UserID
# @param	mixed $fgroup Linux Group, GroupID or null
# @param	int $fperms Linux Permissions
# @return	int	 0 on success, -1 otherwise
#
sub store_file {

	push_el(\@main::el, 'store_file()', 'Starting...');

	my ($fname, $fdata, $fuid, $fgid, $fperms) = @_;

	if (!defined $fname || $fname eq '' || $fuid eq '' || $fgid eq '' ||
		$fperms eq '') {
		push_el(
			\@main::el, 'store_file()',
			"[ERROR] Undefined input data, fname: |$fname|, fdata, " .
				"fuid: '$fuid', fgid: '$fgid', fperms: '$fperms'"
		);

		return -1;
	}

	if (!open(F, '>', $fname)) {
		push_el(
			\@main::el, 'store_file()',
			"[ERROR] Can't open file |$fname| for writing: $!"
		);

		return -1;
	}

	print F $fdata;
	close(F);

	my ($rs, $rdata) = setfmode($fname, $fuid, $fgid, $fperms);
	return -1 if ($rs != 0);

	push_el(\@main::el, 'store_file()', 'Ending...');

	0;
}

################################################################################
# Save a string in a file
#
# Note:
#
# This subroutine don't set any user/group and permissions on the file.
#
# @author		VHCS/ispCP Team
# @copyright 	2006-2009 by ispCP | http://isp-control.net
# @version		1.1
#
# @access	public
# @param	scalar $fname File Name
# @param	scalar $fdata Data to write to file
# @return	int	0 on success -1 otherwise
sub save_file {

	push_el(\@main::el, 'save_file()', 'Starting...');

	my ($fname, $fdata) = @_;

	if (!defined $fname || $fname eq '' ) {
		push_el(
			\@main::el, 'save_file()',
			"[ERROR] Undefined input data, fname: |$fname|"
		);

		return -1;
	}

	if (!open(F, '>', $fname)) {
		push_el(
			\@main::el, 'save_file()',
			"[ERROR] Can't open file |$fname| for writing: $!"
		);

		return -1;
	}

	print F $fdata;
	close(F);

	push_el(\@main::el, 'save_file()', 'Ending...');

	0;
}

################################################################################
# Delete a file
#
# @param scalar $fname File name to be deleted
# @return 0 on sucess, -1 otherwise
#
sub del_file {

	push_el(\@main::el, 'del_file()', 'Starting...');

	my ($fname) = @_;

	if (!defined $fname || $fname eq '') {
		push_el(
			\@main::el, 'del_file()',
			"[ERROR] Undefined input data, fname: |$fname| !"
		);

		return -1;
	}

	if (! -e $fname) {
		push_el(
			\@main::el, 'del_file()', "[ERROR] File '$fname' does not exist !"
		);

		return -1;
	}

	my $res = unlink ($fname);

	if ($res != 1) {
		push_el(
			\@main::el, 'del_file()', "[ERROR] Can't unlink '$fname' !"
		);

		return -1;
	}

	push_el(\@main::el, 'del_file()', 'Ending...');

	0;
}

sub set_zone {

	my ($fdata, $data, $zone, $comment) = @_;

	my @fdata = split("\n", $fdata);

	my $bz = '';
	my $az = '';
	my $zs = 0;
	my $ze = 0;
	my $ll;
	my $curline;

	while(length($fdata) > 0) {
		$ll = index($fdata, "\n");

		if($ll < 0) {
			$ll = length( $fdata );
		} else {
			$ll++;
		}

		$curline = substr( $fdata, 0, $ll );
		$fdata = substr( $fdata, $ll );

		if($zs == 0) {
			if(index($curline, $comment."## START i-MSCP ".$zone." ###") == 0 ) {
				$zs = 1;
			} else {
				$bz .= $curline;
			}
		} elsif($ze == 0) {
			if(index($curline, $comment."## END i-MSCP ".$zone." ###") == 0) {
				$ze = 1;
			}
		} elsif($ze == 1) {
			$az .= $curline;
		}
	}

	return
		$bz . ($zs == 1 ? "" : "\n").
		$comment."## START i-MSCP ".$zone." ###\n".
		$data."\n".
		$comment."## END i-MSCP ".$zone." ###\n".
		$az;
}

sub get_zone {

	my ($fdata, $zone, $comment) = @_;
	my @fdata = split("\n", $fdata);
	my $zonecontent = '';
	my $zs = 0;
	my $ze = 0;
	my $ll;
	my $curline;

	while(length($fdata) > 0) {
		$ll = index($fdata, "\n");

		if($ll < 0) {
			$ll = length($fdata);
		} else {
			$ll++;
		}

		$curline = substr($fdata, 0, $ll);
		$fdata = substr($fdata, $ll);

		if($zs == 0) {
			if(index($curline, $comment."## START i-MSCP ".$zone." ###") == 0) {
				$zs = 1;
			}
		} elsif($ze == 0) {
			if(index($curline, $comment."## END i-MSCP ".$zone." ###") == 0) {
				$ze = 1;
			} else {
				$zonecontent .= $curline;
			}
		}
	}

	return $zonecontent;
}

sub del_zone {

	my ($fdata, $zone, $comment) = @_;
	my @fdata = split("\n", $fdata);
	my $bz = '';
	my $az = '';
	my $zs = 0;
	my $ze = 0;
	my $ll;
	my $curline;

	while(length($fdata) > 0) {
		$ll = index($fdata, "\n");

		if($ll < 0) {
			$ll = length($fdata);
		} else {
			$ll++;
		}

		$curline = substr($fdata, 0, $ll);
		$fdata = substr( $fdata, $ll );

		if($zs == 0) {
			if(index($curline, $comment."## START i-MSCP ".$zone." ###") == 0) {
				$zs = 1;
			} else {
				$bz .= $curline;
			}
		} elsif($ze == 0) {
			if(index($curline, $comment."## END i-MSCP ".$zone." ###") == 0) {
				$ze = 1;
			}
		} elsif($ze == 1) {
			$az .= $curline;
		}
	}

	return $bz.$az;
}

################################################################################
#                         Subroutine for handle external commands              #
################################################################################

################################################################################
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
sub getCmdExitValue() {

	push_el(\@main::el, 'getCmdExitValue()', 'Starting...');

	my $exitValue = -1;

	if ($? == -1) {
 		push_el(
 		    \@main::el, 'getCmdExitValue()',
 		    "[ERROR] Failed to execute external command: $!"
		);
	} elsif ($? & 127) {
 		push_el(
 		    \@main::el, 'getCmdExitValue()',
 		    sprintf "[ERROR] External command died with signal %d, %s coredump",
 		    ($? & 127), ($? & 128) ? 'with' : 'without'
 	    );
	} else {
		$exitValue = $? >> 8;

		push_el(
			\@main::el, 'getCmdExitValue()',
			"[DEBUG] External command exited with value $exitValue",
		);
	}

	push_el(\@main::el, 'getCmdExitValue()', 'Ending...');

	$exitValue;
}

################################################################################
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
sub sys_command {

	push_el(\@main::el, 'sys_command()', 'Starting...');

	my ($cmd) = @_;

	system($cmd);

	my $exit_value = getCmdExitValue();

	if ($exit_value == 0) {
		push_el(\@main::el, "sys_command('$cmd')", 'Ending...');

		return 0;
	} else {
		push_el(
			\@main::el, 'sys_command()',
			"[ERROR] External command '$cmd' exited with value $exit_value !"
		);

		return -1;
	}
}

################################################################################
# Execute an external command and return the real exit value
#
# @param string $cmd External command to be executed
# @return int command exit code
#
sub sys_command_rs {

	my ($cmd) = @_;

	push_el(\@main::el, 'sys_command_rs()', 'Starting...');

	system($cmd);

	push_el(\@main::el, 'sys_command_rs()', 'Ending...');

	return getCmdExitValue();
}

################################################################################
# Execute an external command and return any output from her
#
# @param string $cmd External command to be executed
# @return 0 on success, [-1, errMsg] otherwise
#
sub sys_command_stderr {

	push_el(\@main::el, 'sys_command_stderr()', 'Starting...');

	my ($cmd) = @_;

	my $stderr = `$cmd 2>&1`;

	return (-1, $stderr) if getCmdExitValue() != 0;

	push_el(\@main::el, 'sys_command_stderr()', 'Ending...');

	0;
}

sub make_dir {

	push_el(\@main::el, 'make_dir()', 'Starting...');
	push_el(
		\@main::el, 'make_dir()',
		'[WARNING] This function is deprecated. Use makepath() instead ...'
	);

	my ($dname, $duid, $dgid, $dperms) = @_;

	if (!defined $dname || !defined $duid || !defined $dgid || !defined $dperms
		|| $dname eq '' || $duid eq '' || $dgid eq '' || $dperms eq '' ) {
		push_el(
			\@main::el, 'make_dir()',
			"[ERROR] Undefined input data, dname: |$dname|, duid: |$duid|, " .
				"dgid: |$dgid|, dperms: |$dperms| !"
		);

		return -1;
	}

	my ($rs, $rdata) = ('', '');

	if (-e $dname && -f $dname) {
		push_el(
			\@main::el, 'make_dir()',
			"[NOTICE] '$dname' exists as file ! removing file first..."
		);

		return -1 if (del_file($dname) != 0);
	}

	if (!(-e $dname && -d $dname)) {
		push_el(
			\@main::el, 'make_dir()',
			"[NOTICE] '$dname' doesn't exists as directory! creating..."
		);

		$rs =  mkpath($dname);

		if (!$rs) {
			push_el(
				\@main::el, 'make_dir()',"[ERROR] mkdir() returned '$rs' status!"
			);

			return -1;
		}

	} else {
		push_el(
			\@main::el, 'make_dir()',
			"[NOTICE] '$dname' exists ! Setting its permissions..."
		);
	}

	return -1 if (setfmode($dname, $duid, $dgid, $dperms) != 0);

	push_el(\@main::el, 'make_dir()', 'Ending...');

	0;
}

################################################################################
# Delete a directory
#
# @param scalar $dname Directory to be deleted
# @return int 0 on success, -1 otherwise
#
sub del_dir {

	push_el(\@main::el, 'del_dir()', 'Starting...');

	my ($dname) = @_;

	if (!defined($dname) || ($dname eq '')) {
		push_el(
			\@main::el,
			'del_dir()', "[ERROR]: Undefined input data, dname: |$dname| !"
		);

		return -1;
	}

	push_el(\@main::el, 'del_dir()', "Trying to remove '$dname'...");

	return -1 if sys_command("$main::cfg{'CMD_RM'} -rf $dname") != 0;

	push_el(\@main::el, 'del_dir()', 'Ending...');

	0;
}

sub gen_rand_num {

    push_el(\@main::el, 'gen_rand_num()', 'Starting...');

	my ($len) = @_;

	if (!defined($len) || ($len eq '')) {
		push_el(
			\@main::el, 'gen_rand_num()',
			"[ERROR] Undefined input data, len: |$len| !"
		);

		return (-1, '');
	}

	if (!(0 < $len && $len < 11)) {
		push_el(
			\@main::el, 'gen_rand_num()',
			"[ERROR] Input data length '$len' out of limits [1, 10] !"
		);

		return (-1, '');
	}

	my @rand_data = ('A'..'Z', 'a'..'z', '0'..'9', '.', '/');

	my ($i, $rdata) = ('', '');

	for ($i = 0; $i < $len; $i++) {
		$rdata .= $rand_data[ rand() * ($#rand_data + 1) ];
	}

	push_el(\@main::el, 'gen_rand_num()', 'Ending...');

	return (0, $rdata);
}

sub gen_sys_rand_num {

	push_el(\@main::el, 'gen_sys_rand_num()', 'Starting...');

	my ($len) = @_;

	if (!defined $len || $len eq '') {
		push_el(
			\@main::el, 'gen_sys_rand_num()',
			"[ERROR] Undefined input data, len: |$len| !"
		);

		return (-1, '');
    }

	if (0 >= $len ) {
		push_el(
			\@main::el, 'gen_sys_rand_num()',
			"[ERROR] Input data length '$len' is zero or negative !"
		);

		return (-1, '');
	}

	my $pool_size = 0;
	my $read_avail = 0;

	if (-e '/proc/sys/kernel/random/entropy_avail') {
		$read_avail = 1;
		$pool_size = int(get_file('/proc/sys/kernel/random/entropy_avail'));

		if ($pool_size <= ($len + 10)) {
			push_el(
				\@main::el, 'gen_sys_rand_num()',
				"[WARNING] entropy pool is $pool_size, but we require more or less $len"
			);
		}
	}

	if (-e '/dev/urandom') {
		push_el(
			\@main::el, 'gen_sys_rand_num()',
			"[NOTICE] seeding the entropy pool (possible current size: $pool_size)"
		);

		my $seed = $len;

		while ($seed >= 0 || ($read_avail && int(get_file(
			'/proc/sys/kernel/random/entropy_avail' )) <= ($len + 10))) {

			my ($n, $c, $l) = (100, undef, 0);

			do {
				$l = int(rand() * 100);
				next if ($l < 0 || $l > 255);
				$c .= chr($l);
			} while($n--);

			save_file('/dev/urandom', $c . (rand() * rand() * rand() * rand()));
			save_file('/dev/urandom', time ^ ($$ + ($$ << 15)) << (1 ^ rand -$$ ));
			$seed--;
		}
	}

	if ($read_avail) {
		$pool_size = int(get_file('/proc/sys/kernel/random/entropy_avail'));

		push_el(
			\@main::el, 'gen_sys_rand_num()',
			"[NOTICE] new entropy pool size is $pool_size"
		);
	}

	# DON'T change this back to /dev/random - the pw is reversible encrypted -
	# more randomness is just totally foolish since we already provide the key
	# together with the tresor.

	my $rs = open(F, '<', '/dev/urandom');

	if (!defined $rs) {
		$rs = open(F, '<', '/dev/urandom');

		if (!defined $rs) {
			push_el(
				\@main::el, 'gen_sys_rand_num()',
				"[ERROR] Couldn't open the pseudo-random characters generator: $!"
			);

			return (-1, '');
		}
	}

	my ($i, $rdata, $rc, $rci) = (0, undef, undef, undef);

	while ($i <= $len) {

		read(F, $rc, 1);
		$rci = ord($rc);

		# Excludes all chars below Space (incl.) and bove },
		# the escape char (\) and the '
		next if ($rci <= 32 || $rci >= 126 || $rci == 92 || $rci == 39);

		$rdata .= $rc;
		$rc = undef;
		$i++;
	}

	close(F);

	push_el(\@main::el, 'gen_sys_rand_num()', 'Ending...');

	return (0, $rdata);
}

sub crypt_md5_data {

	push_el(\@main::el, 'crypt_md5_data()', 'Starting...');

	my ($data) = @_;

	if (!defined $data || $data eq '') {
		push_el(
			\@main::el, 'crypt_md5_data()',
			"[ERROR] Undefined input data, data: |$data| !"
		);

		return (-1, '');
	}

	my ($rs, $rdata) = gen_rand_num(8);
	return (-1, '') if ($rs != 0);

	$rdata = unix_md5_crypt($data, $rdata);

	push_el(\@main::el, 'crypt_md5_data()', 'Ending...');

	return (0, $rdata);
}

sub crypt_data {

	push_el(\@main::el, 'crypt_data()', 'Starting...');

	my ($data) = @_;

	if (!defined $data || $data eq '') {
		push_el(
			\@main::el, 'crypt_data()',
			"[ERROR] Undefined input data, data: |$data| !"
		);

		return (-1, '');
	}

	my ($rs, $rdata) = gen_rand_num(2);
	return (-1, '') if ($rs != 0);

	$rdata = crypt($data, $rdata);

	push_el(\@main::el, 'crypt_data()', 'Ending...');

	return (0, $rdata);
}

sub get_tag {

	push_el(\@main::el, 'get_tag()', 'Starting...');

	my ($bt, $et, $src, $function) = @_;

	$function = 'undefined' if(!defined $function);

	if (!defined $bt || !defined $et || !defined $src || $bt eq '' ||
		$et eq '' || $src eq '') {
		push_el(
			\@main::el, 'get_tag()',
			"[ERROR] Undefined input data, bt: |$bt|, et: |$et|, src !"
		);

		return (-1, '');
	}

	my ($bt_len, $et_len, $src_len) = (length($bt), length($et), length($src));

	if ($bt eq $et) {
		my $tag = $bt;
		my $tag_pos = index($src, $tag);

		if ($tag_pos < 0) {

			if($function ne 'repl_tag') {
				push_el(
					\@main::el, 'get_tag()',
					"[ERROR] '$bt' eq '$et', missing '$bt' in src!"
				);
			}

			return (-4, '');

		} else {
			push_el(\@main::el, 'get_tag()', 'Ending...');

			return (0, $tag);
		}

	} else {
		if ($bt_len + $et_len > $src_len) {
			push_el(
				\@main::el, 'get_tag()', "[ERROR] len($bt) + len($et) > len(src) !"
			);

			return (-1, '');
		}

        # Let's search for ...$bt...$et... ;

		my ($bt_pos, $et_pos) = (index($src, $bt), index($src, $et));

		if ($bt_pos < 0 || $et_pos < 0) {
			push_el(
				\@main::el,
				'get_tag()',
				"[ERROR] '$bt' ne '$et', '$bt' or '$et' missing in src !"
			);

			return (-5, '');
        }

		if ($et_pos < $bt_pos + $bt_len) {
			push_el(
				\@main::el, 'get_tag()',
				"[ERROR] '$bt' ne '$et', '$et' overlaps '$bt' in src !"
			);

			return (-1, '');
        }

		push_el(\@main::el, 'get_tag()', 'Ending...');

		my $tag_len = $et_pos + $et_len - $bt_pos;

		return (0, substr($src, $bt_pos, $tag_len));
    }
}

sub repl_tag {

	push_el(\@main::el, 'repl_tag()', 'Starting...');

	my ($bt, $et, $src, $rwith, $function) = @_;

	if (!defined $function) {
		$function = "not defined function"
	}

	if (!defined $rwith) {
		push_el(
			\@main::el, 'repl_tag()',
			"[ERROR] Undefined template replacement data in $function!"
		);

		return (-1, '');

	}

	my ($rs, $rdata) = get_tag($bt, $et, $src, 'repl_tag');
	return ($rs, $src) if ($rs != 0);

	my $tag = $rdata;
	my ($tag_pos, $tag_len) = (index($src, $tag), length($tag));

	if ($rwith eq '') {
		substr($src, $tag_pos, $tag_len, '');
	} else {
		substr($src, $tag_pos, $tag_len, $rwith);
	}

	push_el(\@main::el, 'repl_tag()', 'Ending...');

	return (0, $src);
}

sub add_tag {

	push_el(\@main::el, 'add_tag()', 'Starting...');

	my ($bt, $et, $src, $adata) = @_;

	if (!defined $adata || $adata eq '') {
		push_el(
			\@main::el, 'add_tag()',
			"[ERROR] Undefined input data, adata: |$adata| !"
		);

		return (-1, '');
	}

	my ($rs, $rdata) = get_tag($bt, $et, $src);
	return ($rs, '') if ($rs != 0);

	my $rwith = '';

	if ($bt eq $et) {
		$rwith = "$adata$bt";
	} else {
		$rwith = "$adata$bt$et";
	}

	($rs, $rdata) = repl_tag($bt, $et, $src, $rwith, "add_tag: ($adata)");
	return (-1, '') if ($rs != 0);

	push_el(\@main::el, 'add_tag()', 'Ending...');

	return (0, $rdata);
}

sub del_tag {

	push_el(\@main::el, 'del_tag()', 'Starting...');

	my ($bt, $et, $src) = @_;

	my ($rs, $rdata) = get_tag($bt, $et, $src);
	return ($rs, $src) if ($rs != 0);

	($rs, $rdata) = repl_tag($bt, $et, $src, '', 'del_tag');
	return (-1, '') if ($rs != 0);

	push_el(\@main::el, 'del_tag()', 'Ending...');

	return (0, $rdata);
}

sub get_var {

	my ($var, $src) = @_;

	push_el(\@main::el, 'get_var()', 'Starting...');

	my ($rs, $rdata) = get_tag($var, $var, $src);

	return ($rs, '') if ($rs != 0);

	push_el(\@main::el, 'get_var()', 'Ending...');

	return (0, $rdata);
}

sub repl_var {

	push_el(\@main::el, 'repl_var()', 'Starting...');

	my ($var, $src, $rwith) = @_;
	my ($rs, $rdata, $result) = (0, $src, '');

	while ($rs == 0) {
		$result = $rdata;

		($rs, $rdata) = repl_tag($var, $var, $rdata, $rwith, "repl_var: $var");
		return -1 if ($rs != 0 && $rs != -4);
	}

	push_el(\@main::el, 'repl_var()', 'Ending...');

	return (0, $result);
}

sub add_var {

	push_el(\@main::el, 'add_var()', 'Starting...');

	my ($var, $src, $adata) = @_;

	my ($rs, $rdata) = add_tag($var, $var, $src, $adata);
	return -1 if ($rs != 0);

	push_el(\@main::el, 'add_var()', 'Ending...');

	return (0, $rdata);
}

sub del_var {

	push_el(\@main::el, 'del_var()', 'Starting...');

	my ($var, $src) = @_;

	my ($rs, $rdata) = repl_var($var, $src, '');
	return -1 if ($rs != 0);

	push_el(\@main::el, 'del_var()', 'Ending...');

	return ($rs, $rdata);
}

sub get_tpl {

	push_el(\@main::el, 'get_tpl()', 'Starting...');

	my $tpl_dir = $_[0];
	my @tpls = @_;
	my ($rs, $rdata, $tpl_file) = ('', '', '');
	my @res = (0);

	if (scalar(@tpls) < 2) {
		push_el(
			\@main::el, 'get_tpl()', "[ERROR] Template filename(s) missing !"
		);

		return (-1, '');
	}

	shift(@tpls);

	foreach (@tpls) {
		$tpl_file = $_;

		($rs, $rdata) = get_file("$tpl_dir/$tpl_file");
		return (-1, '') if ($rs != 0);

		push (@res, $rdata);
	}

	push_el(\@main::el, 'get_tpl()', 'Ending...');

	return @res;
}

sub prep_tpl {

	push_el(\@main::el, 'prep_tpl()', 'Starting...');

	my $hash_ptr = $_[0];
	my @tpls = @_;
	my ($rs, $rdata) = ('', '', '');
	my @res = (0);

	if (scalar(@tpls) < 2) {
		push_el(
			\@main::el, 'prep_tpl()', "[ERROR] Template variable(s) missing !"
		);

		return (-1, '');
	}

	shift(@tpls);

	my ($i, $key) = ('', '');

	for($i = 0; $i < scalar(@tpls); $i++) {
		foreach $key (keys %$hash_ptr) {
			my $name = $key;
			my $value = $hash_ptr -> {$key};

			($rs, $rdata) = repl_var($name, $tpls[$i], $value);
			return (-1, '') if ($rs != 0);

			$tpls[$i] = $rdata;
		}

		push (@res, $tpls[$i]);
	}

	push_el(\@main::el, 'prep_tpl()', 'Ending...');

	return @res;
}

################################################################################
# Should be documented
#
# @return int 0 on success, -1 otherwise
#
sub lock_system {

	push_el(\@main::el, 'lock_system()', 'Starting...');

	if(!open($main::fh_lock_file, '>', $main::lock_file)) {
		push_el(
			\@main::el, 'lock_system()', '[ERROR] Unable to open lock file!'
		);

		return -1;
	}

	# Import LOCK_* constants.
	use Fcntl ":flock";

	if(!flock($main::fh_lock_file, LOCK_EX)) {
		push_el(
			\@main::el, 'lock_system()','[ERROR] Unable to acquire global lock!'
		);

		return -1;
	}

	push_el(\@main::el, 'lock_system()', 'Ending...');

	0;
}

sub connect_imscp_daemon {

	push_el(\@main::el, 'connect_imscp_daemon()', 'Starting...');

	my $fd = IO::Socket::INET -> new(
		Proto => 'tcp',
		PeerAddr => '127.0.0.1',
		PeerPort => '8668'
	);

	if (!defined $fd) {

		push_el(
			\@main::el,
			'connect_imscp_daemon()',
			"[ERROR] Can't connect to I-MSCP license daemon !"
		);

		return (-1, '');
	}

	push_el(\@main::el, 'connect_imscp_daemon()', 'Ending...');

	return (0, $fd);
}

sub recv_line {

	push_el(\@main::el, 'recv_line()', 'Starting...');

	my ($fd) = @_;

	my ($res, $row, $ch) = (undef, undef, undef, undef);

	do {
		$res = recv($fd, $ch, 1, 0);

		if (!defined $res) {
			push_el(
				\@main::el, 'recv_line()', '[ERROR] unexpected IO problems !'
			);

			return (-1, '');

		}

		$row .= $ch;

	} while ($ch ne "\n");

	push_el(\@main::el, 'recv_line()', 'Ending...');

	return (0, $row);
}

sub send_line {

	push_el(\@main::el, 'send_line()', 'Starting...');

	my ($fd, $line) = @_;
	my ($i, $res, $ch) = (undef, undef, undef);

	for ($i = 0; $i < length($line); $i++) {
		$ch = substr($line, $i, 1);
		$res = send($fd, $ch, 0);

		if (!defined $res) {
			push_el(
				\@main::el, 'send_line()', "[ERROR] unexpected IO problems !"
			);

			return (-1, '');
		}
	}

	push_el(\@main::el, 'send_line()', 'Ending...');

	return (0, '');
}

sub close_imscp_daemon {

	push_el(\@main::el, 'close_imscp_daemon()', 'Starting...');

	my ($fd) = @_;

	close($fd);

	push_el(\@main::el, 'close_imscp_daemon()', 'Ending...');
}

sub license_request {

	push_el(\@main::el, 'license_query()', 'Starting...');

	my ($rs, $rdata) = connect_imscp_daemon();
	return ($rs, $rdata) if ($rs != 0);

	my $fd = $rdata;

	# Welcome message;

	($rs, $rdata) = recv_line($fd);
	return ($rs, $rdata) if ($rs != 0);

	# 'helo' cmd;

	my $helo_cmd = "helo $main::cfg{'SERVER_HOSTNAME'}\r\n";

	($rs, $rdata) = send_line($fd, $helo_cmd);
	return ($rs, $rdata) if ($rs != 0);

	($rs, $rdata) = recv_line($fd);
	return ($rs, $rdata) if ($rs != 0);

	# 'license request' cmd';

	my $request_cmd = "license request\r\n";

	($rs, $rdata) = send_line($fd, $request_cmd);
	return ($rs, $rdata) if ($rs != 0);

	($rs, $rdata) = recv_line($fd);
	return ($rs, $rdata) if ($rs != 0);

	my $res = $rdata;

	if ($res =~ /^250 OK ([^\r]+)\r\n$/) {
		$rdata = $1;
		$main::working_license = $1;
	}


	# 'bye' cmd;

	($rs, $rdata) = send_line($fd, "bye\r\n");
	($rs, $rdata) = recv_line($fd);

	close_imscp_daemon($fd);

	push_el(\@main::el, 'license_query()', 'Ending...');

	return (0, $main::working_license);
}

sub check_master {

	if (defined $main::engine_debug) {
		push_el(\@$main::el, 'check_master()', 'Starting...');
	}

	sys_command_rs(
		"export COLUMNS=120;/bin/ps auxww | awk '\$0 ~ /$main::master_name/ " .
			"&& \$0 !~ /awk/ { print \$2 ;}' 1>$main::cc_stdout 2>$main::cc_stderr"
	);

	if (-z $main::cc_stdout) {
		del_file($main::cc_stdout);
		del_file($main::cc_stderr);

		push_el(
			\@main::el, 'check_master()',
			'[ERROR] Master manager process is not running !'
		);

		return -1;
	}

	del_file($main::cc_stdout);
	del_file($main::cc_stderr);

	if (defined$main::engine_debug) {
		push_el(\@$main::el, 'check_master()', 'Ending...');
	}

	0;
}

sub decrypt_db_password {

	push_el(\@main::el, 'decrypt_db_password()', 'Starting...');

	my ($pass) = @_;

	if (!defined $pass || $pass eq '') {
		push_el(
			\@main::el, 'decrypt_db_password()', '[ERROR] Undefined input data...'
		);

		return (1, '');
	}

	if (length($main::db_pass_key) != 32 || length($main::db_pass_iv) != 8) {
		push_el(
			\@main::el, 'decrypt_db_password()',
			'[ERROR] KEY or IV has invalid length'
		);

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

################################################################################
# Setup the global database variables and redefines the DSN
#
# @return int 0
#
sub setup_main_vars {

	push_el(\@main::el, 'setup_main_vars()', 'Starting...');

	#
	# Database backend vars;
	#

	$main::db_host = $main::cfg{'DATABASE_HOST'};
	$main::db_user = $main::cfg{'DATABASE_USER'};
	$main::db_pwd  = $main::cfg{'DATABASE_PASSWORD'};
	$main::db_name = $main::cfg{'DATABASE_NAME'};

	if ($main::db_pwd ne '') {
		my $rs = undef;

		($rs, $main::db_pwd) = decrypt_db_password($main::db_pwd);

		# Silently quit
		return 0 if ($rs != 0);
	}

	@main::db_connect = (
		"DBI:mysql:$main::db_name:$main::db_host", $main::db_user, $main::db_pwd
	);

	# Forcing reconnection
	$main::db = undef;

	push_el(\@main::el, 'setup_main_vars()', 'Ending...');

	0;
}

################################################################################
# Load all configuration parameters from a specific configuration file
#
# This subroutine load all configuration parameters from a specific file where
# each of them are represented by a pair of key/value separated by the equal
# sign.
#
# This subroutine also calls the setup_main_vars() subroutine that setup all the
# global database variables and redefines the DSN.
#
# @param [scalar $file_name filename from where the configuration must be loaded]
# Default value is the main i-MSCP configuration file (imscp.conf)
# @return int 0 on success, 1 otherwise
#
sub get_conf {

	push_el(\@main::el, 'get_conf()', 'Starting...');

	my $file_name;

	if ( defined $_[0] ) {
		$file_name = $_[0];
	} else {
		$file_name = $main::cfg_file;
	}

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

	return -1 if (setup_main_vars() != 0);

	push_el(\@main::el, 'get_conf()', 'Ending...');

	0;
}

################################################################################
# Store a configuration parameter in the global $main::cfg_reg hash
#
# The $main::cfg_reg global hash is used by the store_conf() subroutine to
# update the configuration parameters in a specific file.
#
# Note: For now, it's useless to add new configuration parameters that don't
# already exist in the updated configuration file due to the current
# implementation of the store_conf() subroutine that don't allows that.
#
# @param scalar $name Configuration parameter key
# @param scalar $value Configuration parameter value
# @return int 0 on success, 1 otherwise
#
sub set_conf_val {

	my ($name, $value) = @_;

	push_el(\@main::el, 'set_conf_val()', 'Starting...');

	if (!defined $name || $name eq '') {
		push_el(\@main::el, 'set_conf_val()', '[ERROR] Undefined input data...');

		return 1;
	}

	$main::cfg_reg{$name} = $value;

	push_el(\@main::el, 'set_conf_val()', 'Ending...');

	0;
}

################################################################################
# Store all cached configuration parameters in the imscp.conf file
#
# This function updates the configuration settings to a file with those stored
# in the global $main::cfg_reg hash . Only parameters that have a different
# value are updated.
#
# Note:
#
# This subroutine is currently not able to add configuration settings that do
# not exist in the configuration file.
#
# @param [scalar optional filename where the configuration must be stored]
# Default value is the main i-MSCP configuration file (imscp.conf)
# @return int 0 on success, 1 otherwise
#
sub store_conf {

	push_el(\@main::el, 'store_conf()', 'Starting...');

	my ($key, $value, $rwith, $file_name);

	if (defined $_[0]) {
		$file_name = $_[0];
	} else {
		$file_name = $main::cfg_file;
	}

	my ($rs, $fline) = get_file($file_name);
	return 1 if ($rs != 0);

	if (scalar(keys(%main::cfg_reg)) > 0) {
		while (($key, $value) = each %main::cfg_reg) {
			$value = '' if !defined $value;
			$rwith = "$key = $value\n";
			$fline =~ s/^$key *= *([^\n\r]*)[\n\r]/$rwith/gim;
		}
	}

	$rs = store_file($file_name, $fline, 'root', 'null', 0644);
	return 1 if ($rs != 0);

	$rs = get_conf($file_name);
	return 1 if ($rs != 0);

	push_el(\@main::el, 'store_conf()', 'Ending...');

	0;
}

sub get_domain_ids {

	my ($rs, $rows, $sql) = ('', '', '');

	$sql = "
		SELECT
			`domain_id`, `domain_name`, `domain_ip_id`
		FROM
			`domain`
		ORDER BY
			`domain_id`
		;
	";

	($rs, $rows) = doSQL($sql);
	return $rs if ($rs != 0);

	foreach (@$rows) {
		$main::domain_id_name{@$_[0]} = @$_[1];
		$main::domain_name_id{@$_[1]} = @$_[0];
		$main::domain_id_ipid{@$_[0]} = @$_[2];
	}

	0;
}

sub get_subdom_ids {

	my ($rs, $rows, $sql) = ('', '', '');

	$sql = "
		SELECT
			`subdomain_id`, `subdomain_name`, `domain_id`
		FROM
			`subdomain`
		ORDER BY
			subdomain_id
		;
	";

	($rs, $rows) = doSQL($sql);
	return $rs if ($rs != 0);

	foreach (@$rows) {
		$main::sub_id_name{@$_[0]} = @$_[1];
		$main::sub_name_id{@$_[1]} = @$_[0];
		$main::sub_id_parentid{@$_[0]} = @$_[2];
	}

	0;
}

sub get_alssub_ids {

    my ($rs, $rows, $sql) = ('', '', '');

    $sql = "
    	SELECT
    		`subdomain_alias_id`, `subdomain_alias_name`,  `alias_id`
    	FROM
    		`subdomain_alias`
    	ORDER BY
    		`subdomain_alias_id`;
    ";

	($rs, $rows) = doSQL($sql);
	return $rs if ($rs != 0);

	foreach (@$rows) {
		$main::alssub_id_name{@$_[0]} = @$_[1];
		$main::alssub_name_id{@$_[1]} = @$_[0];
		$main::alssub_id_parentid{@$_[0]} = @$_[2];
	}

	0;
}

sub get_alias_ids {

	my ($rs, $rows, $sql) = ('', '', '');

	$sql = "
		SELECT
			 `alias_id`, `domain_id`, `alias_name`
		FROM
			`domain_aliasses`
		ORDER BY
			`alias_id`
		;
	";

	($rs, $rows) = doSQL($sql);
	return $rs if ($rs != 0);

	foreach (@$rows) {
		$main::als_id_name{@$_[0]} = @$_[2];
		$main::als_name_id{@$_[2]} = @$_[0];
		$main::als_id_parentid{@$_[0]} = @$_[1];
	}

	0;
}

sub get_ip_nums {

	my ($rs, $rows, $sql) = ('', '', '');

	$sql = "
		SELECT
			`ip_id`, `ip_number`
		FROM
			`server_ips`
		ORDER BY
			`ip_id`
		;
	";

	($rs, $rows) = doSQL($sql);
	return $rs if ($rs != 0);

	foreach (@$rows) {
		$main::ip_id_num{@$_[0]} = @$_[1];
		$main::ip_num_id{@$_[1]} = @$_[0];
	}

	0;
}

sub get_el_error {

	push_el(\@main::el, 'get_el_error()', 'Starting...');

	my ($fname) = @_;
	my ($rs, $rdata) = (undef, undef);

	($rs, $rdata) = get_file($fname);
	return $rs if ($rs != 0);

	my @frows = split(/\n/, $rdata);
	my $err_row = "$frows[0]\n";

	$err_row =~ /\|\ *([^\n]+)\n$/;

	$rdata = $1;

	push_el(\@main::el, 'get_el_error()', 'Ending...');

	return (0, $rdata);
}

sub get_human_date {

	push_el(\@main::el, 'get_human_date()', 'Starting...');

	my ($sec, $min, $hour,$mday, $mon, $year,$wday, $yday) = localtime;

	$year += 1900;
	$mon += 1;
	$mon = '0'.$mon if ($mon < 10);
	$mday = '0'.$mday if ($mday < 10);
	$hour = '0'.$hour if ($hour < 10);
	$min = '0'.$min if ($min < 10);
	$sec = '0'.$sec if ($sec < 10);

	push_el(\@main::el, 'get_human_date()', 'Ending...');

	return "$year.$mon.$mday-$hour$min$sec";
}

sub check_uid_gid_available {

	push_el(\@main::el, 'check_uid_gid_available()', 'Starting...');

	my ($sys_uid, $sys_gid) = @_;
	my $name = undef;
	my $max_uid = $main::cfg{'APACHE_SUEXEC_MAX_UID'};
	my $max_gid = $main::cfg{'APACHE_SUEXEC_MAX_GID'};

	if($sys_uid > $max_uid){
		push_el(
			\@main::el, 'check_uid_gid_available()',
			"[ERROR] Maximum user id for this system is reached!"
		);

		return (2, $sys_uid, $sys_gid);
	}

	if($sys_gid > $max_gid){
		push_el(
			\@main::el, 'check_uid_gid_available()',
			"[ERROR] Maximum group id for this system is reached!"
		);

		return (2, $sys_uid, $sys_gid);
	}

	$name = getgrgid($sys_gid);

	if( defined($name) ) {
		push_el(
			\@main::el, 'check_uid_gid_available()',
			"[NOTICE] Group id $sys_gid already in use!"
		);

		return (1, $sys_uid, $sys_gid);
	}

	$name = getpwuid($sys_uid);

	if ( defined($name) ) {
		push_el(
			\@main::el, 'check_uid_gid_available()',
			"[NOTICE] User id $sys_uid already in use!"
		);

		return (1, $sys_uid, $sys_gid);
	}

	push_el(\@main::el, 'check_uid_gid_available()', 'Ending...');

	return (0, $sys_uid, $sys_gid);
}

sub add_dmn_suexec_user {

	push_el(\@main::el, 'add_dmn_suexec_user()', 'Starting...');

	my ($dmn_data) = @_;

	my ($suexec_min_uid, $suexec_min_gid) = (
		$main::cfg{'APACHE_SUEXEC_MIN_UID'},
		$main::cfg{'APACHE_SUEXEC_MIN_GID'}
	);

	my ($dmn_uid, $dmn_gid) = (@$dmn_data[3], @$dmn_data[2]);
	my $dmn_id = @$dmn_data[0];
	my ($rs, $rdata, $sql) = (undef, undef, undef);
	my ($num, $sys_uid, $sys_gid) = (undef, undef, undef);

	if ($dmn_uid == 0 && $dmn_gid == 0) {
		do{
			$num = get_auto_num();

			($sys_uid, $sys_gid) = (
				$suexec_min_uid + $num,
				$suexec_min_gid + $num
			);

			($rs, $sys_uid, $sys_gid) = check_uid_gid_available(
				$sys_uid,
				$sys_gid
			);
		} while ($rs == 1);

		return $rs if ($rs != 0);

		my $suexec_user_pref = $main::cfg{'APACHE_SUEXEC_USER_PREF'};
		my $sys_user = "$suexec_user_pref$sys_uid";
		my $sys_group = "$suexec_user_pref$sys_gid";
		my $cmd = undef;

		# group data - BSD has another format:
		# BSD/NUX Command
		if ($main::cfg{'ROOT_GROUP'} eq 'wheel') {
			$cmd = "$main::cfg{'CMD_GROUPADD'} $sys_group -g $sys_gid";
		} else {
			$cmd = "$main::cfg{'CMD_GROUPADD'} -g $sys_gid $sys_group";
		}

		$rs = sys_command($cmd);
		return $rs if ($rs != 0);

		# user data.

		# SSH/SCP Useraccount preperation
		my $homedir = "$main::cfg{'APACHE_WWW_DIR'}/@$dmn_data[1]";

		# BSD has another format:
		# BSD/NUX Command
		if ($main::cfg{'ROOT_GROUP'} eq 'wheel') {
			$cmd = "$main::cfg{'CMD_USERADD'} $sys_user -c virtual-user -d " .
				"$homedir -g $sys_group -s /bin/false -u $sys_uid";
		} else {
			$cmd = "$main::cfg{'CMD_USERADD'} -c virtual-user -d $homedir -g " .
				"$sys_group -s /bin/false -u $sys_uid $sys_user";
		}

		$rs = sys_command($cmd);
		return $rs if ($rs != 0);

		$sql = "
			UPDATE
				`domain`
			SET
				`domain_uid` = '$sys_uid', `domain_gid` = '$sys_gid'
			WHERE
				`domain_id` = $dmn_id
			;
		";

		($rs, $rdata) = doSQL($sql);
		return $rs if ($rs != 0);
	}

	push_el(\@main::el, 'add_dmn_suexec_user()', 'Ending...');

	0;
}

sub get_dmn_suexec_user {

	push_el(\@main::el, 'get_dmn_suexec_user()', 'Starting...');

	my ($dmn_id) = @_;

	my $sql = "
		SELECT
			`domain_uid`, `domain_gid`
		FROM
			`domain`
		WHERE
			`domain_id` = $dmn_id
		;
	";

	my ($rs, $rdata) = doSQL($sql);
	return ($rs, $rdata) if ($rs != 0);

	my $row = @$rdata[0];

	push_el(\@main::el, 'get_dmn_suexec_user()', 'Ending...');

	return (@$row[0], @$row[1]);
}

sub del_dmn_suexec_user {

	push_el(\@main::el, 'del_dmn_suexec_user()', 'Starting...');

	my ($dmn_data) = @_;
	my $dmn_id = @$dmn_data[0];
	my ($sys_uid, $sys_gid) = get_dmn_suexec_user($dmn_id);
	my $suexec_user_pref = $main::cfg{'APACHE_SUEXEC_USER_PREF'};
	my $sys_user = "$suexec_user_pref$sys_uid";
	my $sys_group = "$suexec_user_pref$sys_gid";
	my ($dmn_uid, $dmn_gid) = (@$dmn_data[3], @$dmn_data[2]);
	my ($rs, $rdata, $sql, $cmd) = (undef, undef, undef, undef);

	if ($dmn_uid != 0 && $dmn_gid != 0) {
		my @udata = ();
		my @gdata = ();
		@udata = getpwnam($sys_user);

		# we must remove it from the system
		if (scalar(@udata) != 0) {
			$cmd = "$main::cfg{'CMD_USERDEL'} $sys_user";

			$rs = sys_command($cmd);
			return $rs if ($rs != 0);
		}

		@gdata = getgrnam($sys_group);

		# we have not this one group data;
		if (scalar(@gdata) != 0) {
			$cmd = "$main::cfg{'CMD_GROUPDEL'} $sys_group";

			$rs = sys_command($cmd);
			return $rs if ($rs != 0);
		}

		$sql = "
			UPDATE
				`domain`
			SET
				`domain_uid` = '0', `domain_gid` = '0'
			WHERE
				`domain_id` = $dmn_id
			;
		";

		($rs, $rdata) = doSQL($sql);
		return $rs if ($rs != 0);
	}

	push_el(\@main::el, 'del_dmn_suexec_user()', 'Ending...');

	0;
}

sub sort_domains {

	my @domains = @_;
	my $len = scalar(@domains);
	my ($i, $dmn) = (undef, undef);

	for (($i, $dmn) = (0, ''); $i < $len; $i++) {
		$dmn = $domains[$i];
		$dmn=join(".",reverse(split(/\./,$dmn)));
		$domains[$i] = $dmn;
	}

	@domains = sort(@domains);

	for (($i, $dmn) = (0, ''); $i < $len; $i++) {
		$dmn = $domains[$i];
		$dmn=join(".",reverse(split(/\./,$dmn)));
		$domains[$i] = $dmn;
	}

	return reverse(@domains);
}

################################################################################
## Get a serial number generated according RFC 1912
##
## This subroutine can be used both to get and update serial number. $src must
## contains the SN tag that must be replaced. $wrkFile must contains the current
## SN tag. In case  where the SN tag was never generated, $wrkFile should
## contains the prepared SN tag like:
##
## ; dmn [imscp.net] timestamp entry BEGIN.
##                {TIMESTAMPS}      ; Serial
## ; dmn [imscp.net] timestamp entry END.
##
## @author  Laurent Declercq <laurent.declercq@i-mscp.net>
## @since   1.0.7
## @version 1.0.3
## @param   scalarref $dmnName Domain name
## @param   scalarref $src String that contains SN tag to be replaced
## @param   scalarref|refscalarref $wrkFile String that contains current SN tag
## @return  int on success, negative int otherwise
#
sub getSerialNumber {

	push_el(\@main::el, 'getSerialNumber()', 'Begin...');

	my ($dmnName, $src, $wrkFile) = @_;

	if (!defined $dmnName || $dmnName eq '' || !defined $src || $src eq '' ||
		!defined $wrkFile || $wrkFile eq '') {

		push_el(\@main::el, 'getSerialNumber()', '[FATAL] Undefined args!');

		return -1;
	} elsif(ref $dmnName eq '' || ref $src eq '' || ref $wrkFile eq '') {
		push_el(
			\@main::el, 'getSerialNumber()', '[FATAL] Args must be references!'
		);

		return -1;
	} elsif(ref $wrkFile eq 'REF') {
		# We ensure that we work with a scalar reference (and not a ref to ref)
		$wrkFile = $$wrkFile;
	}

	my ($rs, $tagB, $tagE, $serial);

	# Get Begin/End SN templates tag
	($rs, $tagB, $tagE) = get_tpl(
		"$main::cfg{CONF_DIR}/bind/parts", 'db_time_b.tpl', 'db_time_e.tpl'
	);
	return -1 if($rs != 0);

	# Build Begin/End SN tag for the current domain name
	($rs, $tagB, $tagE) = prep_tpl({'{DMN_NAME}' => $$dmnName}, $tagB, $tagE);
	return -1 if($rs != 0);

	# Get the SN tag from working file
	($rs, $serial) = get_tag($tagB, $tagE, $$wrkFile, 'getSerialNumber()');
	return -1 if($rs != 0);

	# Get current date (ex. 20100703)
	my ($sec, $min, $hour, $mday, $mon, $year) = localtime;
	my $curDate = sprintf '%4d%02d%02d', $year+1900, $mon+1, $mday;

	# Build serial number

	my $regExp = '[\s](?:(\d{4})(\d{2})(\d{2})(\d{2})|\{TIMESTAMP\})';

	if(($year, $mon, $mday, my $nn) = ($serial =~ /$regExp/)) {
		if(defined $nn) {
			if($nn >= 99 && $curDate <= "$year$mon$mday") {
				push_el(
					\@main::el, 'getSerialNumber()',
					"[NOTICE] $$dmnName: Maximum number of modifications is " .
					'reached! +1 day added to avoid any problems.'
				);

				use POSIX qw /mktime/;

				(undef, undef, undef, $mday, $mon, $year) = localtime(
					mktime($sec, $min, $hour, $mday, $mon-1, $year-1900) + 86400
				);

				$serial = sprintf '%4d%02d%02d00', $year+1900, $mon+1, $mday;
			} else {
				$serial = ($curDate <= "$year$mon$mday")
					? "$year$mon$mday$nn"+1 : "${curDate}00";
			}
		} else {
			$serial = "${curDate}00";
		}
	} else {
		push_el(
			\@main::el, 'getSerialNumber()',
			"[FATAL] $$dmnName: Unable to generate new serial number!"
		);

		return -1;
	}

	# Create the new SN tag
	my $newTag = $tagB . "\t" x2 ."$serial\t; Serial\n$tagE";

	# Replaces the current SN tag with the newly created
	($rs, $$src) = repl_tag($tagB, $tagE, $$src, $newTag, 'getSerialNumber()');
	return $rs if ($rs != 0);

	push_el(\@main::el, 'getSerialNumber()', 'Ending...');

	0;
}

################################################################################
# Converts the domain part (right hand side, separated by an at sign) of an
# email address to ASCII (according RFC 3490).
#
# @author Laurent Declercq <laurent.declercq@i-mscp.net>
# @since 1.0.7 (rc2)
# @param scalaref $refEmail Email address
# @return int 1 on success, 0 otherwise
#
sub mailToASCII {

	my $refEmail = shift||'';

	return 0 if $refEmail eq '' || ref $refEmail ne 'SCALAR';

	my $email = $$refEmail;

	# Getting email length
	my $emailLength = length $email;

	# Split email address on local-part and domain part
	my $i = rindex $email, '@';

	# The delimiter '@' or one email part was not found ?
	return 0 if($i == -1 || $i == 0 || $emailLength == ++($i));

	# Retrieving local and domain part
	my ($localPart, $domain) = (
		substr($email, 0, --$i), substr($email, ++$i)
	);

	# Converting domain part to ASCII (Punycode)
	$domain = idn_to_ascii($domain, 'utf-8');

	$$refEmail = "$localPart\@$domain";

	1;
}

################################################################################
# Send error mail
#
# @param string $fname Function where the error occurred
# @param string $errmsg Error message to be sent
#
sub send_error_mail {

	my ($fname, $errmsg) = @_;

	push_el(\@main::el, 'send_error_mail()', 'Starting...');

	my $admin_email = $main::cfg{'DEFAULT_ADMIN_ADDRESS'};
	return if(!mailToASCII(\$admin_email));

	my $date = get_human_date();
	my $server_name = $main::cfg{'SERVER_HOSTNAME'};
	my $server_ip = $main::cfg{'BASE_SERVER_IP'};

	my $msg_data ="
Dear admin,

I'm an automatic email sent by your $server_name ($server_ip) server.

A critical error just was encountered while executing function $fname in $0.

Error encountered was:

=====================================================================
$errmsg
=====================================================================
";

    use Text::Wrap;
	$Text::Wrap::columns = 70;
    $msg_data = wrap('', '', $msg_data);

	my $out = new MIME::Entity;

	$out -> build(
		From => "$server_name ($server_ip) <$admin_email>",
		To => $admin_email,
		Subject => "[$date] i-MSCP Error report",
		Data => $msg_data,
		'X-Mailer' => "i-MSCP $main::cfg{'Version'} Automatic Error Messenger"
	);

	open MAIL, "| /usr/sbin/sendmail -t -oi";

	$out -> print(\*MAIL);

	close MAIL;

	push_el(\@main::el, 'send_error_mail()', 'Ending...');
}


################################################################################
## makepath
##
## Creates a directory path and set ownership and rights for newly created
## folders
##
## @author Daniel Andreca <sci2tech@gmail.com>
## @since   1.0.7
## @version 1.0.7
## @param	scalar $dname File Name
## @param	mixed $duid	Linux User or UserID
## @param	mixed $dgid	Linux Group, GroupID or null
## @param	int $dperms	Linux Permissions
## @return	int	0 on success, -1 otherwise
#
sub makepath {

	push_el(\@main::el, 'makepath()', 'Starting...');

	my ($dname, $duid, $dgid, $dperms) = @_;

	if (!defined $dname || !defined $duid || !defined $dgid || !defined $dperms
		|| $dname eq '' || $duid eq '' || $dgid eq '' || $dperms eq '' ) {
		push_el(
			\@main::el, 'make_path()',
			"[ERROR] Undefined input data, dname: |$dname|, duid: |$duid|, " .
				"dgid: |$dgid|, dperms: |$dperms| !"
		);

		return -1;
	}

	if (-e $dname && -f $dname) {
		push_el(
			\@main::el, 'makepath()',
			"[NOTICE] '$dname' exists as file ! removing file first..."
		);

		return -1 if del_file($dname);
	}

	if (!(-e $dname && -d $dname)) {
		push_el(
			\@main::el, 'makepath()',
			"[NOTICE] '$dname' doesn't exists as directory! creating..."
		);

		my @lines =  mkpath(
			$dname, {owner => $duid, group => $duid, mode => 0755}
		);

		if (!@lines) {
			push_el(
				\@main::el, 'makepath()',
				"[ERROR] mkpath() returned empty path!"
			);

			return -1;
		}
		foreach (@lines){
			return -1 if setfmode($_, $duid, $dgid, $dperms);
		}

	} else {
		push_el(
			\@main::el, 'makepath()',
			"[NOTICE] '$dname' exists ! Setting its permissions..."
		);

		return -1 if setfmode($dname, $duid, $dgid, $dperms);

	}

	push_el(\@main::el, 'makepath()', 'Ending...');

	0;
}


################################################################################
## get_domain_mount_points
##
## return a list with mounting points for aliases domains and subdomains for a
## domain
##
## @author Daniel Andreca <sci2tech@gmail.com>
## @since   1.0.7
## @version 1.0.7
## @param	int $dmn_id	domain id
## @return [0 on success |error code, list of mount points]
sub get_domain_mount_points {

	push_el(\@main::el, 'get_domain_mount_points()', 'Starting...');

	my ($dmn_id) = @_;

	my $sql = "
		SELECT
			`alias_id` AS 'id',
			`alias_mount` AS 'mount_point',
			'alias' AS 'type'
		FROM
			`domain_aliasses`
		WHERE
			`domain_id` = '$dmn_id'
		UNION
		SELECT
			`subdomain_id` AS 'id',
			`subdomain_mount` AS 'mount_point',
			'subdomain' AS 'type'
		FROM
			`subdomain`
		WHERE
			`domain_id` = '$dmn_id'
		UNION
		SELECT
			`subdomain_alias_id` AS 'id',
			`subdomain_alias_mount` AS 'mount_point',
			'alias_subdomain' AS 'type'
		FROM
			`subdomain_alias`
		WHERE
			`alias_id` = ANY (SELECT `alias_id` FROM `domain_aliasses` WHERE `domain_id` = '$dmn_id')
		ORDER BY `mount_point` ASC
		;
	";

	my ($rs, $rdata) = doHashSQL($sql, 'id');

	return (-1, '') if( $rs != 0 );

	push_el(\@main::el, 'get_domain_mount_points()', 'Ending...');

	return ($rs, $rdata);
}

################################################################################
## check_mount_point_in_use
##
## check if a mount point is shared
##
## @author Daniel Andreca <sci2tech@gmail.com>
## @since   1.0.7
## @version 1.0.7
## @param	string $dtype shared point to be deleted is used by
##  alias/subdomain/alias subdomain
## @param	int $did id of alias/subdomain/alias subdomain to be deleted
## @param	string $dmount_point Mount point to be deleted
## @param	array $data	list of shared point in use
## @return array list of shared mount point to be saved
sub check_mount_point_in_use {

	push_el(\@main::el, 'check_mount_point_in_use()', 'Starting...');

	my($dtype, $dmn_id, $did, $dmount_point, $data) = @_;

	my @to_save = ();

	while ( my($k, $v) = each %$data ) {

		my ($id, $mount_point, $type) = (@$v{'id'}, @$v{'mount_point'}, @$v{'type'});

		push_el(
			\@main::el, 'check_mount_point_in_use()',
			"Test $id ne $did or $type ne $dtype (we do not save folder that supose to be deleted)"
		);

		if(!(($id eq $did) && ($type eq $dtype))) {
			push_el(\@main::el, 'check_mount_point_in_use()', "ok. Continue...");

			push_el(
				\@main::el, 'check_mount_point_in_use()',
				"Test $mount_point or system folders are subfolder in $dmount_point (if we will not save it will be deleted)"
			);

			my @mpoints = (
				$mount_point, "$mount_point/backups", "$mount_point/cgi-bin",
				"$mount_point/disabled", "$mount_point/errors",
				"$mount_point/htdocs", "$mount_point/logs", "$mount_point/phptmp"
			);

			foreach my $mpoint (@mpoints){
				push_el(
					\@main::el, 'check_mount_point_in_use()',
					"Test $mpoint is subfolder in $dmount_point (if we will not save it will be deleted)"
				);

				if($mpoint =~ /^$dmount_point.*$/){
					push_el(
						\@main::el, 'check_mount_point_in_use()',
						'it is. Continue...'
					);

					my $save = 1;

					foreach(@to_save){
						push_el(
							\@main::el, 'check_mount_point_in_use()',
							"Test $mpoint is subfolder in a scheduled to save folder"
						);

						if($mpoint =~ /^$_.*$/){
							push_el(
								\@main::el, 'check_mount_point_in_use()',
								'yes. Not need to save again!'
							);

							$save = 0;
						}
					}

					if( $save != 0) {
						push_el(
							\@main::el, 'check_mount_point_in_use()',
							"Schedule to be saved: $mpoint"
						);

						push(@to_save, $mpoint);
					}
				}
			}
		}
	}

	push_el(\@main::el, 'check_mount_point_in_use()', 'Ending...');

	return @to_save;
}

###################################################################################
## save_as_temp_folder
##
## move content from a list of folders in temporary created folders
##
## @author Daniel Andreca <sci2tech@gmail.com>
## @since   1.0.7
## @version 1.0.7
## @param	String 	$path	path to user domain
## @param	array 	$to_save	list of shared mount point to be saved
## @return	int	0 on success, err code otherwise
## @return	hash	list of temporary folder as $temporary folder
## name => source folder
sub save_as_temp_folder {

	push_el(\@main::el, 'save_as_temp_folder()', 'Starting...');

	my ($path, @to_save )= @_;
	my %dirs = ();
	my ($rs, $dir) = (0, undef);

	foreach (@to_save) {

		eval{
			$dir = tempdir( DIR => $path, CLEANUP => 0 );
		};
		if ($@) {
			push_el(
				\@main::el, 'save_as_temp_folder()',
				"[ERROR] while creating temporary folder: $@"
			);
		}

		$dirs{$dir} = "$path$_";

		push_el(
			\@main::el, 'save_as_temp_folder()',
			"Mount point to be saved $_ in $dir"
		);

		$rs = move_dir_content("$path$_", $dir);
		return $rs if ($rs != 0);

	}

	push_el(\@main::el, 'save_as_temp_folder()', 'Ending...');

	return ($rs, %dirs);
}

################################################################################
## move_list_folder
##
## move content from a list of folders in folders provided by list
## and set owner and rights on folder created according to input parameters
##
## @author Daniel Andreca <sci2tech@gmail.com>
## @since   1.0.7
## @version 1.0.7
## @param	octal $perm	permissions
## @param	Mixed $duid	Linux User or UserID
## @param	Mixed $dgid	Linux Group, GroupID or null
## @param	hash %to_restore	list of folders to be restored as source
##  path => destination path
## @return	int	0 on success, err code otherwise
sub restore_list_folder {

	push_el(\@main::el, 'move_list_folder()', 'Starting...');

	my ($perm, $duid, $dgid, %to_restore) = @_;

	 while( my ($folder, $path) = each %to_restore ) {
		my $rs = makepath($path, $duid, $dgid, $perm);
		return $rs if ($rs != 0);

		$rs = move_dir_content("$folder", $path);
		return $rs if ($rs != 0);

		$rs = del_dir($folder);
		return $rs if ($rs != 0);
	}

	push_el(\@main::el, 'move_list_folder()', 'Ending...');

	0;
}

################################################################################
## move_dir_content
##
## move content of source folder in destination (not source folder itself)
##
## @author Daniel Andreca <sci2tech@gmail.com>
## @since   1.0.7
## @version 1.0.7
## @param	String 	$source	Source folder
## @param	String 	$destination	Destination folder
## @return	int	0 on success, -1 otherwise

sub move_dir_content{

	push_el(\@main::el, 'move_dir_content()', 'Starting...');

	my ($source, $destination) = @_;

	push_el(
		\@main::el, 'move_dir_content()',
		"Trying to move content of $source in $destination"
	);

	my @folders = <$source/*>;

	if (@folders){ #move only if not empty
		my @res = rcopy("$source/*", "$destination");

		if(!@res){
			push_el(
				\@main::el, 'move_dir_content()',
				"[ERROR] Failed to move content of $source in $destination!"
			);

			return -1;
		}
	}

	push_el(\@main::el, 'move_dir_content()', 'Ending...');

	0;
}

################################################################################
## get_config_from_db
##
## return the $key=$value from table config in the DB
##
##
## @author hannes@cheat.at
## @since   1.0.1.5
## @version 1.0.1.5
## @return [0 on success |error code, list of $key=$value]
sub get_config_from_db {

        push_el(\@main::el, 'get_config_from_db()', 'Starting...');

        my $sql = "
                SELECT
                        `name`,`value`
                FROM
                        `config`
                ;
        ";

        my ($rs, $rdata) = doHashSQL($sql,'name');
        return (-1, '') if( $rs != 0 );

        push_el(\@main::el, 'get_config_from_db()', 'Ending...');

        return ($rdata);
}

################################################################################
## get_custom_php_ini_from_db
##
## return the array_ref.array_ref from table php_ini in the DB
##
##
## @author hannes@cheat.at
## @since   1.0.1.5
## @version 1.0.1.5
## @return [0 on success |error code, list of array_ref.array_ref]
sub get_custom_php_ini_from_db {

	$dmn_id = shift;

        push_el(\@main::el, 'get_custom_php_ini_from_db()', 'Starting...');

        my $sql = "
                SELECT
                        *
                FROM
                        `php_ini`
                WHERE
                        `domain_id` = '$dmn_id'
                ;
        ";

        my ($rs, $rdata) = doSQL($sql);
        return (-1, '') if( $rs != 0 );

        push_el(\@main::el, 'get_custom_php_ini_from_db()', 'Ending...');
	if (!@$rdata[0]){
		return 0;
	} else {
	        return ($rdata);
	}
}
