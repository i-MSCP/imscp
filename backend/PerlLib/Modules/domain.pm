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

package Modules::domain;

use strict;
use warnings;

use vars qw/@ISA $Dispatchers $decorating/;
@ISA = ("Common::DecoratorClass");
use Common::DecoratorClass;
use iMSCP::Debug;

$Dispatchers = {
	pre		=> sub {pre(@_);},
	post	=> sub {post(@_);}
};

$decorating = {
	alias		=> 'domain',
	domain		=> 'user',
	subdomain	=> 'domain'
};

sub getData{
	my $id	= shift;
	debug((caller(0))[3].': Starting...');
	my $sql = "SELECT * FROM `domain` WHERE `id` = ?";
	my $data = iMSCP::Database::Database->getInstance()->doQuery('id', $sql, $id);
	warning($data) if(ref $data ne "HASH");
	debug((caller(0))[3].': Ending...');
	return ref $data ne "HASH" ? $data : $data->{$id};
}
sub getDecorating{
	my $data	= shift;
	return $decorating->{$data->{entity_type}};
}
sub getParentId{
	my $data		= shift;
	return $data->{parent_id} ? $data->{parent_id} : $data->{user_id};
}
############


sub pre{
	my $self = shift;
	debug((caller(0))[3].": Starting... $self->{name} ");
	debug((caller(0))[3].": Ending...$self->{name}");
}
sub post{
	my $self = shift;
	debug((caller(0))[3].": Starting... $self->{name} ");
	debug((caller(0))[3].": Ending...$self->{name}");
}
sub DESTROY {
	my $self = shift;
	debug((caller(0))[3].": Starting... $self->{name}");
	debug((caller(0))[3].": Ending...$self->{name}");
}
1;

__END__
