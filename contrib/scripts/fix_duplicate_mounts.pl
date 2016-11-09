#!/usr/bin/perl

use strict;
use warnings;
use lib '/var/www/imscp/engine/PerlLib';
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::Mount qw/ umount /;

iMSCP::Bootstrapper->getInstance()->boot(
    {
        mode            => 'backend',
        norequirements  => 1,
        nokeys          => 1,
        nodatabase      => 1,
        nolock          => 1,
        config_readonly => 1
    }
);

setDebug(0);
setVerbose(1);

umount($main::imscpConfig{'USER_WEB_DIR'});

1;
__END__
