=head1 NAME

 iMSCP::Config - i-MSCP configuration files handler

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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

package iMSCP::Config;

use strict;
use warnings;
use Carp;
use Fcntl 'O_RDWR', 'O_CREAT', 'O_RDONLY';
use Tie::File;
use parent 'Common::Object';

=head1 DESCRIPTION

 i-MSCP configuration files handler.

=head1 PRIVATE METHODS

=over 4

=item TIEHASH()

 Constructor

 Required arguments:
  - fileName: Filename of the configuration file (including path)
 Optional arguments:
  - nowarn: Do not raise warning when trying to access to an inexistent configuration parameter
  - nocreate: Do not create file if it doesn't already exist (throws a fatal error instead)
  - nofail: Do not raise error in case configuration file doesn't exist
  - readonly: Sets a read-only access on the tied configuration file
  - temporary: Enable temporary overriding of configuration values ( changes are not persistent )

=cut

sub TIEHASH
{
	(shift)->new(@_);
}

=item _init()

 Initialization

 Return iMSCP::Config, croak on failure

=cut

sub _init
{
	my $self = shift;

	defined $self->{'fileName'} or croak('fileName attribut is not defined');

	@{$self->{'tiedFile'}} = ();
	$self->{'configValues'} = {};
	$self->{'lineMap'} = {};

	my $mode;
	if($self->{'nocreate'}) {
		$mode = $self->{'readonly'} ? O_RDONLY : O_RDWR
	} else {
		$mode = $self->{'readonly'} ? O_RDONLY : O_RDWR|O_CREAT;
	}

	unless(tie @{$self->{'tiedFile'}}, 'Tie::File', $self->{'fileName'}, mode => $mode, memory => 0) {
		if($self->{'nofail'}) {
			require Tie::Array;
			tie @{$self->{'tiedFile'}}, 'Tie::StdArray';
		} else {
			die(sprintf('Could not tie file %s: %s', $self->{'fileName'}, $!));
		}
	}

	my $lineNo = 0;
	for my $line(@{$self->{'tiedFile'}}) {
		if ($line =~ /^([^#\s=]+)\s{0,}=\s{0,}(.*)/) {
			$self->{'configValues'}->{$1} = $2;
			$self->{'lineMap'}->{$1} = $lineNo;
		}

		$lineNo++;
	}

	$self;
}

=item FETCH($param)

 Return value of the given parameter

 Param string $param Parameter name
 Return scalar|undef Parameter value or undef if config parameter is not defined

=cut

sub FETCH
{
	my ($self, $param) = @_;

	exists $self->{'configValues'}->{$param} || $self->{'nowarn'} or carp(sprintf(
		'Attempt to access inexistent %s configuration parameter in %s', $param, $self->{'fileName'}
	));

	$self->{'configValues'}->{$param};
}

=item STORE($param, $value)

 Store the given parameter

 Param string $param Parameter name
 Param string $value Parameter value
 Return string Stored value

=cut

sub STORE
{
	my ($self, $param, $value) = @_;

	!$self->{'readonly'} || $self->{'temporary'} or croak(sprintf('The %s tied file is readonly', $self->{'fileName'}));
	$value //= ''; # Don't try to store undefined value

	unless($self->{'temporary'}) {
		if(exists $self->{'configValues'}->{$param}) {
			@{$self->{'tiedFile'}}[ $self->{'lineMap'}->{$param} ] = "$param = $value";
		} else {
			push @{$self->{'tiedFile'}}, "$param = $value";
			$self->{'lineMap'}->{$param} = $#{$self->{tiedFile}};
		}
	}

	$self->{'configValues'}->{$param} = $value;
}

=item FIRSTKEY()

 Return the first parameter

 Return string

=cut

sub FIRSTKEY
{
	my $self = shift;

	$self->{'_list'} = [ sort keys %{$self->{'configValues'}} ];
	$self->NEXTKEY;
}

=item NEXTKEY()

 Return the next parameter

 Return string

=cut

sub NEXTKEY
{
	shift @{ (shift)->{'_list'} };
}

=item EXISTS($param)

 Verify that the given parameter exists

 Param string $param Parameter name
 Return bool TRUE if the given parameter exists, FALSE otherwise

=cut

sub EXISTS
{
	my ($self, $param) = @_;

	exists $self->{'configValues'}->{$param};
}

=item CLEAR()

 Clear all parameters

=cut

sub CLEAR
{
	my $self = shift;

	@{$self->{'tiedFile'}} = ();
	$self->{'configValues'} = {};
	$self->{'lineMap'} = {};
	$self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
