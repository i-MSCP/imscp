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
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Library for loading and retrieval of i-MSCP plugins.

=head1 PUBLIC METHODS

=over 4

=item getList( )

 Get list of available plugins

 Return list of available plugins

=cut

sub getList
{
    keys %{ $_[0]->{'_plugins'} };
}

=item getClass( $plugin )

 Get the full class name of the given plugin
 
 This will also load the plugin class if not already done.

 Param string $plugin Plugin name
 Return string Plugin name, dieon failure
=cut

sub getClass
{
    my ( $self, $plugin ) = @_;

    unless ( $self->{'_loaded'}->{$plugin} ) {
        $self->{'_plugins'}->{$plugin} or die( sprintf(
            "Plugin %s not found.", $plugin
        ));

        require $self->{'_plugins'}->{$plugin};
        $self->{'_loaded'}->{$plugin} = TRUE;
    }

    "Plugin::$plugin";
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

    %{ $self->{'_plugins'} } = map {
        s%.+?([^/]+)\.pm$%$1%r => $_
    } grep {
        m%([^/]+)/backend/(.+)\.pm$% && $1 eq $2;
    } glob(
        "$::imscpConfig{'PLUGINS_DIR'}/*/backend/*.pm"
    );

    $self->{'_loaded'} = {};
    $self;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
