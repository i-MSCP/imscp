#!/usr/bin/perl

use strict;

use warnings;

if (scalar(@ARGV) != 1) {
    
    print STDERR "\nvhcs2-mkdirs.pl command line argument error !\n\n";
    
    exit 1;
    
}

my $dir_path = $ARGV[0];

$dir_path .= "\n";

my $current_dir = '';

while ($dir_path =~ /(\/[^\/\n]+)/g) {
    
    $current_dir .= $1;
    
    if (! -e $current_dir) {
        
        mkdir($current_dir);
        
        print "Creating Directory [$current_dir] ...\n";
        
    }
}
