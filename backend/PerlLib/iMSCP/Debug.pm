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

package iMSCP::Debug;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA @EXPORT_OK @EXPORT %EXPORT_TAGS/;
use Exporter;
use Common::SingletonClass;
use Log::Message;

@ISA = ("Common::SingletonClass", 'Exporter');
@EXPORT = qw/debug warning error newDebug endDebug/;

sub _init{
	my $self	= shift;
	$self->{log}		= [];
	$self->{logLevels}	= ();
	$self->{lastLog}	= Log::Message->new( private => 1);
	push (@{$self->{log}}, {'imscp-rqst-mngr' => $self->{lastLog}});
}
sub newDebug{
	my $debug	= shift || '';
	push(@{iMSCP::Debug->new()->{logLevels}}, iMSCP::Debug->new()->{lastLog});
	iMSCP::Debug->new()->{lastLog} = Log::Message->new( private => 1);
	push (@{iMSCP::Debug->new()->{log}}, {$debug => iMSCP::Debug->new()->{lastLog}});
}
sub endDebug{
	my $debug = iMSCP::Debug->new();
	if($debug->{logLevels} && (@{$debug->{logLevels}}>0) ){
		$debug->{lastLog} = pop(@{$debug->{logLevels}});
		return 1;
	}
	0;
}
sub debug{
	my $message		= shift || '';
	my $verbosity	= shift or 0;
	iMSCP::Debug->new()->{lastLog}->store(message => $message, tag => 'DEBUG', level => 'log');
}
sub warning{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self = iMSCP::Debug->new();
	$self->{lastLog}->store(message => $message, tag => 'WARNING', level => $verbosity ? 'cluck' : 'log');
	print STDERR "[WARNING] $message\n";
}
sub error{
	my $message		= shift || '';
	my $verbosity	= shift or 1;
	my $self = iMSCP::Debug->new();
	$self->{lastLog}->store(message => $message, tag => 'ERROR', level => $verbosity ? 'cluck' : 'log');
	while($self->endDebug()){};
	exit 1;
}
1;

END{
	my $self = iMSCP::Debug->new();
	while (my $logFile = shift(@{$self->{log}})){
		my $key = (keys %$logFile)[0];
		my $opened = open(DEBUG, '>', $main::configs{'imscp::paths::log_dir'}.'/'.$key);
		print STDERR 'Can\'t save file '.$main::configs{'imscp::paths::log_dir'}.'/'.$key.'!' if(!$opened);
		foreach my $log ($logFile->{$key}->flush()){
			#my $line = "[".$log->tag."] [".$log->when."] ".$log->message."\n";
			my $line = "[".$log->tag."] ".$log->message."\n";
			print $line;
			print DEBUG $line if($opened);
		}
		close(DEBUG) if($opened);
	}
}
