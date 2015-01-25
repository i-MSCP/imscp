#!/usr/bin/perl

=head1 NAME

 iMSCP::Database::mysql iMSCP MySQL database adapter

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
#
# @category     i-MSCP
# @copyright    2010-2015 by i-MSCP | http://i-mscp.net
# @author       Daniel Andreca <sci2tech@gmail.com>
# @author       Laurent <l.declercq@nuxwin.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

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

 iMSCP database adapter factory.

=cut

=head1 FUNCTIONS

=over 4

=item factory($adapterName)

 Param string $adapterName Adapter name
 Return an instance of the specified database adapter

=cut

=item($prop, $value)

 Set properties

 Param string $prop Propertie name
 Param string $value Propertie value
 Return string|undef Value of propertie which has been set or undef in case the properties doesn't exist

=cut

sub set
{
	my ($self, $prop, $value) = @_;

	if(exists $self->{'db'}->{$prop}) {
		$self->{'db'}->{$prop} = $value;
	} else {
		undef;
	}
}

=item connect()

 Connect to the MySQL server

 Return int - 0 on success, error string on failure

=cut

sub connect
{
	my $self = $_[0];

	my $dsn =
		"dbi:mysql:database=$self->{'db'}->{'DATABASE_NAME'}" .
		($self->{'db'}->{'DATABASE_HOST'} ? ';host=' . $self->{'db'}->{'DATABASE_HOST'} : '').
		($self->{'db'}->{'DATABASE_PORT'} ? ';port=' . $self->{'db'}->{'DATABASE_PORT'} : '');

	if(
		! $self->{'connection'} ||
		(
			$self->{'_dsn'} ne $dsn || $self->{'_currentUser'} ne $self->{'db'}->{'DATABASE_USER'} ||
			$self->{'_currentPassword'} ne $self->{'db'}->{'DATABASE_PASSWORD'}
		)
	) {
		$self->{'connection'}->disconnect() if $self->{'connection'};

		# Set connection timeout to 3 seconds
		my $mask = POSIX::SigSet->new(SIGALRM);
		my $action = POSIX::SigAction->new(sub { die "SQL database connection timeout\n" }, $mask);
		my $oldaction = POSIX::SigAction->new();
		sigaction(SIGALRM, $action, $oldaction);

		eval {
			alarm 3;
			$self->{'connection'} = DBI->connect(
				$dsn, $self->{'db'}->{'DATABASE_USER'}, $self->{'db'}->{'DATABASE_PASSWORD'},
				(
					defined($self->{'db'}->{'DATABASE_SETTINGS'}) &&
					ref $self->{'db'}->{'DATABASE_SETTINGS'} eq 'HASH' ? $self->{'db'}->{'DATABASE_SETTINGS'} : ()
				)
			);
			alarm 0;

			$self->{'connection'}->do('SET NAMES utf8');
		};

		alarm 0;
		sigaction(SIGALRM, $oldaction);

		return "$@" if $@;

		$self->{'_dsn'} = $dsn;
		$self->{'_currentUser'} = $self->{'db'}->{'DATABASE_USER'};
		$self->{'_currentPassword'} = $self->{'db'}->{'DATABASE_PASSWORD'};
		$self->{'connection'}->{'RaiseError'} = 0;
	}

	0;
}

=item startTransaction()

 Start a database transaction

=cut

sub startTransaction
{
	my $self = $_[0];

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
	my $self = $_[0];

	my $rawDb = $self->getRawDb();

	$rawDb->{'AutoCommit'} = 1;
	$rawDb->{'RaiseError'} = 0;
	$rawDb->{'mysql_auto_reconnect'} = 1;

	$self->{'connection'};
}

=item getRawDb()

 Get raw database connection

=cut

sub getRawDb
{
	my $self = $_[0];

	if(! $self->{'connection'}) {
		my $rs = $self->connect();
		fatal("Unable to connect: $rs") if $rs;
	}

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

	$query or error('No query provided');

	$self->{'sth'} = $self->{'connection'}->prepare($query)
		or return "Error while preparing query: $DBI::errstr $key|$query";

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute(@bindValues);

	($self->{'sth'}->{'NUM_OF_FIELDS'}) ? $self->{'sth'}->fetchall_hashref($key) : {};
}

=item getDBTables()

 Return list of table for the current selected database

 Return array_ref on success, error string on failure

=cut

sub getDBTables
{
	my $self = $_[0];

	$self->{'sth'} = $self->{'connection'}->prepare(
		"
			SELECT
				TABLE_NAME
			FROM
				INFORMATION_SCHEMA.COLUMNS
			WHERE
				TABLE_SCHEMA = '$self->{'db'}->{'DATABASE_NAME'}'
		"
	);

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

	my $href = $self->{'sth'}->fetchall_hashref('TABLE_NAME');

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
		"
			SELECT
				COLUMN_NAME
			FROM
				INFORMATION_SCHEMA.COLUMNS
			WHERE
				TABLE_SCHEMA = '$self->{'db'}->{'DATABASE_NAME'}'
			AND
				TABLE_NAME = '$tableName'
		"
	);

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

	my $href = $self->{'sth'}->fetchall_hashref('COLUMN_NAME');

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

	debug("Dump $dbName into $filename");

	$dbName = escapeShell($dbName);
	$filename = escapeShell($filename);

	my $dbHost = escapeShell($self->{'db'}->{'DATABASE_HOST'});
	my $dbPort = escapeShell($self->{'db'}->{'DATABASE_PORT'});
	my $dbUser = escapeShell($self->{'db'}->{'DATABASE_USER'});
	my $dbPass = escapeShell($self->{'db'}->{'DATABASE_PASSWORD'});

	my $rootHomeDir = File::HomeDir->users_home($main::imscpConfig{'ROOT_USER'});

	my @cmd;

	if(defined $rootHomeDir && -f "$rootHomeDir/my.cnf") {
		@cmd = (
			$main::imscpConfig{'CMD_MYSQLDUMP'}, '--opt', '--complete-insert', '--add-drop-database', '--allow-keywords',
			'--compress', '--default-character-set=utf8', '--quote-names', "--result-file=$filename", $dbName
		);
	} else {
		@cmd = (
			$main::imscpConfig{'CMD_MYSQLDUMP'}, '--opt', '--complete-insert', '--add-drop-database', '--allow-keywords',
			'--compress', '--default-character-set=utf8', '--quote-names', "-h $dbHost", "-P $dbPort", "-u $dbUser",
			"-p$dbPass", "--result-file=$filename", $dbName
		);
	}

	my ($stdout, $stderr);
	my $rs = execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Unable to dump $dbName") if $rs && ! $stderr;

	$rs;
}

=item quoteIdentifier($identifier)

 Quote the given identifier (database name, table name or column name)

 Return string Quoted identifier

=cut

sub quoteIdentifier
{
	my ($self, $identifier) = @_;

	$identifier = join(', ', $identifier) if ref $identifier eq 'ARRAY';

	$self->{'connection'}->quote_identifier($identifier);
}

=item quote($string)

=cut

sub quote
{
	my ($self, $string) = @_;

	$self->{'connection'}->quote($string);
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance.

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
		'AutoCommit' => 1,
		'PrintError' => 0,
		'RaiseError' => 1,
		'mysql_auto_reconnect' => 1,
		'mysql_enable_utf8' => 1
	};

	# For internal use only
	$self->{'_dsn'} = '';
	$self->{'_currentUser'} = '';
	$self->{'_currentPassword'} = '';

	$self;
}

=back

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
