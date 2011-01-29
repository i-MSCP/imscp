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
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Scheduler;

use strict;
use warnings;
use iMSCP::Debug;
use Symbol;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;
use Class::ISA;

sub addAction{
	my $self	= shift;
	my $data	= shift;
	my $rv = 0;
	debug((caller(0))[3].': Starting...');
	foreach my $key(keys %$data){
		my $module	= $data->{$key}->{module};
		my $id		= $data->{$key}->{subject_id};
		debug((caller(0))[3].": Request to schedule for $module with id $id");
		$self->{actions}->{$module} = {} if(ref($self->{actions}->{$module}) ne 'HASH');
		if (! exists $self->{actions}->{$module}->{$id} ){
			my $module_data = $self->getData($module, $id);
			if(ref $module_data ne "HASH"){
				warning((caller(0))[3].": Can`t read data for module $module with id $id");
				$rv = 1;
				next;
			}
			use Data::Dumper;
			debug Dumper $module_data;
			my $mRef;
			if ( grep { $_ eq 'Common::DecoratorClass'} Class::ISA::super_path("Modules::${module}") ){
				debug((caller(0))[3].": $module is a decorator ");
				my $parent_type = eval "Modules::${module}::getDecorating(\$module_data)";
				my $parent_id	= eval "Modules::${module}::getParentId(\$module_data)";
				my $rt = $self->addAction({1 => {module => $parent_type, subject_id => $parent_id}});
				if ($rt){
					warning((caller(0))[3].": Can't instantiate parent for $module with id: $id ");
					$rv = 1;
					next;
				} else {
					my $parentRef	= $self->{actions}->{$parent_type}->{$parent_id};
					$mRef = eval "Modules::${module}->new( { 'obj' => \$parentRef } )";
				}
			} else {
				debug((caller(0))[3].": $module is not a decorator ");
				$mRef = eval "use Modules::${module}; Modules::${module}->new()";
			}
			foreach my $prop (keys %$module_data){
				$mRef->{$prop} = $module_data->{$prop};
			}
			$self->{actions}->{$module}->{$id} = $mRef;
		} else {
			debug((caller(0))[3].": Already here (maybe a child instantiated as parent object)");
		}
	}
	debug((caller(0))[3].': Ending...');
	$rv;
}

sub getData{
	my $self	= shift;
	my $module	= shift;
	my $id		= shift;
	debug((caller(0))[3].': Starting...');
	eval "use Modules::${module};";
	my $data;
	if(eval "Modules::${module}->can('getData')"){
		$data = eval "Modules::${module}::getData($id)";
	} else {
		warning ((caller(0))[3].": $module does not provide interface to obtain data!");
	}
	debug((caller(0))[3].': Ending...');
	return $data;
}
#################################
#          post process
#################################
sub _callAction{
	my $self	= shift;
	my $module	= shift;
	debug((caller(0))[3].': Starting...');
	foreach my $id (keys %{$self->{actions}->{$module}}){
		debug((caller(0))[3].": $module with id $id");
		$self->{actions}->{$module}->{$id}->process();
		delete($self->{actions}->{$module}->{$id});
	}
	debug((caller(0))[3].': Ending...');
}
sub processActions{
	my $self	= shift;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].': User...');
	#use Data::Dumper;
	#debug Dumper $self->{actions};
	#exit;
	foreach my $module (keys %{$self->{actions}}){
		$self->_callAction($module);
	}
	debug((caller(0))[3].': Ending...');
}
sub registerRollBack{
	my $self		= shift;
	my $actionId	= shift;
	my $rollBack	= shift;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Register action on user id: $actionId");
	$self->{rollBacks}->{$actionId} = [] if(!$self->{rollBacks}->{$actionId});
	push (@{$self->{rollBacks}->{$actionId}}, $rollBack);
	debug((caller(0))[3].': Ending...');
}
sub unregisterRollback{
	my $self		= shift;
	my $actionId	= shift;
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Unregister action on user id: $actionId");
	delete($self->{rollBacks}->{$actionId});
	debug((caller(0))[3].': Ending...');
}
1;

END{
	while(endDebug()){};
	debug((caller(0))[3].': Starting rollbacks');
	my $self = iMSCP::Scheduler->new();
	warning((caller(0))[3].': We have errors.') if(keys %{$self->{rollBacks}});
	foreach (keys %{$self->{rollBacks}}){
		while( my $code = pop(@{$self->{rollBacks}->{$_}}) ){
			&$code;
		}
	}
}
