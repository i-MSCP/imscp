# i-MSCP Listener::Gitlab listener file
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
## Listener file that stop/start GitLabl during i-MSCP update.
#

package Listener::Gitlab;

use iMSCP::EventManager;
use iMSCP::Service;

sub startGitlab
{
	iMSCP::Service->getInstance()->start('gitlab-ctl');

	0;
}

sub stopGitlab
{
	iMSCP::Service->getInstance()->stop('gitlab-ctl');

	0;
}

my $eventManager = iMSCP::EventManager->getInstance();
$eventManager->register('beforeFrontEndStop', \&stopGitlab);
$eventManager->register('afterFrontEndStart', \&startGitlab);
$eventManager->register('beforeFrontEndReload', \&stopGitlab);
$eventManager->register('afterFrontEndReload', \&startGitlab);
$eventManager->register('beforeFrontEndRestart', \&stopGitlab);
$eventManager->register('afterFrontEndRestart', \&startGitlab);

1;
__END__
