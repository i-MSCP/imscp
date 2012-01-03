#!/usr/bin/perl

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
# @copyright	2010 - 2012 by i-MSCP | http://i-mscp.net
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
@ISA = ('Common::SimpleClass');
use Common::SimpleClass;

sub TIEHASH {
	my $self = shift;
	$self = $self->new(@_);

	$self->{confFile} = ();

	$self->{configValues} = {};
	$self->{lineMap} = {};

	$self->{confFileName} = $self->{args}->{fileName};

	debug("Tieing $self->{confFileName}");

	$self->_loadConfig();
	$self->_parseConfig();

	return $self;
}

sub _loadConfig{
	my $self	= shift;

	debug('Config file ' . $self->{confFileName});

	tie @{$self->{confFile}}, 'Tie::File', $self->{confFileName} or
		fatal("Can`t read " . $self->{confFileName}, 1);

}

sub _parseConfig{
	my $self = shift;

	my $lineNo = 0;

	for my $line (@{$self->{confFile}}){
		if ($line =~ /^([^#\s=]+)\s{0,}=\s{0,}(.{0,})$/) {
			$self->{configValues}->{$1}	= $2;
			$self->{lineMap}->{$1}		= $lineNo;
		}
		$lineNo++;
	}

}

sub FETCH {
	my $self	= shift;
	my $config	= shift;

	debug("Fetching ${config}..." );

	if (!exists($self->{configValues}->{$config}) && !$self->{args}->{noerrors}){
		error(sprintf('Accessing non existing config value %s', $config));
	}

	return $self->{configValues}->{$config};
}

sub STORE {
	my $self	= shift;
	my $config	= shift;
	my $value	= shift;

	debug("Store ${config} as ".($value ? $value : 'empty')."..." );

	if(!exists($self->{configValues}->{$config})){
		$self->_insertConfig($config, $value);
	} else {
		$self->_replaceConfig($config, $value);
	}

}

sub FIRSTKEY {
	my $self = shift;

	$self->{_list} = [ sort keys %{$self->{configValues}} ];

	return $self->NEXTKEY;
}

sub NEXTKEY {
	my $self = shift;

	return shift @{$self->{_list}};
}

sub _replaceConfig{
	my $self	= shift;
	my $config	= shift;
	my $value	= shift;

	$value = '' unless defined $value;

	debug("Setting $config as $value");

	@{$self->{confFile}}[$self->{lineMap}->{$config}] = "$config = $value";
	$self->{configValues}->{$config} = $value;
}

sub _insertConfig{
	my $self	= shift;
	my $config	= shift;
	my $value	= shift;

	$value = '' unless defined $value;

	debug("Setting $config as $value");

	push (@{$self->{confFile}}, "$config = $value");
	$self->{lineMap}->{$config} = $#{$self->{confFile}};
	$self->{configValues}->{$config} = $value;
}

1;
