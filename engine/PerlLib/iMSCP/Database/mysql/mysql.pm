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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
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
@ISA = ("Common::SingletonClass");

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
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Setting $prop as ".($value ? $value :''));
	$self->{db}->{$prop} = $value if(exists $self->{db}->{$prop});
	debug((caller(0))[3].': Ending...');
}

sub connect{
	my $self		= shift;

	debug((caller(0))[3].': Starting...');

	my $data_source	=
		'dbi:mysql:'.
		'database=' . $self->{db}->{DATABASE_NAME} .
		($self->{db}->{DATABASE_HOST} ? ';host=' . $self->{db}->{DATABASE_HOST} : '').
		($self->{db}->{DATABASE_PORT} ? ';port=' . $self->{db}->{DATABASE_PORT} : '');

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

	debug((caller(0))[3].': Ending...');

	0;
}

sub doQuery{
	my $self			= shift;
	my $key				= shift;
	my $query			= shift || error("No query provided");
	my @subs			= @_;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].": $query with @subs");

	$self->{sth} = $self->{connection}->prepare($query) || return("Error while preparing query: $DBI::errstr $key|$query");

	if(@subs){
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute(@subs);;
	} else {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();
	}

	my $href = $self->{sth}->fetchall_hashref( eval "[ qw/$key/ ]" );

	debug((caller(0))[3].': Ending...');

	tie my %href , 'iMSCP::Database::mysql::Result', result => $href;

	return \%href;
}

sub getDBTables{

	my $self			= shift;

	debug((caller(0))[3].': Starting...');

	$self->{sth} = $self->{connection}->prepare("SELECT `TABLE_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA` = '$self->{db}->{DATABASE_NAME}';");

	return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();

	my $href = $self->{sth}->fetchall_hashref("TABLE_NAME");

	debug((caller(0))[3].': Ending...');

	my @tables = keys %$href;

	return  \@tables;

}

sub quoteIdentifier{
	my $self		= shift;
	my $identifier	= shift;

	return $self->{connection}->quote_identifier($identifier)
}
1;

