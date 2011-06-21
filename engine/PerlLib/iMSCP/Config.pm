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
use Tie::File;
use iMSCP::Debug;

use vars qw/@ISA/;
@ISA = ("Common::SimpleClass");
use Common::SimpleClass;

sub TIEHASH {
	my $self = shift;
	$self = $self->new(@_);

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': Tieing ...');

	$self->{confFile} = ();

	$self->{configValues} = {};
	$self->{lineMap} = {};

	$self->{confFileName} = $self->{args}->{fileName};

	$self->_loadConfig();
	$self->_parseConfig();

	debug((caller(0))[3].': Ending...');

	return $self;
}

sub _loadConfig{
	my $self	= shift;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': Config file ' . $self->{confFileName});

	tie @{$self->{confFile}}, 'Tie::File', $self->{confFileName} or
		fatal("Can`t read " . $self->{confFileName}, 1);

	debug((caller(0))[3].': Ending...');
}

sub _parseConfig{
	my $self = shift;
	debug((caller(0))[3].': Starting...');

	my $lineNo = 0;

	for my $line (@{$self->{confFile}}){
		if ($line =~ /^([^#\s=]+)\s{0,}=\s{0,}(.{0,})$/) {
			$self->{configValues}->{$1}	= $2;
			$self->{lineMap}->{$1}		= $lineNo;
		}
		$lineNo++;
	}

	debug((caller(0))[3].': Ending...');
}

sub FETCH {
	my $self	= shift;
	my $config	= shift;

	debug((caller(0))[3].": Starting...");

	debug((caller(0))[3].": Fetching ${config}..." );

	if (!exists($self->{configValues}->{$config})){
		error(sprintf('Accessing non existing config value %s', $config));
	}

	debug((caller(0))[3].': Ending...');

	return $self->{configValues}->{$config};
}

sub STORE {
	my $self	= shift;
	my $config	= shift;
	my $value	= shift || '';

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].": Store ${config} as ${value}..." );

	if(!exists($self->{configValues}->{$config})){
		$self->_insertConfig($config, $value);
	} else {
		$self->_replaceConfig($config, $value);
	}

	debug((caller(0))[3].': Ending...');
}

sub FIRSTKEY {
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	$self->{_list} = [ sort keys %{$self->{configValues}} ];

	debug((caller(0))[3].': Ending...');

	return $self->NEXTKEY;
}

sub NEXTKEY {
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': Ending...');

	return shift @{$self->{_list}};
}

sub _replaceConfig{
	my $self	= shift;
	my $config	= shift;
	my $value	= shift || '';

	debug((caller(0))[3].': Starting...');

	@{$self->{confFile}}[$self->{lineMap}->{$config}] = "$config = $value";
	$self->{configValues}->{$config} = $value;

	debug((caller(0))[3].': Ending...');
}

sub _insertConfig{
	my $self	= shift;
	my $config	= shift;
	my $value	= shift;

	debug((caller(0))[3].': Starting...');

	push (@{$self->{confFile}}, "$config = $value");
	$self->{configValues}->{$config} = $value;

	debug((caller(0))[3].': Ending...');
}

1;

__END__
