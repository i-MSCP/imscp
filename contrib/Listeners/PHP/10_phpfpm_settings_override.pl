# i-MSCP Listener::PhpFpm::Settings::Override listener file
# Copyright (C) 2016-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Allows to override PHP-FPM settings in pool configuration files
##
## Note: When you want operate on a per domain basis, don't forget to set the PHP configuration level to 'per_site'.
## You can do this by running: perl /var/www/imscp/engine/setup/imscp-reconfigure -dar php
#

package Listener::PhpFpm::Settings::Override;

use strict;
use warnings;
use iMSCP::EventManager;

#
## Configuration parameters
#

# Overrides the PHP-FPM settings globally or per domain.
# - The per domain PHP-FPM settings take precedence over global PHP-FPM settings.
# - The PHP-FPM settings take precedence over those which are defined in the /etc/imscp/php/php.data file.
#
# Note that domain names must be in ASCII format.
my %SETTINGS = (
    # Global PHP-FPM settings - Any setting added here will apply to all domains (globally).
    '*'                    => {
        'pm'                      => 'ondemand',
        'pm.max_children'         => 6,
        'pm.start_servers '       => 1,
        'pm.min_spare_servers'    => 1,
        'pm.max_spare_servers'    => 2,
        'pm.process_idle_timeout' => '60s',
        'pm.max_requests'         => 1000
    },

    # Per domain PHP-FPM settings - Any setting added here will apply to the `test.domain.tld' domains only
    'test.domain.tld' => {
        'pm'                   => 'dynamic',
        'pm.max_children'      => 10,
        'pm.start_servers '    => 2,
        'pm.min_spare_servers' => 1,
        'pm.max_spare_servers' => 4
    }
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConfFile',
    sub {
        my ($tplContent, $tplName, $data) = @_;

        return 0 unless $tplName eq 'pool.conf' && $main::imscpConfig{'HTTPD_SERVER'} eq 'apache_php_fpm';

        # Apply global PHP-FPM settings
        if (exists $SETTINGS{'*'}) {
            while(my ($setting, $value) = each( %{$SETTINGS{'*'}} )) {
                $$tplContent =~ s/^\Q$setting\E\s+=.*?\n/$setting = $value\n/gm;
            }
        }

        return 0 unless exists $SETTINGS{$data->{'DOMAIN_NAME'}};

        # Apply per domain PHP-FPM settings
        while(my ($setting, $value) = each( %{$SETTINGS{$data->{'DOMAIN_NAME'}}} )) {
            $$tplContent =~ s/^\Q$setting\E\s+=.*?\n/$setting = $value\n/gm;
        }

        0;
    }
);

1;
__END__
