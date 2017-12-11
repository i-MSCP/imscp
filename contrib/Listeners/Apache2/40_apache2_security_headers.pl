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

our $VERSION = '1.0.1';

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser qw/ getBlocByRef replaceBlocByRef /;
use Version;

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 40_apache2_security_headers.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'beforeApache2BuildConf',
    sub {
        my ($cfgTpl, $tplName, undef, $serverData) = @_;

        return 0 unless $tplName eq 'domain.tpl' && grep( $_ eq $serverData->{'VHOST_TYPE'}, ( 'domain', 'domain_ssl' ) );

        $serverData->{'CONTENT_SECURITY_POLICY_HEADER_PREFIX'} = $serverData->{'VHOST_TYPE'} eq 'domain' ? 'http' : 'https';

        replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{ [ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ] }
    <IfModule mod_headers.c>
        Header always set Content-Security-Policy "default-src {CONTENT_SECURITY_POLICY_HEADER_PREFIX}: data: 'unsafe-inline' 'unsafe-eval'"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
    # SECTION addons END.
EOF
        0;
    }
);

1;
__END__
