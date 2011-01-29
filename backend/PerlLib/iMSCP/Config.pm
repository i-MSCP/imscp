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

package iMSCP::Config;

use strict;
use warnings;
use iMSCP::Debug;
use Switch;
use XML::Simple;

use vars qw/@ISA/;
@ISA = ("Common::SingletonClass");
use Common::SingletonClass;

sub TIEHASH {
	my $self = shift;
	$self = $self->new(@_);

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': Tieing ...');

	$self->{prefix} = ();

	$self->_loadConfig();
	$self->_parseConfig($self->{conf});

	debug((caller(0))[3].': Ending...');

	return $self;
}

sub FETCH {
	my $self	= shift;
	my $config	= shift;

	debug((caller(0))[3].": Starting...");

	debug((caller(0))[3].": Fetching ${config}..." );

	if (!exists($self->{configValues}->{$config})){
		iMSCP::Exception->new()->exception(sprintf('Accessing non existing config value %s', $config));
	}

	debug((caller(0))[3].': Ending...');

	return ${$self->{configValues}->{$config}}
}

sub STORE {
	my $self	= shift;
	my $config	= shift;
	my $value	= shift;

	debug((caller(0))[3].': Starting...');

	if(!exists($self->{configValues}->{$config})){
		iMSCP::Exception->new()->exception(sprintf('Accessing non existing config value %s', $config));
	} else {
		$self->_replaceConfig($config, $value);
	}

	debug((caller(0))[3].': Ending...');
}

sub _loadConfig{
	my $self	= shift;

	debug((caller(0))[3].': Starting...');

	switch ($^O) {
		case /bsd$/ {
			$self->{confFile} = '/usr/local/etc/imscp/imscp.xml';
		} else {
			$self->{confFile} = '/etc/imscp/imscp.xml';
		}
	}

	debug((caller(0))[3].': Config file ' . $self->{confFile});
	iMSCP::Exception->new()->exception("Can`t use ".$self->{confFile}) unless -f $self->{confFile};
	$self->{conf} = XML::Simple->new(NoAttr=>1, RootName=>'config')->XMLin($self->{confFile}, ForceArray => 1);

	debug((caller(0))[3].': Ending...');
}

sub _parseConfig{
	my $self = shift;
	my $hash = shift;

	#debug((caller(0))[3].': Starting...');

	foreach (keys %{$hash}){
		push(@{$self->{prefix}}, $_);
		if(ref($hash->{$_}[0]) eq 'HASH'){
			#debug((caller(0))[3].": $_ is a hash. Going recursive");
			$self->_parseConfig(\%{$hash->{$_}[0]})
		} else {
			#debug((caller(0))[3].": We have a value $_ -> $hash->{$_}[0]");
			$self->{configValues}->{join("::", @{$self->{prefix}})} = \$hash->{$_}[0];
		}
		pop(@{$self->{prefix}});
	}

	#debug((caller(0))[3].': Ending...');
}

sub _replaceConfig{
	my $self	= shift;
	my $config	= shift;
	my $value	= shift;

	debug((caller(0))[3].': Starting...');

	${$self->{configValues}->{$config}} = $value;
	XML::Simple->new(NoAttr=>1, RootName=>'config',  XMLDecl => '<?xml version="1.0" encoding="UTF-8"?>', OutputFile => $self->{confFile})->XMLout($self->{conf});

	debug((caller(0))[3].': Ending...');
}

1;

__END__
