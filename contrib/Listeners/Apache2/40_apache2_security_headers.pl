# i-MSCP Listener::Apache2::Security::Headers listener file
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
## Listener file for Apache2 security headers - https://securityheaders.io
#

package Listener::Apache2::Security::Headers;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser;

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        my $cfgSnippet = <<EOF;
    # BEGIN Listener::Apache2::Security::Headers
    <IfModule mod_headers.c>
        Header always set Content-Security-Policy "default-src {PREFIX}: data: 'unsafe-inline' 'unsafe-eval'"
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
    # END Listener::Apache2::Security::Headers
EOF

        if($tplName =~ /^domain(?:_ssl)?\.tpl$/) {
            if($tplName =~ /^domain\.tpl$/) {
                $cfgSnippet = process( { PREFIX => 'http' }, $cfgSnippet );
            } else {
                $cfgSnippet = process( { PREFIX => 'https' }, $cfgSnippet );
            }
            $$cfgTpl =~ s/(^\s+Include.*<\/VirtualHost>)/\n$cfgSnippet\n$1/sm;
        }
        0;
    }
);

1;
__END__
