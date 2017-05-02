# i-MSCP Listener::Named::Rrl listener file
# Copyright (C) 2010-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
## Implement RRL (Response Rate Limiting Feature for Bind9)
## See https://kb.isc.org/article/AA-00994/0/Using-the-Response-Rate-Limiting-Feature-in-BIND-9.10.html
## Note: Before use of this listener, you must ensure that your Bind9 version support RRL.
##
#

package Listener::Named::Rrl;

use strict;
use warnings;
use File::Basename;
use iMSCP::EventManager;
use iMSCP::TemplateParser;
use Servers::named;

#
## Configuration variables
#

# Max responses per second
my $responsesPerSecond = 10;

#
## Please, don't edit anything below this line
#

iMSCP::EventManager->getInstance()->register(
    'afterNamedBuildConf',
    sub {
        my ($tplContent, $tplName) = @_;

        return 0 unless $tplName eq basename( Servers::named->factory()->{'config'}->{'BIND_OPTIONS_CONF_FILE'} );

        $$tplContent = replaceBloc(
            "// imscp [{ENTRY_ID}] entry BEGIN\n",
            "// imscp [{ENTRY_ID}] entry ENDING\n", <<"EOF", $$tplContent, 'preserveTags' );
        rate-limit {
            responses-per-second $responsesPerSecond;
        };

EOF
        0;
    }
);

1;
__END__
