# i-MSCP Listener::PhpFcgid::Settings::Override listener file
# Copyright (C) 2018 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## Allows to override the *_PER_CLASS fcgid settings in Apache2 vhost files
##

package Listener::PhpFcgid::Settings::Override;

our $VERSION = '1.0.0';

use strict;
use warnings;
use iMSCP::EventManager;
use version;

#
## Configuration parameters
#

# Overrides the *_PER_CLASS fcgid settings inside Apache2 vhosts
# Note that domain names must be in ASCII format.
my %SETTINGS = (
    # Global *_PER_CLASS fcgid settings 
    # These settings apply to all domains.
    '*'               => {
        # Min PHP process count for a domain
        PHP_FCGID_MIN_PROCESSES_PER_CLASS => 0,
        # Max PHP process count for a domain
        PHP_FCGID_MAX_PROCESS_PER_CLASS   => 6
    },

    # Per domain *_PER_CLASS fcgid settings
    # These settings apply to the test.domain.tld domain only. They have
    # higher precedence than global settings.
    'test.domain.tld' => {
        # Min PHP process count for that domain
        PHP_FCGID_MIN_PROCESSES_PER_CLASS => 0,
        # Max PHP process count for that domain
        PHP_FCGID_MAX_PROCESS_PER_CLASS   => 10
    }
);

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 10_phpfcgid_settings_override.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'afterPhpApache2BuildConfFile',
    sub {
        my ($phpServer, undef, undef, undef, $moduleData, $apache2ServerData) = @_;

        return 0 unless $phpServer->{'config'}->{'PHP_SAPI'} eq 'cgi'
            && $moduleData->{'FORWARD'} eq 'no'
            && $moduleData->{'PHP_SUPPORT'} eq 'yes';

        if ( exists $SETTINGS{'*'} ) {
            # Apply global *_PER_CLASS fcgid settings
            @{$apache2ServerData}{keys %{$SETTINGS{'*'}}} = values %{$SETTINGS{'*'}};
        }

        return 0 unless exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}};

        if ( exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}} ) {
            # Apply per domain *_PER_CLASS fcgid settings
            @{$apache2ServerData}{keys %{$SETTINGS{$moduleData->{'DOMAIN_NAME'}}}} = values %{$SETTINGS{$moduleData->{'DOMAIN_NAME'}}};
        }

        0;
    }
);

1;
__END__
