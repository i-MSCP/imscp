#!/usr/bin/perl

# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (c) 2001-2006 by moleSoftware GmbH
# http://www.molesoftware.com
# Copyright (c) 2006-2007 by isp Control Panel
# http://isp-control.net
#
#
# License:
#    This program is free software; you can redistribute it and/or
#    modify it under the terms of the MPL Mozilla Public License
#    as published by the Free Software Foundation; either version 1.1
#    of the License, or (at your option) any later version.
#
#    This program is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    MPL Mozilla Public License for more details.
#
#    You may have received a copy of the MPL Mozilla Public License
#    along with this program.
#
#    An on-line copy of the MPL Mozilla Public License can be found
#    http://www.mozilla.org/MPL/MPL-1.1.html
#
#
# The ISPCP ω Home Page is at:
#
#    http://isp-control.net
#

BEGIN {

    my @needed = (strict,
                  warnings,
                  IO::Socket,
                  DBI,
                  DBD::mysql,
                  MIME::Entity,
                  MIME::Parser,
                  Crypt::CBC,
                  Crypt::Blowfish,
                  Crypt::PasswdMD5,
                  MIME::Base64,
                  Term::ReadPassword,
                  File::Basename,
                  File::Path);

    my ($mod, $mod_err, $mod_missing) = ('', '_off_', '');

    for $mod (@needed) {

        if (eval "require $mod") {

            $mod -> import();

        } else {

            print STDERR "\nCRITICAL ERROR: Module [$mod] WAS NOT FOUND !\n" ;

            $mod_err = '_on_';

            if ($mod_missing eq '') {

                $mod_missing .= $mod;

            } else {

                $mod_missing .= ", $mod";

            }
        }

    }

    if ($mod_err eq '_on_') {

        print STDERR "\nModules [$mod_missing] WAS NOT FOUND in your system...\n";

        exit 1;

    } else {

        $| = 1;

    }
}


#$main::engine_debug = '_on_';

$main::cc_stdout = '/tmp/ispcp-cc.stdout';

$main::cc_stderr = '/tmp/ispcp-cc.stderr';

$main::el_sep = "\t#\t";

@main::el = ();


sub push_el {

    my ($el, $sub_name, $msg) = @_;

    push @$el, "$sub_name".$main::el_sep."$msg";

    if (defined($main::engine_debug)) {

        print STDOUT "DEBUG: push_el() sub_name: $sub_name, msg: $msg\n";

    }


}

sub pop_el {

    my ($el) = @_;

    my $data = pop @$el;

    if (!defined($data)) {

        if (defined($main::engine_debug)) {

            print STDOUT "DEBUG: pop_el() Empty 'EL' Stack !\n";

        }

        return undef;
    }

    my ($sub_name, $msg) = split(/$main::el_sep/, $data);

    if (defined($main::engine_debug)) {

        print STDOUT "DEBUG: pop_el() sub_name: $sub_name, msg: $msg\n";

    }


    return $data;

}


sub dump_el {

    my ($el, $fname) = @_;

    my $res;

    if ($fname ne 'stdout') {

        $res = open(FP, ">", $fname);

        if (!defined($res)) {

            return 0;

        }

    }

    my $el_data = undef;

    #
    #if ($fname eq 'stdout') {
    #
    #    print STDOUT "%-20s | %s\n",  ' function', 'message';
    #
    #    print STDOUT "---------------------|---------------------------------------------------------------\n";
    #
    #} else {
    #
    #    print FP "%-20s | %s\n",  ' function', 'message';
    #
    #    print FP "---------------------|---------------------------------------------------------------\n";
    #
    #}
    #

    while (defined($el_data = pop_el(\@main::el))) {

        my ($sub_name, $msg) = split(/$main::el_sep/, $el_data);

        if ($fname eq 'stdout') {

            printf STDOUT "%-30s | %s\n",  $sub_name, $msg;

        } else {

            printf FP "%-30s | %s\n",  $sub_name, $msg;

        }

    }

    close(FP);

}

# Global variables;

$main::db_host = undef;

$main::db_user = undef;

$main::db_pwd = undef;

$main::db_name = undef;

@main::db_connect = ();

$main::db = undef;

#

sub doSQL {

    my ($sql) = @_;

    my $qr = undef;

    push_el(\@main::el, 'doSQL()', 'Starting...');

    if (!defined($sql) || ($sql eq '')) {

        push_el(\@main::el, 'doSQL()', 'ERROR: Undefined SQL query !');

        return (-1, '');

    }

    if (!defined($main::db) || !ref($main::db)) {

        $main::db = DBI -> connect(@main::db_connect, {PrintError => 0});

        if ( !defined($main::db) ) {

            push_el(
                    \@main::el,
                    'doSQL()',
                    'ERROR: Unable to connect SQL server !'
                   );

            return (-1, '');

        }
    }

    if ($sql =~ /select/i) {

        $qr = $main::db -> selectall_arrayref($sql);

    } elsif ($sql =~ /show/i) {

        $qr = $main::db -> selectall_arrayref($sql);

    } else {

        $qr = $main::db -> do($sql);

    }

    if (defined($qr)) {

        push_el(\@main::el, 'doSQL()', 'Ending...');

        return (0, $qr);

    } else {

        push_el(\@main::el, 'doSQL()', 'ERROR: Incorrect SQL Query -> '.$main::db -> errstr);

        return (-1, '');

    }

}

sub setfmode {

    my ($fname, $fuid, $fgid, $fperms) = @_;

    push_el(\@main::el, 'setfmode()', 'Starting...');

    if (
        !defined($fname) || !defined($fuid) ||
        !defined($fgid) || !defined($fperms) ||
        $fname eq '' || $fuid eq '' ||
        $fgid eq '' || $fperms eq ''
       )
    {

        push_el(
                \@main::el,
                'setfmode()',
                "ERROR: Undefined input data, fname: |$fname|, fuid: |$fuid|, fgid: |$fgid|, fperms: |$fperms| !"
               );

        return -1;

    }

    if (! -e $fname) {

        push_el(
                \@main::el,
                'setfmode()',
                "ERROR: File '$fname' does not exist !"
               );

        return -1;
    }

    my @udata = ();

    my @gdata = ();

    my ($uid, $gid) = ($fuid, $fgid);

	if ($fuid =~ /^\d+$/) {

		$uid = $fuid;

    } elsif ($fuid ne '-1') {

        @udata = getpwnam($fuid);

        if (scalar(@udata) == 0) {

            push_el(
                    \@main::el,
                    'setfmode()',
                    "ERROR: Unknown user '$fuid' !"
                   );

            return -1;

        }

        $uid = $udata[2];
    }

	if ($fgid =~ /^\d+$/) {

		$gid = $fgid;

	} elsif ($fgid ne '-1') {

        @gdata = getgrnam($fgid);

        if (scalar(@gdata) == 0) {

            push_el(
                    \@main::el,
                    'setfmode()',
                    "ERROR: Unknown group '$fgid' !"
                   );

            return -1;

        }

        $gid = $gdata[2];
    }

    my $res = chmod ($fperms, $fname);

    if ($res != 1) {

        push_el(
                \@main::el,
                'setfmode()',
                "ERROR: Can not change permissions of file '$fname' !"
               );

        return -1;

    }

    $res = chown ($uid, $gid, $fname);

    if ($res != 1) {

        push_el(
                \@main::el,
                'setfmode()',
                "ERROR: Can not change user/group of file '$fname' !"
               );

        return -1;

    }

    push_el(\@main::el, 'setfmode()', 'Ending...');

    return 0;

}

sub get_file {

    my ($fname) = @_;

    push_el(\@main::el, 'get_file()', 'Starting...');

    if (!defined($fname) || ($fname eq '')) {

        push_el(
                \@main::el,
                'get_file()',
                "ERROR: Undefined input data, fname: |$fname| !"
               );

        return (-1, '');

    }

    if (! -e $fname) {

        push_el(
                \@main::el,
                'get_file()',
                "ERROR: File '$fname' does not exist !"
               );

        return (-1, '');

    }

    my $res = open(F, '<', $fname);

    if (!defined($res)) {

        push_el(
                \@main::el,
                'get_file()',
                "ERROR: Can't open '$fname' for reading !"
               );

        return (-1, '');

    }

    my @fdata = <F>;

    close(F);

    my $line = join('', @fdata);

    push_el(\@main::el, 'get_file()', 'Ending...');

    return (0, $line);

}

sub store_file {

    my ($fname, $fdata, $fuid, $fgid, $fperms) = @_;

    push_el(\@main::el, 'store_file()', 'Starting...');

    if (
        !defined($fname) || !defined($fuid) ||
        !defined($fgid) || !defined($fperms) ||
        $fname eq '' || $fuid eq '' ||
        $fgid eq '' || $fperms eq ''
       )
    {
        push_el(
                \@main::el,
                'store_file()',
                "ERROR: Undefined input data, fname: |$fname|, fdata, fuid: '$fuid', fgid: '$fgid', fperms: '$fperms'"
               );

        return -1;
    }

    my $res = open(F, '>', $fname);

    if (!defined($res)) {

        push_el(
                \@main::el,
                'store_file()',
                "ERROR: Can't open file |$fname| for writing !"
               );

        return -1;

    }

    print F $fdata;

    close(F);

    my ($rs, $rdata) = setfmode($fname, $fuid, $fgid, $fperms);

    return -1 if ($rs != 0);

    push_el(\@main::el, 'store_file()', 'Ending...');

    return 0;

}

sub del_file {

    my ($fname) = @_;

    push_el(\@main::el, 'del_file()', 'Starting...');

    if (!defined($fname) || ($fname eq '')) {

        push_el(
                \@main::el,
                'del_file()',
                "ERROR: Undefined input data, fname: |$fname| !"
               );

        return -1;

    }

    if (! -e $fname) {

        push_el(
                \@main::el,
                'del_file()',
                "ERROR: File '$fname' does not exist !"
               );

        return -1;

    }

    my $res = unlink ($fname);

    if ($res != 1) {

        push_el(
                \@main::el,
                'del_file()',
                "ERROR: Can't unlink '$fname' !"
               );

        return -1;

    }

    push_el(\@main::el, 'del_file()', 'Ending...');

    return 0;

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
        if( $ll < 0 ) {
            $ll = length( $fdata );
        } else {
            $ll++;
        }
        $curline = substr( $fdata, 0, $ll );
        $fdata = substr( $fdata, $ll );

        if( $zs == 0 ) {
            if( index($curline, $comment."## START ISPCP ".$zone." ###") == 0 ) {
                $zs = 1;
            } else {
                $bz .= $curline;
            }
        } elsif( $ze == 0 ) {
            if( index($curline, $comment."## END ISPCP ".$zone." ###") == 0) {
                $ze = 1;
            }
        } elsif( $ze == 1 ) {
                $az .= $curline;
        }
    }

    return
        $bz.($zs == 1 ? "" : "\n").
        $comment."## START ISPCP ".$zone." ###\n".
        $data."\n".
        $comment."## END ISPCP ".$zone." ###\n".
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
        if( $ll < 0 ) {
            $ll = length( $fdata );
        } else {
            $ll++;
        }
        $curline = substr( $fdata, 0, $ll );
        $fdata = substr( $fdata, $ll );

        if( $zs == 0 ) {
            if( index($curline, $comment."## START ISPCP ".$zone." ###") == 0 ) {
                $zs = 1;
            }
        } elsif( $ze == 0 ) {
            if( index($curline, $comment."## END ISPCP ".$zone." ###") == 0) {
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
        if( $ll < 0 ) {
            $ll = length( $fdata );
        } else {
            $ll++;
        }
        $curline = substr( $fdata, 0, $ll );
        $fdata = substr( $fdata, $ll );

        if( $zs == 0 ) {
            if( index($curline, $comment."## START ISPCP ".$zone." ###") == 0 ) {
                $zs = 1;
            } else {
                $bz .= $curline;
            }
        } elsif( $ze == 0 ) {
            if( index($curline, $comment."## END ISPCP ".$zone." ###") == 0) {
                $ze = 1;
            }
        } elsif( $ze == 1 ) {
                $az .= $curline;
        }
    }

    return $bz.$az;
}

sub sys_command {

    my ($cmd) = @_;

    push_el(\@main::el, 'sys_command()', 'Starting...');

    my $result = system($cmd);

    my $exit_value  = $? >> 8;

    my $signal_num  = $? & 127;

    my $dumped_core = $? & 128;

    if ($exit_value == 0) {

        push_el(\@main::el, "sys_command('$cmd')", 'Ending...');

        return 0;

    } else {

        push_el(\@main::el, 'sys_command()', "ERROR: External command '$cmd' returned '$exit_value' status !");

        return -1;

    }

}

sub sys_command_rs {

    my ($cmd) = @_;

    push_el(\@main::el, 'sys_command_rs()', 'Starting...');

    my $result = system($cmd);

    my $exit_value  = $? >> 8;

    my $signal_num  = $? & 127;

    my $dumped_core = $? & 128;

    push_el(\@main::el, 'sys_command_rs()', 'Ending...');

    if ($exit_value == 0) {

        return 0;

    } else {


        return $exit_value;

    }

}

sub make_dir {

    my ($dname, $duid, $dgid, $dperms) = @_;

    my ($rs, $rdata) = ('', '');

    push_el(\@main::el, 'make_dir()', 'Starting...');

    if (
        !defined($dname) || !defined($duid) ||
        !defined($dgid) || !defined($dperms) ||
        $dname eq '' || $duid eq '' ||
        $dgid eq '' || $dperms eq ''
       )
    {

        push_el(\@main::el, 'make_dir()', "ERROR: Undefined input data, dname: |$dname|, duid: |$duid|, dgid: |$dgid|, dperms: |$dperms| !");

        return -1;

    }

    if ( -e $dname && -f $dname ) {

        push_el(\@main::el,'make_dir()', "'$dname' exists as file ! removing file first...");

        return -1 if (del_file($dname) != 0);

    }

    if (!(-e $dname && -d $dname)) {

        push_el(\@main::el, 'make_dir()', "'$dname' doesn't exists as directory! creating...");

        $rs =  mkpath($dname);

        if (!$rs) {

            push_el(\@main::el, 'make_dir()', "ERROR: mkdir() returned '$rs' status !");

            return -1;

        }

    } else {

        push_el(\@main::el, 'make_dir()', "'$dname' exists ! Setting its permissions...");

    }

    return -1 if (setfmode($dname, $duid, $dgid, $dperms) != 0);

    push_el(\@main::el, 'make_dir()', 'Ending...');

    return 0;
}

sub del_dir {

    my ($dname) = @_;

    push_el(\@main::el, 'make_dir()', 'Starting...');

    if (!defined($dname) || ($dname eq '')) {

        push_el(\@main::el, 'make_dir()', "ERROR: Undefined input data, dname: |$dname| !");

        return -1;

    }

    push_el(\@main::el, 'make_dir()', "Trying to remove '$dname'...");

    return -1 if (sys_command("rm -rf $dname") != 0);

    push_el(\@main::el, 'make_dir()', 'Ending...');

    return 0;

}

sub gen_rand_num {

    my ($len) = @_;

    push_el(\@main::el, 'gen_rand_num()', 'Starting...');

    if (!defined($len) || ($len eq '')) {

        push_el(\@main::el, 'gen_rand_num()', "ERROR: Undefined input data, len: |$len| !");

        return (-1, '');

    }

    if (!(0 < $len && $len < 11)) {

        push_el(\@main::el, 'gen_rand_num()', "ERROR: Input data length '$len' out of limits [1, 10] !");

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

sub crypt_md5_data {

    my ($data) = @_;

    push_el(\@main::el, 'crypt_md5_data()', 'Starting...');

    if (!defined($data) || $data eq '') {

        push_el(\@main::el, 'crypt_md5_data()', "ERROR: Undefined input data, data: |$data| !");

        return (-1, '');

    }

    my ($rs, $rdata) = gen_rand_num(2);

    return (-1, '') if ($rs != 0);

    $rdata = unix_md5_crypt($data, $rdata);

    push_el(\@main::el, 'crypt_md5_data()', 'Ending...');

    return (0, $rdata);

}

sub crypt_data {

    my ($data) = @_;

    push_el(\@main::el, 'crypt_data()', 'Starting...');

    if (!defined($data) || $data eq '') {

        push_el(\@main::el, 'crypt_data()', "ERROR: Undefined input data, data: |$data| !");

        return (-1, '');

    }

    my ($rs, $rdata) = gen_rand_num(2);

    return (-1, '') if ($rs != 0);

    $rdata = crypt($data, $rdata);

    push_el(\@main::el, 'crypt_data()', 'Ending...');

    return (0, $rdata);

}

sub get_tag {

    my ($bt, $et, $src) = @_;

    push_el(\@main::el, 'get_tag()', "Starting...");

    if (
        !defined($bt) || !defined($et) ||
        !defined($src) || $bt eq '' ||
        $et eq '' || $src eq ''
       )
    {

        push_el(\@main::el, 'get_tag()', "ERROR: Undefined intput data, bt: |$bt|, et: |$et|, src !");

        return (-1, '');

    }

    my ($bt_len, $et_len, $src_len) = (
                                       length($bt),
                                       length($et),
                                       length($src)
                                      );

    #
    #return ('_e03_', $main::strerr{'_e03_'})
    #
    #if ($bt_len > $src_len || $et_len > $src_len);
    #

    if ($bt eq $et) {


        # Let's search for ...$tag... ;

        # $bt == $et == $tag ;


        my $tag = $bt;

        my $tag_pos = index($src, $tag);

        if ($tag_pos < 0) {

            push_el(\@main::el, 'get_tag()', "ERROR: '$bt' eq '$et', missing '$bt' in src !");

            return (-4, '');

        } else {

            push_el(\@main::el, 'get_tag()', 'Ending...');

            return (0, $tag);

        }

    } else {

        if ($bt_len + $et_len > $src_len) {

            push_el(\@main::el, 'get_tag()', "ERROR: len($bt) + len($et) > len(src) !");

            return (-1, '');

        }


        # Let's search for ...$bt...$et... ;


        my ($bt_pos, $et_pos) = (index($src, $bt), index($src, $et));

        if ($bt_pos < 0 || $et_pos < 0) {

            push_el(\@main::el, 'get_tag()', "ERROR: '$bt' ne '$et', '$bt' or '$et' missing in src !");

            return (-5, '');

        }

        if ($et_pos < $bt_pos + $bt_len) {

            push_el(\@main::el, 'get_tag()', "ERROR: '$bt' ne '$et', '$et' overlaps '$bt' in src !");

            return (-1, '');

        }

        push_el(\@main::el, 'get_tag()', 'Ending...');

        my $tag_len = $et_pos + $et_len - $bt_pos;

        return (0, substr($src, $bt_pos, $tag_len));

    }

}

sub repl_tag {

    my ($bt, $et, $src, $rwith) = @_;

    push_el(\@main::el, 'repl_tag()', "Starting...");

    if (!defined ($rwith)) {

        push_el(\@main::el, 'repl_tag()', "ERROR: Undefined input data, rwith: |$rwith| !");

        return (-1, '');

    }

    my ($rs, $rdata) = get_tag($bt, $et, $src);

    return $rs if ($rs != 0);

    my $tag = $rdata;

    my ($tag_pos, $tag_len) = (index($src, $tag), length($tag));

    if ($rwith eq '') {

        substr($src, $tag_pos, $tag_len, '');

    } else {

        substr($src, $tag_pos, $tag_len, $rwith);

    }

    push_el(\@main::el, 'repl_tag()', "Ending...");

    return (0, $src);
}

sub add_tag {

    my ($bt, $et, $src, $adata) = @_;

    push_el(\@main::el, 'add_tag()', "Starting...");

    if (!defined($adata) || $adata eq '') {

        push_el(\@main::el, 'add_tag()', "ERROR: Undefined input data, adata: |$adata| !");

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

    ($rs, $rdata) = repl_tag($bt, $et, $src, $rwith);

    return (-1, '') if ($rs != 0);

    push_el(\@main::el, 'add_tag()', "Ending...");

    return (0, $rdata);
}

sub del_tag {

    my ($bt, $et, $src) = @_;

    push_el(\@main::el, 'del_tag()', "Starting...");

    my ($rs, $rdata) = get_tag($bt, $et, $src);

    return ($rs, '') if ($rs != 0);

    ($rs, $rdata) = repl_tag($bt, $et, $src, '');

    return (-1, '') if ($rs != 0);

    push_el(\@main::el, 'del_tag()', "Ending...");

    return (0, $rdata);

}

sub get_var {

    my ($var, $src) = @_;

    push_el(\@main::el, 'get_var()', "Starting...");

    my ($rs, $rdata) = get_tag($var, $var, $src);

    return ($rs, '') if ($rs != 0);

    push_el(\@main::el, 'get_var()', "Ending...");

    return (0, $rdata);

}

sub repl_var {

    my ($var, $src, $rwith) = @_;

    my ($rs, $rdata, $result) = (0, $src, '');

    push_el(\@main::el, 'repl_var()', "Starting...");

    while ($rs == 0) {

        $result = $rdata;

        ($rs, $rdata) = repl_tag($var, $var, $rdata, $rwith);

        return -1 if ($rs != 0 && $rs != -4);

    }

    push_el(\@main::el, 'repl_var()', "Ending...");

    return (0, $result);
}

sub add_var {

    my ($var, $src, $adata) = @_;

    push_el(\@main::el, 'add_var()', "Starting...");

    my ($rs, $rdata) = add_tag($var, $var, $src, $adata);

    return -1 if ($rs != 0);

    push_el(\@main::el, 'add_var()', "Ending...");

    return (0, $rdata);

}

sub del_var {

    my ($var, $src) = @_;

    push_el(\@main::el, 'del_var()', "Starting...");

    my ($rs, $rdata) = repl_var($var, $src, '');

    return -1 if ($rs != 0);

    push_el(\@main::el, 'del_var()', "Ending...");

    return ($rs, $rdata);

}

sub get_tpl {

    my $tpl_dir = $_[0];

    my @tpls = @_;

    my ($rs, $rdata, $tpl_file) = ('', '', '');

    my @res = (0);

    push_el(\@main::el, 'get_tpl()', "Starting...");

    if (scalar(@tpls) < 2) {

        push_el(\@main::el, 'get_tpl()', "ERROR: Template filename(s) missing !");

        return (-1, '');

    }

    shift(@tpls);

    foreach (@tpls) {

        $tpl_file = $_;

        ($rs, $rdata) = get_file("$tpl_dir/$tpl_file");

        return (-1, '') if ($rs != 0);

        push (@res, $rdata);
    }

    push_el(\@main::el, 'get_tpl()', "Ending...");

    return @res;

}

sub prep_tpl {

    my $hash_ptr = $_[0];

    my @tpls = @_;

    my ($rs, $rdata) = ('', '', '');

    my @res = (0);

    push_el(\@main::el, 'prep_tpl()', "Starting...");

    if (scalar(@tpls) < 2) {

        push_el(\@main::el, 'prep_tpl()', "ERROR: Template variable(s) missing !");

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

    push_el(\@main::el, 'prep_tpl()', "Ending...");

    return @res;
}

$main::lock_file = '/tmp/ispcp.lock';

sub lock_system {

    push_el(\@main::el, 'lock_system()', 'Starting...');

    if (-e $main::lock_file) {

        push_el(\@main::el, 'lock_system()', 'ERROR: request engine already locked !');

        return -1;

    }

    my $touch_cmd = "`which touch` $main::lock_file";

    my $rs = sys_command($touch_cmd);

    return -1 if ($rs != 0);

    push_el(\@main::el, 'lock_system()', 'Ending...');

    return 0;
}

sub unlock_system {

    push_el(\@main::el, 'unlock_system()', 'Starting...');

    my $rm_cmd = "`which rm` -rf $main::lock_file";

    my $rs = sys_command($rm_cmd);

    return -1 if ($rs != 0);

    push_el(\@main::el, 'unlock_system()', 'Ending...');

    return 0;

}

# License request function must not SIGPIPE;

$SIG{PIPE} = 'IGNORE';

$SIG{HUP} = 'IGNORE';

sub connect_ispcp_daemon {

    push_el(\@main::el, 'connect_ispcp_daemon()', 'Starting...');

    my $fd = IO::Socket::INET -> new(
                                     Proto => "tcp",
                                     PeerAddr => "127.0.0.1",
                                     PeerPort => "8668"
                                    );

    if (!defined($fd)) {

        push_el(\@main::el, 'connect_ispcp_daemon()', "ERROR: Can't connect to ISPCP license daemon !");

        return (-1, '');

    }

    push_el(\@main::el, 'connect_ispcp_daemon()', 'Ending...');

    return (0, $fd);
}

sub recv_line {

    my ($fd) = @_;

    my ($res, $row, $ch) = (undef, undef, undef, undef);

    push_el(\@main::el, 'recv_line()', 'Starting...');

    do {

        $res = recv($fd, $ch, 1, 0);

        if (!defined($res)) {

            push_el(\@main::el, 'recv_line()', "ERROR: unexpected IO prebolems !");

            return (-1, '');

        }

        $row .= $ch;

    } while ($ch ne "\n");

    push_el(\@main::el, 'recv_line()', 'Ending...');

    return (0, $row);

}

sub send_line {

    my ($fd, $line) = @_;

    my ($i, $res, $ch) = (undef, undef, undef);

    push_el(\@main::el, 'send_line()', 'Starting...');

    for ($i = 0; $i < length($line); $i++) {

        $ch = substr($line, $i, 1);

        $res = send($fd, $ch, 0);

        if (!defined($res)) {

            push_el(\@main::el, 'send_line()', "ERROR: unexpected IO prebolems !");

            return (-1, '');

        }

    }

    push_el(\@main::el, 'send_line()', 'Ending...');

    return (0, '');
}

sub close_ispcp_daemon {

    my ($fd) = @_;

    push_el(\@main::el, 'close_ispcp_daemon()', 'Starting...');

    close($fd);

    push_el(\@main::el, 'close_ispcp_daemon()', 'Ending...');

}

sub license_request {

    push_el(\@main::el, 'license_query()', 'Starting...');

    my ($rs, $rdata) = connect_ispcp_daemon();

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

    close_ispcp_daemon($fd);

    push_el(\@main::el, 'license_query()', 'Ending...');

    return (0, $main::working_license);

}

$main::master_name = 'ispcp-rqst-mngr';

sub check_master {

    if (defined($main::engine_debug)) {

        push_el(\@$main::el, 'check_master()', 'Starting...');

    }

    sys_command("export COLUMNS=120;/bin/ps auxww | awk '\$0 ~ /$main::master_name/ && \$0 !~ /awk/ { print \$2 ;}' 1>$main::cc_stdout 2>$main::cc_stderr");

    if (-z $main::cc_stdout) {

        del_file($main::cc_stdout); del_file($main::cc_stderr);

        push_el(\@main::el, 'check_master()', 'ERROR: Master manager process is not running !');

        return -1;

    }

    del_file($main::cc_stdout); del_file($main::cc_stderr);

    if (defined($main::engine_debug)) {

        push_el(\@$main::el, 'check_master()', 'Starting...');

    }

    return 0;

}

# Global variables

%main::cfg = ();

%main::cfg_reg = ();

$main::cfg_file = '/etc/ispcp/ispcp.conf';

$main::cfg_re = '^([\_A-Za-z0-9]+) *= *([^\n\r]*)[\n\r]';

my $scriptdir = dirname(__FILE__);

require $scriptdir.'/ispcp-db-keys.pl';

sub encrypt_db_password {

    my ($pass) = @_;

    push_el(\@main::el, 'encrypt_db_password()', 'Starting...');

    if (!defined($pass) || $pass eq '') {

        push_el(\@main::el, 'encrypt_db_password()', 'ERROR: Undefined input data ($pass)...');

        return (1, '');

    }

    my $cipher = Crypt::CBC -> new(
                                    {
                                        'key'             => $main::db_pass_key,
										'keysize' 		  => 32,
                                        'cipher'          => 'Blowfish',
                                        'iv'              => $main::db_pass_iv,
                                        'regenerate_key'  => 0,
                                        'padding'         => 'space',
                                        'prepend_iv'      => 0
                                    }
                                  );

    my $ciphertext = $cipher->encrypt($pass);

    my $encoded = encode_base64($ciphertext); chop($encoded);

    push_el(\@main::el, 'encrypt_db_password()', 'Ending...');

    return (0, $encoded);

}

sub decrypt_db_password {

    my ($pass) = @_;

    push_el(\@main::el, 'decrypt_db_password()', 'Starting...');

    if (!defined($pass) || $pass eq '') {

        push_el(\@main::el, 'decrypt_db_password()', 'ERROR: Undefined input data ($pass)...');

        return (1, '');

    }

    my $cipher = Crypt::CBC -> new(
                                    {
                                        'key'             => $main::db_pass_key,
										'keysize' 		  => 32,
                                        'cipher'          => 'Blowfish',
                                        'iv'              => $main::db_pass_iv,
                                        'regenerate_key'  => 0,
                                        'padding'         => 'space',
                                        'prepend_iv'      => 0
                                    }
                                  );

    my $decoded = decode_base64("$pass\n");

    my $plaintext = $cipher -> decrypt($decoded);


    push_el(\@main::el, 'decrypt_db_password()', 'Ending...');

    return (0, $plaintext);

}

sub setup_main_vars {

    push_el(\@main::el, 'setup_main_vars()', 'Starting...');

    #
    # Database backend vars;
    #

    $main::db_host = $main::cfg{'DATABASE_HOST'};

    $main::db_user = $main::cfg{'DATABASE_USER'};

    $main::db_pwd = $main::cfg{'DATABASE_PASSWORD'};

    if ($main::db_pwd ne '') {

        my $rs = undef;

        ($rs, $main::db_pwd) = decrypt_db_password($main::db_pwd);

    }



    $main::db_name = $main::cfg{'DATABASE_NAME'};

    @main::db_connect = (
                         "DBI:mysql:$main::db_name:$main::db_host",
                         $main::db_user,
                         $main::db_pwd
                        );

    push_el(\@main::el, 'setup_main_vars()', 'Ending...');

    return 0;
}

sub get_conf {

    push_el(\@main::el, 'get_conf()', 'Starting...');

    my ($rs, $fline) = get_file($main::cfg_file);

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

    return 0;

}

sub set_conf_val {

    my ($name, $value) = @_;

    push_el(\@main::el, 'set_conf_val()', 'Starting...');

    if (!defined($name) || $name eq '') {

        push_el(\@main::el, 'set_conf_val()', 'ERROR: Undefined input data ($name)...');

        return 1;

    }

    $main::cfg_reg{$name} = $value;

    push_el(\@main::el, 'set_conf_value()', 'Ending...');

    return 0;

}

sub store_conf {

    my ($key, $value, $fline, $rs) = (undef, undef, undef, undef);

    my $rwith = undef;

    push_el(\@main::el, 'store_conf()', 'Starting...');

    ($rs, $fline) = get_file($main::cfg_file);

    return 1 if ($rs != 0);

    if (scalar(keys(%main::cfg_reg)) > 0) {

        while (($key, $value) = each %main::cfg_reg) {

            $rwith = "$key = $value\n";

            $fline =~ s/^$key *= *([^\n\r]*)[\n\r]/$rwith/gim;

        }

    }

    $rs = store_file($main::cfg_file, $fline, 'root', 'root', 0644);

    return 1 if ($rs != 0);

    $rs = get_conf($main::cfg_file);

    return 1 if ($rs != 0);

    push_el(\@main::el, 'store_conf()', 'Ending...');

    return 0;

}

%main::domain_id_name = ();

%main::domain_name_id = ();

%main::domain_id_ipid = ();

sub get_domain_ids {

    my ($rs, $rows, $sql) = ('', '', '');

    $sql = "select domain_id, domain_name, domain_ip_id from domain order by domain_id;";

    ($rs, $rows) = doSQL($sql);

    return $rs if ($rs != 0);

    foreach (@$rows) {

        $main::domain_id_name{@$_[0]} = @$_[1];

        $main::domain_name_id{@$_[1]} = @$_[0];

        $main::domain_id_ipid{@$_[0]} = @$_[2];

    }

    return 0;
}

%main::sub_id_name = ();

%main::sub_name_id = ();

%main::sub_id_parentid = ();

sub get_subdom_ids {

    my ($rs, $rows, $sql) = ('', '', '');

    $sql = "select subdomain_id, subdomain_name, domain_id from subdomain order by subdomain_id;";

    ($rs, $rows) = doSQL($sql);

    return $rs if ($rs != 0);

    foreach (@$rows) {

        $main::sub_id_name{@$_[0]} = @$_[1];

        $main::sub_name_id{@$_[1]} = @$_[0];

        $main::sub_id_parentid{@$_[0]} = @$_[2];

    }

    return 0;

}

%main::als_id_name = ();

%main::als_name_id = ();

%main::als_id_parentid = ();

sub get_alias_ids {

    my ($rs, $rows, $sql) = ('', '', '');

    $sql = "select alias_id, domain_id, alias_name from domain_aliasses order by alias_id";

    ($rs, $rows) = doSQL($sql);

    return $rs if ($rs != 0);

    foreach (@$rows) {

        $main::als_id_name{@$_[0]} = @$_[2];

        $main::als_name_id{@$_[2]} = @$_[0];

        $main::als_id_parentid{@$_[0]} = @$_[1];

    }

    return 0;

}

%main::ip_id_num = ();

%main::ip_num_id = ();

sub get_ip_nums {

    my ($rs, $rows, $sql) = ('', '', '');

    $sql = "select ip_id, ip_number from server_ips order by ip_id";

    ($rs, $rows) = doSQL($sql);

    return $rs if ($rs != 0);

    foreach (@$rows) {

        $main::ip_id_num{@$_[0]} = @$_[1];

        $main::ip_num_id{@$_[1]} = @$_[0];

    }

    return 0;

}

sub get_el_error {

    my ($fname) = @_;

    my ($rs, $rdata) = (undef, undef);

    push_el(\@main::el, 'get_el_error()', 'Starting...');

    ($rs, $rdata) = get_file($fname);

    return $rs if ($rs != 0);

    my @frows = split(/\n/, $rdata);

    my $err_row = "$frows[0]\n";;

    $err_row =~ /\|\ *([^\n]+)\n$/;

    $rdata = $1;

    push_el(\@main::el, 'get_el_error()', 'Ending...');

    return (0, $rdata);

}

sub get_human_date {

    push_el(\@main::el, 'get_human_date()', 'Starting...');

    my (
        $sec, $min, $hour,
        $mday, $mon, $year,
        $wday, $yday, $isdst
       ) = localtime(time);

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

sub add_dmn_suexec_user {

    my ($dmn_data) = @_;

    push_el(\@main::el, 'add_dmn_suexec_user()', 'Starting...');

    my ($suexec_min_uid, $suexec_min_gid) = ($main::cfg{'APACHE_SUEXEC_MIN_UID'}, $main::cfg{'APACHE_SUEXEC_MIN_GID'});

    my $num = get_auto_num();

    my ($sys_uid, $sys_gid) = ($suexec_min_uid + $num, $suexec_min_gid + $num);

    my $suexec_user_pref = $main::cfg{'APACHE_SUEXEC_USER_PREF'};

    my $sys_user = "$suexec_user_pref$sys_uid";

    my $sys_group = "$suexec_user_pref$sys_gid";

    my ($dmn_uid, $dmn_gid) = (@$dmn_data[3], @$dmn_data[2]);

    my $dmn_id = @$dmn_data[0];

    my ($rs, $rdata, $sql) = (undef, undef, undef);

    if ($dmn_uid == 0 && $dmn_gid == 0) {

        # group data.

        my $cmd = "$main::cfg{'CMD_GROUPADD'} -g $sys_gid $sys_group";

        $rs = sys_command($cmd);

        return $rs if ($rs != 0);

        # user data.

		# SSH/SCP Useraccount preperation
		my $homedir = "$main::cfg{'APACHE_WWW_DIR'}/@$dmn_data[1]";

#        $cmd = "$main::cfg{'CMD_USERADD'} -c virtual-user -g $sys_group -s /bin/false -u $sys_uid $sys_user";
		$cmd = "$main::cfg{'CMD_USERADD'} -c virtual-user -d $homedir -g $sys_group -s /bin/false -u $sys_uid $sys_user";

        $rs = sys_command($cmd);

        return $rs if ($rs != 0);

        $sql = "update domain set domain_uid = '$sys_uid', domain_gid = '$sys_gid' where domain_id = $dmn_id";

        ($rs, $rdata) = doSQL($sql);

        return $rs if ($rs != 0);

    }

    push_el(\@main::el, 'add_dmn_suexec_user()', 'Ending...');

    return 0;

}

sub get_dmn_suexec_user {

    my ($dmn_id) = @_;

    push_el(\@main::el, 'get_dmn_suexec_user()', 'Starting...');

    my $sql = "select domain_uid, domain_gid from domain where domain_id = $dmn_id";

    my ($rs, $rdata) = doSQL($sql);

    return ($rs, $rdata) if ($rs != 0);

    my $row = @$rdata[0];

    push_el(\@main::el, 'get_dmn_suexec_user()', 'Ending...');

    return (@$row[0], @$row[1]);

}

sub del_dmn_suexec_user {

    my ($dmn_data) = @_;

    push_el(\@main::el, 'del_dmn_suexec_user()', 'Starting...');

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

        if (scalar(@udata) != 0) { # we must remove it from the system

            $cmd = "$main::cfg{'CMD_USERDEL'} $sys_user";

            $rs = sys_command($cmd);

            return $rs if ($rs != 0);
        }


        @gdata = getgrnam($sys_group);

        if (scalar(@gdata) != 0) { # we have not this one group data;

            $cmd = "$main::cfg{'CMD_GROUPDEL'} $sys_group";

            $rs = sys_command($cmd);

            return $rs if ($rs != 0);

        }

        $sql = "update domain set domain_uid = '0', domain_gid = '0' where domain_id = $dmn_id";

        ($rs, $rdata) = doSQL($sql);

        return $rs if ($rs != 0);

    }

    push_el(\@main::el, 'del_dmn_suexec_user()', 'Ending...');

    return 0;

}

my $rs = get_conf();

return $rs if ($rs != 0);

$main::log_dir = $main::cfg{'LOG_DIR'};

$main::root_dir = $main::cfg{'ROOT_DIR'};

$main::ispcp = "$main::log_dir/ispcp-rqst-mngr.el";

$main::ispcp_rqst_mngr = "$main::root_dir/engine/ispcp-rqst-mngr";

$main::ispcp_rqst_mngr_el = "$main::log_dir/ispcp-rqst-mngr.el";
$main::ispcp_rqst_mngr_stdout = "$main::log_dir/ispcp-rqst-mngr.stdout";
$main::ispcp_rqst_mngr_stderr = "$main::log_dir/ispcp-rqst-mngr.stderr";

$main::ispcp_dmn_mngr = "$main::root_dir/engine/ispcp-dmn-mngr";

$main::ispcp_dmn_mngr_el = "$main::log_dir/ispcp-dmn-mngr.el";
$main::ispcp_dmn_mngr_stdout = "$main::log_dir/ispcp-dmn-mngr.stdout";
$main::ispcp_dmn_mngr_stderr = "$main::log_dir/ispcp-dmn-mngr.stderr";

$main::ispcp_sub_mngr = "$main::root_dir/engine/ispcp-sub-mngr";

$main::ispcp_sub_mngr_el = "$main::log_dir/ispcp-sub-mngr.el";
$main::ispcp_sub_mngr_stdout = "$main::log_dir/ispcp-sub-mngr.stdout";
$main::ispcp_sub_mngr_stderr = "$main::log_dir/ispcp-sub-mngr.stderr";

$main::ispcp_als_mngr = "$main::root_dir/engine/ispcp-als-mngr";

$main::ispcp_als_mngr_el = "$main::log_dir/ispcp-als-mngr.el";
$main::ispcp_als_mngr_stdout = "$main::log_dir/ispcp-als-mngr.stdout";
$main::ispcp_als_mngr_stderr = "$main::log_dir/ispcp-als-mngr.stderr";

$main::ispcp_mbox_mngr = "$main::root_dir/engine/ispcp-mbox-mngr";

$main::ispcp_mbox_mngr_el = "$main::log_dir/ispcp-mbox-mngr.el";
$main::ispcp_mbox_mngr_stdout = "$main::log_dir/ispcp-mbox-mngr.stdout";
$main::ispcp_mbox_mngr_stderr = "$main::log_dir/ispcp-mbox-mngr.stderr";

$main::ispcp_serv_mngr = "$main::root_dir/engine/ispcp-serv-mngr";

$main::ispcp_serv_mngr_el = "$main::log_dir/ispcp-serv-mngr.el";
$main::ispcp_serv_mngr_stdout = "$main::log_dir/ispcp-serv-mngr.stdout";
$main::ispcp_serv_mngr_stderr = "$main::log_dir/ispcp-serv-mngr.stderr";

#
# htuser manager variables.
#

$main::ispcp_htuser_mngr = "$main::root_dir/engine/ispcp-htuser-mngr";

$main::ispcp_htuser_mngr_el = "$main::log_dir/ispcp-htuser-mngr.el";
$main::ispcp_htuser_mngr_stdout = "$main::log_dir/ispcp-htuser-mngr.stdout";
$main::ispcp_htuser_mngr_stderr = "$main::log_dir/ispcp-htuser-mngr.stderr";


$main::ispcp_vrl_traff = "$main::root_dir/engine/messager/ispcp-vrl-traff";

$main::ispcp_vrl_traff_el = "$main::log_dir/ispcp-vrl-traff.el";
$main::ispcp_vrl_traff_stdout = "$main::log_dir/ispcp-vrl-traff.stdout";
$main::ispcp_vrl_traff_stderr = "$main::log_dir/ispcp-vrl-traff.stderr";

$main::ispcp_vrl_traff_correction_el = "$main::log_dir/ispcp-vrl-traff-correction.el";

$main::ispcp_httpd_logs_mngr_el = "$main::log_dir/ispcp-httpd-logs-mngr.el";
$main::ispcp_httpd_logs_mngr_stdout = "$main::log_dir/ispcp-httpd-logs-mngr.stdout";
$main::ispcp_httpd_logs_mngr_stderr = "$main::log_dir/ispcp-httpd-logs-mngr.stderr";

$main::ispcp_ftp_acc_mngr_el = "$main::log_dir/ispcp-ftp-acc-mngr.el";
$main::ispcp_ftp_acc_mngr_stdout = "$main::log_dir/ispcp-ftp-acc-mngr.stdout";
$main::ispcp_ftp_acc_mngr_stderr = "$main::log_dir/ispcp-ftp-acc-mngr.stderr";

$main::ispcp_bk_task_el = "$main::log_dir/ispcp-bk-task.el";

$main::ispcp_srv_traff_el = "$main::log_dir/ispcp-srv-traff.el";

$main::ispcp_dsk_quota_el = "$main::log_dir/ispcp-dsk-quota.el";


$main::level1_license = "S2F10-144BD";

$main::level1_dmn_count = 10;

$main::level2_license = "37FD1-ED3DB";

$main::level2_dmn_count = 30;

$main::level3_license = "QDA53-HE4CC";

$main::level3_dmn_count = 100;

$main::level4_license = "A5F26-AC7DF";

$main::level4_dmn_count = "unlimited";

$main::working_license = $main::level1_license;

########################################################################

return 1;

