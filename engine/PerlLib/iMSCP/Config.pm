=head1 NAME

 iMSCP::Config - i-MSCP configuration files handler

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use 5.012;
use iMSCP::Debug;
use Fcntl 'O_RDWR', 'O_CREAT', 'O_RDONLY';
use Tie::File;
use parent 'Common::Object';

=head1 DESCRIPTION

 Provides access to various i-MSCP configuration files through tied hash variable

=head1 PUBLIC METHODS

=over 4

=item flush()

 Write data immediately in file
 Return int 0;

=cut

sub flush
{
    my $self = shift;

    return 0 if $self->{'readonly'}
        || !($self->{'tieFileObject'}->{'defer'} || $self->{'tieFileObject'}->{'autodeferring'});

    $self->{'tieFileObject'}->flush();
}

=back

=head1 PRIVATE METHODS

=over 4

=item TIEHASH()

 Constructor. Called by the tie function

 Required arguments for tie()
  - fileName: Configuration file path
 Optional arguments for tie()
  - nocreate: Do not create file if it doesn't already exist, die instead
  - nodie: Do not die when accessing to an non-existent configuration parameter
  - readonly: Sets a read-only access on the configuration file
  - temporary: Enable temporary overriding of configuration values (changes are not persistent)

=cut

sub TIEHASH
{
    (shift)->new( @_ );
}

=item FETCH($param)

 Return value of the given configuration parameter

 Param string param Configuration parameter name
 Return scalar|undef Configuration parameter value if defined or undef 'nodie' attribute is set

=cut

sub FETCH
{
    my ($self, $param) = @_;

    return $self->{'configValues'}->{$param} if exists $self->{'configValues'}->{$param};
    return if $self->{'nodie'};
    die(sprintf(
        'Accessing a non-existing parameter: %s in %s file from: %s (line %s)',
        $param,
        $self->{'fileName'},
        (caller)[1, 2]
    ));
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

    @{$self->{'tiefile'}} = ();
    $self->{'configValues'} = { };
    $self->{'lineMap'} = { };
    $self;
}

=item DESTROY()

 Destroy

=cut

sub DESTROY
{
    my $self = shift;

    undef $self->{'tieFileObject'};
    untie(@{$self->{'tiefile'}});
}

=item _init()

 Initialization

 Return iMSCP::Config, die on failure

=cut

sub _init
{
    my $self = shift;

    defined $self->{'fileName'} or die( 'fileName attribut is not defined' );

    @{$self->{'tiefile'}} = ();
    $self->{'tieFileObject'} = undef;
    $self->{'configValues'} = { };
    $self->{'lineMap'} = { };
    $self->{'confFileName'} = $self->{'fileName'};
    $self->_loadConfig();
    $self;
}

=item _loadConfig()

 Load i-MSCP configuration file

 Return undef or die on failure

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

    debug( sprintf( 'Tying %s file in %s mode', $self->{'confFileName'}, $self->{'readonly'} ? 'readonly' : 'writing' ) );

    $self->{'tieFileObject'} = tie @{$self->{'tiefile'}}, 'Tie::File', $self->{'confFileName'}, mode => $mode;
    $self->{'tieFileObject'} or die( sprintf( 'Could not tie %s file: %s', $self->{'confFileName'}, $! ) );

    # Enable deffered writing if we are in writing mode
    $self->{'tieFileObject'}->defer unless $self->{'readonly'};

    while(my ($lineNo, $value) = each(@{$self->{'tiefile'}})) {
        next unless $value =~ /^([^#\s=]+)\s*=\s*(.*)$/;
        $self->{'configValues'}->{$1} = $2;
        $self->{'lineMap'}->{$1} = $lineNo;
    }

    undef;
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
    @{$self->{'tiefile'}}[$self->{'lineMap'}->{$param}] = "$param = $value" unless $self->{'temporary'};
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

    unless ($self->{'temporary'}) {
        push @{$self->{'tiefile'}}, "$param = $value";
        $self->{'lineMap'}->{$param} = $#{$self->{'tiefile'}};
    }

    $self->{'configValues'}->{$param} = $value;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
