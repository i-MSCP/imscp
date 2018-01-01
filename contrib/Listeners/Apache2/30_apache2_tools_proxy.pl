# i-MSCP iMSCP::Listener::Apache2::Tools::Proxy listener file
# Copyright (C) 2017-2018 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2015-2017 Rene Schuster <mail@reneschuster.de>
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
## Provides transparent access to i-MSCP tools (pma, webmail...) through customer domains. For instance:
#
#  http://customer.tld/webmail/ will be redirected to https://customer.tld/webmail/ if ssl is enabled for customer domain
#  http://customer.tld/webmail/ will proxy to i-MSCP webmail transparently if ssl is not enabled for customer domain
#  https://customer.tld/webmail/ will proxy to i-MSCP webmail transparently
#

package iMSCP::Listener::Apache2::Tools::Proxy;

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser qw/ getBlocByRef processByRef replaceBlocByRef /;
use version;

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 30_apache2_tools_proxy.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'beforeApache2BuildConf',
    sub {
        my ($cfgTpl, $tplName, undef, $moduleData, $serverData) = @_;

        return 0 unless $tplName eq 'domain.tpl' && grep( $_ eq $moduleData->{'VHOST_TYPE'}, ( 'domain', 'domain_ssl' ) );

        if ( $serverData->{'VHOST_TYPE'} eq 'domain' && $moduleData->{'SSL_SUPPORT'} ) {
            replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{ [ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ] }
    RedirectMatch 301 ^(/(?:ftp|pma|webmail)\/?)\$ https://$moduleData->{'DOMAIN_NAME'}\$1
    # SECTION addons END.
EOF
            return 0;
        }

        my $cfgProxy = ( $main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes' ) ? "    SSLProxyEngine On\n" : '';
        $cfgProxy .= <<'EOF';
    ProxyPass /ftp/ {HTTP_URI_SCHEME}{HTTP_HOST}:{HTTP_PORT}/ftp/ retry=1 acquire=3000 timeout=600 Keepalive=On
    ProxyPassReverse /ftp/ {HTTP_URI_SCHEME}{HTTP_HOST}:{HTTP_PORT}/ftp/
    ProxyPass /pma/ {HTTP_URI_SCHEME}{HTTP_HOST}:{HTTP_PORT}/pma/ retry=1 acquire=3000 timeout=600 Keepalive=On
    ProxyPassReverse /pma/ {HTTP_URI_SCHEME}{HTTP_HOST}:{HTTP_PORT}/pma/
    ProxyPass /webmail/ {HTTP_URI_SCHEME}{HTTP_HOST}:{HTTP_PORT}/webmail/ retry=1 acquire=3000 timeout=600 Keepalive=On
    ProxyPassReverse /webmail/ {HTTP_URI_SCHEME}{HTTP_HOST}:{HTTP_PORT}/webmail/
EOF
        processByRef(
            {
                HTTP_URI_SCHEME => ( $main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes' ) ? 'https://' : 'http://',
                HTTP_HOST       => $main::imscpConfig{'BASE_SERVER_VHOST'},
                HTTP_PORT       => ( $main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes' )
                    ? $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'} : $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
            },
            \$cfgProxy
        );
        replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{ [ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ] }
    $cfgProxy
    # SECTION addons END.
EOF
        0;
    }
);

1;
__END__
