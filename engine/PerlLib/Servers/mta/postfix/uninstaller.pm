# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package Servers::mta::postfix::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::File;
use File::Basename;
use iMSCP::Dir;
use iMSCP::SystemUser;
use Servers::mta;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'mta'} = Servers::mta->factory();
	$self->{'cfgDir'} = $self->{'mta'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";
	$self->{'config'} = $self->{'mta'}->{'config'};
	$self;
}

sub uninstall
{
	my $self = shift;

	my $rs = $self->_restoreConfFile();
	return $rs if $rs;

	$rs = $self->_buildAliasses();
	return $rs if $rs;

	$rs = $self->_removeUsers();
	return $rs if $rs;

	$self->_removeDirs();
}

sub _removeDirs
{
	my $self = shift;

	for my $file($self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}) {
		iMSCP::Dir->new( dirname => $file )->remove();
	}

	0;
}

sub _removeUsers
{
	my $self = shift;

	iMSCP::SystemUser->new( force => 'yes')->delSystemUser($self->{'config'}->{'MTA_MAILBOX_UID_NAME'});
}

sub _buildAliasses
{
	my $self = shift;

	my $rs = execute('newaliases', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing newaliases command") if $rs && !$stderr;
	$rs;
}

sub _restoreConfFile
{
	my $self = shift;

	for my $file($self->{'config'}->{'POSTFIX_CONF_FILE'}, $self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'}) {
		my $basename = basename($file);

		if(-f "$self->{'bkpDir'}/$basename.system"){
			iMSCP::File->new( filename => "$self->{'bkpDir'}/$basename.system" )->copyFile($file);
		}
	}

	if(-f "$self->{'config'}->{'MTA_SASL_CONF_DIR'}/smtpd.conf") {
		iMSCP::File->new( filename => "$self->{'config'}->{'MTA_SASL_CONF_DIR'}/smtpd.conf" )->delFile();
	}

	0;
}

1;
__END__
