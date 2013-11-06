#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @copyright    2010-2013 by i-MSCP | http://i-mscp.net
# @author       Daniel Andreca <sci2tech@gmail.com>
# @author       Laurent <l.declercq@nuxwin.com>
# @link         http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Database::mysql::mysql;

use strict;
use warnings;

use iMSCP::Debug;
use DBI;
use iMSCP::Execute;
use POSIX ':signal_h';
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

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

# Set database properties (eg DSN propertie)
sub set
{
	my $self = shift;
	my $prop = shift;
	my $value = shift;

	debug("Setting $prop as " . ($value ? $value : 'undef'));
	$self->{'db'}->{$prop} = $value if exists $self->{'db'}->{$prop};
}

# Try to connect to the MySQL server with the current DSN (as set via the set() method)
# Return mixed - 0 on success, error string on failure
sub connect
{
	my $self = shift;

	my $dsn =
		'dbi:mysql:' .
		'database=' . $self->{'db'}->{'DATABASE_NAME'} .
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
		debug("Connecting with ($dsn, $self->{'db'}->{'DATABASE_USER'}, $self->{'db'}->{'DATABASE_PASSWORD'})");

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
	} else {
		debug('Reusing previous SQL connection');
	}

	0;
}

# Start transaction and return raw db connection
sub startTransaction
{
	my $self = shift;

	my $rawDb = $self->getRawDb();

	$rawDb->{'AutoCommit'} = 0;
	$rawDb->{'RaiseError'} = 1;

	$rawDb;
}

# End transaction
sub endTransaction
{
	my $self = shift;

	my $rawDb = $self->getRawDb();

	$rawDb->{'AutoCommit'} = 1;
	$rawDb->{'RaiseError'} = 0;
	$rawDb->{'mysql_auto_reconnect'} = 1;

	$self->{'connection'};
}

# Return raw db connection
sub getRawDb
{
	my $self = shift;

	if(!$self->{'connection'}) {
		my $rs = $self->connect();
		fatal("Unable to connect: $rs") if $rs;
	}

	$self->{'connection'};
}

# Execute the given query
#
# Param int|string Query key
# Param string SQL statement to be executed
# Param array| string... Optionnal binds parameters
sub doQuery
{
	my $self = shift;
	my $key = shift;
	my $query = shift || error('No query provided');
	my @bindValues = @_;

	debug("$query" . ((@bindValues) ? ' with: ' . join(', ', @bindValues) : ''));

	$self->{'sth'} = $self->{'connection'}->prepare($query) || return "Error while preparing query: $DBI::errstr $key|$query";

	if(@bindValues) {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute(@bindValues);
	} else {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();
	}

	($self->{'sth'}->{'NUM_OF_FIELDS'}) ? $self->{'sth'}->fetchall_hashref($key) : {};
}

# Return tables for the current database (see DATABASE_NAME attribute)
#
# Return ARRAY REFERENCCE on success, error string on failure
sub getDBTables
{
	my $self = shift;

	$self->{'sth'} = $self->{'connection'}->prepare(
		"
			SELECT
				`TABLE_NAME`
			FROM
				`INFORMATION_SCHEMA`.`COLUMNS`
			WHERE
				`TABLE_SCHEMA` = '$self->{'db'}->{'DATABASE_NAME'}'
		"
	);

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

	my $href = $self->{'sth'}->fetchall_hashref('TABLE_NAME');

	my @tables = keys %{$href};

	\@tables;
}

# Return columns for the given table of the current database (see DATABASE_NAME attribute)
#
# Return ARRAY REFERENCCE on success, error string on failure
sub getTableColumns($ $)
{
	my $self = shift;
	my $tableName = shift;

	$self->{'sth'} = $self->{'connection'}->prepare(
		"
			SELECT
				`COLUMN_NAME`
			FROM
				`INFORMATION_SCHEMA`.`COLUMNS`
			WHERE
				`TABLE_SCHEMA` = '$self->{'db'}->{'DATABASE_NAME'}'
			AND
				`TABLE_NAME` = '$tableName'
		"
	);

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

	my $href = $self->{'sth'}->fetchall_hashref('COLUMN_NAME');

	my @columns = keys %{$href};

	\@columns;
}

# Dump the given database in the given filename
#
# Param string Database name
# Param string Path of filename where the database should be dumped
# Return int 0 on success 1 on failure
sub dumpdb
{
	my $self = shift;
	my $dbName = shift || $self->{'db'}->{'DATABASE_NAME'};
	my $filename = shift;

	unless($self->{'connection'}) {
		error('Not database name provided');
		return 1;
	}

	unless($self ne __PACKAGE__) {
		error('Not an instance');
		return 1;
	}

	unless($filename) {
		error('No filename provided');
		return 1;
	}

	debug("Dumping $dbName into $filename");

	my ($rs, $stdout, $stderr);
	$rs = execute('which mysqldump', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error('Unable to find mysqldump programm') if $rs && ! $stderr;
	return $rs if $rs;

	$dbName = escapeShell($dbName);
	my $dbHost = escapeShell($self->{'db'}->{'DATABASE_HOST'});
	my $dbPort = escapeShell($self->{'db'}->{'DATABASE_PORT'});
	my $dbUser = escapeShell($self->{'db'}->{'DATABASE_USER'});
	my $dbPass = escapeShell($self->{'db'}->{'DATABASE_PASSWORD'});

	my $bkpCmd = "$stdout --add-drop-database --add-drop-table --allow-keywords --compress --create-options " .
		"--default-character-set=utf8 --extended-insert --lock-tables --quote-names -h $dbHost -P $dbPort -u $dbUser " .
		"-p$dbPass $dbName > $filename";

	$rs = execute($bkpCmd, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Unable to dump $dbName") if $rs && ! $stderr;

	$rs;
}

# Quote the given identifier (database name, table name or column name)
#
# Return string Quoted identifier
sub quoteIdentifier
{
	my ($self, $identifier)	= (@_);

	$identifier = join(', ', $identifier) if ref $identifier eq 'ARRAY';

	$self->{'connection'}->quote_identifier($identifier);
}

sub quote
{
	my ($self, $string)	= (@_);

	$self->{'connection'}->quote($string);
}

1;
