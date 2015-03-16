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

use Tie::File;
use iMSCP::Debug;
use Fcntl 'O_RDWR', 'O_CREAT', 'O_RDONLY';
use parent 'Common::Object';

=head1 DESCRIPTION

 This class allow to tie an i-MSCP configuration file to a hash variable.
 See perl tie and tie::file for more information.

=head1 PRIVATE METHODS

=over 4

=item

 Constructor. Called by the tie command

 The required arguments for the tie command are:
  - fileName: Filename of the configuration file (including path)

 Optional arguments for the tie command are:
  - nowarn: Do not warn when trying to access to an inexistent configuration parameter
  - nocreate: Do not create file if it doesn't already exist (throws a fatal error instead)
  - nofail: Do not throws fatal error in case configuration file doesn't exist
  - readonly: Sets a read-only access on the tied configuration file

=cut

sub TIEHASH
{
	(shift)->new(@_);
}

=item

 Initialize tied hash variable

 Return iMSCP::Config

=cut

sub _init
{
	my $self = $_[0];

	@{$self->{'confFile'}} = ();
	$self->{'configValues'} = { };
	$self->{'lineMap'} = { };

	if(defined $self->{'fileName'}) {
		$self->{'confFileName'} = $self->{'fileName'};
	} else {
		fatal('fileName attribut is not defined');
	}

	debug("Tying $self->{'confFileName'}");

	$self->_loadConfig();
	$self->_parseConfig();

	$self;
}

=item

 Load i-MSCP configuration file

 Return undef

=cut

sub _loadConfig
{
	my $self = $_[0];

	my $mode;

	debug("Loading $self->{'confFileName'}");

	if($self->{'nocreate'}) {
		if($self->{'readonly'}) {
			$mode = O_RDONLY;
		} else {
			$mode = O_RDWR;
		}
	} elsif($self->{'readonly'}) {
		$mode = O_RDONLY;
	} else {
		$mode = O_RDWR | O_CREAT;
	}

	if(! tie @{$self->{'confFile'}}, 'Tie::File', $self->{'confFileName'}, 'mode' => $mode) {
		if($self->{'nofail'}) {
			require Tie::Array;
			tie @{$self->{'confFile'}}, 'Tie::StdArray';
		} else {
			fatal("Unable to tie file $self->{'confFileName'}: $!");
		}
	}

	undef;
}

=item

 Parse configuration file

 Return undef

=cut

sub _parseConfig
{
	my $self = $_[0];

	my $lineNo = 0;

	debug("Parsing $self->{'confFileName'}");

	for (@{$self->{'confFile'}}) {
		if (/^([^#\s=]+)\s{0,}=\s{0,}(.{0,})$/) {
			$self->{'configValues'}->{$1} = $2;
			$self->{'lineMap'}->{$1} = $lineNo;
		}

		$lineNo++;
	}

	undef;
}

=item

 Return value of the given configuration parameter

 Return scalar|undef - Configuration parameter value or undef if config parameter is not defined

=cut

sub FETCH
{
	my ($self, $config) = @_;

	unless (exists $self->{'configValues'}->{$config}) {
		unless($self->{'nowarn'}) {
			my (undef, $file, $line) = caller;

			warning(
				sprintf(
					'Accessing non existing config value %s from the %s file (see file %s at line %s)',
					$config,
					$self->{'fileName'},
					$file,
					$line
				)
			);
		}

		undef;
	} else {
		$self->{'configValues'}->{$config};
	}
}

=item

 Store the given configuration parameters

 Return stored value

=cut

sub STORE
{
	my ($self, $config, $value) = @_;

	if(! $self->{'readonly'}) {
		if(! exists $self->{'configValues'}->{$config}) {
			$self->_insertConfig($config, $value);
		} else {
			$self->_replaceConfig($config, $value);
		}
	} else {
		fatal('Config object is readonly');
	}

	$value;
}

=item

 Return the first configuration parameter

 Return string

=cut

sub FIRSTKEY
{
	my $self = $_[0];

	$self->{'_list'} = [ sort keys %{$self->{'configValues'}} ];

	$self->NEXTKEY;
}

=item

 Return the next configuration parameters

 Return string

=cut

sub NEXTKEY
{
	shift @{$_[0]->{'_list'}};
}

=item

 Verify that the given configuration parameter exists.

 Return true if the given configuration parameter exists, false otherwise

=cut

sub EXISTS
{
	my ($self, $config) = @_;

	exists $self->{'configValues'}->{$config};
}


=item

 Clear all configuration parameters

=cut

sub CLEAR
{
	my $self = $_[0];

	@{$self->{'confFile'}} = ();
	$self->{'configValues'} = { };
	$self->{'lineMap'} = { };

	$self;
}

=item _insertConfig($config, $value)

 Replace the given configuration parameter value

 Param string $config Configuration parameter name
 Param string $config Configuration parameter value
 Return string Configuration parameter value

=cut

sub _replaceConfig
{
	my ($self, $config, $value) = @_;

	$value = '' unless defined $value;

	@{$self->{'confFile'}}[$self->{'lineMap'}->{$config}] = "$config = $value";
	$self->{'configValues'}->{$config} = $value;
}

=item _insertConfig($config, $value)

 Insert the given configuration parameter.

 Param string $config Configuration parameter name
 Param string $config Configuration parameter value
 Return string Configuration parameter value

=cut

sub _insertConfig
{
	my ($self, $config, $value) = @_;

	$value = '' unless defined $value;

	push (@{$self->{'confFile'}}, "$config = $value");
	$self->{'lineMap'}->{$config} = $#{$self->{confFile}};
	$self->{'configValues'}->{$config} = $value;
}

=head1 AUTHORS

 Daniel Andreca <sci2tech@gmail.com>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
