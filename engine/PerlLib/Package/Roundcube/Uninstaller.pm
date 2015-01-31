=head1 NAME

Package::Roundcube::Uninstaller - i-MSCP Roundcube package uninstaller

=cut

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
# @category    i-MSCP
# @copyright   2010-2015 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Package::Roundcube::Uninstaller;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Database;
use Package::FrontEnd;
use Package::Roundcube;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Roundcube package uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_removeSqlUser();
	return $rs if $rs;

	$rs = $self->_removeSqlDatabase();
	return $rs if $rs;

	rs = $self->_unregisterConfig();
	return $rs if $rs;

	$self->_removeFiles();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Roundcube::Uninstaller

=cut

sub _init
{
	my $self = $_[0];

	$self->{'frontend'} = Package::FrontEnd->getInstance();
	$self->{'roundcube'} = Package::Roundcube->getInstance();

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/roundcube";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	$self->{'config'} = $self->{'roundcube'}->{'config'};

	$self;
}

=item _removeSqlUser()

 Remove SQL user

 Return int 0 on success, other on failure

=cut

sub _removeSqlUser
{
	my $self = $_[0];

	my $database = iMSCP::Database->factory();

	# We do not catch any error here - It's expected
	for($main::imscpConfig{'DATABASE_USER_HOST'}, $main::imscpConfig{'BASE_SERVER_IP'}, 'localhost', '127.0.0.1', '%') {
		next unless $_;
		$database->doQuery('dummy', "DROP USER ?@?", $self->{'config'}->{'DATABASE_USER'}, $_);
	}

	$database->doQuery('dummy', 'FLUSH PRIVILEGES');

	0;
}

=item _removeSqlDatabase()

 Remove database

 Return int 0

=cut

sub _removeSqlDatabase
{
	my $self = $_[0];

	my $database = iMSCP::Database->factory();

	my $dbName = $database->quoteIdentifier($main::imscpConfig{'DATABASE_NAME'} . '_pma');

	$database->doQuery('delete', "DROP DATABASE IF EXISTS $dbName");

	0;
}

=item _unregisterConfig

 Remove include directive from frontEnd vhost files

 Return int 0 on success, other on failure

=cut

sub _unregisterConfig
{
	my ($tplContent, $tplName) = @_;

	for my $vhostFile('00_master.conf', '00_master_ssl.conf') {
		if(-f "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$vhostFile") {
			my $file = iMSCP::File->new(
				filename => "$self->{'frontend'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$vhostFile"
			);

			my $fileContent = $file->get();
			unless(defined $fileContent) {
				error("Unable to read file $file->{'filename'}");
				return 1;
			}

			$fileContent =~ s/[\t ]*include imscp_roundcube.conf;\n//;

			my $rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;
		}
	}

	$self->{'frontend'}->{'reload'} = 1;

	0;
}

=item _removeFiles()

 Remove files

 Return int 0

=cut

sub _removeFiles
{
	my $self = $_[0];

	my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail" )->remove();
	return $rs if $rs;

	$rs = iMSCP::Dir->new( dirname => $self->{'cfgDir'} )->remove();
	return $rs if $rs;

	if(-f "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf") {
		$rs = iMSCP::File->new(
			filename => "$self->{'frontend'}->{'config'}->{'HTTPD_CONF_DIR'}/imscp_roundcube.conf"
		)->delFile();
		return $rs if $rs;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
