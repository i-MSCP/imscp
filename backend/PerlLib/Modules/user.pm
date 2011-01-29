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

package Modules::user;

use strict;
use warnings;

use vars qw/@ISA $Dispatchers $decorating/;
@ISA = ("Common::DecoratorClass");
use Common::DecoratorClass;
use iMSCP::Debug;
use Switch;
#
#$Dispatchers = {
	#pre		=> sub {pre(@_);},
	#post	=> sub {post(@_);}
#};

$decorating = {
	user		=> 'baseIO'
};

sub getDecorating{
	return 'baseIO';
}
sub getParentId{'';}
sub getData{
	my $id	= shift;
	debug((caller(0))[3].': Starting...');
	my $sql = "SELECT * FROM `user` WHERE `id` = ?";
	my $data = iMSCP::Database::Database->getInstance()->doQuery('id', $sql, $id);
	warning($data) if(ref $data ne "HASH");
	debug((caller(0))[3].': Ending...');
	return ref $data ne "HASH" ? $data : $data->{$id};
}
sub hasModule{
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].': Ending...');
	1;
}

sub process{
	my $self	= shift;
	my $rv		= 0;
	newDebug('imscp-user:'.$self->{username});
	debug((caller(0))[3].': Starting...');
	switch($self->status){
		case 'add'{
			$self->add;
		}
		case 'delete'{
			$self->delete;
		}
		case 'disable'{
			$self->disable;
		}
		case 'enable'{
			$self->enable;
		}
		case 'rebuild'{
			$self->rebuild;
		}
	}
	debug((caller(0))[3].': Ending...');
	endDebug('imscp-user:'.$self->{username});
	$rv;
}
sub add{
	my $self	= shift;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Starting $self->{username} adition");
	use Modules::user::SystemGroup;
	use Modules::user::SystemUser;
	my $group = Modules::user::SystemGroup->new(data => \%{$self->{args}->{data}});
	my $rv = $group->addGroup();
	return $rv if($rv);
	iMSCP::Scheduler->new()->registerRollBack(
		$self->{args}->{data}->{id}, sub { $group->deleteGroup(); }
	);
	$rv = Modules::user::SystemUser->new(data => \$self->{args}->{data})->addUser();
	return $rv if($rv);
	iMSCP::Scheduler->new()->unregisterRollback($self->{args}->{data}->{id});
	debug((caller(0))[3].': Ending...');
	0;
}
sub delete{
	my $self	= shift;
	my $rv		= 0;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].': Ending...');
	$rv;
}
sub enable{
	my $self	= shift;
	my $rv		= 0;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].': Ending...');
	$rv;
}
sub disable{
	my $self	= shift;
	my $rv		= 0;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].': Ending...');
	$rv;
}
sub rebuild{
	my $self	= shift;
	my $rv		= 0;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].': Ending...');
	$rv;
}








sub _add{
	my $self = shift;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Starting $self->{username} adition");
	use Modules::user::SystemGroup;
	use Modules::user::SystemUser;
	my $group = Modules::user::SystemGroup->new(data => \%{$self->{args}->{data}});
	my $rv = $group->addGroup();
	return $rv if($rv);
	iMSCP::Scheduler->new()->registerRollBack(
		$self->{args}->{data}->{id}, sub { $group->deleteGroup(); }
	);
	$rv = Modules::user::SystemUser->new(data => \$self->{args}->{data})->addUser();
	return $rv if($rv);
	iMSCP::Scheduler->new()->unregisterRollback($self->{args}->{data}->{id});
	debug((caller(0))[3].': Ending...');
	0;
}

sub DESTROY {
	my $self = shift;
	debug((caller(0))[3].": $self->{username}");
}

1;

__END__
