# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2017-2018 by Laurent Declercq <l.declercq@nnuxwin.com>
# Copyright (C) 2013-2017 by Sascha Bay
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

#
## Allows to add additional server aliases in the given Apache2 vhosts.
#

package iMSCP::Listener::Apache2::ServerAlias::Override;

our $VERSION = '1.0.2';

use strict;
use warnings;
use iMSCP::EventManager;
use version;

#
## Configuration variables
#

# Map Apache2 vhosts (domains) to additional server aliases 
my %serverAliases = (
    'example1.com' => 'example1.in example1.br', # Add example1.in and example1.br server aliases to exemple1.com vhost
    'example2.com' => 'example2.in example2.br' # Add example2.in and example2.br server aliases to exemple2.com vhost
);

#
## Please, don't edit anything below this line
#

version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.1' ) or die(
    sprintf( "The 20_apache2_serveralias_override.pl listener file version %s requires i-MSCP >= 1.6.0", $VERSION )
);

iMSCP::EventManager->getInstance()->register(
    'afterApache2BuildConf',
    sub {
        my ($tplContent, $tplName, undef, $moduleData) = @_;

        return 0 unless $tplName eq 'domain.tpl' && $serverAliases{$moduleData->{'DOMAIN_NAME'}};

        ${$tplContent} =~ s/^(\s+ServerAlias.*)/$1 $serverAliases{$moduleData->{'DOMAIN_NAME'}}/m;
        0;
    }
);

1;
__END__
