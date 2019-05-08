=head1 NAME

 Servers::po::dovecot::uninstaller - i-MSCP Dovecot server uninstaller

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

package Servers::po::dovecot::uninstaller;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Crypt 'decryptRijndaelCBC';
use iMSCP::Database;
use iMSCP::Debug 'error';
use iMSCP::File;
use Servers::mta;
use Servers::po::dovecot;
use Servers::sqld;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Dovecot server uninstaller.

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

 Return Servers::po::dovecot::uninstaller

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'po'} = Servers::po::dovecot->getInstance();
    $self->{'mta'} = Servers::mta->factory();
    $self->{'events'} = $self->{'po'}->{'events'};
    $self->{'cfgDir'} = $self->{'po'}->{'cfgDir'};
    $self->{'bkpDir'} = "$self->{'cfgDir'}/backup";
    $self->{'config'} = $self->{'po'}->{'config'};
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
        my ( $dovecotSqlUser ) = @{ $dbh->selectcol_arrayref(
            "
                SELECT `value`
                FROM `config`
                WHERE `name` = 'DOVECOT_SQL_USER'
            "
        ) };

        if ( defined $dovecotSqlUser ) {
            $dovecotSqlUser = decryptRijndaelCBC(
                $::imscpDBKey, $::imscpDBiv, $dovecotSqlUser
            );

            for my $host (
                $::imscpOldConfig{'DATABASE_USER_HOST'},
                $::imscpConfig{'DATABASE_USER_HOST'}
            ) {
                next unless length $host;
                Servers::sqld->factory()->dropUser( $dovecotSqlUser, $host );
            }
        }

        $dbh->do(
            "DELETE FROM `config` WHERE `name` LIKE 'DOVECOT_SQL_%'"
        );

        return 0 unless -d $self->{'config'}->{'DOVECOT_CONF_DIR'};

        for my $file ( qw/ dovecot.conf dovecot-sql.conf / ) {
            next unless -f "$self->{'bkpDir'}/$file.system";
            my $rs = iMSCP::File->new(
                filename => "$self->{'bkpDir'}/$file.system"
            )->copyFile(
                "$self->{'config'}->{'DOVECOT_CONF_DIR'}/$file", { preserve => 'no' }
            );
            return $rs if $rs;
        }

        if ( -f "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf" ) {
            my $file = iMSCP::File->new(
                filename => "$self->{'config'}->{'DOVECOT_CONF_DIR'}/dovecot-sql.conf"
            );
            my $rs = $file->owner(
                $main::imscpConfig{'ROOT_USER'},
                $self->{'mta'}->{'config'}->{'MTA_MAILBOX_GID_NAME'}
            );
            $rs ||= $file->mode( 0644 );
        }
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
