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
#
# @category     i-MSCP
# @copyright    2010-2015 by i-MSCP | http://i-mscp.net
# @author       Daniel Andreca <sci2tech@gmail.com>
# @author       Laurent Declercq <l.declercq@nuxwin.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Servers::po::courier::uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use Servers::po::courier;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = $_[0];

	$self->{'po'} = Servers::po::courier->getInstance();

	$self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'po'}->{'config'};

	$self;
}

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_removeSqlUser();
	return $rs if $rs;

	$rs = $self->_restoreConfFile();
	return $rs if $rs;

	$rs = $self->_authDaemon();
	return $rs if $rs;

	$self->_deleteQuotaWarning();
}

=item _removeSqlUser()

 Remove any authdaemon SQL user

 Return int 0

=cut

sub _removeSqlUser
{
	my $self = $_[0];

	my $database = iMSCP::Database->factory();

	# We do not catch any error here - It's expected
	for($main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%') {
		next if ! $_;
		$database->doQuery('dummy', "DROP USER ?@?", $self->{'config'}->{'DATABASE_USER'}, $_);
	}

	$database->doQuery('dummy', 'FLUSH PRIVILEGES');

	0;
}

sub _restoreConfFile
{
	my $self = $_[0];

	if(-f "$self->{'bkpDir'}/$self->{'config'}->{'AUTHDAEMON_SNAME'}.system") {
		my $file = iMSCP::File->new('filename' => "$self->{'bkpDir'}/$self->{'config'}->{'AUTHDAEMON_SNAME'}.system");

		my $rs = $file->copyFile("$main::imscpConfig{'INIT_SCRIPTS_DIR'}/$self->{'config'}->{'AUTHDAEMON_SNAME'}");
		return $rs if $rs;

		$file->{'filename'} = "$main::imscpConfig{'INIT_SCRIPTS_DIR'}/$self->{'config'}->{'AUTHDAEMON_SNAME'}";

		$rs = $file->mode(0755);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;
	}

	for ('authdaemonrc', 'authmysqlrc', $self->{'config'}->{'COURIER_IMAP_SSL'}, $self->{'config'}->{'COURIER_POP_SSL'}) {
		my $rs = iMSCP::File->new(
			'filename' => "$self->{'bkpDir'}/$_.system"
		)->copyFile(
			"$self->{'config'}->{'AUTHLIB_CONF_DIR'}/$_"
		) if -f "$self->{'bkpDir'}/$_.system";
		return $rs if $rs;
	}

	0;
}

sub _authDaemon
{
	my $self= $_[0];

	my $file = iMSCP::File->new('filename' => "$self->{'config'}->{'AUTHLIB_CONF_DIR'}/authdaemonrc");

	my $rs = $file->mode(0660);
	return $rs if $rs;

	$file->owner($self->{'config'}->{'AUTHDAEMON_USER'}, $self->{'config'}->{'AUTHDAEMON_GROUP'});
}

sub _deleteQuotaWarning
{
	my $self = $_[0];

	if(-f $self->{'config'}->{'QUOTA_WARN_MSG_PATH'}) {
		iMSCP::File->new('filename' => $self->{'config'}->{'QUOTA_WARN_MSG_PATH'})->delFile();
	} else {
		0;
	}
}

1;
__END__
