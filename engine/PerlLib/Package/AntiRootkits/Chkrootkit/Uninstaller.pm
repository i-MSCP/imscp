#!/usr/bin/perl

=head1 NAME

Package::AntiRootkits::Chkrootkit::Uninstaller - i-MSCP Chkrootkit package uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2014 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::AntiRootkits::Chkrootkit::Uninstaller;

use strict;
use warnings;

use iMSCP::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Chkrootkit package uninstaller

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->_restoreDebianConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _disableDebianConfig()

 Restore default configuration as provided by the chkrootkit Debian package

 Return int - 0 on success, 1 on failure

=cut

sub _restoreDebianConfig
{
	my $rs = 0;

	# Restore daily cron task
	$rs = iMSCP::File->new(
		'filename' => '/etc/cron.daily/chkrootkit.disabled'
	)->moveFile(
		'/etc/cron.daily/chkrootkit'
	) if -f '/etc/cron.daily/chkrootkit.disabled';

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
