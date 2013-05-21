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
use iMSCP::Database::mysql::Result;
use iMSCP::Execute;
use parent 'Common::SingletonClass';

sub _init
{
	my $self = shift;

	$self->{'db'}->{'DATABASE_NAME'} = '';
	$self->{'db'}->{'DATABASE_HOST'} = '';
	$self->{'db'}->{'DATABASE_PORT'} = '';
	$self->{'db'}->{'DATABASE_USER'} = '';
	$self->{'db'}->{'DATABASE_PASSWORD'} = '';
	$self->{'db'}->{'DATABASE_SETTINGS'} = { 'PrintError' => 0 };

	# for internal use only
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

		$self->{'connection'} = DBI->connect(
			$dsn, $self->{'db'}->{'DATABASE_USER'}, $self->{'db'}->{'DATABASE_PASSWORD'},
			(
				defined($self->{'db'}->{'DATABASE_SETTINGS'}) &&
				ref $self->{'db'}->{'DATABASE_SETTINGS'} eq 'HASH' ? $self->{'db'}->{'DATABASE_SETTINGS'} : ()
			)
		) or return $DBI::errstr;

		$self->{'_dsn'} = $dsn;
		$self->{'_currentUser'} = $self->{'db'}->{'DATABASE_USER'};
		$self->{'_currentPassword'} = $self->{'db'}->{'DATABASE_PASSWORD'};
	} else {
		debug('Reusing previous SQL connection');
	}

	0;
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
	my @subs = @_;

	debug("$query" . (@subs ? ' with: ' . join(', ', @subs) : ''));

	$self->{'sth'} = $self->{'connection'}->prepare($query) || return "Error while preparing query: $DBI::errstr $key|$query";

	if(@subs) {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute(@subs);
	} else {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();
	}

	my $href = $self->{'sth'}->fetchall_hashref(eval "[ qw/$key/ ]");

	tie my %href , 'iMSCP::Database::mysql::Result', 'result' => $href;

	\%href;
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

	my @tables = keys %$href;

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

	my @columns = keys %$href;

	return  \@columns;
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

	my $rs = $self->{'connection'}->quote_identifier($identifier);
	debug("Quote identifier: |$rs|");

	return $rs;
}

sub quote
{
	my ($self, $string)	= (@_);

	$self->{'connection'}->quote($string);
}

1;
