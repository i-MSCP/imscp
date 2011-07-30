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
# @copyright	2010 - 2011 by i-MSCP | http://i-mscp.net
# @author		Daniel Andreca <sci2tech@gmail.com>
# @version		SVN: $Id$
# @link			http://i-mscp.net i-MSCP Home Site
# @license		http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package iMSCP::Templator;

use strict;
use warnings;

use iMSCP::Debug;
use Exporter;
use Common::SingletonClass;

use vars qw/@ISA @EXPORT/;
@ISA = ('Common::SingletonClass', 'Exporter');
@EXPORT = qw/process replaceBloc getBloc/;

sub _init{

	my $self = shift;

	debug((caller(0))[3].': Starting...');

	$self->{varStartTag}		= '\{';
	$self->{varEndTag}			= '\}';
	$self->{varRegexp}			= "$self->{varStartTag}%s$self->{varEndTag}";
	$self->{inclusionTagStart}	= '# [(\{.*\})](.*)START\.';
	$self->{inclusionTagEnd}	= '# [\1](.)END\.';

	debug((caller(0))[3].': Ending...');
}

sub set($ $){
	my $prop	= shift;
	my $value	= shift;
	my $self	= iMSCP::Templator->new();
	debug((caller(0))[3].': Starting...');
	debug((caller(0))[3].": Setting $prop.");
	$self->{$prop} = $value if(exists $self->{$prop});
	debug((caller(0))[3].': Ending...');
}

sub loadlayout{
}

sub process($ $){
	my $self			= iMSCP::Templator->new();
	$self->{vars}		= shift || ref {};
	$self->{tContent}	= shift || '';

	debug((caller(0))[3].': Starting...');

	$self->{vars} = {} if (ref $self->{vars} ne 'HASH');

	$self->_replaceStatic();

	#restore default tags
	$self->{args} = {};
	$self->_init();

	debug((caller(0))[3].': Ending...');

	return $self->{tContent};
}

sub _getInclusion{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	debug((caller(0))[3].': TODO...');

	debug((caller(0))[3].': Ending...');
}

sub _replaceStatic{
	my $self = shift;

	debug((caller(0))[3].': Starting...');

	my $meta = "\\\|\(\)\[\{\^\$\*\+\?\.";

	for my $key (keys %{$self->{vars}}){

		my $cleanKey = $key;
		$cleanKey =~ s/([$meta])/\\$1/g;

		my $regexp = sprintf($self->{varRegexp}, $cleanKey);

		$self->{tContent} =~ s/$regexp/$self->{vars}->{$key}/mig
	}

	debug((caller(0))[3].': Ending...');

}

sub replaceBloc($ $ $ $ $){
	debug((caller(0))[3].': Starting...');

	my $self		= iMSCP::Templator->new();
	my $startTag	= shift;
	my $endTag		= shift;
	my $replacement = shift;
	my $content		= shift;
	my $preserve	= shift;

	my $meta = "\\\|\(\)\[\{\^\$\*\+\?\.";

	$startTag =~ s/([$meta])/\\$1/g;
	$endTag =~ s/([$meta])/\\$1/g;

	my $regexp = "(".$startTag.".*".$endTag.")";

	if($preserve){
		$content =~ s/$regexp/$1$replacement/smig;
	} else {
		$content =~ s/$regexp/$replacement/smig;
	}

	debug((caller(0))[3].': Ending...');
	return $content;
}

sub getBloc($ $ $){
	debug((caller(0))[3].': Starting...');

	my $self		= iMSCP::Templator->new();
	my $startTag	= shift;
	my $endTag		= shift;
	my $content		= shift;

	my $meta = "\\\|\(\)\[\{\^\$\*\+\?\.";

	$startTag =~ s/([$meta])/\\$1/g;
	$endTag =~ s/([$meta])/\\$1/g;

	my $regexp = $startTag."(.*)".$endTag;

	$content =~ m/$regexp/smig;

	debug((caller(0))[3].': Ending...');
	return $1;
}

1;
