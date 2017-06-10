# i-MSCP Listener::Nginx::HSTS listener file
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
## Activates HTTP Strict Transport Security (HSTS).
#

package Listener::Nginx::HSTS;

use strict;
use warnings;
use iMSCP::EventManager;
use iMSCP::TemplateParser qw/ getBloc replaceBloc /;

iMSCP::EventManager->getInstance()->register(
    'afterFrontEndBuildConfFile',
    sub {
        my ($tplContent, $tplName) = @_;

        return 0 unless $tplName eq '00_master_ssl.nginx'
            && $main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes';

        ${$tplContent} = replaceBloc(
            "# SECTION custom BEGIN.\n",
            "# SECTION custom END.\n",
            "    # SECTION custom BEGIN.\n".
                getBloc(
                    "# SECTION custom BEGIN.\n",
                    "# SECTION custom END.\n",
                    ${$tplContent}
                ).
                <<'EOF'
    add_header Strict-Transport-Security "max-age=31536000";
EOF
                .
                "    # SECTION custom END.\n",
            ${$tplContent}
        );

        0;
    }
);

1;
__END__
