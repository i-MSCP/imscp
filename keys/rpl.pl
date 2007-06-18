#!/usr/bin/perl

use strict;
use warnings;

die ("Undefined Input Data!") if (!defined($ARGV[0]) || !defined($ARGV[1]) || !defined($ARGV[2]));

my ($php_fname, $perl_fname, $perl_fname2) = ($ARGV[0], $ARGV[1], $ARGV[2]);

my $key = gen_sys_rand_num(32);
my $iv  = gen_sys_rand_num(8);

$key =~ s/'/\\'/gi;
$iv =~ s/'/\\'/gi;

# remove \n at the end of lines;

chop($key);
chop($iv);

my ($rs, $php_file, $perl_file) = (undef, undef, undef);

my %tag_hash = (
                    '{KEY}' => $key,
                    '{IV}' =>  $iv
                );

# php lib;

$php_file = get_file($php_fname);

($rs, $php_file) = prep_tpl(\%tag_hash, $php_file);

return $rs if ($rs != 0);

$rs = save_file($php_fname, $php_file);

return $rs if ($rs != 0);

# perl lib;

$perl_file = get_file($perl_fname);

($rs, $perl_file) = prep_tpl(\%tag_hash, $perl_file);

return $rs if ($rs != 0);

$rs = save_file($perl_fname, $perl_file);

return $rs if ($rs != 0);


# perl lib for autoresponder;

$rs = save_file($perl_fname2, $perl_file);

return $rs if ($rs != 0);


sub gen_sys_rand_num {

    my ($len) = @_;

    if (!defined($len) || ($len eq '')) {

        print STDERR "gen_sys_rand_num() ERROR: Undefined input data, len: |$len| !";

        return (-1, '');

    }

    if (0 >= $len ) {

        print STDERR "gen_sys_rand_num() ERROR: Input data length '$len' is zero or negative !";

        return (-1, '');

    }
    
    my $rs = open(F, '<', '/dev/random');

    if (!defined($rs)) {

        print STDERR "gen_sys_rand_num() ERROR: Couldn't open the pseudo-random characters generator";

        return (-1, '');
    
    }
    
    my ($i, $rdata, $rc, $rci) = (0, undef, undef, undef);
    
    while ($i < $len) {

        read(F, $rc, 1);
        
        $rci = ord($rc);
        
        next if ($rci <= 32 || $rci >= 125 || $rci == 92 );
        
        $rdata .= $rc;
        $rc = undef;
        $i++;

    }
    
    close(F);

    return (0, $rdata);

}