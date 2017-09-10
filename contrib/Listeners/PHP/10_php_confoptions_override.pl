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

# Allows to add or override PHP directives values globally or per domain,
# depending on the PHP INI level in use.
#
# Be aware that only Fcgid and PHP-FPM Apache2 httpd server implementations are
# supported.
#
# PHP INI level:
#
# Depending on PHP INI level in use, this listener file act differently:
# 
# - Per user level: PHP directive values can be set for the main domain name
#   only. They will apply to main domain, domain aliases and subdomains.
# - Per domain: PHP directive values can be set for the main domain and domain
#   aliases only. PHP directive values set for the main domain will also apply
#   to the main domain's subdomains and PHP directive values set for domain
#   aliases will also apply to domain aliases's subdomains.
# - Per site: PHP directive values can be set for the main domain, domain
#   aliases and subdomains. PHP directive values set will apply only to the
#   targeted domain, domain alias or subdomain.
#
# You can change the PHP INI level by running the following command:
#
#  perl /var/www/imscp/engine/setup/imscp-reconfigure -dar php

package Listener::Php::ConfOptions::Override;

use strict;
use warnings;
use iMSCP::EventManager;
use Servers::httpd;

#
## Configuration parameters
#

# Adds or overrides PHP directive values globally or per domain.
# - The per domain PHP directive values take precedence over global PHP
#   directive values.
# - The PHP directives values take precedence over those which are defined
#   through the i-MSCP PHP editor.
#
# Placeholders that can be used in PHP directive values:
#
# {HOME_DIR} Will be replaced by client homedir path
# {PEAR_DIR} Will be replaced by PHP Pear directory path
# {TMPDIR}   Will be replaced by PHP temporary directory
#
# Note that domain names must be in ASCII format.
my %phpDirectives = (
    # Global PHP directives
    # PHP directive values added here will apply globally
    '*'          => {
        directive_name1 => 'directive_value',
        directive_name2 => 'directive_value'
    },

    # Per domain PHP directives
    # PHP directive values added here will apply according on the
    # current PHP INI level (see the above explainations).
    'domain.tld' => {
        directive_name1 => 'directive_value',
        directive_name2 => 'directive_value'
    }
);

#
## Please, don't edit anything below this line
#

# PHP INI level
my $iniLevel;

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConfFile',
    sub {
        my ($tplContent, $tplName, $data) = @_;

        return unless grep($_ eq $tplName, 'php.ini', 'pool.conf');

        if ( $tplName eq 'php.ini' && $main::imscpConfig{'HTTPD_SERVER'} eq 'apache_fcgid' ) {
            # Adds/Overrides PHP directive values globally
            if ( exists $phpDirectives{'*'} ) {
                while ( my ($directive, $value) = each( %{$phpDirectives{'*'}} ) ) {
                    next if ${$tplContent} =~ s/^$directive\s+=.*/$directive = $value/gim;
                    ${$tplContent} .= "$directive = $value\n";
                }
            }

            return 0 unless exists $phpDirectives{my $domain = _getIniLevel( $data )};

            # Adds/Overrides per domain PHP directive values
            while ( my ($directive, $value) = each( %{$phpDirectives{$domain}} ) ) {
                next if ${$tplContent} =~ s/^$directive\s+=.*/$directive = $value/gim;
                ${$tplContent} .= "$directive = $value\n";
            }

            return 0;
        }

        return 0 unless $tplName eq 'pool.conf' && $main::imscpConfig{'HTTPD_SERVER'} eq 'apache_php_fpm';

        # Adds/Overrides PHP directive values globally
        if ( exists $phpDirectives{'*'} ) {
            while ( my ($directive, $value) = each( %{$phpDirectives{'*'}} ) ) {
                next if ${$tplContent} =~ s/^(php_(?:admin_)?(?:value|flag)\[$directive\]).*/$1 = $value/gim;

                if ( grep($_ eq lc( $value ), ( 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' )) ) {
                    ${$tplContent} .= "php_admin_flag[$directive] = $value\n";
                    next;
                }

                ${$tplContent} .= "php_admin_value[$directive] = $value\n";
            }
        }

        return 0 unless exists $phpDirectives{my $domain = _getIniLevel( $data )};

        # Adds/Overrides per domain PHP directive values
        while ( my ($directive, $value) = each( %{$phpDirectives{$domain}} ) ) {
            next if ${$tplContent} =~ s/^(php_(?:admin_)?(?:value|flag)\[$directive\]).*/$1 = $value/gim;

            if ( grep($_ eq lc( $value ), ( 'on', 'off', '1', '0', 'true', 'false', 'yes', 'no' )) ) {
                ${$tplContent} .= "php_admin_flag[$directive] = $value\n";
                next;
            }

            ${$tplContent} .= "php_admin_value[$directive] = $value\n";
        }

        0;
    }
);

sub _getIniLevel
{
    my ($data) = @_;

    $iniLevel ||= Servers::httpd->factory()->{'phpConfig'}->{'PHP_CONFIG_LEVEL'};
    return $data->{'ROOT_DOMAIN_NAME'} if $iniLevel eq 'per_user';
    return $data->{'PARENT_DOMAIN_NAME'} if $iniLevel eq 'per_domain';
    $data->{'DOMAIN_NAME'};
}

1;
__END__
