# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 by internet Multi Server Control Panel
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

package iMSCP::Database::Database;

use strict;
use warnings;
use DBI;
use iMSCP::Debug;
use iMSCP::Exception;
use iMSCP::Database::Result;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub _init{
	my $self			= shift;

	debug((caller(0))[3].': Starting...');

	my $data_source	=
		'dbi:' . $self->{args}->{DATABASE_TYPE} . ':'.
		'database=' . $self->{args}->{DATABASE_NAME} .
		(defined($self->{args}->{DATABASE_HOST}) ? ';host=' . $self->{args}->{DATABASE_HOST} : '').
		(defined($self->{args}->{DATABASE_PORT}) ? ';port=' . $self->{args}->{DATABASE_PORT} : '');

	$self->{connection} = DBI->connect(
		$data_source,
		$self->{args}->{DATABASE_USER},
		$self->{args}->{DATABASE_PASSWORD},
		(defined($self->{args}->{DATABASE_SETTINGS}) && ref($self->{args}->{DATABASE_SETTINGS}) eq 'HASH' ? $self->{args}->{DATABASE_SETTINGS} : ())
	) or iMSCP::Exception->new()->exception($DBI::errstr);

	debug((caller(0))[3].': Ending...');
}

sub getInstance{
	my $self = shift;
	return $self->new();
}

sub setProperty{
	my $self			= shift;
	my $prop			= shift || 0;
	my $value			= shift || 0;

	debug((caller(0))[3].': Starting...');

	if(!$self->{'connection'}){
		iMSCP::Exception->new()->exception('Connection not available!');
	}

	if($prop){
		$self->{connection}->{$prop} = $value;
		debug((caller(0))[3].": Set $prop to $value");
	}

	debug((caller(0))[3].': Ending...');
}
sub doImediatQuery{
	debug((caller(0))[3].': Starting...');
	my $self			= shift;
	my $key				= shift;
	my $query			= shift || iMSCP::Exception->new()->exception("No query provided");
	my @subs			= @_;
	$self->doQuery($key, $query, @subs);
	$self->endTransaction();
	debug((caller(0))[3].': Ending...');
}
sub doQuery{
	my $self			= shift;
	my $key				= shift;
	my $query			= shift || iMSCP::Exception->new()->exception("No query provided");
	my @subs			= @_;

	debug((caller(0))[3].': Starting...');

	$self->{sth} = $self->{connection}->prepare($query) || iMSCP::Exception->new()->exception("Error while preparing query: $DBI::errstr");

	debug((caller(0))[3].": $query with @subs");

	if(@subs){
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute(@subs);# || iMSCP::Exception->new()->exception("Error while executing query: $DBI::errstr");
	} else {
		return "Error while executing query: $DBI::errstr" unless $self->{'sth'}->execute();# || iMSCP::Exception->new()->exception("Error while executing query: $DBI::errstr");
	}
	debug $key;

	my $href = $self->{sth}->fetchall_hashref( eval "[ qw/$key/ ]" );

	debug((caller(0))[3].': Ending...');

	tie my %href , 'iMSCP::Database::Result', result => $href;

	return \%href;
}
sub endTransaction{
	debug((caller(0))[3].': Starting...');
	my $self = shift;
	$self->{connection}->commit();
	debug((caller(0))[3].': Ending...');
}
sub rollback{
	debug((caller(0))[3].': Starting...');
	my $self = shift;
	$self->{connection}->rollback();
	debug((caller(0))[3].': Ending...');
}

1;

__END__
