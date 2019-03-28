=head1 NAME

 Package::AntiRootkits::Rkhunter::Rkhunter - i-MSCP Rkhunter package

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

package Package::AntiRootkits::Rkhunter::Rkhunter;

use strict;
use warnings;
use Class::Autouse qw/ :nostat Package::AntiRootkits::Rkhunter::Installer Package::AntiRootkits::Rkhunter::Uninstaller /;
use iMSCP::Rights;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Rkhunter package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    Package::AntiRootkits::Rkhunter::Installer->getInstance()->preinstall();
}

=item postinstall( )

 Process post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    Package::AntiRootkits::Rkhunter::Installer->getInstance()->postinstall();
}

=item uninstall( )

 Process uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    Package::AntiRootkits::Rkhunter::Uninstaller->getInstance()->uninstall();
}

=item setEnginePermissions( )

 Set engine permissions.

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my $rs = setRights( "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/AntiRootkits/Rkhunter/Cron.pl", {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_USER'},
        mode  => '0700'
    } );

    return $rs if $rs || !-f $::imscpConfig{'RKHUNTER_LOG'};

    setRights( $::imscpConfig{'RKHUNTER_LOG'}, {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'IMSCP_GROUP'},
        mode  => '0640'
    } );
}

=item getDistributionPackages( )

 Get list of distribution packages

 Return list List of packages

=cut

sub getDistributionPackages
{
    'rkhunter';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
