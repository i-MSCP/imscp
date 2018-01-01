# i-MSCP iMSCP::Listener::PhpFpm::Settings::Override listener file
# Copyright (C) 2016-2018 Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Listener::PhpFpm::Settings::Override;

our $VERSION = '1.1.1';

use strict;
use warnings;
use iMSCP::EventManager;
use version;

#
## Configuration parameters
#

# Allows to override PHP-FPM settings in pool configuration files
#
# This listener is only compatible with the fpm PHP SAPI.
#
# PHP configuration level (Per site, per domain or per user FPM settings):
#
# Depending on the PHP configuration level set for a given customer, this
# listener file acts differently:
#
# - Per site   : FPM settings can be set for the main domain, domain aliases
#                and subdomains. FPM settings set will apply only to the
#                targeted domain, domain alias or subdomain.
#
# - Per domain : FPM settings can be set for the main domain and domain aliases
#                only. FPM settings set for the main domain will also apply to
#                the main domain's subdomains, and PHP directives set for domain
#                aliases will also apply to domain aliases's subdomains.
#
# - Per user   : FPM settings can be set for the main domain name only. They
#                will apply to the main domain, domain aliases and subdomains.
#
# The PHP configuration level is set on a per customer basis. You can change it
# for a specific customer as follows:
#
#  1. Connect as administrator and edit the PHP settings for the customer'
#     reseller, then set the PHP configuration level that fit your needs.
#  2. Connect as reseller and edit the PHP settings for the customer, then set
#     the PHP configuration level that fit your needs.
#


# Note that domain names must be in ASCII format.
my %SETTINGS = (
    # Global FPM settings 
    # These settings apply to all domains.
    '*'               => {
        pm                        => 'ondemand',
        'pm.max_children'         => 6,
        'pm.start_servers '       => 1,
        'pm.min_spare_servers'    => 1,
        'pm.max_spare_servers'    => 2,
        'pm.process_idle_timeout' => '60s',
        'pm.max_requests'         => 1000
    },

    # Per site, per domain or per user FPM settings
    # FPM settings added here apply according the current PHP
    # configuration level that is set for the customer that owns the domain.
    # These settings have higher precedence than global settings.
    'test.domain.tld' => {
        pm                     => 'dynamic',
        'pm.max_children'      => 10,
        'pm.start_servers '    => 2,
        'pm.min_spare_servers' => 1,
        'pm.max_spare_servers' => 4
    }
);

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 10_phpfpm_settings_override.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'beforePhpBuildConfFile',
    sub {
        my ($cfgTpl, $filename, undef, $moduleData) = @_;

        return 0 unless $filename eq 'pool.conf'
            && $moduleData->{'PHP_CONFIG_LEVEL_DOMAIN'} eq $moduleData->{'DOMAIN_NAME'};

        if ( exists $SETTINGS{'*'} ) {
            # Apply global PHP-FPM settings
            while ( my ($setting, $value) = each( %{$SETTINGS{'*'}} ) ) {
                next if exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}}->{$setting};
                ${$cfgTpl} =~ s/^\Q$setting\E\s+=.*?\n/$setting = $value\n/gm;
            }
        }

        return 0 unless exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}};

        # Apply per domain PHP-FPM settings
        while ( my ($setting, $value) = each( %{$SETTINGS{$moduleData->{'DOMAIN_NAME'}}} ) ) {
            ${$cfgTpl} =~ s/^\Q$setting\E\s+=.*?\n/$setting = $value\n/gm;
        }

        0;
    }
);

1;
__END__
