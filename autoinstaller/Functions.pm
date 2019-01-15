=head1 NAME

 autoinstaller::Functions - Functions for the i-MSCP autoinstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2019 by internet Multi Server Control Panel
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

package autoinstaller::Functions;

use strict;
use warnings;
use autouse 'iMSCP::Stepper' => qw/ step /;
use File::Basename;
use File::Find;
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Config;
use iMSCP::Cwd '$CWD';
use iMSCP::Debug qw/ debug error endDebug newDebug /;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::LsbRelease;
use iMSCP::Umask '$UMASK';
use iMSCP::Rights 'setRights';
use version;
use parent 'Exporter';

our @EXPORT_OK = qw/ loadConfig build install /;

my $ADAPTER;
my $EM;

=head1 DESCRIPTION

 Common functions for the i-MSCP installer

=head1 PUBLIC FUNCTIONS

=over 4

=item loadConfig( )

 Load main i-MSCP configuration

 Return undef

=cut

sub loadConfig
{
    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    my $distroConffile = "$FindBin::Bin/configs/" . lc( $lsbRelease->getId( TRUE )) . '/imscp.conf';
    my $defaultConffile = "$FindBin::Bin/configs/debian/imscp.conf";
    my $newConffile = -f $distroConffile ? $distroConffile : $defaultConffile;

    # Load new configuration
    tie %::imscpConfig, 'iMSCP::Config', fileName => $newConffile, readonly => TRUE, temporary => TRUE;

    # Load old configuration
    if ( -f "$::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) { # Recovering following an installation or upgrade failure
        tie %::imscpOldConfig, 'iMSCP::Config', fileName => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf", readonly => TRUE, temporary => TRUE;
    } elsif ( -f "$::imscpConfig{'CONF_DIR'}/imscp.conf" ) { # Upgrade case
        tie %::imscpOldConfig, 'iMSCP::Config', fileName => "$::imscpConfig{'CONF_DIR'}/imscp.conf", readonly => TRUE, temporary => TRUE;
    } else { # Fresh installation case
        %::imscpOldConfig = %::imscpConfig;
    }

    if ( tied( %::imscpOldConfig ) ) {
        debug( 'Merging old configuration with new configuration...' );
        # Merge old configuration in new configuration, excluding upstream defined values
        while ( my ( $key, $value ) = each( %::imscpOldConfig ) ) {
            next unless exists $::imscpConfig{$key};
            next if $key =~ /^(?:Build|Version|CodeName|PluginApi|THEME_ASSETS_VERSION)$/;
            $::imscpConfig{$key} = $value;
        }

        # Make sure that all configuration parameter exists
        while ( my ( $param, $value ) = each( %::imscpConfig ) ) {
            $::imscpOldConfig{$param} = $value unless exists $::imscpOldConfig{$param};
        }
    }

    # Set system based values
    $::imscpConfig{'DISTRO_ID'} = lc( iMSCP::LsbRelease->getInstance()->getId( TRUE ));
    $::imscpConfig{'DISTRO_CODENAME'} = lc( iMSCP::LsbRelease->getInstance()->getCodename( TRUE ));
    $::imscpConfig{'DISTRO_RELEASE'} = iMSCP::LsbRelease->getInstance()->getRelease( TRUE, TRUE );

    $EM = iMSCP::EventManager->getInstance();
    undef;
}

=item build( )

 Process build tasks

 Return int 0 on success, other on failure

=cut

sub build
{
    newDebug( 'imscp-build.log' );

    if ( !iMSCP::Getopt->preseed && !( $::imscpConfig{'FRONTEND_SERVER'} && $::imscpConfig{'FTPD_SERVER'} && $::imscpConfig{'HTTPD_SERVER'}
        && $::imscpConfig{'NAMED_SERVER'} && $::imscpConfig{'MTA_SERVER'} && $::imscpConfig{'PHP_SERVER'} && $::imscpConfig{'PO_SERVER'}
        && $::imscpConfig{'SQL_SERVER'} )
    ) {
        iMSCP::Getopt->noprompt( FALSE );
        $::skippackages = FALSE;
    }

    my $rs = 0;
    $rs = _installPreRequiredPackages() unless $::skippackages;
    return $rs if $rs;

    my $dialog = iMSCP::Dialog->getInstance();

    unless ( iMSCP::Getopt->noprompt || $::reconfigure ne 'none' ) {
        $rs = _showWelcomeMsg( $dialog );
        return $rs if $rs;

        if ( %::imscpOldConfig ) {
            $rs = _showUpdateWarning( $dialog );
            return $rs if $rs;
        }

        $rs = _confirmDistro( $dialog );
        return $rs if $rs;
    }

    $rs = _askInstallerMode( $dialog ) unless iMSCP::Getopt->noprompt || $::buildonly || $::reconfigure ne 'none';

    my @steps = (
        ( $::skippackages ? () : [ \&_installDistroPackages, 'Installing distribution packages' ] ),
        [ \&_checkRequirements, 'Checking for requirements' ],
        [ \&_buildDistributionFiles, 'Building distribution files' ],
        [ \&_compileDaemon, 'Compiling daemon' ],
        [ \&_removeObsoleteFiles, 'Removing obsolete files' ],
        [ \&_savePersistentData, 'Saving persistent data' ]
    );

    $rs ||= $EM->trigger( 'preBuild', \@steps );
    $rs ||= _getDistroAdapter()->preBuild( \@steps );
    return $rs if $rs;

    my ( $nStep, $countSteps ) = ( 1, scalar @steps );
    for my $step ( @steps ) {
        $rs = step( @{ $step }, $countSteps, $nStep );
        error( 'An error occurred while performing build steps' ) if $rs && $rs != 50;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    $rs = $EM->trigger( 'postBuild' );
    $rs ||= _getDistroAdapter()->postBuild();
    return $rs if $rs;

    undef ${ $ADAPTER };

    # Clean build directory (remove any .gitkeep file)
    find(
        sub {
            return unless $_ eq '.gitkeep';
            unlink or die( sprintf( "Couldn't remove %s file: %s", $File::Find::name, $! ));
        },
        $::{'INST_PREF'}
    );

    $rs = $EM->trigger( 'afterPostBuild' );
    return $rs if $rs;

    my %confMap = (
        imscp    => \%::imscpConfig,
        imscpOld => \%::imscpOldConfig
    );

    # Write configuration
    while ( my ( $name, $config ) = each %confMap ) {
        if ( $name eq 'imscpOld' ) {
            local $UMASK = 027;
            iMSCP::File->new( filename => "$::{'SYSTEM_CONF'}/$name.conf" )->save();
        }

        tie my %config, 'iMSCP::Config', fileName => "$::{'SYSTEM_CONF'}/$name.conf";
        @config{ keys %{ $config } } = values %{ $config };
        untie %config;
    }

    endDebug();
}

=item install( )

 Process install tasks

 Return int 0 on success, other otherwise

=cut

sub install
{
    newDebug( 'imscp-setup.log' );

    {
        package main;
        require "$FindBin::Bin/engine/imscp-setup-methods.pl";
    }

    # Not really the right place to do that job but we have not really choice because this must be done before
    # installation of new files
    my $serviceMngr = iMSCP::Service->getInstance();
    if ( $serviceMngr->hasService( 'imscp_network' ) ) {
        $serviceMngr->remove( 'imscp_network' );
    }

    my $bootstrapper = iMSCP::Bootstrapper->getInstance();
    my @runningJobs = ();

    for my $job ( 'imscp-clients-backup', 'imscp-cp-backup', 'imscp-disk-quota', 'imscp-server-traffic', 'imscp-clients-traffic',
        'awstats_updateall.pl', 'imscp-clients-suspend', 'imscp'
    ) {
        next if $bootstrapper->lock( "$job.lock", TRUE );
        push @runningJobs, $job,
    }

    if ( @runningJobs ) {
        iMSCP::Dialog->getInstance()->msgbox( <<"EOF" );

There are jobs currently running on your system that can not be locked by the installer.

You must wait until the end of these jobs.

Running jobs are: @runningJobs
EOF
        return 1;
    }

    undef @runningJobs;

    my @steps = (
        [ \&::setupInstallFiles, 'Installing distribution files' ],
        [ \&::setupBoot, 'Bootstrapping installer' ],
        [ \&::setupRegisterListeners, 'Registering servers/packages event listeners' ],
        [ \&::setupDialog, 'Processing setup dialog' ],
        [ \&::setupTasks, 'Processing setup tasks' ],
        [ \&::setupDeleteBuildDir, 'Deleting build directory' ]
    );

    my $rs = $EM->trigger( 'preInstall', \@steps );
    $rs ||= _getDistroAdapter()->preInstall( \@steps );
    return $rs if $rs;

    my ( $nStep, $countSteps ) = ( 1, scalar @steps );
    for my $step ( @steps ) {
        $rs = step( @{ $step }, $countSteps, $nStep );
        error( 'An error occurred while performing installation steps' ) if $rs;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    $rs = $EM->trigger( 'postInstall' );
    $rs ||= _getDistroAdapter()->postInstall();
    return $rs if $rs;

    require Net::LibIDN;
    Net::LibIDN->import( 'idn_to_unicode' );

    my $port = $::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://'
        ? $::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'} : $::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};
    my $vhost = idn_to_unicode( $::imscpConfig{'BASE_SERVER_VHOST'}, 'utf-8' );

    iMSCP::Dialog->getInstance()->infobox( <<"EOF" );

\\Z1Congratulations\\Zn

i-MSCP has been successfully installed/updated.

Please connect to $::imscpConfig{'BASE_SERVER_VHOST_PREFIX'}$vhost:$port and login with your administrator account.

Thank you for choosing i-MSCP.
EOF

    endDebug();
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _installPreRequiredPackages( )

 Trigger pre-required package installation tasks from distro autoinstaller adapter

 Return int 0 on success, other otherwise

=cut

sub _installPreRequiredPackages
{
    _getDistroAdapter()->installPreRequiredPackages();
}

=item _showWelcomeMsg( \%dialog )

 Show welcome message

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other otherwise

=cut

sub _showWelcomeMsg
{
    my ( $dialog ) = @_;

    $dialog->msgbox( <<"EOF" );

\\Zb\\Z4i-MSCP - internet Multi Server Control Panel
============================================\\Zn\\ZB

Welcome to the i-MSCP installer.

i-MSCP (internet Multi Server Control Panel) is an open source software (OSS) easing shared hosting environments management on Linux servers. It comes with a large choice of modules for various services such as Apache2, ProFTPd, Dovecot, Courier, Bind9, and can be easily extended through plugins, or listener files using its events-based API.

i-MSCP was designed for professional Hosting Service Providers (HSPs), Internet Service Providers (ISPs) and IT professionals.

\\Zb\\Z4License\\Zn\\ZB

Unless otherwise stated all source code and material is licensed under LGPL 2.1 and has the following copyright:

  \\Zb© 2010-@{[ (localtime)[5]+1900 ]}, Laurent Declercq (i-MSCP™)
  All rights reserved\\ZB

The design material and the "i-MSCP" trademark stay the property of their authors. Reuse of them without prior consent of their respective author is strictly prohibited.
EOF
}

=item _showUpdateWarning( \%dialog )

 Show update warning

 Return 0 on success, other on failure or when user is aborting

=cut

sub _showUpdateWarning
{
    my ( $dialog ) = @_;

    my $warning = '';
    if ( index( $::imscpConfig{'Version'}, 'Git' ) != -1 ) {
        $warning = <<"EOF";

Before continue, be sure to have read the errata file:

  \\Zbhttps://github.com/i-MSCP/imscp/blob/1.5.x/docs/1.5.x_errata.md\\ZB
EOF

    } else {
        $warning = <<"EOF";

The installer has detected that you intends to install an unreleased i-MSCP version.

We would remind you that the development versions can be highly unstable and that the i-MSCP team do not provides any support for them.

Before continue, be sure to have read the errata file:

  \\Zbhttps://github.com/i-MSCP/imscp/blob/1.5.x/docs/1.5.x_errata.md\\ZB
EOF
    }

    return 0 if $warning eq '';

    $dialog->set( 'yes-label', 'Continue' );
    $dialog->set( 'no-label', 'Abort' );
    return 50 if $dialog->yesno( <<"EOF", 'abort_by_default' );

\\Zb\\Z1WARNING - PLEASE READ CAREFULLY\\Zn\\ZB
$warning
You can now either continue or abort.
EOF

    $dialog->resetLabels();
    0;
}

=item _confirmDistro( \%dialog )

 Distribution confirmation dialog

 Param iMSCP::Dialog \%dialog
 Return 0 on success, other on failure on when user is aborting

=cut

sub _confirmDistro
{
    my ( $dialog ) = @_;

    $dialog->infobox( "\nDetecting target distribution..." );

    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    my $distID = $lsbRelease->getId( TRUE );
    my $distCodename = ucfirst( $lsbRelease->getCodename( TRUE ));
    my $distRelease = $lsbRelease->getRelease( TRUE );

    if ( $distID ne 'n/a' && $distCodename ne 'n/a' && $distID =~ /^(?:de(?:bi|vu)an|ubuntu)$/i ) {
        unless ( -f "$FindBin::Bin/autoinstaller/Packages/" . lc( $distID ) . '-' . lc( $distCodename ) . '.xml' ) {
            $dialog->msgbox( <<"EOF" );

\\Z1$distID $distCodename ($distRelease) not supported yet\\Zn

We are sorry but your $distID version is not supported.

Thanks for choosing i-MSCP.
EOF

            return 50;
        }

        my $rs = $dialog->yesno( <<"EOF" );

$distID $distCodename ($distRelease) has been detected. Is this ok?
EOF

        $dialog->msgbox( <<"EOF" ) if $rs;

\\Z1Distribution not supported\\Zn

We are sorry but the installer has failed to detect your distribution.

Only \\ZuDebian-like\\Zn operating systems are supported.

Thanks for choosing i-MSCP.
EOF

        return 50 if $rs;
    } else {
        $dialog->msgbox( <<"EOF" );

\\Z1Distribution not supported\\Zn

We are sorry but your distribution is not supported yet.

Only \\ZuDebian-like\\Zn operating systems are supported.

Thanks for choosing i-MSCP.
EOF

        return 50;
    }

    0;
}

=item _askInstallerMode( \%dialog )

 Asks for installer mode

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, 50 otherwise

=cut

sub _askInstallerMode
{
    my ( $dialog ) = @_;

    $dialog->set( 'cancel-label', 'Abort' );

    my ( $rs, $mode ) = $dialog->radiolist( <<"EOF", [ 'auto', 'manual' ], 'auto' );

Please choose the installer mode:

See https://wiki.i-mscp.net/doku.php?id=start:installer#installer_modes for a full description of the installer modes.
 
EOF

    return 50 if $rs;

    $::buildonly = $mode eq 'manual' ? TRUE : FALSE;
    $dialog->set( 'cancel-label', 'Back' );
    0;
}

=item _installDistroPackages( )

 Trigger packages installation/uninstallation tasks from distro autoinstaller adapter

 Return int 0 on success, other on failure

=cut

sub _installDistroPackages
{
    my $rs = _getDistroAdapter()->installPackages();
    $rs ||= _getDistroAdapter()->uninstallPackages();
}

=item _checkRequirements( )

 Check for requirements

 Return undef if all requirements are met, die otherwise

=cut

sub _checkRequirements
{
    iMSCP::Requirements->new()->all();
}

=item _buildDistributionFiles( )

 Build distribution files

 Return int 0 on success, other on failure

=cut

sub _buildDistributionFiles
{
    my $rs = _buildLayout();
    $rs ||= _buildConfigFiles();
    $rs ||= _buildEngineFiles();
    $rs ||= _buildFrontendFiles();
}

=item _buildLayout( )

 Build layout

 Return int 0 on success, other on failure

=cut

sub _buildLayout
{
    my $distroLayout = "$FindBin::Bin/autoinstaller/Layout/" . iMSCP::LsbRelease->getInstance()->getId( TRUE ) . '.xml';
    my $defaultLayout = "$FindBin::Bin/autoinstaller/Layout/Debian.xml";
    _processXmlFile( -f $distroLayout ? $distroLayout : $defaultLayout );
}

=item _buildConfigFiles( )

 Build configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConfigFiles
{
    my $distroConfigDir = "$FindBin::Bin/configs/" . lc( iMSCP::LsbRelease->getInstance()->getId( TRUE ));
    my $defaultConfigDir = "$FindBin::Bin/configs/debian";
    my $confDir = -d $distroConfigDir ? $distroConfigDir : $defaultConfigDir;

    local $CWD = $confDir;
    my $file = -f "$distroConfigDir/install.xml" ? "$distroConfigDir/install.xml" : "$defaultConfigDir/install.xml";
    my $rs = _processXmlFile( $file );
    return $rs if $rs;

    for my $dir ( iMSCP::Dir->new( dirname => $defaultConfigDir )->getDirs() ) {
        # Override sub config dir path if it is available in selected distro, else set it to default path
        $confDir = -d "$distroConfigDir/$dir" ? "$distroConfigDir/$dir" : "$defaultConfigDir/$dir";

        local $CWD = $confDir;
        $file = -f "$distroConfigDir/$dir/install.xml" ? "$distroConfigDir/$dir/install.xml" : "$defaultConfigDir/$dir/install.xml";
        next unless -f $file;
        $rs = _processXmlFile( $file );
        return $rs if $rs;
    }

    0;
}

=item _buildEngineFiles( )

 Build engine files

 Return int 0 on success, other on failure

=cut

sub _buildEngineFiles
{
    local $CWD = "$FindBin::Bin/engine";
    my $rs = _processXmlFile( "$FindBin::Bin/engine/install.xml" );
    return $rs if $rs;

    for my $dir ( iMSCP::Dir->new( dirname => "$FindBin::Bin/engine" )->getDirs() ) {
        next unless -f "$FindBin::Bin/engine/$dir/install.xml";
        local $CWD = "$FindBin::Bin/engine/$dir";
        $rs = _processXmlFile( "$FindBin::Bin/engine/$dir/install.xml" );
        return $rs if $rs;
    }

    0;
}

=item _buildFrontendFiles( )

 Build frontEnd files

 Return int 0 on success, other on failure

=cut

sub _buildFrontendFiles
{
    eval { iMSCP::Dir->new( dirname => "$FindBin::Bin/gui" )->rcopy( "$::{'SYSTEM_ROOT'}/gui", { preserve => 'no' } ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item _compileDaemon( )

 Compile daemon

 Return int 0 on success, other on failure

=cut

sub _compileDaemon
{

    local $CWD = "$FindBin::Bin/daemon";

    my $rs = execute( [ '/usr/bin/make', 'clean', 'imscp_daemon' ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    iMSCP::Dir->new( dirname => "$::{'SYSTEM_ROOT'}/daemon" )->make();

    $rs = iMSCP::File->new( filename => 'imscp_daemon' )->copyFile( "$::{'SYSTEM_ROOT'}/daemon", { preserve => 'no' } );
    $rs ||= iMSCP::Rights::setRights( "$::{'SYSTEM_ROOT'}/daemon/imscp_daemon", {
        user  => $::imscpConfig{'ROOT_GROUP'},
        group => $::imscpConfig{'ROOT_GROUP'},
        mode  => '0750'
    } );

}

=item _savePersistentData( )

 Save persistent data

 Return int 0 on success, other on failure

=cut

sub _savePersistentData
{
    my $destdir = $::{'INST_PREF'};

    # Move old skel directory to new location
    iMSCP::Dir->new( dirname => "$::imscpConfig{'CONF_DIR'}/apache/skel" )->rcopy(
        "$::imscpConfig{'CONF_DIR'}/skel", { preserve => 'no' }
    ) if -d "$::imscpConfig{'CONF_DIR'}/apache/skel";

    iMSCP::Dir->new( dirname => "$::imscpConfig{'CONF_DIR'}/skel" )->rcopy(
        "$destdir$::imscpConfig{'CONF_DIR'}/skel", { preserve => 'no' }
    ) if -d "$::imscpConfig{'CONF_DIR'}/skel";

    # Move old listener files to new location
    iMSCP::Dir->new( dirname => "$::imscpConfig{'CONF_DIR'}/hooks.d" )->rcopy(
        "$::imscpConfig{'CONF_DIR'}/listeners.d", { preserve => 'no' }
    ) if -d "$::imscpConfig{'CONF_DIR'}/hooks.d";

    # Save ISP logos (older location)
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos" )->rcopy(
        "$destdir$::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos", { preserve => 'no' }
    ) if -d "$::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos";

    # Save ISP logos (new location)
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos" )->rcopy(
        "$destdir$::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos", { preserve => 'no' }
    ) if -d "$::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos";

    # Save GUI logs
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/data/logs" )->rcopy(
        "$destdir$::imscpConfig{'ROOT_DIR'}/gui/data/logs", { preserve => 'no' }
    ) if -d "$::imscpConfig{'ROOT_DIR'}/gui/data/logs";

    # Save persistent data
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/data/persistent" )->rcopy(
        "$destdir$::imscpConfig{'ROOT_DIR'}/gui/data/persistent", { preserve => 'no' }
    ) if -d "$::imscpConfig{'ROOT_DIR'}/gui/data/persistent";

    # Save software (older path ./gui/data/softwares) to new path (./gui/data/persistent/softwares)
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/data/softwares" )->rcopy(
        "$destdir$::imscpConfig{'ROOT_DIR'}/gui/data/persistent/softwares", { preserve => 'no' }
    ) if -d "$::imscpConfig{'ROOT_DIR'}/gui/data/softwares";

    # Save plugins
    iMSCP::Dir->new( dirname => "$::imscpConfig{'PLUGINS_DIR'}" )->rcopy(
        "$destdir$::imscpConfig{'PLUGINS_DIR'}", { preserve => 'no' }
    ) if -d $::imscpConfig{'PLUGINS_DIR'};

    # Quick fix for #IP-1340 (Removes old filemanager directory which is no longer used)
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager" )->remove();

    # Save tools
    iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/gui/public/tools" )->rcopy(
        "$destdir$::imscpConfig{'ROOT_DIR'}/gui/public/tools", { preserve => 'no' }
    ) if -d "$::imscpConfig{'ROOT_DIR'}/gui/public/tools";

    0;
}

=item _removeObsoleteFiles( )

 Removes obsolete files

 Return int 0 on success, other on failure

=cut

sub _removeObsoleteFiles
{
    for my $dir ( "$::imscpConfig{'CACHE_DATA_DIR'}/addons",
        "$::imscpConfig{'CONF_DIR'}/apache/backup",
        "$::imscpConfig{'CONF_DIR'}/apache/skel/alias/phptmp",
        "$::imscpConfig{'CONF_DIR'}/apache/skel/subdomain/phptmp",
        "$::imscpConfig{'CONF_DIR'}/apache/working",
        "$::imscpConfig{'CONF_DIR'}/cron.d",
        "$::imscpConfig{'CONF_DIR'}/fcgi",
        "$::imscpConfig{'CONF_DIR'}/hooks.d",
        "$::imscpConfig{'CONF_DIR'}/init.d",
        "$::imscpConfig{'CONF_DIR'}/nginx",
        "$::imscpConfig{'CONF_DIR'}/php-fpm",
        "$::imscpConfig{'CONF_DIR'}/courier/backup",
        "$::imscpConfig{'CONF_DIR'}/courier/working",
        "$::imscpConfig{'CONF_DIR'}/postfix/backup",
        "$::imscpConfig{'CONF_DIR'}/postfix/imscp",
        "$::imscpConfig{'CONF_DIR'}/postfix/parts",
        "$::imscpConfig{'CONF_DIR'}/postfix/working",
        "$::imscpConfig{'CONF_DIR'}/skel/domain/domain_disable_page",
        "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/.composer",
        "$::imscpConfig{'LOG_DIR'}/imscp-arpl-msgr"
    ) {
        iMSCP::Dir->new( dirname => $dir )->remove();
    }

    for my $file ( "$::imscpConfig{'CONF_DIR'}/apache/parts/domain_disabled_ssl.tpl",
        "$::imscpConfig{'CONF_DIR'}/apache/parts/domain_redirect.tpl",
        "$::imscpConfig{'CONF_DIR'}/apache/parts/domain_redirect_ssl.tpl",
        "$::imscpConfig{'CONF_DIR'}/apache/parts/domain_ssl.tpl",
        "$::imscpConfig{'CONF_DIR'}/vsftpd/imscp_allow_writeable_root.patch",
        "$::imscpConfig{'CONF_DIR'}/vsftpd/imscp_pthread_cancel.patch",
        "$::imscpConfig{'CONF_DIR'}/apache/parts/php5.itk.ini",
        "$::imscpConfig{'CONF_DIR'}/dovecot/dovecot.conf.2.0",
        "$::imscpConfig{'CONF_DIR'}/dovecot/dovecot.conf.2.1",
        '/etc/default/imscp_panel',
        "$::imscpConfig{'CONF_DIR'}/frontend/00_master.conf",
        "$::imscpConfig{'CONF_DIR'}/frontend/00_master_ssl.conf",
        "$::imscpConfig{'CONF_DIR'}/frontend/imscp_fastcgi.conf",
        "$::imscpConfig{'CONF_DIR'}/frontend/imscp_php.conf",
        "$::imscpConfig{'CONF_DIR'}/frontend/nginx.conf",
        "$::imscpConfig{'CONF_DIR'}/frontend/php-fcgi-starter",
        "$::imscpConfig{'CONF_DIR'}/listeners.d/README",
        "$::imscpConfig{'CONF_DIR'}/skel/domain/.htgroup",
        "$::imscpConfig{'CONF_DIR'}/skel/domain/.htpasswd",
        "$::imscpConfig{'IMSCP_HOMEDIR'}/packages/composer.phar",
        '/usr/sbin/maillogconvert.pl',
        # Due to a mistake in previous i-MSCP versions (Upstart conffile copied into systemd confdir)
        "/etc/systemd/system/php5-fpm.override",
        "/etc/init/php5-fpm.override", # Removed in 1.4.x
        "$::imscpConfig{'CONF_DIR'}/imscp.old.conf",
        "/usr/local/lib/imscp_panel/imscp_panel_checkconf" # Removed in 1.4.x,

    ) {
        next unless -f $file;
        my $rs = iMSCP::File->new( filename => $file )->delFile();
        return $rs if $rs;
    }

    0;
}

=item _processXmlFile( $filepath )

 Process an install.xml file or distribution layout.xml file

 Param string $filepath xml file path
 Return int 0 on success, other on failure, die when a variable cannot be exported

=cut

sub _processXmlFile
{
    my ( $file ) = @_;

    unless ( -f $file ) {
        error( sprintf( "File %s doesn't exist", $file ));
        return 1;
    }

    # Permissions hardening
    local $UMASK = 027;

    require XML::Simple;
    my $xml = XML::Simple->new( ForceArray => TRUE, ForceContent => TRUE );
    my $data = $xml->XMLin( $file, VarAttr => 'export' );

    # Process xml 'folder' nodes if any
    for my $node ( @{ $data->{'folder'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        $::{$node->{'export'}} = $node->{'content'} if defined $node->{'export'};
        my $rs = _processFolder( $node );
        return $rs if $rs;
    }

    # Process xml 'copy_config' nodes if any
    for my $node ( @{ $data->{'copy_config'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        my $rs = _copyConfig( $node );
        return $rs if $rs;
    }

    # Process xml 'copy' nodes if any
    for my $node ( @{ $data->{'copy'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        my $rs = _copy( $node );
        return $rs if $rs;
    }

    0;
}

=item _expandVars( $string )

 Expand variables in the given string

 Param string $string string containing variables to expands
 Return string

=cut

sub _expandVars
{
    my ( $string ) = @_;
    $string //= '';

    while ( my ( $var ) = $string =~ /\$\{([^\}]+)\}/g ) {
        if ( defined $::{$var} ) {
            $string =~ s/\$\{$var\}/$::{$var}/g;
        } elsif ( defined $::imscpConfig{$var} ) {
            $string =~ s/\$\{$var\}/$::imscpConfig{$var}/g;
        } else {
            die( "Couldn't expand variable \${$var}. Variable not found." );
        }
    }

    $string;
}

=item _processFolder( \%data )

 Process a folder node from an install.xml file

 Process the xml folder node by creating the described directory.

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _processFolder
{
    my ( $data ) = @_;

    my $dir = iMSCP::Dir->new( dirname => $data->{'content'} );
    # Needed to be sure to not keep any file from a previous build that has failed
    $dir->remove() if defined $::{'INST_PREF'} && $::{'INST_PREF'} eq $data->{'content'};
    $dir->make( {
        user  => defined $data->{'user'} ? _expandVars( $data->{'owner'} ) : undef,
        group => defined $data->{'group'} ? _expandVars( $data->{'group'} ) : undef,
        mode  => defined $data->{'mode'} ? oct( $data->{'mode'} ) : undef
    } );
}

=item _copyConfig( \%data )

 Process a copy_config node from an install.xml file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _copyConfig
{
    my ( $data ) = @_;

    if ( defined $data->{'if'} && !eval _expandVars( $data->{'if'} ) ) {
        return 0 if $data->{'kept'};
        ( my $sysPath = $data->{'content'} ) =~ s/^$::{'INST_PREF'}//;
        return 0 unless $sysPath ne '/' && -f $sysPath;
        return iMSCP::File->new( filename => $sysPath )->delFile();
    }

    my ( $name, $path ) = fileparse( $data->{'content'} );
    my $distribution = lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ));
    ( my $alternativeFolder = $CWD ) =~ s/$distribution/debian/;
    my $source = -f $name ? $name : "$alternativeFolder/$name";

    if ( -d $source ) {
        iMSCP::Dir->new( dirname => $source )->rcopy( "$path/$name", { preserve => 'no' } );
    } else {
        my $rs = iMSCP::File->new( filename => $source )->copyFile( $path, { preserve => 'no' } );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'user'} || defined $data->{'group'} || defined $data->{'mode'} || defined $data->{'filemode'}
        || defined $data->{'dirmode'};

    setRights( "$path/$name" ? "$path/$name" : $path, {
        user      => defined $data->{'user'} ? _expandVars( $data->{'user'} ) : undef,
        group     => defined $data->{'group'} ? _expandVars( $data->{'group'} ) : undef,
        mode      => $data->{'mode'},
        filemode  => $data->{'filemode'},
        dirmode   => $data->{'dirmode'},
        recursive => $data->{'recursive'}
    } );
}

=item _copy( \%data )

 Process the copy node from an install.xml file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _copy
{
    my ( $data ) = @_;

    my ( $name, $path ) = fileparse( $data->{'content'} );

    if ( -d $name ) {
        iMSCP::Dir->new( dirname => $name )->rcopy( "$path/$name", { preserve => 'no' } );
    } else {
        my $rs = iMSCP::File->new( filename => $name )->copyFile( $path, { preserve => 'no' } );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'user'} || defined $data->{'group'} || defined $data->{'mode'} || defined $data->{'filemode'}
        || defined $data->{'dirmode'};

    setRights( "$path/$name" ? "$path/$name" : $path, {
        user      => defined $data->{'user'} ? _expandVars( $data->{'user'} ) : undef,
        group     => defined $data->{'group'} ? _expandVars( $data->{'group'} ) : undef,
        mode      => $data->{'mode'},
        filemode  => $data->{'filemode'},
        dirmode   => $data->{'dirmode'},
        recursive => $data->{'recursive'}
    } );
}

=item _getDistroAdapter( )

 Return distro autoinstaller adapter instance

 Return autoinstaller::Adapter::Abstract

=cut

sub _getDistroAdapter
{
    return ${ $ADAPTER } if defined ${ $ADAPTER };

    my $distribution = iMSCP::LsbRelease->getInstance()->getId( 'short' );

    eval {
        my $file = "$FindBin::Bin/autoinstaller/Adapter/${distribution}Adapter.pm";
        my $adapterClass = "autoinstaller::Adapter::${distribution}Adapter";
        require $file;
        ${ $ADAPTER } = $adapterClass->new()
    };

    die( sprintf( "Couldn't instantiate %s autoinstaller adapter: %s", $distribution, $@ )) if $@;
    ${ $ADAPTER };
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
