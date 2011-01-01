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
# @copyright	2010 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@i-mscp.net>
# @version		SVN: $Id: imscp-build 3933 2010-12-01 19:35:32Z sci2tech $
# @link			http://i-mscp.net i-MSCP Home Site
# @license      http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Prerequish;

use strict;
use warnings;
use Log::Message::Simple;

use vars qw/@ISA/;
@ISA = ("Common::SimpleClass");
use Common::SimpleClass;

sub test{
	my $self = shift;
	my $test = shift;

	debug((caller(0))[3].': Starting...');

	if($self->can($test)){
		$self->$test();
	} else {
		error("Test $test is not available", 1);
	}

	debug((caller(0))[3].': Ending...');
}

sub _all{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	$self->_user();
	$self->_modules();
	$self->_externalProgram();
	$self->_externalProgramVersions();

	debug((caller(0))[3].': Ending...');
}
sub _user{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	iMSCP::Exception->new()->exception(sprintf('Must run as root')) if( $< != 0 );

	debug((caller(0))[3].': Ending...');
}

sub _modules{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	my ($mod, $mod_missing) = (undef, undef);

	for $mod (keys(%main::needed)) {
		ITER: {
			foreach my $prefix (@INC) {
				my $realfilename = "$prefix/$mod.pm";
				$realfilename =~ s!::!/!g;
				if (-f $realfilename) {
					$INC{$mod} = $realfilename;
					eval "use $mod $main::needed{$mod}";
					if($@){
						$mod_missing .= ($mod_missing ? ', ' : '').$mod;
					}
					last ITER;
				}
			}
			$mod_missing .= ($mod_missing ? ', ' : '').$mod;
		}
	}

	debug((caller(0))[3].': Ending...');

	iMSCP::Exception->new()->exception("Modules [$mod_missing] WAS NOT FOUND in your system...") if ($mod_missing) ;
}

sub _externalProgram{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	error((caller(0))[3].': TODO');

	debug((caller(0))[3].': Ending...');
}
sub _externalProgramVersions{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	error((caller(0))[3].': TODO');

	debug((caller(0))[3].': Ending...');
}

1;

__END__
