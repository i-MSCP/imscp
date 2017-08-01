=head1 NAME

 Servers::ftpd::vsftpd::uninstaller - i-MSCP VsFTPd server uninstaller

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::ftpd::vsftpd::uninstaller;

use strict;
use warnings;
use File::Basename;
use iMSCP::Config;
use iMSCP::EventManager;
use iMSCP::Dir;
use iMSCP::File;
use Servers::ftpd::vsftpd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP VsFTPd server uninstaller.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
    my ($self) = @_;

    # In setup context, processing must be delayed, else we won't be able to connect to SQL server
    if ( $main::execmode eq 'setup' ) {
        return iMSCP::EventManager->getInstance()->register(
            'afterSqldPreinstall',
            sub {
                my $rs ||= $self->_dropSqlUser();
                $rs ||= $self->_removeConfig();
            }
        );
    }

    my $rs = $self->_dropSqlUser();
    $rs ||= $self->_removeConfig();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::ftpd::vsftpd::uninstaller

=cut

sub _init
{
    my ($self) = @_;

    $self->{'ftpd'} = Servers::ftpd::vsftpd->getInstance();
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'ftpd'}->{'config'};
    $self;
}

=item _dropSqlUser( )

 Drop SQL user

 Return int 0 on success, 1 on failure

=cut

sub _dropSqlUser
{
    my ($self) = @_;

    # In setup context, take value from old conffile, else take value from current conffile
    my $dbUserHost = ( $main::execmode eq 'setup' )
        ? $main::imscpOldConfig{'DATABASE_USER_HOST'} : $main::imscpConfig{'DATABASE_USER_HOST'};

    return 0 unless $self->{'config'}->{'DATABASE_USER'} && $dbUserHost;

    local $@;
    eval { Servers::sqld->factory()->dropUser( $self->{'config'}->{'DATABASE_USER'}, $dbUserHost ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _removeConfig( )

 Remove configuration

 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my ($self) = @_;

    for ( $self->{'config'}->{'FTPD_CONF_FILE'}, $self->{'config'}->{'FTPD_PAM_CONF_FILE'} ) {
        # Setup context means switching to another FTP server. In such case, we simply delete the files
        if ( $main::execmode eq 'setup' ) {
            if ( -f $self->{'config'}->{'FTPD_CONF_FILE'} ) {
                my $rs = iMSCP::File->new( filename => $self->{'config'}->{'FTPD_CONF_FILE'} )->delFile();
                return $rs if $rs;
            }

            my $filename = basename( $_ );
            if ( -f "$self->{'bkpDir'}/$filename.system" ) {
                my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->delFile();
                return $rs if $rs;
            }

            next;
        }

        my $dirname = dirname( $self->{'config'}->{'FTPD_CONF_FILE'} );
        my $filename = basename( $_ );

        if ( -d $dirname && -f "$self->{'bkpDir'}/$filename.system" ) {
            my $rs = iMSCP::File->new( filename => "$self->{'bkpDir'}/$filename.system" )->copyFile(
                $self->{'config'}->{'FTPD_CONF_FILE'}, { preserve => 'no' }
            );
            return $rs if $rs;
        }
    }

    iMSCP::Dir->new( dirname => $self->{'config'}->{'FTPD_USER_CONF_DIR'} )->remove();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
