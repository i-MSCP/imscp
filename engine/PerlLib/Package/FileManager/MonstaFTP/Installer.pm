=head1 NAME

 Package::FileManager::MonstaFTP::Installer - i-MSCP MonstaFTP package installer

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

package Package::FileManager::MonstaFTP::Installer;

use strict;
use warnings;
use iMSCP::Composer;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use JSON;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

our $VERSION = '2.1.x';

=head1 DESCRIPTION

 i-MSCP MonstaFTP package installer.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = iMSCP::Composer->getInstance()->registerPackage( 'imscp/monsta-ftp', $VERSION );
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
    $rs ||= $self->_buildConfig();
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
            "    include imscp_monstaftp.conf;\n" .
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

 Return Package::FileManager::MonstaFTP::Installer

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _installFiles( )

 Install MonstaFTP files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $packageDir = "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/monsta-ftp";

    unless ( -d $packageDir ) {
        error( "Couldn't find the imscp/monsta-ftp package into the packages cache directory" );
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
        "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/vendor/imscp/monsta-ftp/iMSCP/nginx/imscp_monstaftp.conf",
        {
            GUI_PUBLIC_DIR => $main::imscpConfig{'GUI_PUBLIC_DIR'}
        },
        {
            destination => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_monstaftp.conf"
        }
    );
}

=item _buildConfig( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
    my ($self) = @_;

    my $panelUName = my $panelGName =
        $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

    # config.php file

    my $conffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp/settings/config.php";
    my $data = {
        TIMEZONE => main::setupGetQuestion( 'TIMEZONE', 'UTC' ),
        TMP_PATH => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/tmp"
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'monstaftp', 'config.php', \ my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => $conffile )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", $conffile ));
            return 1;
        }
    }

    $cfgTpl = process( $data, $cfgTpl );

    my $file = iMSCP::File->new( filename => $conffile );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->mode( 0440 );
    return $rs if $rs;

    $conffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/ftp/settings/settings.json";
    $data = {
        showDotFiles            => JSON::true,
        language                => 'en_us',
        editNewFilesImmediately => JSON::true,
        editableFileExtensions  => 'txt,htm,html,php,asp,aspx,js,css,xhtml,cfm,pl,py,c,cpp,rb,java,xml,json',
        hideProUpgradeMessages  => JSON::true,
        disableMasterLogin      => JSON::true,
        connectionRestrictions  => {
            types => [
                'ftp'
            ],
            ftp   => {
                host             => '127.0.0.1',
                port             => 21,
                # Enable passive mode excepted if the FTP daemon is vsftpd
                # vsftpd doesn't allows to operate on a per IP basic (IP masquerading)
                passive          => ( $main::imscpConfig{'FTPD_SERVER'} eq 'vsftpd' ) ? JSON::false : JSON::true,
                ssl              => main::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes'
                    ? JSON::true : JSON::false,
                initialDirectory => '/' # Home directory as set for the FTP user
            }
        }
    };

    undef $cfgTpl;
    $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'monstaftp', 'settings.json', \ $cfgTpl, $data );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => $conffile );
    $file->set( $cfgTpl || JSON->new()->utf8( 1 )->pretty( 1 )->encode( $data ));
    $rs = $file->save();
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->mode( 0440 );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
