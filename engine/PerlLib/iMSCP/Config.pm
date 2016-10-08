=head1 NAME

 iMSCP::Config - i-MSCP configuration files handler

=cut

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

package iMSCP::Config;

use strict;
use warnings;
use iMSCP::Debug;
use Fcntl 'O_RDWR', 'O_CREAT', 'O_RDONLY';
use Tie::File;
use parent 'Common::Object';

=head1 DESCRIPTION

 This class allow to tie an i-MSCP configuration file to a hash variable.
 See perl tie and tie::file for more information.

=head1 PRIVATE METHODS

=over 4

=item TIEHASH()

 Constructor. Called by the tie function

 The required arguments for the tie function are:
  - fileName: Filename of the configuration file (including path)

 Optional arguments for the tie function are:
  - nowarn: Do not warn when trying to access to an inexistent configuration parameter
  - nocreate: Do not create file if it doesn't already exist (throws a fatal error instead)
  - nofail: Do not throws fatal error in case configuration file doesn't exist
  - readonly: Sets a read-only access on the tied configuration file
  - temporary: Enable temporary overriding of configuration values (changes are not persistent)

=cut

sub TIEHASH
{
    (shift)->new( @_ );
}

=item _init()

 Initialization

 Return iMSCP::Config

=cut

sub _init
{
    my $self = shift;

    defined $self->{'fileName'} or die( 'fileName attribut is not defined' );

    @{$self->{'confFile'}} = ();
    $self->{'configValues'} = { };
    $self->{'lineMap'} = { };
    $self->{'confFileName'} = $self->{'fileName'};

    debug( sprintf( 'Tying %s file in %s mode', $self->{'confFileName'}, $self->{'readonly'} ? 'readonly' : 'writing' ) );

    $self->_loadConfig();
    $self->_parseConfig();
    $self;
}

=item _loadConfig()

 Load i-MSCP configuration file

 Return undef

=cut

sub _loadConfig
{
    my $self = shift;

    my $mode;

    if ($self->{'nocreate'}) {
        $mode = $self->{'readonly'} ? O_RDONLY : O_RDWR;
    } elsif ($self->{'readonly'}) {
        $mode = O_RDONLY;
    } else {
        $mode = O_RDWR | O_CREAT;
    }

    return if tie @{$self->{'confFile'}}, 'Tie::File', $self->{'confFileName'}, 'mode' => $mode;

    $self->{'nofail'} or die( sprintf( 'Could not tie %s file: %s', $self->{'confFileName'}, $! ) );

    require Tie::Array;
    tie @{$self->{'confFile'}}, 'Tie::StdArray';
    undef;
}

=item _parseConfig()

 Parse configuration file

 Return undef

=cut

sub _parseConfig
{
    my $self = shift;

    my $lineNo = 0;
    for (@{$self->{'confFile'}}) {
        if (/^([^#\s=]+)\s{0,}=\s{0,}(.{0,})$/) {
            $self->{'configValues'}->{$1} = $2;
            $self->{'lineMap'}->{$1} = $lineNo;
        }

        $lineNo++;
    }

    undef;
}

=item FETCH($param)

 Return value of the given configuration parameter

 Param string param Configuration parameter name
 Return scalar|undef Configuration parameter value or undef if config parameter is not defined

=cut

sub FETCH
{
    my ($self, $param) = @_;

    return $self->{'configValues'}->{$param} if exists $self->{'configValues'}->{$param};

    unless ($self->{'nowarn'}) {
        my (undef, $file, $line) = caller;
        warning(
            sprintf(
                'Accessing non existing config value %s from the %s file (see file %s at line %s)',
                $param, $self->{'fileName'}, $file, $line
            )
        );
    }

    undef;
}

=item STORE($param, $value)

 Store the given configuration parameter

 Param string param Configuration parameter name
 Param string $value Configuration parameter value
 Return string Stored value

=cut

sub STORE
{
    my ($self, $param, $value) = @_;

    !$self->{'readonly'} || $self->{'temporary'} or die(
        sprintf("Could not change value for the `%s' parameter: config object is readonly", $param )
    );

    unless (exists $self->{'configValues'}->{$param}) {
        $self->_insertConfig( $param, $value );
    } else {
        $self->_replaceConfig( $param, $value );
    }

    $value;
}

=item FIRSTKEY()

 Return the first configuration parameter

 Return string

=cut

sub FIRSTKEY
{
    my $self = shift;

    $self->{'_list'} = [ sort keys %{$self->{'configValues'}} ];
    $self->NEXTKEY;
}

=item NEXTKEY()

 Return the next configuration parameters

 Return string

=cut

sub NEXTKEY
{
    shift @{$_[0]->{'_list'}};
}

=item EXISTS($param)

 Verify that the given configuration parameter exists

 Param string param configuration parameter name
 Return true if the given configuration parameter exists, false otherwise

=cut

sub EXISTS
{
    my ($self, $param) = @_;

    exists $self->{'configValues'}->{$param};
}

=item CLEAR()

 Clear all configuration parameters

=cut

sub CLEAR
{
    my $self = shift;

    @{$self->{'confFile'}} = ();
    $self->{'configValues'} = { };
    $self->{'lineMap'} = { };
    $self;
}

=item _replaceConfig($param, $value)

 Replace the given configuration parameter value

 Param string param Configuration parameter name
 Param string $value Configuration parameter value
 Return string Configuration parameter value

=cut

sub _replaceConfig
{
    my ($self, $param, $value) = @_;
    $value //= '';
    
    unless ($self->{'temporary'}) {
        @{$self->{'confFile'}}[$self->{'lineMap'}->{$param}] = "$param = $value";
    }

    $self->{'configValues'}->{$param} = $value;
}

=item _insertConfig($param, $value)

 Insert the given configuration parameter

 Param string param Configuration parameter name
 Param string $config Configuration parameter value
 Return string $value Configuration parameter value

=cut

sub _insertConfig
{
    my ($self, $param, $value) = @_;
    $value //= '';

    unless($self->{temporary}) {
        push @{$self->{'confFile'}}, "$param = $value";
        $self->{'lineMap'}->{$param} = $#{$self->{confFile}};
    }

    $self->{'configValues'}->{$param} = $value;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
