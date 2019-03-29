=head1 NAME

 Package::WebFtpClients::MonstaFTP::Installer - i-MSCP MonstaFTP package installer

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

package Package::WebFtpClients::MonstaFTP::Installer;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Composer;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;
use JSON;
use Package::FrontEnd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP MonstaFTP package installer.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%em )

 Register setup event listeners

 Param iMSCP::EventManager \%em
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( undef, $em ) = @_;

    return 0 if iMSCP::Getopt->skipComposerUpdate;

    $em->registerOne( 'beforeSetupPreInstallServers', sub {
        eval {
            iMSCP::Composer->new(
                user          => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
                composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
                composer_json => 'composer.json'
            )
                ->require( 'imscp/monsta-ftp', '2.1.x-dev' )
                ->dumpComposerJson();
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        0;
    }, 10 );
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    $self->{'eventManager'}->register( 'afterFrontEndBuildConfFile', \&afterFrontEndBuildConfFile );
}

=item install( )

 Process installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

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
    my ( $tplContent, $tplName ) = @_;

    return 0 unless grep ($_ eq $tplName, '00_master.nginx', '00_master_ssl.nginx');

    ${ $tplContent } = replaceBloc(
        "# SECTION custom BEGIN.\n",
        "# SECTION custom END.\n",
        "    # SECTION custom BEGIN.\n"
            . getBloc( "# SECTION custom BEGIN.\n", "# SECTION custom END.\n", ${ $tplContent } )
            . "    include imscp_monstaftp.conf;\n"
            . "    # SECTION custom END.\n",
        ${ $tplContent }
    );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::WebFtpClients::MonstaFTP::Installer

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self;
}

=item _installFiles( )

 Install MonstaFTP files in production directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    my $packageDir = "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp";

    unless ( -d $packageDir ) {
        error( "Couldn't find the imscp/monsta-ftp package in the $::imscpConfig{'GUI_ROOT_DIR'}/vendor directory" );
        return 1;
    }

    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/monstaftp" )->remove();
    iMSCP::Dir->new( dirname => "$packageDir/src" )->rcopy( "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/monstaftp", { preserve => 'no' } );
    iMSCP::Dir->new( dirname => "$packageDir/iMSCP/src" )->rcopy( "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/monstaftp", { preserve => 'no' } );
}

=item _buildHttpdConfig( )

 Build Httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
    my $frontEnd = Package::FrontEnd->getInstance();
    $frontEnd->buildConfFile(
        "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/monsta-ftp/iMSCP/nginx/imscp_monstaftp.conf",
        { GUI_PUBLIC_DIR => "$::imscpConfig{'GUI_ROOT_DIR'}/public" },
        { destination => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_monstaftp.conf" }
    );

    my $file = iMSCP::File->new( filename => "$frontEnd->{'config'}->{'HTTPD_CONF_DIR'}/imscp_monstaftp.conf" );
    return 1 unless defined( my $fileC = $file->getAsRef());

    ${ $fileC } =~ s/\bftp\b/monstaftp/g;

    $file->save();
}

=item _buildConfig( )

 Build configuration file

 Return int 0 on success, other on failure

=cut

sub _buildConfig
{
    my ( $self ) = @_;

    my $panelUName = my $panelGName = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    # config.php file

    my $conffile = "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/monstaftp/settings/config.php";
    my $data = {
        TIMEZONE => ::setupGetQuestion( 'TIMEZONE', 'UTC' ),
        TMP_PATH => "$::imscpConfig{'GUI_ROOT_DIR'}/data/tmp"
    };

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'monstaftp', 'config.php', \my $cfgTpl, $data );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $cfgTpl = iMSCP::File->new( filename => $conffile )->get();
        return 1 unless defined $cfgTpl;
    }

    $cfgTpl = process( $data, $cfgTpl );

    my $file = iMSCP::File->new( filename => $conffile );
    $file->set( $cfgTpl );
    $rs = $file->save();
    $rs ||= $file->owner( $panelUName, $panelGName );
    $rs ||= $file->mode( 0440 );
    return $rs if $rs;

    $conffile = "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/monstaftp/settings/settings.json";
    $data = {
        showDotFiles            => JSON::true,
        language                => 'en_us',
        editNewFilesImmediately => JSON::true,
        editableFileExtensions  => 'txt,htm,html,php,asp,aspx,js,css,xhtml,cfm,pl,py,c,cpp,rb,java,xml,json',
        hideProUpgradeMessages  => JSON::true,
        disableMasterLogin      => JSON::true,
        connectionRestrictions  => {
            types => [ 'ftp' ],
            ftp   => {
                host             => '127.0.0.1',
                port             => 21,
                # Enable passive mode excepted if the FTP daemon is vsftpd
                # vsftpd doesn't allows to operate on a per IP basic (IP masquerading)
                passive          => ( $::imscpConfig{'FTPD_SERVER'} eq 'vsftpd' ) ? JSON::false : JSON::true,
                ssl              => ::setupGetQuestion( 'SERVICES_SSL_ENABLED' ) eq 'yes' ? JSON::true : JSON::false,
                initialDirectory => '/' # Home directory as set for the FTP user
            }
        }
    };

    undef $cfgTpl;
    $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'monstaftp', 'settings.json', \$cfgTpl, $data );
    return $rs if $rs;

    $file = iMSCP::File->new( filename => $conffile );
    $file->set( $cfgTpl || JSON->new()->utf8( TRUE )->pretty( TRUE )->encode( $data ));
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
