# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2014 by Laurent Declercq
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
## Listener file that allows to setup Bind9 for local network.
#

package Hooks::Bind9::Localnets;

use iMSCP::HooksManager;

sub onBeforeNamedBuildConf
{
	my $tplContent = shift;
	my $tplName = shift;

	if($tplName eq 'named.conf.options') {
		$$tplContent =~ s/^(\s*allow-recursion).*$/$1 { localnets; };/m;
		$$tplContent =~ s/^(\s*allow-query-cache).*$/$1 { localnets; };/m;
		$$tplContent =~ s/^(\s*allow-transfer).*$/$1 { localnets; };/m;
	}

	0;
}

iMSCP::HooksManager->getInstance()->register('beforeNamedBuildConf', \&onBeforeNamedBuildConf);

1;
__END__
