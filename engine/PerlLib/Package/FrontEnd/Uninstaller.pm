#!/usr/bin/perl

=head1 NAME

Package::FrontEnd::Uninstaller - i-MSCP FrontEnd package Uninstaller

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

package Package::FrontEnd::Uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package uninstaller

=head1 PUBLIC METHODS

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_removeMasterWebUser();
	return $rs if $rs;

	$rs = $self->_removeHttpdConfig();
	return $rs if $rs;

	$rs = $self->_removePhpConfig();
	return $rs if $rs;

	$self->_removeInitScript();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::FrontEnd::Installer

=cut

sub _init
{
	my $self = $_[0];

	$self->{'frontend'} = Package::FrontEnd;

	$self->{'config'} = $self->{'frontend'}->{'config'};

	$self;
}

=item _removeMasterWebUser()

 Remove master Web user

 Return int 0 on success, other on failure

=cut

sub _removeMasterWebUser
{
	my $self = $_[0];

	my $rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	return $rs if $rs;

	iMSCP::SystemGroup->getInstance()->delSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
}

=item _removeHttpdConfig()

 Remove httpd configuration

 Return int 0 on success, other on failure

=cut

sub _removeHttpdConfig
{
	my $self = $_[0];

	my $rs = 0;

	# Remove vhost files
	for('00_master_ssl.conf', '00_master.conf') {
		$rs = $self->{'frontend'}->disableSites($_);
		return $rs if $rs;

		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_") {
			$rs = iMSCP::File->new(
				'filename' => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_"
			)->delFile();
			return $rs if $rs;
		}
	}

	# Remove imscp_fastcgi.conf file
	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf"
		)->delFile();
		return $rs if $rs;
	}

	# Remove imscp_php.conf file
	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf") {
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf"
		)->delFile();
		return $rs if $rs;
	}

	# Re-enable default vhost
	if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/default") { # Nginx as provided by Debian
		$rs = $self->{'frontend'}->enableSites('default');
		return $rs if $rs;
	} elsif("$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled") { # Nginx package as provided by Nginx
		$rs = iMSCP::File->new(
			'filename' => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
		)->moveFile("$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf");
		return $rs if $rs;
	}

	0;
}

=item _removePhpConfig()

 Remove PHP configuration

 Return int 0 on success, other on failure

=cut

sub _removePhpConfig
{
	my $self = $_[0];

	iMSCP::Dir->new('dirname' => "$self->{'config'}->{'PHP_STARTER_DIR'}/master")->remove();
}

=item _removeInitScript()

 Remove init script

 Return int 0 on success, other on failure

=cut

sub _removeInitScript
{
	my $self = $_[0];

	0;
}

=back

=head1 AUTHORS

Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
