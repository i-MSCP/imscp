# i-MSCP Listener::Php::ConfOptions::Override listener file
# Copyright (C) 2016 Laurent Declercq <l.declercq@nuxwin.com>
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
## You can do this by running: perl imscp-autoinstall -dar httpd
#

package Listener::Php::ConfOptions::Override;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::EventManager;

#
## Configuration parameters
#

# Add or overrides the PHP configuration options globally or per domain.
# - The per domain PHP configuration options take precedence over global PHP configuration options.
# - The PHP configuration options take precedence over those which are defined through the i-MSCP PHP editor.
my %configOptions = (
    '<domain_name>' => { # Any PHP configuration added here will apply to test.tld only
        '<option_name>' => '<option_value>'
    },
    '*' => { # Any PHP configuration option added here will apply globally.
        '<option_name>' => '<option_value>'
    }
);

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register('beforeHttpdBuildConfFile', sub {
    my ($tplContent, $tplName, $data) = @_;

    if($tplName eq 'php.ini' && $main::imscpConfig{'HTTPD_SERVER'} eq 'apache_fcgid') {
        # Apply per domain PHP configuration options overriding if any
        if(exists $configOptions{$data->{'DOMAIN_NAME'}}) {
            while(my($option, $value) = each(%{$configOptions{$data->{'DOMAIN_NAME'}}})) {
                unless($$tplContent =~ s/^$option\s+=.*/$option = $value/gim) {
                    $$tplContent .= "$option = $value\n";
                }
            }
        }

        # Apply global PHP configuration options overriding if any
        if(exists $configOptions{'*'}) {
            while(my($option, $value) = each(%{$configOptions{'*'}})) {
                unless($$tplContent =~ s/^$option\s+=.*/$option = $value/gim) {
                    $$tplContent .= "$option = $value\n";
                }
            }
        }

        return 0;
    }

    if($tplName ne 'pool.conf' || $main::imscpConfig{'HTTPD_SERVER'} ne 'apache_php_fpm') {
        return 0;
    }

    # Apply per domain PHP configuration options overriding if any
    if(exists $configOptions{$data->{'DOMAIN_NAME'}}) {
        while(my($option, $value) = each(%{$configOptions{$data->{'DOMAIN_NAME'}}})) {
            unless($$tplContent =~ s/^(php_(?:admin_)?(?:value|flag)\[$option\]).*/$1 = $value/gim) {
                if(lc($value) ~~ [ 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' ]) {
                    $$tplContent .= "php_flag[$option] = $value\n";
                } else {
                    $$tplContent .= "php_value[$option] = $value\n";
                }
            }
        }
    }

    # Apply global PHP configuration options overriding if any
    if(exists $configOptions{'*'}) {
        while(my($option, $value) = each(%{$configOptions{'*'}})) {
            unless($$tplContent =~ s/^(php_(?:admin_)?(?:value|flag)\[$option\]).*/$1 = $value/gim) {
                if(lc($value) ~~ [ 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' ]) {
                    $$tplContent .= "php_flag[$option] = $value\n";
                } else {
                    $$tplContent .= "php_value[$option] = $value\n";
                }
            }
        }
    }

    0;
});

1;
__END__
