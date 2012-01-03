#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
# @category		i-MSCP
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Database::mysql::mysql;

use strict;
use warnings;

use DBI;
use iMSCP::Debug;
use iMSCP::Database::mysql::Result;

use Common::SingletonClass;

use vars qw/@ISA/;
@ISA = ('Common::SingletonClass');

sub _init{
	my $self = shift;
	$self->{db}->{DATABASE_NAME}		= '';
	$self->{db}->{DATABASE_HOST}		= '';
	$self->{db}->{DATABASE_PORT}		= '';
	$self->{db}->{DATABASE_USER}		= '';
	$self->{db}->{DATABASE_PASSWORD}	= '';
	$self->{db}->{DATABASE_SETTINGS}	= { PrintError => 0 };
}

sub set{
	my $self		= shift;
	my $prop		= shift;
	my $value		= shift;
	debug("Setting $prop as ".($value ? $value : 'undef'));
	$self->{db}->{$prop} = $value if(exists $self->{db}->{$prop});
}

sub connect{
	my $self		= shift;

	my $data_source	=
		'dbi:mysql:'.
		'database=' . $self->{db}->{DATABASE_NAME} .
		($self->{db}->{DATABASE_HOST} ? ';host=' . $self->{db}->{DATABASE_HOST} : '').
		($self->{db}->{DATABASE_PORT} ? ';port=' . $self->{db}->{DATABASE_PORT} : '');

	debug("Connect with $data_source");

	if($self->{connection}){
		$self->{connection}->disconnect;
	}

	if(! ($self->{connection} = DBI->connect(
		$data_source,
		$self->{db}->{DATABASE_USER},
		$self->{db}->{DATABASE_PASSWORD},
		(defined($self->{db}->{DATABASE_SETTINGS}) && ref($self->{db}->{DATABASE_SETTINGS}) eq 'HASH' ? $self->{db}->{DATABASE_SETTINGS} : ())
	))){
		return $DBI::errstr;
	}

	0;
}

sub doQuery{
	my $self			= shift;
	my $key				= shift;
	my $query			= shift || error("No query provided");
	my @subs			= @_;

	debug("$query with @subs");

	$self->{sth} = $self->{connection}->prepare($query) || return("Error while preparing query: $DBI::errstr $key|$query");

	if(@subs){
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute(@subs);
	} else {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();
	}

	my $href = $self->{sth}->fetchall_hashref( eval "[ qw/$key/ ]" );

	tie my %href , 'iMSCP::Database::mysql::Result', result => $href;

	return \%href;
}

sub getDBTables{

	my $self			= shift;

	$self->{sth} = $self->{connection}->prepare("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '$self->{db}->{DATABASE_NAME}';");

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

	my $href = $self->{sth}->fetchall_hashref("TABLE_NAME");

	my @tables = keys %$href;

	return  \@tables;

}

sub dumpdb{

	my $self		= shift;
	my $db			= shift;
	my $filename	= shift;

	unless($self->{connection}){
		error('Not connected');
		return 1;
	}

	unless($self ne __PACKAGE__){
		error('Not an instance instances');
		return 1;
	}

	unless($filename){
		error('No filename provided');
		return 1;
	}

	debug("Dumping $db as $filename");

	$db = $self->{db}->{DATABASE_NAME} unless $db;

	use iMSCP::Execute;

	my ($rs, $stdout, $stderr);
	$rs = execute('which mysqldump', \$stdout, \$stderr);
	#chomp($stdout);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error("Can find mysqldump") if (!$stderr && $rs);
	return $rs if $rs;

	my $dbName = $db;
	my $dbHost = $self->{db}->{DATABASE_HOST};
	my $dbPort = $self->{db}->{DATABASE_PORT};
	my $dbUser = $self->{db}->{DATABASE_USER};
	my $dbPass = $self->{db}->{DATABASE_PASSWORD};

	eval "\$$_ =~ s/\'/\\'/g" for (qw/dbHost dbPort dbName dbUser dbPass/);

	my $bkpCmd	=	"$stdout ".
					"--add-drop-database ".
					"--add-drop-table ".
					"--add-drop-database ".
					"--allow-keywords ".
					"--compress ".
					"--create-options ".
					"--default-character-set=utf8 ".
					"--extended-insert ".
					"--lock-tables ".
					"--quote-names ".
					"-h '$dbHost' ".
					"-P '$dbPort' ".
					"-u '$dbUser' ".
					"-p'$dbPass' ".
					"'$db' > '$filename'";

	$rs = execute($bkpCmd, \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error("Can not dump $dbName") if (!$stderr && $rs);
	return $rs if $rs;
	0;
}

sub quoteIdentifier{
	my ($self, $identifier)	= (@_);

	$identifier = join(', ', $identifier) if( ref $identifier eq 'ARRAY');

	my $rv = $self->{connection}->quote_identifier($identifier);
	debug("Quote identifier: |$rv|");

	return $rv;
}

1;

