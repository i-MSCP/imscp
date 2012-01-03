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

	$self->{varStartTag}		= '\{';
	$self->{varEndTag}			= '\}';
	$self->{varRegexp}			= "$self->{varStartTag}%s$self->{varEndTag}";
	$self->{inclusionTagStart}	= '# [(\{.*\})](.*)START\.';
	$self->{inclusionTagEnd}	= '# [\1](.)END\.';
}

sub set($ $){
	my $prop	= shift;
	my $value	= shift;
	my $self	= iMSCP::Templator->new();
	debug("Setting $prop as $value");
	$self->{$prop} = $value if(exists $self->{$prop});
}

sub loadlayout{
}

sub process($ $){
	my $self			= iMSCP::Templator->new();
	$self->{vars}		= shift || ref {};
	$self->{tContent}	= shift || '';

	$self->{vars} = {} if (ref $self->{vars} ne 'HASH');

	$self->_replaceStatic();

	#restore default tags
	$self->{args} = {};
	$self->_init();

	return $self->{tContent};
}

sub _replaceStatic{
	my $self = shift;

	my $meta = "\\\|\(\)\[\{\^\$\*\+\?\.";

	for my $key (keys %{$self->{vars}}){

		next unless defined $self->{vars}->{$key};

		my $cleanKey = $key;
		$cleanKey =~ s/([$meta])/\\$1/g;

		my $regexp = sprintf($self->{varRegexp}, $cleanKey);
		#debug("Replace $regexp with $self->{vars}->{$key}");

		$self->{tContent} =~ s/$regexp/$self->{vars}->{$key}/mig
	}

}

sub replaceBloc($ $ $ $ $){

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

	return $content;
}

sub getBloc($ $ $){

	my $self		= iMSCP::Templator->new();
	my $startTag	= shift;
	my $endTag		= shift;
	my $content		= shift;

	my $meta = "\\\|\(\)\[\{\^\$\*\+\?\.";

	$startTag =~ s/([$meta])/\\$1/g;
	$endTag =~ s/([$meta])/\\$1/g;

	my $regexp = $startTag."(.*)".$endTag;
	my $rs;

	if($content =~ m/$regexp/smig){
		$rs = $1;
	} else {
		$rs = '';
	}

	$rs;
}

1;
