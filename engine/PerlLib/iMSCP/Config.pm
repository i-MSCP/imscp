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
  - temporary: Enable temporary overriding of configuration values ( changes are not persistent )

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

    debug( sprintf( 'Tying %s file', $self->{'confFileName'} ) );

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

=item FETCH($paramName)

 Return value of the given configuration parameter

 Param string $paramName Configuration parameter name
 Return scalar|undef Configuration parameter value or undef if config parameter is not defined

=cut

sub FETCH
{
    my ($self, $paramName) = @_;

    return $self->{'configValues'}->{$paramName} if exists $self->{'configValues'}->{$paramName};

    unless ($self->{'nowarn'}) {
        my (undef, $file, $line) = caller;
        warning( sprintf(
                'Accessing non existing config value %s from the %s file (see file %s at line %s)',
                $paramName, $self->{'fileName'}, $file, $line
            ) );
    }

    undef;
}

=item STORE($paramName, $value)

 Store the given configuration parameter

 Param string $paramName Configuration parameter name
 Param string $value Configuration parameter value
 Return string Stored value

=cut

sub STORE
{
    my ($self, $paramName, $value) = @_;

    !$self->{'readonly'} || $self->{'temporary'} or die( 'Config object is readonly' );

    unless (exists $self->{'configValues'}->{$paramName}) {
        $self->_insertConfig( $paramName, $value );
    } else {
        $self->_replaceConfig( $paramName, $value );
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

=item EXISTS($paramName)

 Verify that the given configuration parameter exists

 Param string $paramName configuration parameter name
 Return true if the given configuration parameter exists, false otherwise

=cut

sub EXISTS
{
    my ($self, $paramName) = @_;

    exists $self->{'configValues'}->{$paramName};
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

=item _replaceConfig($paramName, $value)

 Replace the given configuration parameter value

 Param string $paramName Configuration parameter name
 Param string $value Configuration parameter value
 Return string Configuration parameter value

=cut

sub _replaceConfig
{
    my ($self, $paramName, $value) = @_;

    $value = '' unless defined $value;

    unless ($self->{'temporary'}) {
        @{$self->{'confFile'}}[$self->{'lineMap'}->{$paramName}] = "$paramName = $value";
    }

    $self->{'configValues'}->{$paramName} = $value;
}

=item _insertConfig($paramName, $value)

 Insert the given configuration parameter

 Param string $paramName Configuration parameter name
 Param string $config Configuration parameter value
 Return string $value Configuration parameter value

=cut

sub _insertConfig
{
    my ($self, $paramName, $value) = @_;

    $value ||= '' unless defined $value;
    push @{$self->{'confFile'}}, "$paramName = $value";
    $self->{'lineMap'}->{$paramName} = $#{$self->{confFile}};
    $self->{'configValues'}->{$paramName} = $value;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
