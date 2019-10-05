=head1 NAME

 Servers::ftpd::vsftpd::uninstaller - i-MSCP VsFTPd server uninstaller

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

package Servers::ftpd::vsftpd::uninstaller;

use strict;
use warnings;
use File::Basename qw/ basename dirname /;
use iMSCP::Boolean;
use iMSCP::Crypt 'decryptRijndaelCBC';
use iMSCP::Database;
use iMSCP::Debug 'error';
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
    my ( $self ) = @_;

    # In setup context, uninstallation must be delayed, else we won't be able
    # to connect to SQL server
    if ( $::execmode eq 'setup' ) {
        return $self->{'events'}->getInstance()->register(
            'afterSqldPreinstall',
            sub { $self->_uninstall(); }
        );
    }

    $self->_uninstall();
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
    my ( $self ) = @_;

    $self->{'ftpd'} = Servers::ftpd::vsftpd->getInstance();
    $self->{'events'} = $self->{'ftpd'}->{'events'};
    $self->{'cfgDir'} = $self->{'ftpd'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'ftpd'}->{'config'};
    $self;
}

=item _uninstall( )

 Uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub _uninstall
{
    my ( $self ) = @_;

    local $@;
    my $rs = eval {
        my $dbh = iMSCP::Database->factory()->getRawDb();
        my ( $vsftpdSqlUser ) = @{ $dbh->selectcol_arrayref(
            "
                SELECT `value`
                FROM `config`
                WHERE `name` = 'VSFTPD_SQL_USER'
            "
        ) };

        if ( defined $vsftpdSqlUser ) {
            $vsftpdSqlUser = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $vsftpdSqlUser
            );

            for my $host (
                $::imscpOldConfig{'DATABASE_USER_HOST'},
                $::imscpConfig{'DATABASE_USER_HOST'}
            ) {
                next unless length $host;
                Servers::sqld->factory()->dropUser( $vsftpdSqlUser, $host );
            }
        }

        $dbh->do(
            "DELETE FROM `config` WHERE `name` LIKE 'VSFTPD_SQL_%'"
        );

        for my $file ( $self->{'config'}->{'FTPD_CONF_FILE'},
            $self->{'config'}->{'FTPD_PAM_CONF_FILE'}
        ) {
            # Setup context means switching to another FTP server. In such
            # case, we simply delete the files
            if ( $::execmode eq 'setup' ) {
                if ( -f $self->{'config'}->{'FTPD_CONF_FILE'} ) {
                    my $rs = iMSCP::File->new(
                        filename => $self->{'config'}->{'FTPD_CONF_FILE'}
                    )->delFile();
                    return $rs if $rs;
                }

                my $filename = basename( $file );
                if ( -f "$self->{'bkpDir'}/$filename.system" ) {
                    my $rs = iMSCP::File->new(
                        filename => "$self->{'bkpDir'}/$filename.system"
                    )->delFile();
                    return $rs if $rs;
                }

                next;
            }

            my $dirname = dirname( $self->{'config'}->{'FTPD_CONF_FILE'} );
            my $filename = basename( $_ );

            if ( -d $dirname && -f "$self->{'bkpDir'}/$filename.system" ) {
                my $rs = iMSCP::File->new(
                    filename => "$self->{'bkpDir'}/$filename.system"
                )->copyFile(
                    $self->{'config'}->{'FTPD_CONF_FILE'}, { preserve => 'no' }
                );
                return $rs if $rs;
            }
        }

        iMSCP::Dir->new(
            dirname => $self->{'config'}->{'FTPD_USER_CONF_DIR'}
        )->remove();
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
