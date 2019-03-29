=head1 NAME

 Package::WebmailClients::Roundcube::Roundcube - Roundcube package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::WebmailClients::Roundcube::Roundcube;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::WebmailClients::Roundcube::Installer Package::WebmailClients::Roundcube::Uninstaller /;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 RoundCube Webmail is a browser-based multilingual IMAP client with an application-like user interface. It provides full
 functionality expected from an email client, including MIME support, address book, folder manipulation and message
 filters.

 The user interface is fully skinnable using XHTML and CSS 2.

 Project homepage: http://www.roundcube.net/

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    Package::WebmailClients::Roundcube::Installer->getInstance()->registerSetupListeners( $em );
}

=item setupDialog( \%dialog )

 Setup dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub setupDialog
{
    my ( undef, $dialog ) = @_;

    Package::WebmailClients::Roundcube::Installer->getInstance()->setupDialog( $dialog );
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    Package::WebmailClients::Roundcube::Installer->getInstance()->preinstall();
}

=item install( )

 Process installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::WebmailClients::Roundcube::Installer->getInstance()->install();
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    return 0 if $self->{'skip_uninstall'};

    Package::WebmailClients::Roundcube::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions( )

 Set GUI permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    return 0 unless -d "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/roundcube";

    my $panelUName = my $panelGName = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    my $rs = setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/roundcube", {
        user      => $panelUName,
        group     => $panelGName,
        dirmode   => '0550',
        filemode  => '0440',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/roundcube/logs", {
        user      => $panelUName,
        group     => $panelGName,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => TRUE
    } );
}

=item deleteMail( \%data )

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
    my ( undef, $data ) = @_;

    return 0 unless $data->{'MAIL_TYPE'} =~ /_mail/;

    local $@;
    eval {
        my $db = iMSCP::Database->factory();
        my $oldDbName = $db->useDatabase( $::imscpConfig{'DATABASE_NAME'} . '_roundcube' );
        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;
        $dbh->do( 'DELETE FROM users WHERE username = ?', undef, $data->{'MAIL_ADDR'} );
        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::WebmailClients::Roundcube::Roundcube

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/roundcube";
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'wrkDir'} = "$self->{'cfgDir'}/working";

    if ( -f "$self->{'cfgDir'}/roundcube.data" ) {
        tie %{ $self->{'config'} }, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/roundcube.data", readonly => TRUE;
    } else {
        $self->{'config'} = {};
        $self->{'skip_uninstall'} = TRUE;
    }

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
