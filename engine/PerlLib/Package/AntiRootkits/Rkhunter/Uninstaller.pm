#!/usr/bin/perl

=head1 NAME

Package::AntiRootkits::Rkhunter::Uninstaller - i-MSCP Rkhunter Anti-Rootkits package uninstaller

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

package Package::AntiRootkits::Rkhunter::Uninstaller;

use strict;
use warnings;

use iMSCP::File;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Rkhunter package uninstaller

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

=item _restoreDebianConfig()

 Restore default configuration as provided by the rkhunter Debian package

 Return int 0 on success, 1 on failure

=cut

sub _restoreDebianConfig
{
	my $rs = 0;

	if(-f '/etc/default/rkhunter') {
		my $file = iMSCP::File->new ('filename' => '/etc/default/rkhunter');
		my $rdata = $file->get();
		unless(defined $rdata) {
			error("Unable to read /etc/default/rkhunter file");
			return 1;
		}

		$rdata =~ s/CRON_DAILY_RUN=".*"/CRON_DAILY_RUN=""/i;
		$rdata =~ s/CRON_DB_UPDATE=".*"/CRON_DB_UPDATE=""/i;

		$rs = $file->set($rdata);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Restore daily cron task
	$rs = iMSCP::File->new(
		'filename' => '/etc/cron.daily/rkhunter.disabled'
	)->moveFile(
		'/etc/cron.daily/rkhunter'
	) if -f '/etc/cron.daily/rkhunter.disabled';
	return $rs if $rs;

	# Restore weekly cron tasks
	$rs = iMSCP::File->new(
		'filename' => '/etc/cron.weekly/rkhunter.disabled'
	)->moveFile(
		'/etc/cron.weekly/rkhunter'
	) if -f '/etc/cron.weekly/rkhunter.disabled';
	return $rs if $rs;

	# Restore logrotate task
	$rs = iMSCP::File->new(
		'filename' => '/etc/logrotate.d/rkhunter.disabled'
	)->moveFile(
		'/etc/logrotate.d/rkhunter'
	) if -f '/etc/logrotate.d/rkhunter.disabled';

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
