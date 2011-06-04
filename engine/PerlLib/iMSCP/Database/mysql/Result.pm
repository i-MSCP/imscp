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

package iMSCP::Database::mysql::Result;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA/;
@ISA = ("Common::SimpleClass");
use Common::SimpleClass;

sub TIEHASH {

	my $self = shift;

	$self = $self->new(@_);

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': Tieing ...');

	debug((caller(0))[3].': Ending...');

	return $self;
};

sub FIRSTKEY {
	my $self	= shift;

	debug((caller(0))[3].': Starting...');

	my $a = scalar keys %{$self->{args}->{result}};

	debug((caller(0))[3].': Ending...');

	each %{$self->{args}->{result}};
}

sub NEXTKEY {
	my $self	= shift;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': Ending...');

	each %{$self->{args}->{result}};
}

sub FETCH {
	my $self = shift;
	my $key = shift;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].": Fetching $key");

	debug((caller(0))[3].': Ending...');

	$self->{args}->{result}->{$key} ? $self->{args}->{result}->{$key} : undef;
};

sub EXISTS {
	my $self = shift;
	my $key = shift;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].": Cheching key $key ...".(exists $self->{args}->{result}->{$key} ? 'exists' : 'not exists'));

	debug((caller(0))[3].': Ending...');

	$self->{args}->{result}->{$key} ? 1 : 0;
};

sub STORE {};

1;
