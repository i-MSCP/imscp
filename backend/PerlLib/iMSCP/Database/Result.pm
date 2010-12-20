# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
# or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
# for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>
#
# The Original Code is "i-MSCP internet Multi Server Control Panel".
#
# The Initial Developer of the Original Code is i-MSCP Team.
# Portions created by Initial Developer are Copyright (C) 22010 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# @category		i-MSCP
# @copyright	2010 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@i-mscp.net>
# @version		SVN: $Id: imscp-build 3933 2010-12-01 19:35:32Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/ GPL v2

package iMSCP::Database::Result;

use strict;
use warnings;
use Log::Message::Simple;

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
sub STORE {};

1;

