# i-MSCP Listener::Apache2::Security::Headers listener file
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2016-2017 Rene Schuster <mail@reneschuster.de>
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
## Listener file that add security headers (https://securityheaders.io) in customer Apache2 vhosts
#

package Listener::Apache2::Security::Headers;

use iMSCP::EventManager;
use iMSCP::TemplateParser;

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConf',
    sub {
        my ($cfgTpl, $tplName, $data) = @_;

        return 0 unless $tplName eq 'domain.tpl'
            && grep( $_ eq $data->{'VHOST_TYPE'}, ( 'domain', 'domain_ssl' ) );

        ${$cfgTpl} = replaceBloc(
            "# SECTION addons BEGIN.\n",
            "# SECTION addons END.\n",
            "    # SECTION addons BEGIN.\n".
                getBloc(
                    "# SECTION addons BEGIN.\n",
                    "# SECTION addons END.\n",
                    ${$cfgTpl}
                ).process({ PREFIX => ($data->{'VHOST_TYPE'} eq 'domain') ? 'http' : 'https' }, <<"EOF")
    <IfModule mod_headers.c>
        Header always set Content-Security-Policy "default-src {PREFIX}: data: 'unsafe-inline' 'unsafe-eval'"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
EOF
                ."    # SECTION addons END.\n",
            ${$cfgTpl}
        );

        0;
    }
);

1;
__END__
