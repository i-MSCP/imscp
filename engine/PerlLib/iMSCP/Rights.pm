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

package iMSCP::Rights;

use strict;
use warnings;
use iMSCP::Debug;

use vars qw/@ISA @EXPORT/;
use Exporter;
use Common::SingletonClass;

@ISA = ('Common::SingletonClass', 'Exporter');
@EXPORT = qw/setRights/;

sub setRights{
	debug('Starting...');

	my $file	= shift;
	my $option	= shift;
	my $rs		= 0;
	$option		= {} if ref $option ne 'HASH';

	my  @dchmod = (
		"find $file -type d -print0 | xargs",
		($^O !~ /bsd$/ ? '-r' : ''),
		'-0 chmod',
		($main::imscpConfig{DEBUG} ? '-v' : ''),
		$option->{dirmode}
	)if $option->{dirmode};

	my  @fchmod = (
		"find $file -type f -print0 | xargs",
		($^O !~ /bsd$/ ? '-r' : ''),
		'-0 chmod',
		($main::imscpConfig{DEBUG} ? '-v' : ''),
		$option->{filemode}
	)if $option->{filemode};

	my  @chmod = (
		'chmod',
		($main::imscpConfig{DEBUG} ? '-v' : ''),
		($option->{recursive} ? '-R' : ''),
		$option->{mode},
		$file
	)if $option->{mode};

	my  @chown = (
		"chown",
		($main::imscpConfig{DEBUG} ? '-v' : ''),
		($option->{recursive} ? '-R' : ''),
		"$option->{user}:$option->{group} $file"
	) if $option->{user} && $option->{group};

	$rs |= _set(@chmod) if($option->{mode});

	$rs |= _set(@dchmod) if($option->{dirmode} && $option->{recursive});
	$rs |= _set(@fchmod) if($option->{filemode} && $option->{recursive});

	$rs |= _set(@chown) if($option->{user} && $option->{group});

	debug('Ending...');
	$rs;
}

sub _set{
	debug('Starting...');

	use iMSCP::Execute;

	my ($rs, $stdout, $stderr);
	$rs = execute("@_", \$stdout, \$stderr);
	debug("$stdout") if $stdout;
	error("$stderr") if $stderr;
	error("Error while executing @_") if !$stderr && $rs;

	debug('Ending...');
	$rs;
}
1;
