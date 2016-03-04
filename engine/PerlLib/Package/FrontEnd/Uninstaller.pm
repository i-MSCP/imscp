=head1 NAME

Package::FrontEnd::Uninstaller - i-MSCP FrontEnd package Uninstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::FrontEnd::Uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::SystemUser;
use iMSCP::SystemGroup;
use iMSCP::Service;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package uninstaller.

=head1 PUBLIC METHODS

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_removeMasterWebUser();
	$rs ||= $self->_removeHttpdConfig();
	$rs ||= $self->_removePhpConfig();
	$rs ||= $self->_removeInitScript();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::FrontEnd::Uninstaller

=cut

sub _init
{
	my $self = shift;

	$self->{'frontend'} = Package::FrontEnd->getInstance();
	$self->{'config'} = $self->{'frontend'}->{'config'};
	$self;
}

=item _removeMasterWebUser()

 Remove master Web user

 Return int 0 on success, other on failure

=cut

sub _removeMasterWebUser
{
	my $self = shift;

	my $rs = iMSCP::SystemUser->new( force => 'yes' )->delSystemUser(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
	$rs ||= iMSCP::SystemGroup->getInstance()->delSystemGroup(
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'}
	);
}

=item _removeHttpdConfig()

 Remove httpd configuration

 Return int 0 on success, other on failure

=cut

sub _removeHttpdConfig
{
	my $self = shift;

	for my $vhost('00_master_ssl.conf', '00_master.conf') {
		my $rs = $self->{'frontend'}->disableSites($vhost);
		return $rs if $rs;

		if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$vhost") {
			$rs = iMSCP::File->new(filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$vhost")->delFile();
			return $rs if $rs;
		}
	}

	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf") {
		my $rs = iMSCP::File->new(filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf")->delFile();
		return $rs if $rs;
	}

	if(-f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf") {
		my $rs = iMSCP::File->new(filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf")->delFile();
		return $rs if $rs;
	}

	if(-f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/default") { # Nginx as provided by Debian
		my $rs = $self->{'frontend'}->enableSites('default');
		return $rs if $rs;
	} elsif("$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled") { # Nginx package as provided by Nginx
		my $rs = iMSCP::File->new(
			filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
		)->moveFile(
			"$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf"
		);
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
	my $self = shift;

	iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_STARTER_DIR'}/master" )->remove();
}

=item _removeInitScript()

 Remove init script

 Return int 0 on success, other on failure

=cut

sub _removeInitScript
{
	my $self = shift;

	iMSCP::Service->getInstance()->remove('imscp_panel');

	for my $pFormat('/etc/init.d/%s', '/etc/systemd/system/%s.service', '/etc/init/%s.conf', '/etc/init/%s.override') {
		my $file = sprintf($pFormat, 'imscp_panel');

		if(-f $file) {
			my $rs = iMSCP::File->new( filename => $file )->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
