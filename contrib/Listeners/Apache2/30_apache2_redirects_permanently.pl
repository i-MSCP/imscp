# i-MSCP Listener::Apache2::Redirect::Permanently listener file
# Copyright (C) 2015-2016 Ninos Ego <me@ninosego.de>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA

#
## Changes the domain redirect type in customer's vhost files from 302 to 301.
#

package Listener::Apache2::Redirect::Permanently;

use strict;
use warnings;
use iMSCP::EventManager;

iMSCP::EventManager->getInstance()->register(
    'beforeHttpdBuildConf',
    sub {
        my ($cfgTpl, $tplName) = @_;

        return 0 unless $tplName =~ /^domain_redirect(?:_ssl)?\.tpl$/;

        $$cfgTpl =~ s%Redirect / {FORWARD}\n%Redirect 301 / {FORWARD}\n%;
        0;
    }
);

1;
__END__
