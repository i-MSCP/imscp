# i-MSCP iMSCP::Listener::Php::ConfOptions::Override listener file
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

package iMSCP::Listener::Php::IniOptions::Override;

our $VERSION = '1.1.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::Servers::Php;
use versions;

#
## Configuration parameters
#

# Allows to add or override INI options.
#
# This listener is only compatible with the cgi and fpm PHP SAPIs.
#
# PHP configuration level (Per site, per domain or per user INI options):
#
# Depending on the PHP configuration level set for a given customer, this
# listener file acts differently:
# 
# - Per site   : INI options can be set for the main domain, domain aliases
#                and subdomains. INI options set will apply only to the
#                targeted domain, domain alias or subdomain.
#
# - Per domain : INI options can be set for the main domain and domain aliases
#                only. INI options set for the main domain will also apply to
#                the main domain's subdomains, and INI options set for domain
#                aliases will also apply to domain aliases's subdomains.
#
# - Per user   : INI options can be set for the main domain name only. They
#                will apply to the main domain, domain aliases and subdomains.
#
# The PHP configuration level is set on a per customer basis. You can change it
# for a specific customer as follows:
#
#  1. Connect as administrator and edit the PHP settings for the customer'
#     reseller, then set the PHP configuration level that fit your needs.
#  2. Connect as reseller and edit the PHP settings for the customer, then set
#     the PHP configuration level that fit your needs.

# Placeholders that can be used in INI options:
#
# {HOME_DIR} Will be replaced by client homedir path
# {TMPDIR}   Will be replaced by PHP temporary directory
#
# Note that domain names must be in ACE form.
my %SETTINGS = (
    # Global INI options
    # These settings apply to all domains.
    '*'          => {
        ini_option_1 => 'ini_option_value',
        ini_option_2 => 'ini_option_value'
    },

    # Per site, per domain or per user INI options
    # INI options added here apply according the current PHP configuration
    # level that is set for the customer that owns the domain.
    # These settings have higher precedence than global settings.
    'domain.tld' => {
        ini_option_1 => 'ini_option_value',
        ini_option_2 => 'ini_option_value'
    }
);

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 10_php_inioptions_override.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'beforePhpBuildConfFile',
    sub {
        my ($cfgTpl, $filename, undef, $moduleData) = @_;

        return 0 unless grep($filename eq $_, 'php.ini.user', 'pool.conf')
            && $moduleData->{'PHP_CONFIG_LEVEL_DOMAIN'} eq $moduleData->{'DOMAIN_NAME'};

        if ( $tplName eq 'php.ini.user' ) {
            if ( exists $SETTINGS{'*'} ) {
                # Adds/Overrides INI options values globally
                while ( my ($option, $value) = each( %{$SETTINGS{'*'}} ) ) {
                    next if exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}}->{$option};
                    next if ${$cfgTpl} =~ s/^$option\s+=.*/$option = $value/gim;
                    ${$cfgTpl} .= "$option = $value\n";
                }
            }

            return 0 unless exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}};

            # Adds/Overrides per domain INI options values
            while ( my ($option, $value) = each( %{$SETTINGS{$moduleData->{'DOMAIN_NAME'}}} ) ) {
                next if ${$cfgTpl} =~ s/^$option\s+=.*/$option = $value/gim;
                ${$cfgTpl} .= "$option = $value\n";
            }

            return 0;
        }

        return 0 unless $tplName eq 'pool.conf';

        if ( exists $SETTINGS{'*'} ) {
            # Adds/Overrides INI options values globally
            while ( my ($option, $value) = each( %{$SETTINGS{'*'}} ) ) {
                next if ${$cfgTpl} =~ s/^(php_(?:admin_)?(?:value|flag)\[$option\]).*/$1 = $value/gim;

                if ( grep($_ eq lc( $value ), ( 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' )) ) {
                    ${$cfgTpl} .= "php_admin_flag[$option] = $value\n";
                    next;
                }

                ${$cfgTpl} .= "php_admin_value[$option] = $value\n";
            }
        }

        return 0 unless exists $SETTINGS{$moduleData->{'DOMAIN_NAME'}};

        # Adds/Overrides per domain INI options values
        while ( my ($option, $value) = each( %{$SETTINGS{$moduleData->{'DOMAIN_NAME'}}} ) ) {
            next if ${$cfgTpl} =~ s/^(php_(?:admin_)?(?:value|flag)\[$option\]).*/$1 = $value/gim;

            if ( grep($_ eq lc( $value ), ( 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' )) ) {
                ${$cfgTpl} .= "php_admin_flag[$option] = $value\n";
                next;
            }

            ${$cfgTpl} .= "php_admin_value[$option] = $value\n";
        }

        0;
    }
);

1;
__END__
