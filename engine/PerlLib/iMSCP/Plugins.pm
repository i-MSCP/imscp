=head1 NAME

 iMSCP::Plugins - Library for loading and retrieval of i-MSCP plugins

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Plugins;

use strict;
use warnings;
use iMSCP::Boolean;
use File::Basename 'basename';
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Library for loading and retrieval of i-MSCP plugins.

=head1 PUBLIC METHODS

=over 4

=item getList( )

 Get list of available plugins

 Return server list

=cut

sub getList
{
    @{ $_[0]->{'__plugins__'} };
}

=item getClass( $pluginName )

 Get class name of the given plugin
 
 This will also load the plugin class if not already done.

 Param string $pluginName Plugin name
 Return string Plugin name or die if the plugin is not available
=cut

sub getClass
{
    my ( $self, $pluginName ) = @_;

    unless ( $self->{'__loaded__'}->{$pluginName} ) {
        grep ( $_ eq $pluginName, @{ $self->{'__plugins__'} } ) or die( sprintf( "Plugin %s isn't available", $pluginName ));
        require "$main::imscpConfig{'PLUGINS_DIR'}/$pluginName/backend/$pluginName.pm";
        $self->{'__loaded__'}->{$pluginName} = TRUE;
    }

    "Plugin::$pluginName";
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Plugins

=cut

sub _init
{
    my ( $self ) = @_;

    $_ = basename( $_, '.pm' ) for @{ $self->{'__plugins__'} } = glob( "$::imscpConfig{'PLUGINS_DIR'}/*/backend/*.pm" );
    $self->{'__loaded__'} = {};
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
