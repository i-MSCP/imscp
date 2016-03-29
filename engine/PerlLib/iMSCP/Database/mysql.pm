=head1 NAME

 iMSCP::Database::mysql - iMSCP MySQL database adapter

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package iMSCP::Database::mysql;

use strict;
use warnings;

use iMSCP::Debug;
use DBI;
use iMSCP::Execute;
use File::HomeDir;
use POSIX ':signal_h';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 iMSCP MySQL database adapter

=cut

=head1 FUNCTIONS

=over 4

=item ($prop, $value)

 Set properties

 Param string $prop Propertie name
 Param string $value Propertie value
 Return string|undef Value of propertie which has been set or undef in case the properties doesn't exist

=cut

sub set
{
    my ($self, $prop, $value) = @_;

    if (exists $self->{'db'}->{$prop}) {
        $self->{'db'}->{$prop} = $value;
    } else {
        undef;
    }
}

=item connect()

 Connect to the MySQL server

 Return int 0 on success, error string on failure

=cut

sub connect
{
    my $self = shift;

    my $dsn = "dbi:mysql:database=$self->{'db'}->{'DATABASE_NAME'}".
        ($self->{'db'}->{'DATABASE_HOST'} ? ';host='.$self->{'db'}->{'DATABASE_HOST'} : '').
        ($self->{'db'}->{'DATABASE_PORT'} ? ';port='.$self->{'db'}->{'DATABASE_PORT'} : '');

    if (!$self->{'connection'} ||
        (
            $self->{'_dsn'} ne $dsn || $self->{'_currentUser'} ne $self->{'db'}->{'DATABASE_USER'}
                || $self->{'_currentPassword'} ne $self->{'db'}->{'DATABASE_PASSWORD'}
        )
    ) {
        $self->{'connection'}->disconnect() if $self->{'connection'};

        # Set connection timeout to 3 seconds
        my $mask = POSIX::SigSet->new( SIGALRM );
        my $action = POSIX::SigAction->new( sub { die "SQL database connection timeout\n" }, $mask );
        my $oldaction = POSIX::SigAction->new();
        sigaction( SIGALRM, $action, $oldaction );

        eval {
            alarm 3;
            $self->{'connection'} = DBI->connect(
                $dsn, $self->{'db'}->{'DATABASE_USER'}, $self->{'db'}->{'DATABASE_PASSWORD'},
                (
                        defined( $self->{'db'}->{'DATABASE_SETTINGS'} ) &&
                            ref $self->{'db'}->{'DATABASE_SETTINGS'} eq 'HASH' ? $self->{'db'}->{'DATABASE_SETTINGS'} : ()
                )
            );
            alarm 0;

            $self->{'connection'}->do( 'SET NAMES utf8' );
        };

        alarm 0;
        sigaction( SIGALRM, $oldaction );

        return "$@" if $@;

        $self->{'_dsn'} = $dsn;
        $self->{'_currentUser'} = $self->{'db'}->{'DATABASE_USER'};
        $self->{'_currentPassword'} = $self->{'db'}->{'DATABASE_PASSWORD'};
        $self->{'connection'}->{'RaiseError'} = 0;
    }

    0;
}

=item useDatabase($database)

 Change database for the current connection

 Param string $database Database name
 Return string Old database on success, die on failure

=cut

sub useDatabase
{
    my ($self, $database) = @_;

    defined $database or die('$database parameter is not defined');

    return $database if $database eq '' || $self->{'db'}->{'DATABASE_NAME'} eq $database;

    my $qDatabase = $self->quoteIdentifier( $database );

    my $rawDb = $self->getRawDb();
    $rawDb->{'RaiseError'} = 1;
    local $@;
    eval { $self->getRawDb->do( "use $qDatabase" ); };
    $rawDb->{'RaiseError'} = 0;
    die($@) if $@;

    my $oldDatabase = $self->{'db'}->{'DATABASE_NAME'};
    $self->{'db'}->{'DATABASE_NAME'} = $database;
    $oldDatabase;
}

=item startTransaction()

 Start a database transaction

=cut

sub startTransaction
{
    my $self = shift;

    my $rawDb = $self->getRawDb();
    $rawDb->{'AutoCommit'} = 0;
    $rawDb->{'RaiseError'} = 1;
    $rawDb;
}

=item endTransaction()

 End a database transaction

=cut

sub endTransaction
{
    my $self = shift;

    my $rawDb = $self->getRawDb();

    $rawDb->{'AutoCommit'} = 1;
    $rawDb->{'RaiseError'} = 0;
    $rawDb->{'mysql_auto_reconnect'} = 1;
    $self->{'connection'};
}

=item getRawDb()

 Get raw DBI instance

=cut

sub getRawDb
{
    my $self = shift;

    return $self->{'connection'} if $self->{'connection'};

    my $rs = $self->connect();
    $rs == 0 or die( sprintf( 'Could not connect to SQL server: %s', $rs ) ) if $rs;
    $self->{'connection'};
}

=item doQuery($key, $query, [@bindValues = undef])

 Execute the given SQL statement

 Param int|string $key Query key
 Param string $query SQL statement to be executed
 Param array @bindValues Optionnal binds parameters
 Return Hash An anonymous hash containing data if any on success, error string on failure

=cut

sub doQuery
{
    my ($self, $key, $query, @bindValues) = @_;

    # Must be done prior any processing to handle error case
    # (ie, wrong sql statement and then, next query which set error status use default fetch mode)
    my $fetchMode = $self->{'db'}->{'FETCH_MODE'};
    $self->set( 'FETCH_MODE', 'hashref' );

    $query or return 'No query provided';

    $self->{'sth'} = $self->{'connection'}->prepare( $query ) or return "Error while preparing statement: $DBI::errstr";
    $self->{'sth'}->execute( @bindValues ) or return "Error while executing statement: $DBI::errstr";

    if ($fetchMode eq 'hashref') {
        $self->{'sth'}->fetchall_hashref( $key ) || { };
    } elsif ($fetchMode eq 'arrayref') {
        $self->{'sth'}->fetchall_arrayref( $key ) || [ ];
    } else {
        return sprintf( 'Unsupported fetch mode: %s', $fetchMode );
    }
}

=item getDBTables()

 Return list of table for the current selected database

 Return array_ref on success, error string on failure

=cut

sub getDBTables
{
    my $self = shift;

    $self->{'sth'} = $self->{'connection'}->prepare(
        'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ?', $self->{'db'}->{'DATABASE_NAME'}
    );

    return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

    my $href = $self->{'sth'}->fetchall_hashref( 'TABLE_NAME' );
    my @tables = keys %{$href};
    \@tables;
}

=item getTableColumns($tableName)

 Return list of columns for the given table

 Return array_ref on success, error string on failure

=cut

sub getTableColumns
{
    my ($self, $tableName) = @_;

    $self->{'sth'} = $self->{'connection'}->prepare(
        'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
        $self->{'db'}->{'DATABASE_NAME'},
        $tableName
    );

    return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

    my $href = $self->{'sth'}->fetchall_hashref( 'COLUMN_NAME' );
    my @columns = keys %{$href};
    \@columns;
}

=item dumpdb($dbName, $filename)

 Dump the given database in the given filename

 Param string Database name
 Param string Path of filename where the database should be dumped
 Return int 0 on success 1 on failure

=cut

sub dumpdb
{
    my ($self, $dbName, $filename) = @_;

    debug( "Dump $dbName into $filename" );

    $dbName = escapeShell( $dbName );
    $filename = escapeShell( $filename );

    my $rootHomeDir = File::HomeDir->users_home( $main::imscpConfig{'ROOT_USER'} );

    my @cmd = (
        'mysqldump',
        '--opt',
        '--complete-insert',
        '--add-drop-database',
        '--allow-keywords',
        '--compress',
        '--default-character-set=utf8',
        '--quote-names',
        "--result-file=$filename",
        $dbName
    );

    my ($stdout, $stderr);
    my $rs = execute( "@cmd", \$stdout, \$stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( sprintf( 'Could not dump %s', $dbName ) ) if $rs && !$stderr;
    $rs;
}

=item quoteIdentifier($identifier)

 Quote the given identifier (database name, table name or column name)

 Return string Quoted identifier

=cut

sub quoteIdentifier
{
    my ($self, $identifier) = @_;

    $identifier = join( ', ', $identifier ) if ref $identifier eq 'ARRAY';
    $self->{'connection'}->quote_identifier( $identifier );
}

=item quote($string)

 Quote the given string

 Return string Quoted string

=cut

sub quote
{
    my ($self, $string) = @_;

    $self->{'connection'}->quote( $string );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return iMSCP::Database::mysql

=cut

sub _init
{
    my $self = $_[0];

    $self->{'db'}->{'DATABASE_NAME'} = '';
    $self->{'db'}->{'DATABASE_HOST'} = '';
    $self->{'db'}->{'DATABASE_PORT'} = '';
    $self->{'db'}->{'DATABASE_USER'} = '';
    $self->{'db'}->{'DATABASE_PASSWORD'} = '';
    $self->{'db'}->{'DATABASE_SETTINGS'} = {
        'AutoCommit'           => 1,
        'PrintError'           => 0,
        'RaiseError'           => 1,
        'mysql_auto_reconnect' => 1,
        'mysql_enable_utf8'    => 1
    };

    # Default fetch mode
    $self->{'db'}->{'FETCH_MODE'} = 'hashref';

    # For internal use only
    $self->{'_dsn'} = '';
    $self->{'_currentUser'} = '';
    $self->{'_currentPassword'} = '';
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
