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
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::SystemUser;
use Servers::mta::postfix;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = $_[0];

	$self->{'mta'} = Servers::mta::postfix->getInstance();

	$self->{'cfgDir'} = $self->{'mta'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'vrlDir'} = "$self->{'cfgDir'}/imscp";

	$self->{'config'} = $self->{'mta'}->{'config'};

	$self;
}

sub uninstall
{
	my $self = $_[0];

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
	my $self = $_[0];
	my $rs = 0;

	for ($self->{'config'}->{'MTA_VIRTUAL_CONF_DIR'}, $self->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'}) {
		$rs = iMSCP::Dir->new('dirname' => $_)->remove();
		return $rs if $rs;
	}

	0;
}

sub _removeUsers
{
	my $self = $_[0];

	iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($self->{'config'}->{'MTA_MAILBOX_UID_NAME'});
}

sub _buildAliasses
{
	my $self = $_[0];

	# Rebuilding the database for the mail aliases file - Begin
	my ($stdout, $stderr);
	my $rs = execute("$self->{'config'}->{'CMD_NEWALIASES'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing $self->{'config'}->{'CMD_NEWALIASES'}") if ! $stderr && $rs;

	$rs;
}

sub _restoreConfFile
{
	my $self = $_[0];
	my $rs = 0;

	for ($self->{'config'}->{'POSTFIX_CONF_FILE'}, $self->{'config'}->{'POSTFIX_MASTER_CONF_FILE'}) {
		my $filename = fileparse($_);

		if(-f "$self->{'bkpDir'}/$filename.system"){
			$rs = iMSCP::File->new(
				'filename' => "$self->{'bkpDir'}/$filename.system"
			)->copyFile(
				$_
			);
			return $rs if $rs;
		}
	}

	if(-f "$self->{'config'}->{'MTA_SASL_CONF_DIR'}/smtpd.conf") {
		$rs = iMSCP::File->new('filename' => "$self->{'config'}->{'MTA_SASL_CONF_DIR'}/smtpd.conf")->delFile();
		return $rs if $rs;
	}

	0;
}

1;
__END__
