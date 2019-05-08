=head1 NAME

 iMSCP::Database::MySQL - MySQL database adapter

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
use DBI;
use iMSCP::Boolean;
use iMSCP::Debug 'debug';
use iMSCP::Execute 'execute';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 MySQL database adapter

=cut

=head1 PUBLIC METHODS

=over 4

=item ( $prop, $value )

 Set properties

 Param string $prop Propertie name
 Param string|undef $value Propertie value
 Return string|undef Value of propertie which has been set or undef in case the properties doesn't exist

=cut

sub set
{
    my ( $self, $prop, $value ) = @_;

    return unless exists $self->{'db'}->{$prop};

    $self->{'db'}->{$prop} = $value;
}

=item connect( )

 Connect to the SQL server

 Return int 0 on success, error string on failure

=cut

sub connect
{
    my ( $self ) = @_;

    my $dsn = join ';', (
        "dbi:mysql:database=$self->{'db'}->{'DATABASE_NAME'}",
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
        'mysql_init_command=SET NAMES utf8, SESSION sql_mode = '
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

=item useDatabase( $dbName )

 Change database for the current connection

 Param string $dbName Database name
 Return string Old database on success, die on failure

=cut

sub useDatabase
{
    my ( $self, $dbName ) = @_;

    defined $dbName && $dbName ne '' or die(
        '$dbName parameter is not defined or invalid'
    );

    my $oldDbName = $self->{'db'}->{'DATABASE_NAME'};
    return $oldDbName if $dbName eq $oldDbName;

    my $dbh = $self->getRawDb();
    unless ( $dbh->ping() ) {
        $self->connect();
        $dbh = $self->getRawDb();
    }

    $dbh->do( 'USE ' . $self->quoteIdentifier( $dbName ));

    $self->{'db'}->{'DATABASE_NAME'} = $dbName;
    $oldDbName;
}

=item startTransaction( )

 Warning: This method is deprecated as of version 1.5.0 and will be removed in
 later version. Don't call it in new code.

 Start a database transaction

=cut

sub startTransaction
{
    my ( $self ) = @_;

    my $dbh = $self->getRawDb();
    $dbh->begin_work();
    $dbh->{'RaiseError'} = TRUE;
    $dbh;
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

    @{ $dbh }{qw/ AutoCommit RaiseError mysql_auto_reconnect /} = (
        TRUE, FALSE, TRUE
    );

    $self->{'connection'};
}

=item getRawDb( )

 Get raw DBI instance

 Return DBI instance, die on failure

=cut

sub getRawDb
{
    my ( $self ) = @_;

    return $self->{'connection'} if $self->{'connection'};

    my $rs = $self->connect();
    !$rs or die( sprintf( "Couldn't connect to SQL server: %s", $rs ));
    $self->{'connection'};
}

=item doQuery( $key, $query [, @bindValues = ( ) ] )

 Execute the given SQL statement

 Warning: This method is deprecated as of version 1.5.0 and will be removed in
 later version. Don't call it in new code.

 Param int|string $key Query key
 Param string $query SQL statement to be executed
 Param array @bindValues Optionnal binds parameters
 Return hashref on success, error string on failure

=cut

sub doQuery
{
    my ( $self, $key, $query, @bindValues ) = @_;

    local $@;
    my $qrs = eval {
        defined $query or die 'No query provided';
        my $dbh = $self->getRawDb();
        local $dbh->{'RaiseError'} = FALSE;
        my $sth = $dbh->prepare( $query ) or die $DBI::errstr;
        $sth->execute( @bindValues ) or die $DBI::errstr;
        $sth->fetchall_hashref( $key ) || {};
    };

    return "$@" if $@;
    $qrs;
}

=item getDbTables( [ $dbName = $self->{'db'}->{'DATABASE_NAME'} ] )

 Return list of table for the given database

 Param string $dbName Database name
 Return array on success, die on failure

=cut

sub getDbTables
{
    my ( $self, $dbName ) = @_;
    $dbName //= $self->{'db'}->{'DATABASE_NAME'};

    my $dbh = $self->getRawDb();

    my $tables = $dbh->selectall_hashref(
        '
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
        ',
        'TABLE_NAME',
        undef,
        $dbName
    );

    [ keys %{ $tables } ];
}

=item getTableColumns( [$tableName [, dbName = $self->{'db'}->{'DATABASE_NAME'} ] ] )

 Return list of columns for the given table in the given database

 Param string $tableName Table name
 Param string $dbName Database name
 Return arrayref on success, die on failure

=cut

sub getTableColumns
{
    my ( $self, $tableName, $dbName ) = @_;
    $dbName //= $self->{'db'}->{'DATABASE_NAME'};

    my $columns = $self->getRawDb()->selectall_hashref(
        '
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = ?
            AND TABLE_NAME = ?
        ',
        'COLUMN_NAME',
        undef,
        $dbName,
        $tableName
    );

    [ keys %{ $columns } ];
}

=item dumpdb( $dbName, $dbDumpTargetDir )

 Dump the given database

 Param string $dbName Database name
 Param string $dbDumpTargetDir Database dump target directory
 Return void, die on failure

=cut

sub dumpdb
{
    my ( undef, $dbName, $dbDumpTargetDir ) = @_;

    # Encode slashes as SOLIDUS unicode character
    # Encode dots as Full stop unicode character
    ( my $encodedDbName = $dbName )
        =~ s%([./])%{ '/', '@002f', '.', '@002e' }->{$1} %ge;

    debug( sprintf(
        "Dump '%s' database into %s",
        $dbName,
        $dbDumpTargetDir . '/' . $encodedDbName . '.sql'
    ));

    my $stderr;
    execute(
        [
            '/usr/bin/mysqldump',
            '--opt',
            '--complete-insert',
            '--add-drop-database',
            '--allow-keywords',
            '--compress',
            '--quote-names',
            '-r', "$dbDumpTargetDir/$encodedDbName.sql",
            '-B', $dbName
        ],
        undef,
        \$stderr
    ) == 0 or die( sprintf(
        "Couldn't dump the '%s' database: %s",
        $dbName,
        $stderr || 'Unknown error'
    ));
}

=item quoteIdentifier( $identifier )

 Quote the given identifier (database name, table name or column name)


 Param string $identifier Identifier to be quoted
 Return string Quoted identifier

=cut

sub quoteIdentifier
{
    my ( $self, $identifier ) = @_;

    $self->getRawDb()->quote_identifier( $identifier );
}

=item quote( $string )

 Quote the given string

 Param string $string String to be quoted
 Return string Quoted string

=cut

sub quote
{
    my ( $self, $string ) = @_;

    $self->getRawDb()->quote( $string );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Database::MySQL

=cut

sub _init
{
    my ( $self ) = @_;

    # For internal use only
    @{ $self }{qw/ _dsn _currentUser _currentPassword db /} = ( '', '', '', {
        DATABASE_NAME     => '',
        DATABASE_HOST     => 'localhost',
        DATABASE_PORT     => '3306',
        DATABASE_USER     => undef,
        DATABASE_PASSWORD => undef,
        DATABASE_SETTINGS => {
            AutoCommit            => TRUE,
            AutoInactiveDestroy   => TRUE,
            mysql_connect_timeout => 5,
            mysql_auto_reconnect  => TRUE,
            PrintError            => FALSE,
            RaiseError            => TRUE,
        } } );

    $self;
}

=back

=head1 MONKEY PATCHES

=over 4

=item begin_work( )

 Monkey patch for https://github.com/perl5-dbi/DBD-mysql/issues/202

=cut

{
    no warnings qw/ once redefine /;

    *DBD::_::db::begin_work = sub
    {
        my $dbh = shift;
        return $dbh->set_err( $DBI::stderr, 'Already in a transaction' )
            unless $dbh->FETCH( 'AutoCommit' );
        # Make sure that connection is alive (mysql_auto_reconnect)
        $dbh->ping();
        # will croak if driver doesn't support it
        $dbh->STORE( 'AutoCommit', FALSE );
        # trigger post commit/rollback action
        $dbh->STORE( 'BegunWork', TRUE );
        return 1;
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
