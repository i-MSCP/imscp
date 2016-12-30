# i-MSCP Listener::Php::ConfOptions::Override listener file
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
## Allows to add or override PHP configuration options globally or per domain.
##
## Be aware that only Fcgid and PHP-FPM Apache2 httpd server implementations are supported.
## Note: When you want operate on a per domain basis, don't forget to set the PHP configuration level to 'per_site'.
## You can do this by running: perl /var/www/imscp/engine/setup/imscp-reconfigure -dar php
#

package Listener::Php::ConfOptions::Override;

use strict;
use warnings;
use iMSCP::EventManager;

#
## Configuration parameters
#

# Add or overrides the PHP configuration options globally or per domain.
# - The per domain PHP configuration options take precedence over global PHP configuration options.
# - The PHP configuration options take precedence over those which are defined through the i-MSCP PHP editor.
#
# Placeholders that can be used in PHP option values:
#
# {HOME_DIR} Will be replaced by client homedir path
# {PEAR_DIR} Will be replaced by PHP Pear directory path
# {TMPDIR}   Will be replaced by PHP temporary directory
#
# Note that domain names must be in ASCII format.
my %configOptions = (
    # Any PHP configuration option added here will apply globally (to all domains).
    '*'             => {
        '<option_name1>' => '<option_value1>',
        '<option_name2>' => '<option_value2>'
    },

    # Any PHP configuration added here will apply to domain1.tld only
    'test.domain.tld' => {
        'option_name1' => 'option_value1',
        'option_name2' => 'option_value2'
    }
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConfFile',
    sub {
        my ($tplContent, $tplName, $data) = @_;

        if ($tplName eq 'php.ini' && $main::imscpConfig{'HTTPD_SERVER'} eq 'apache_fcgid') {
            # Apply global PHP configuration options overriding if any
            if (exists $configOptions{'*'}) {
                while(my ($option, $value) = each( %{$configOptions{'*'}} )) {
                    $$tplContent .= "$option = $value\n" unless $$tplContent =~ s/^$option\s+=.*/$option = $value/gim;
                }
            }

            # Apply per domain PHP configuration options overriding if any
            if (exists $configOptions{$data->{'DOMAIN_NAME'}}) {
                while(my ($option, $value) = each( %{$configOptions{$data->{'DOMAIN_NAME'}}} )) {
                    $$tplContent .= "$option = $value\n" unless $$tplContent =~ s/^$option\s+=.*/$option = $value/gim;
                }
            }

            return 0;
        }

        return 0 unless $tplName eq 'pool.conf' && $main::imscpConfig{'HTTPD_SERVER'} eq 'apache_php_fpm';

        # Apply global PHP configuration options overriding if any
        if (exists $configOptions{'*'}) {
            while(my ($option, $value) = each( %{$configOptions{'*'}} )) {
                next if $$tplContent =~ s/^(php_(?:admin_)?(?:value|flag)\[$option\]).*/$1 = $value/gim;
                if (grep($_ eq lc( $value ), ( 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' ))) {
                    $$tplContent .= "php_admin_flag[$option] = $value\n";
                } else {
                    $$tplContent .= "php_admin_value[$option] = $value\n";
                }
            }
        }

        return 0 unless exists $configOptions{$data->{'DOMAIN_NAME'}};

        # Apply per domain PHP configuration options overriding if any
        while(my ($option, $value) = each( %{$configOptions{$data->{'DOMAIN_NAME'}}} )) {
            next if $$tplContent =~ s/^(php_(?:admin_)?(?:value|flag)\[$option\]).*/$1 = $value/gim;
            if (grep($_ eq lc( $value ), ( 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' ))) {
                $$tplContent .= "php_admin_flag[$option] = $value\n";
            } else {
                $$tplContent .= "php_admin_value[$option] = $value\n";
            }
        }

        0;
    }
);

1;
__END__
