=head1 NAME

 iMSCP::Database::MariaDB - MariaDB database adapter

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

package iMSCP::Database::MariaDB;

use strict;
use warnings;
use iMSCP::Boolean;
use parent 'iMSCP::Database::MySQL';

=head1 DESCRIPTION

 MariaDB database adapter

=cut

=head1 PUBLIC METHODS

=over 4

=item connect( )

 Connect to the SQL server

 Return int 0 on success, error string on failure

=cut

sub connect
{
    my ( $self ) = @_;

    my $dsn = join ';', (
        "dbi:MariaDB:database=$self->{'db'}->{'DATABASE_NAME'}",
        'host=' . ( index( $self->{'db'}->{'DATABASE_HOST'}, ':' ) != -1
            ? '[' . $self->{'db'}->{'DATABASE_HOST'} . ']'
            : $self->{'db'}->{'DATABASE_HOST'}
        ),
        ( $self->{'db'}->{'DATABASE_HOST'} eq 'localhost'
            ? ()
            : ( length $self->{'db'}->{'DATABASE_PORT'}
                ? "port=$self->{'db'}->{'DATABASE_PORT'}" : ()
            )
        ),
        'mariadb_init_command=SET NAMES utf8, SESSION sql_mode = '
            . "'NO_AUTO_CREATE_USER', SESSION group_concat_max_len = 65535"
    );

    if ( $self->{'connection'}
        && $self->{'_dsn'} eq $dsn
        && $self->{'_currentUser'} eq $self->{'db'}->{'DATABASE_USER'}
        && $self->{'_currentPassword'} eq $self->{'db'}->{'DATABASE_PASSWORD'}
    ) {
        return 0;
    }

    eval {
        $self->{'connection'}->disconnect() if $self->{'connection'};
        $self->{'connection'} = DBI->connect(
            $dsn,
            $self->{'db'}->{'DATABASE_USER'},
            $self->{'db'}->{'DATABASE_PASSWORD'},
            $self->{'db'}->{'DATABASE_SETTINGS'}
        );
    };
    return $@ if $@;

    @{ $self }{qw/ _dsn _currentUser _currentPassword /} = (
        $dsn,
        $self->{'db'}->{'DATABASE_USER'},
        $self->{'db'}->{'DATABASE_PASSWORD'}
    );
    
    0;
}

=item endTransaction( )

 Warning: This method is deprecated as of version 1.5.0 and will be removed in
 later version. Don't call it in new code.

 End a database transaction

=cut

sub endTransaction
{
    my ( $self ) = @_;

    my $dbh = $self->getRawDb();

    @{ $dbh }{qw/ AutoCommit RaiseError mariadb_auto_reconnect /} = (
        TRUE, FALSE, TRUE
    );

    $self->{'connection'};
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Database::MariaDB

=cut

sub _init
{
    my ( $self ) = @_;

    $self->SUPER::_init();

    delete @{ $self->{'db'}->{'DATABASE_SETTINGS'} }{
        qw/ mysql_connect_timeout mysql_auto_reconnect /
    };

    @{ $self->{'db'}->{'DATABASE_SETTINGS'} }{qw/
        mariadb_connect_timeout mariadb_auto_reconnect
    /} = ( 5, TRUE );

    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
