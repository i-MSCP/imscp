#!/usr/bin/perl

use FindBin;
use lib "$FindBin::Bin/../engine/PerlLib/test", "$FindBin::Bin/../engine/PerlLib";

$main::assetDir = "$FindBin::Bin/TestAsset";

# TODO discover, load and run all test packages automatically
require iMSCP::DirTests;
iMSCP::DirTests::runTests();

1;
__END__
