# i-MSCP Listener::Apache2::Redirect::Temporarily listener file
# Copyright (C) 2015 Ninos Ego <me@ninosego.de>
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
## Listener file which changes the domain redirect type in customer's vhost files from 301 to 302.
#

package Listener::Apache2::Redirect::Temporarily;

use iMSCP::EventManager;

sub changeRedirectType
{
	my ($cfgTpl, $tplName, $data) = @_;

	if($tplName =~ /^domain_redirect(?:_ssl)?\.tpl$/ && !$data->{'HSTS_SUPPORT'}) {
		my $search = "Redirect 301 / {FORWARD}\n";
		my $replace = "Redirect / {FORWARD}\n";

		$$cfgTpl =~ s/$search/$replace/;
	}

	0;
}

iMSCP::EventManager->getInstance()->register('beforeHttpdBuildConf', \&changeRedirectType);

1;
__END__
