=head1 NAME

 Package::FileManager::Pydio::Installer - i-MSCP Pydio package installer

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Package::FileManager::Pydio::Installer;

use strict;
use warnings;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Composer;
use iMSCP::TemplateParser;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

our $VERSION = '0.2.0.*@dev';

=head1 DESCRIPTION

 i-MSCP Pydio package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = iMSCP::Composer->getInstance()->registerPackage( 'imscp/ajaxplorer', $VERSION );
    $rs ||= $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_installFiles();
    $rs ||= $self->_buildHttpdConfig();
}

=back

=head1 EVENT LISTENERS

=over 4

=item afterFrontEndBuildConfFile( \$tplContent, $filename )

 Include httpd configuration into frontEnd vhost files

 Param string \$tplContent Template file tplContent
 Param string $tplName Template name
 Return int 0 on success, other on failure

=cut

sub afterFrontEndBuildConfFile
{
    my ($tplContent, $tplName) = @_;

    return 0 unless grep($_ eq $tplName, '00_master.nginx', '00_master_ssl.nginx');

    ${$tplContent} = replaceBloc(
        "# SECTION custom BEGIN.\n",
        "# SECTION custom END.\n",
        "    # SECTION custom BEGIN.\n" .
            getBloc(
                "# SECTION custom BEGIN.\n",
                "# SECTION custom END.\n",
                ${$tplContent}
            ) .
            "    include imscp_pydio.conf;\n" .
            "    # SECTION custom END.\n",
        ${$tplContent}
    );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::Pydio::Installer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _installFiles( )

 Install files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $packageDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/ajaxplorer";
    unless ( -d $packageDir ) {
        error( "Couldn't find the imscp/ajaxplorer (Pydio) package into the packages cache directory" );
        return 1;
    }

    iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp" )->remove();
    iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp", { preserve => 'no' }
    );
    iMSCP::Dir->new( dirname => "$packageDir/iMSCP/src" )->rcopy(
        "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp", { preserve => 'no' }
    );
    0;
}

=item _buildHttpdConfig( )

 Build Httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
    my $frontEnd = Package::FrontEnd->getInstance();
    $frontEnd->buildConfFile(
        "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/ajaxplorer/iMSCP/config/nginx/imscp_pydio.conf",
        {
            GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'}
        },
        {
            destination => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_pydio.conf"
        }
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
