=head1 NAME

 Package::Webmail::RainLoop::RainLoop - i-MSCP RainLoop package

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::Webmail::RainLoop::RainLoop;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::Webmail::RainLoop::Installer Package::Webmail::RainLoop::Uninstaller /;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

my $dbInitialized = undef;

=head1 DESCRIPTION

 RainLoop package for i-MSCP.

 RainLoop Webmail is a simple, modern and fast Web-based email client.

 Project homepage: http://http://rainloop.net/

=head1 PUBLIC METHODS

=over 4

=item showDialog( \%dialog )

 Show dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 or 30

=cut

sub showDialog
{
    my (undef, $dialog) = @_;

    Package::Webmail::RainLoop::Installer->getInstance()->showDialog( $dialog );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    Package::Webmail::RainLoop::Installer->getInstance()->preinstall();
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    Package::Webmail::RainLoop::Installer->getInstance()->install();
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    return 0 if $self->{'skip_uninstall'};

    Package::Webmail::RainLoop::Uninstaller->getInstance()->uninstall();
}

=item setGuiPermissions( )

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    return 0 unless -d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop";

    my $panelUName = my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $rs = setRights(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop",
        {
            user      => $panelUName,
            group     => $panelGName,
            dirmode   => '0550',
            filemode  => '0440',
            recursive => 1
        }
    );
    $rs ||= setRights(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop/data",
        {
            user      => $panelUName,
            group     => $panelGName,
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
}

=item deleteMail( \%data )

 Process deleteMail tasks

 Param hash \%data Mail data
 Return int 0 on success, other on failure

=cut

sub deleteMail
{
    my (undef, $data) = @_;

    return 0 unless $data->{'MAIL_TYPE'} =~ /_mail/;

    local $@;
    eval {
        my $db = iMSCP::Database->factory();
        my $dbh = $db->getRawDb();
        $dbh->{'RaiseError'} = 1;

        unless ( $dbInitialized ) {
            my $quotedRainLoopDbName = $dbh->quote_identifier( $main::imscpConfig{'DATABASE_NAME'} . '_rainloop' );
            my $row = $dbh->selectrow_hashref( "SHOW TABLES FROM $quotedRainLoopDbName" );
            $dbInitialized = 1 if $row;
        }

        if ( $dbInitialized ) {
            my $oldDbName = $db->useDatabase( $main::imscpConfig{'DATABASE_NAME'} . '_rainloop' );
            $dbh->do(
                '
                    DELETE u, c, p
                    FROM rainloop_users u
                    LEFT JOIN rainloop_ab_contacts c USING(id_user)
                    LEFT JOIN rainloop_ab_properties p USING(id_user)
                    WHERE rl_email = ?
                ',
                undef, $data->{'MAIL_ADDR'}
            );
            $db->useDatabase( $oldDbName ) if $oldDbName;
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my $storageDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/rainloop/data/_data_/_default_/storage";
    ( my $email = $data->{'MAIL_ADDR'} ) =~ s/[^a-z0-9\-\.@]+/_/;
    ( my $storagePath = substr( $email, 0, 2 ) ) =~ s/\@$//;

    for my $storageType( qw/ cfg data files / ) {
        iMSCP::Dir->new( dirname => "$storageDir/$storageType/$storagePath/$email" )->remove();
        next unless -d "$storageDir/$storageType/$storagePath";
        my $dir = iMSCP::Dir->new( dirname => "$storageDir/$storageType/$storagePath" );
        next unless $dir->isEmpty();
        $dir->remove();
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::Webmail::RainLoop::RainLoop

=cut

sub _init
{
    my ($self) = @_;

    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/rainloop";

    if ( -f "$self->{'cfgDir'}/rainloop.data" ) {
        tie %{$self->{'config'}}, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/rainloop.data", readonly => 1;
    } else {
        $self->{'config'} = {};
        $self->{'skip_uninstall'} = 1;
    }

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
