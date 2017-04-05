=head1 NAME

 autoinstaller::Adapter::AbstractAdapter - Abstract class for autoinstaller distro adapters

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2017 by internet Multi Server Control Panel
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

package autoinstaller::Adapter::AbstractAdapter;

use strict;
use warnings;
use parent 'Common::Object';

=head1 DESCRIPTION

 Abstract class for distro autoinstaller adapters. Any distro autoinstaller adapter class *should* implement methods of
 this class.

=head1 PUBLIC METHODS

=over 4

=item installPreRequiredPackages( )

 Install pre-required packages

 Return int 0 on success, other on failure

=cut

sub installPreRequiredPackages
{
    0;
}

=item preBuild( \@steps )

 Process preBuild tasks

 Param array \@steps List of build steps
 Return int 0 on success, other on failure

=cut

sub preBuild
{
    0;
}

=item installPackages( )

 Install distribution packages

 Return int 0 on success, other on failure

=cut

sub installPackages
{
    0;
}

=item uninstallPackages( )

 Uninstall distribution packages no longer needed

 Return int 0 on success, other on failure

=cut

sub uninstallPackages
{
    0;
}

=item postBuild( )

 Process postBuild tasks

 Return int 0 on success, other on failure

=cut

sub postBuild
{
    0;
}

=item preInstall( \@steps )

 Process preInstall tasks

 Param array \@steps List of install steps
 Return int 0 on success, other on failure

=cut

sub preInstall
{
    0;
}

=item postInstall( )

 Process postInstall tasks

 Return int 0 on success, other on failure

=cut

sub postInstall()
{
    0;
}

=back

=head1 Author

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
