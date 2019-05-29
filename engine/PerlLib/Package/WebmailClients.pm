=head1 NAME

 Package::WebmailClients - Webmail client packages

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

package Package::WebmailClients;

use strict;
use warnings;
use parent 'Package::AbstractPackageCollection';

=head1 DESCRIPTION

 Provides webmail client packages.

=head1 PUBLIC METHODS

=over 4

=item getConfVarname( )

 See Package::AbstractPackageCollection::getConfVarname()

=cut

sub getConfVarname
{
    my ( $self ) = @_;

    'WEB_MAIL_CLIENT_PACKAGES';
}

=item getOptName( )

 See Package::AbstractPackageCollection::getOptName()

=cut

sub getOptName
{
    my ( $self ) = @_;

    'webmail_client_packages';
}

=item getDefaultValues( )

 See Package::AbstractPackageCollection::getDefaultValues()

=cut

sub getDefaultValues
{
    my ( $self ) = @_;

    'RainLoop,Roundcube';
}

=item getPackageHumanName( )

 See Package::AbstractPackageCollection::getPackageHumanName()

=cut

sub getPackageHumanName
{
    my ( $self ) = @_;

    'webmail clients';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
