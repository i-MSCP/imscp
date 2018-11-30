=head1 NAME

 iMSCP::Database::mysql - iMSCP MySQL database adapter

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

package iMSCP::Database::mysql;

use strict;
use warnings;
use DBI;
use iMSCP::Debug qw/ debug /;
use iMSCP::Execute qw / execute /;
use POSIX ':signal_h';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 iMSCP MySQL database adapter

=cut

=head1 FUNCTIONS

=over 4

=item ( $prop, $value )

 Set properties

 Param string $prop Propertie name
 Param string $value Propertie value
 Return string|undef Value of propertie which has been set or undef in case the properties doesn't exist

=cut

sub set
{
    my ($self, $prop, $value) = @_;

    return unless exists $self->{'db'}->{$prop};

    $self->{'db'}->{$prop} = $value;
}

=item connect( )

 Connect to the MySQL server

 Return int 0 on success, error string on failure

=cut

sub connect
{
    my ($self) = @_;

    my $dsn = "dbi:mysql:database=$self->{'db'}->{'DATABASE_NAME'}" .
        ( $self->{'db'}->{'DATABASE_HOST'} ? ';host=' . $self->{'db'}->{'DATABASE_HOST'} : '' )
        . ( $self->{'db'}->{'DATABASE_PORT'} ? ';port=' . $self->{'db'}->{'DATABASE_PORT'} : '' )
        . ";mysql_init_command=SET NAMES utf8, SESSION sql_mode = 'NO_AUTO_CREATE_USER', SESSION group_concat_max_len = 65535";

    if ( $self->{'connection'}
        && $self->{'_dsn'} eq $dsn
        && $self->{'_currentUser'} eq $self->{'db'}->{'DATABASE_USER'}
        && $self->{'_currentPassword'} eq $self->{'db'}->{'DATABASE_PASSWORD'}
    ) {
        return 0;
    }

    $self->{'connection'}->disconnect() if $self->{'connection'};

    # Set connection timeout to 5 seconds
    my $mask = POSIX::SigSet->new( SIGALRM );
    my $action = POSIX::SigAction->new( sub { die "SQL database connection timeout\n" }, $mask );
    my $oldaction = POSIX::SigAction->new();
    sigaction( SIGALRM, $action, $oldaction );

    eval {
        eval {
            alarm 5;
            $self->{'connection'} = DBI->connect(
                $dsn, $self->{'db'}->{'DATABASE_USER'}, $self->{'db'}->{'DATABASE_PASSWORD'},
                $self->{'db'}->{'DATABASE_SETTINGS'}
            );
        };

        alarm 0;
        die if $@;
    };

    sigaction( SIGALRM, $oldaction );
    return $@ if $@;

    $self->{'_dsn'} = $dsn;
    $self->{'_currentUser'} = $self->{'db'}->{'DATABASE_USER'};
    $self->{'_currentPassword'} = $self->{'db'}->{'DATABASE_PASSWORD'};
    $self->{'connection'}->{'RaiseError'} = 0;
}

=item useDatabase( $dbName )

 Change database for the current connection

 Param string $dbName Database name
 Return string Old database on success, die on failure

=cut

sub useDatabase
{
    my ($self, $dbName) = @_;

    defined $dbName && $dbName ne '' or die( '$dbName parameter is not defined or invalid' );

    my $oldDbName = $self->{'db'}->{'DATABASE_NAME'};
    return $oldDbName if $dbName eq $oldDbName;

    my $dbh = $self->getRawDb();
    unless ( $dbh->ping() ) {
        $self->connect();
        $dbh = $self->getRawDb();
    }

    {
        local $dbh->{'RaiseError'} = 1;
        $dbh->do( 'USE ' . $self->quoteIdentifier( $dbName ));
    }

    $self->{'db'}->{'DATABASE_NAME'} = $dbName;
    $oldDbName;
}

=item startTransaction( )

 Warning: This method is deprecated as of version 1.5.0 and will be removed in
 version 1.6.0. Don't use it in new code.

 Start a database transaction

=cut

sub startTransaction
{
    my ($self) = @_;

    my $dbh = $self->getRawDb();
    $dbh->begin_work();
    $dbh->{'RaiseError'} = 1;
    $dbh;
}

=item endTransaction( )

 Warning: This method is deprecated as of version 1.5.0 and will be removed in
 version 1.6.0. Don't use it in new code.

 End a database transaction

=cut

sub endTransaction
{
    my ($self) = @_;

    my $dbh = $self->getRawDb();

    $dbh->{'AutoCommit'} = 1;
    $dbh->{'RaiseError'} = 0;
    $dbh->{'mysql_auto_reconnect'} = 1;
    $self->{'connection'};
}

=item getRawDb( )

 Get raw DBI instance

 Return DBI instance, die on failure
=cut

sub getRawDb
{
    my ($self) = @_;

    return $self->{'connection'} if $self->{'connection'};

    my $rs = $self->connect();
    !$rs or die( sprintf( "Couldn't connect to SQL server: %s", $rs ));
    $self->{'connection'};
}

=item doQuery( $key, $query [, @bindValues = ( ) ] )

 Execute the given SQL statement

 Warning: This method is deprecated as of version 1.5.0 and will be removed in
 version 1.6.0. Don't use it in new code.

 Param int|string $key Query key
 Param string $query SQL statement to be executed
 Param array @bindValues Optionnal binds parameters
 Return hashref on success, error string on failure

=cut

sub doQuery
{
    my ($self, $key, $query, @bindValues) = @_;

    local $@;
    my $qrs = eval {
        defined $query or die 'No query provided';
        my $dbh = $self->getRawDb();
        local $dbh->{'RaiseError'} = 0;
        my $sth = $dbh->prepare( $query ) or die $DBI::errstr;
        $sth->execute( @bindValues ) or die $DBI::errstr;
        $sth->fetchall_hashref( $key ) || {};
    };

    return "$@" if $@;
    $qrs;
}

=item getDbTables( [ $dbName ] )

 Return list of table for the current selected database

 Param string $dbName Database name
 Return arrayref on success, error string on failure

=cut

sub getDbTables
{
    my ($self, $dbName) = @_;
    $dbName //= $self->{'db'}->{'DATABASE_NAME'};

    local $@;
    my @tables = eval {
        my $dbh = $self->getRawDb();
        local $dbh->{'RaiseError'} = 1;
        keys %{$dbh->selectall_hashref(
                'SELECT TABLE_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ?', 'TABLE_NAME', undef, $dbName
            )};
    };

    return "$@" if $@;
    \@tables;
}

=item getTableColumns( [$tableName [, dbName ] ] )

 Return list of columns for the given table

 Param string $tableName Table name
 Param string $dbName Database name
 Return arrayref on success, error string on failure

=cut

sub getTableColumns
{
    my ($self, $tableName, $dbName) = @_;
    $dbName //= $self->{'db'}->{'DATABASE_NAME'};

    local $@;
    my @columns = eval {
        my $dbh = $self->getRawDb();
        local $dbh->{'RaiseError'} = 1;
        keys %{$dbh->selectall_hashref(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                'COLUMN_NAME', undef, $dbName, $tableName
            )};
    };

    return "$@" if $@;
    \@columns;
}

=item dumpdb( $dbName, $dbDumpTargetDir )

 Dump the given database

 Param string $dbName Database name
 Param string $dbDumpTargetDir Database dump target directory
 Return void, die on failure

=cut

sub dumpdb
{
    my (undef, $dbName, $dbDumpTargetDir) = @_;

    # Encode slashes as SOLIDUS unicode character
    # Encode dots as Full stop unicode character
    ( my $encodedDbName = $dbName ) =~ s%([./])%{ '/', '@002f', '.', '@002e' }->{$1}%ge;

    debug( sprintf( 'Dump `%s` database into %s', $dbName, $dbDumpTargetDir . '/' . $encodedDbName . '.sql' ));

    my $stderr;
    execute(
        [
            'mysqldump', '--opt', '--complete-insert', '--add-drop-database', '--allow-keywords', '--compress',
            '--quote-names', '-r', "$dbDumpTargetDir/$encodedDbName.sql", '-B', $dbName
        ],
        undef,
        \ $stderr
    ) == 0 or die(
        sprintf( "Couldn't dump the `%s` database: %s", $dbName, $stderr || 'Unknown error' )
    );
}

=item quoteIdentifier( $identifier )

 Quote the given identifier (database name, table name or column name)


 Param string $identifier Identifier to be quoted
 Return string Quoted identifier

=cut

sub quoteIdentifier
{
    my ($self, $identifier) = @_;

    $self->getRawDb()->quote_identifier( $identifier );
}

=item quote( $string )

 Quote the given string

 Param string $string String to be quoted
 Return string Quoted string

=cut

sub quote
{
    my ($self, $string) = @_;

    $self->getRawDb()->quote( $string );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Database::mysql

=cut

sub _init
{
    my ($self) = @_;

    $self->{'db'} = {
        DATABASE_NAME     => '',
        DATABASE_HOST     => '',
        DATABASE_PORT     => '',
        DATABASE_USER     => '',
        DATABASE_PASSWORD => '',
        DATABASE_SETTINGS => {
            AutoCommit           => 1,
            AutoInactiveDestroy  => 1,
            # Now in DSN
            #Callbacks            => {
            #    connected => sub {
            #        $_[0]->do( "SET SESSION sql_mode = 'NO_AUTO_CREATE_USER', SESSION group_concat_max_len = 65535" );
            #        return;
            #    }
            #},
            mysql_auto_reconnect => 1,
            # Now in DSN
            #mysql_enable_utf8    => 1,
            PrintError           => 0,
            RaiseError           => 1,
        }
    };

    # For internal use only
    $self->{'_dsn'} = '';
    $self->{'_currentUser'} = '';
    $self->{'_currentPassword'} = '';
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

    *DBD::_::db::begin_work = sub {
        my $dbh = shift;
        return $dbh->set_err($DBI::stderr, 'Already in a transaction')
            unless $dbh->FETCH('AutoCommit');
        $dbh->ping(); # Make sure that connection is alive (mysql_auto_reconnect)
        $dbh->STORE('AutoCommit', 0); # will croak if driver doesn't support it
        $dbh->STORE('BegunWork',  1); # trigger post commit/rollback action
        return 1;
    };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
