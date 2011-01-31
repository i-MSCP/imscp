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

package iMSCP::STDCapture;

use File::Temp 'tempfile';
use Symbol qw/gensym qualify qualify_to_ref/;
use iMSCP::Debug;

sub new {
	debug((caller(0))[3].': Starting...');

	my $proto			= shift;
	my $class			= ref($proto) || $proto;
	my $self			= {};
	my $STD				= shift;
	$self->{capture}	= shift;

	$STD				= qualify($STD);
	$self->{STDHandler}	= qualify_to_ref($STD);

	debug ((caller(0))[3].": Capturing ${$self->{STDHandler}}");

	open $self->{saved}, ">& $STD" or error("Can't redirect <$STD> - $!");
	(undef, $self->{newSTDFile}) = tempfile;
	open $self->{newSTDHandler}, "+> $self->{newSTDFile}" or error("Can't create temporary file for $STD - $!");
	open $self->{STDHandler}, ">& ".fileno($self->{newSTDHandler}) or error("Can't redirect $STD - $!");
	$self->{pid} = $$;

	debug((caller(0))[3].': Ending...');
	bless($self, $class);
}

sub DESTROY {
	debug((caller(0))[3].': Starting...');
	my $self	= shift;
	return unless $self->{pid} eq $$;
	debug ((caller(0))[3].": Finishing capture of ${$self->{STDHandler}}");
	select((select ($self->{STDHandler}), $|=1)[0]);
	open $self->{STDHandler}, ">& ". fileno($self->{saved}) or error("Can't restore ${$self->{STDHandler}} - $!");
	seek $self->{newSTDHandler}, 0, 0;
	my $file			= $self->{newSTDHandler};
	${$self->{capture}}	= do {local $/; <$file>};
	unlink $self->{newSTDFile} or error("Couldn't remove temp file '$self->{newSTDFile}' - $!",1);
	debug((caller(0))[3].': Ending...');
}

1;
