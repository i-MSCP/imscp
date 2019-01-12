=head1 NAME

 iMSCP::Database::MySQL - iMSCP MySQL database handler

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

package iMSCP::Database::MySQL;

use strict;
use warnings;
use DBIx::Connector;
use iMSCP::Boolean;
use iMSCP::Debug 'debug';
use iMSCP::Execute 'execute';
use parent 'Common::Object';

=head1 DESCRIPTION

 iMSCP MySQL database adapter

=cut

=head1 PUBLIC METHODS

=over 4

=item getConnector( )

 Return DBIx::Connector instance, die on failure

=cut

sub getConnector
{
    my ( $self ) = @_;

    $self->_conn();
}

=item getRawDb( )

 Get underlying DBI instance
 
 This method is deprecated and will be removed in a later version. Don't use it
 in new code.
 
 Return DBI, die on failure

=cut

sub getRawDb
{
    my ( $self ) = @_;

    $self->_conn()->dbh();
}

=item startTransaction( )

 Start a database transaction

 This method is deprecated and will be removed in a later version. Don't use it
 in new code.

 Return void
 
=cut

sub startTransaction
{
    my ( $self ) = @_;

    $self->_conn()->dbh()->begin_work();
}

=item endTransaction( )

 Terminate a transaction

 This method is deprecated and will be removed in a later version. Don't use it
 in new code.

 Return void

=cut

sub endTransaction
{
    my ( $self ) = @_;

    $self->_conn()->dbh()->{'AutoCommit'} = TRUE;
}

=item doQuery( $key, $query [, @bindValues = ( ) ] )

 Execute the given SQL statement

 This method is deprecated and will be removed in a later version. Don't use it
 in new code.

 Param int|string $key Query key
 Param string $query SQL statement to be executed
 Param array @bindValues Optionnal binds parameters
 Return hashref on success, error string on failure

=cut

sub doQuery
{
    my ( $self, $key, $query, @bindValues ) = @_;

    defined $query or die 'No SQL query provided';

    local $@;
    my $qrs = $self->_conn()->run( fixup => sub {
        my $sth = $_->prepare( $query );
        $sth->execute( @bindValues );
        $sth->fetchall_hashref( $key ) || {};
    } );
    return $@ if $@;
    $qrs;
}

=item getDatabase( )

 Get current database

 Return string current database

=cut

sub getDatabase
{
    $_[0]->{'DATABASE_NAME'};
}

=item isDatabase( $dbName )

 Does the given database exists?

 Param string $dbName Database name
 Return TRUE if the given database exist, FALSE otherwise, die on failure

=cut

sub isDatabase
{
    my ( $self, $dbName ) = @_;

    $self->_conn()->run( fixup => sub { $_->selectrow_hashref( 'SHOW DATABASES LIKE ?', undef, $dbName ) } ) ? TRUE : FALSE;
}

=item useDatabase( $dbName )

 Change database for the current connection

 Param string $dbName Database name
 Return string Old database on success, die on failure

=cut

sub useDatabase
{
    my ( $self, $dbName ) = @_;

    length $dbName or die( 'Missing or invalid $dbName parameter' );

    return $dbName if $dbName eq $self->{'DATABASE_NAME'};

    my $oldDbName = length $self->{'DATABASE_NAME'} ? $self->{'DATABASE_NAME'} : $dbName;
    $self->_conn()->run( sub { $_->do( 'USE ' . $self->quoteIdentifier( $dbName )); } );
    $self->{'DATABASE_NAME'} = $dbName;
    $oldDbName;
}

=item getDbTables( [ $dbName = $self->{'DATABASE_NAME'} ] )

 Return list of table for the current or given database

 Param string $dbName Database name
 Return arrayref on success, die on failure

=cut

sub getDbTables
{
    my ( $self, $dbName ) = @_;
    $dbName //= $self->{'DATABASE_NAME'};

    length $dbName or die( 'No database selected and no database given' );

    $self->_conn()->run( fixup => sub { $_->selectcol_arrayref( "SHOW TABLES FROM @{ [ $_->quote_identifier( $dbName ) ] }" ); } );
}

=item databaseHasTables( $dbName [, @tables ] )

 Does the given database has tables (or the given tables)?

 Param string $dbName Database name
 Param string @tables Table to look for
 Return TRUE if the given database has tables, FALSE otherwise, die on failure

=cut

sub databaseHasTables
{
    my ( $self, $dbName, @tables ) = @_;

    if ( @tables ) {
        my $dbTables = $self->getDbTables();
        for my $table ( @tables ) {
            return FALSE unless grep ( $_ eq $table, @{ $dbTables } );
        }

        return TRUE;
    }

    @{ $self->getDbTables( $dbName ) } ? TRUE : FALSE;
}

=item dumpdb( $dbName, $targetDir )

 Dump the given database

 Param string $dbName Database name
 Param string $targetDir Database dump target directory
 Return void, die on failure

=cut

sub dumpdb
{
    my ( undef, $dbName, $targetDir ) = @_;

    # Encode slashes as SOLIDUS unicode character
    # Encode dots as Full stop unicode character
    ( my $encodedDbName = $dbName ) =~ s%([./])%{ '/', '@002f', '.', '@002e' }->{$1}%ge;

    debug( sprintf( "Dump '%s' database into %s", $dbName, $targetDir . '/' . $encodedDbName . '.sql' ));

    my $stderr;
    execute(
        [
            '/usr/bin/mysqldump', '--opt', '--complete-insert', '--add-drop-database', '--allow-keywords', '--compress', '--quote-names', '-r',
            "$targetDir/$encodedDbName.sql", '-B', $dbName
        ],
        undef,
        \$stderr
    ) == 0 or die( sprintf( "Couldn't dump the '%s' database: %s", $dbName, $stderr || 'Unknown error' ));
}

=item quoteIdentifier( $identifier )

 Quote the given identifier (database name, table name or column name)

 This method is deprecated and will be removed in a later version. Don't use it
 in new code.

 Param string $identifier Identifier to be quoted
 Return string Quoted identifier

=cut

sub quoteIdentifier
{
    my ( $self, $identifier ) = @_;

    $self->_conn()->dbh()->quote_identifier( $identifier );
}

=item quote( $string )

 Quote the given string

 This method is deprecated and will be removed in a later version. Don't use it
 in new code.

 Param string $string String to be quoted
 Return string Quoted string

=cut

sub quote
{
    my ( $self, $string ) = @_;

    $self->_conn()->dbh()->quote( $string );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Database::MySQL, die on failure

=cut

sub _init
{
    my ( $self ) = @_;

    for my $param ( qw/ DATABASE_USER DATABASE_PASSWORD / ) {
        length $self->{$param} or die( sprintf( 'Missing or invalid %s parameter', $param ));
    }

    $self->{'DATABASE_NAME'} //= '';
    $self->{'DATABASE_HOST'} //= 'localhost';
    $self->{'DATABASE_PORT'} //= 3306;
    $self;
}

=item _conn( )

 Connect to the MySQL server

 Return DBIx::Connector on success, die on failure

=cut

sub _conn
{
    my ( $self ) = @_;

    $self->{'_CONN'} //= do {
        my $conn = DBIx::Connector->new(
            "dbi:mysql:database=$self->{'DATABASE_NAME'};host=$self->{'DATABASE_HOST'};port=$self->{'DATABASE_PORT'}"
                . ";mysql_init_command=SET NAMES utf8, SESSION sql_mode = 'NO_AUTO_CREATE_USER', SESSION group_concat_max_len = 65535",
            $self->{'DATABASE_USER'}, $self->{'DATABASE_PASSWORD'},
            {
                AutoCommit           => TRUE,
                RaiseError           => TRUE,
                PrintError           => FALSE,
                mysql_auto_reconnect => FALSE # Must be FALSE as reconnect is handled by DBIx::Connector
            }
        );
        # Set default mode to 'fixup' for automatic reconnection
        $conn->mode( 'fixup' );
        $conn;
    }
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
