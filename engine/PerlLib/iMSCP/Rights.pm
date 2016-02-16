# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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

package iMSCP::Rights;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Execute;
use parent 'Common::SingletonClass', 'Exporter';

use vars qw/@EXPORT/;
@EXPORT = qw/setRights/;

sub setRights
{
	my ($file, $options) = @_;

	$options = { } if ref $options ne 'HASH';

	my $rs = 0;

	my  @dchmod = (
		"find $file -type d -print0 | xargs", ($^O !~ /bsd$/) ? '-r' : '', "-0 chmod $options->{'dirmode'}"
	) if $options->{'dirmode'};

	my  @fchmod = (
		"find $file -type f -print0 | xargs", ($^O !~ /bsd$/) ? '-r' : '', "-0 chmod $options->{'filemode'}"
	) if $options->{'filemode'};

	my  @chmod = ('chmod', $options->{'recursive'} ? '-R' : '', "$options->{'mode'} $file") if $options->{'mode'};

	my  @chown = (
		'chown',
		'-h', # Do not dereference (never modify the target referenced by a symlink). Acts on the symlink itself
		$options->{'recursive'} ? '-R' : '',
		"$options->{'user'}:$options->{'group'} $file"
	) if $options->{'user'} && $options->{'group'};

	$rs = _set(@chmod) if $options->{'mode'};
	return $rs if $rs;

	$rs = _set(@dchmod) if $options->{'dirmode'} && $options->{'recursive'};
	return $rs if $rs;

	$rs = _set(@fchmod) if $options->{'filemode'} && $options->{'recursive'};
	return $rs if $rs;

	$rs = _set(@chown) if $options->{'user'} && $options->{'group'};
	return $rs if $rs;

	$rs;
}

sub _set
{
	my ($stdout, $stderr);

	my $rs = execute("@_", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error("Error while executing @_") if $rs && ! $stderr;

	$rs;
}

1;
__END__
