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

package Servers::po::dovecot::uninstaller;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Database;
use iMSCP::ProgramFinder;
use Servers::po;
use Servers::mta;
use parent 'Common::SingletonClass';

sub uninstall
{
	my $self = shift;

	my $rs = $self->_restoreConfFile();
	return $rs if $rs;

	$self->_dropSqlUser();

	if(iMSCP::ProgramFinder::find('doveadm')) {
		$rs = execute("doveadm mount remove $main::imscpConfig{'USER_WEB_DIR'}/*", \my $stdout, \my $stderr);
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}
}

sub _init
{
	my $self = shift;

	$self->{'po'} = Servers::po->factory();
	$self->{'mta'} = Servers::mta->factory();
	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";
	$self->{'config'} = $self->{'po'}->{'config'};
	$self;
}

sub _restoreConfFile
{
	my $self = shift;

	for my $filename('dovecot.conf', 'dovecot-sql.conf') {
		if(-f "$self->{bkpDir}/$filename.system") {
			iMSCP::File->new( filename => "$self->{bkpDir}/$filename.system" )->copyFile(
				"$self->{'config'}->{'DOVECOT_CONF_DIR'}/$filename"
			);
		}
	}

	my $file = iMSCP::File->new( filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf" );
	$file->mode(0644);
	$file->owner($main::imscpConfig{'ROOT_USER'}, $self->{'mta'}->{'MTA_MAILBOX_GID_NAME'});
}

sub _dropSqlUser
{
	my $self = shift;

	if($self->{'config'}->{'DATABASE_USER'}) {
		my $database = iMSCP::Database->factory();

		$database->doQuery('d', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, 'localhost');
		$database->doQuery('d', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, '%');
		$database->doQuery(
			'd', 'DROP USER ?@?', $self->{'config'}->{'DATABASE_USER'}, $main::imscpConfig{'DATABASE_USER_HOST'}
		);
		$database->doQuery('f', 'FLUSH PRIVILEGES');

	}

	0;
}

1;
__END__
