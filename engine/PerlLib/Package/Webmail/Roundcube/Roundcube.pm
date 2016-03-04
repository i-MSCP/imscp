=head1 NAME

Package::Webmail::Roundcube::Roundcube - i-MSCP Roundcube package

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

package Package::Webmail::Roundcube::Roundcube;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Database;
use Scalar::Defer;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Roundcube package for i-MSCP.

 RoundCube Webmail is a browser-based multilingual IMAP client with an application-like user interface. It provides full
functionality expected from an email client, including MIME support, address book, folder manipulation and message
filters.

 The user interface is fully skinnable using XHTML and CSS 2.

 Project homepage: http://www.roundcube.net/

=head1 PUBLIC METHODS

=over 4

=item showDialog(\%dialog)

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
	my ($self, $dialog) = @_;

	require Package::Webmail::Roundcube::Installer;
	Package::Webmail::Roundcube::Installer->getInstance()->showDialog($dialog);
}

=item preinstall()

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
	require Package::Webmail::Roundcube::Installer;
	Package::Webmail::Roundcube::Installer->getInstance()->preinstall();
}

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	require Package::Webmail::Roundcube::Installer;
	Package::Webmail::Roundcube::Installer->getInstance()->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	require Package::Webmail::Roundcube::Uninstaller;
	Package::Webmail::Roundcube::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions()

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
	require Package::Webmail::Roundcube::Installer;
	Package::Webmail::Roundcube::Installer->getInstance()->setGuiPermissions();
}

=item deleteMail(\%data)

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
	my ($self, $data) = @_;

	my $roundcubeDbName = $main::imscpConfig{'DATABASE_NAME'} . '_roundcube';

	return 0 unless $data->{'MAIL_TYPE'} =~ /_mail/;

	my $db = iMSCP::Database->factory();
	$db->set('DATABASE_NAME', $roundcubeDbName);
	my $rs = $db->connect();

	if($rs) {
		error($rs);
		return 1;
	}

	my $rdata = $db->doQuery('dummy', 'DELETE FROM `users` WHERE `username` = ?', $data->{'MAIL_ADDR'});
	unless(ref $rdata eq 'HASH') {
		error(sprintf("Could not remove mail user '%s' from roundcube database: %s", $data->{'MAIL_ADDR'}, $rdata));
		return 1;
	}

	$db->set('DATABASE_NAME', $main::imscpConfig{'DATABASE_NAME'});

	if($db->connect()) {
		error(sprintf('Could not restore connection to i-MSCP database: %s', $rs));
		return 1;
	}

	0
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Package::Webmail::Roundcube::Roundcube

=cut

sub _init
{
	my $self = shift;

	$self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/roundcube";
	$self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
	$self->{'wrkDir'} = "$self->{'cfgDir'}/working";

	if(-f "$self->{'cfgDir'}/roundcube.data") {
		$self->{'config'} = lazy { tie my %c, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data"; \%c; };
	} else {
		$self->{'config'} = { };
	}

	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
