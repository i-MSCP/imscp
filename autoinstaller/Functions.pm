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
use iMSCP::Bootstrapper;
use iMSCP::Config;
use iMSCP::Cwd;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::LsbRelease;
use iMSCP::Umask;
use iMSCP::Rights;
use version;
use parent 'Exporter';

our @EXPORT_OK = qw/ loadConfig build install /;

my $autoinstallerAdapterInstance;
my $eventManager;

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
    my $distroConffile = "$FindBin::Bin/configs/" . lc( $lsbRelease->getId( 1 )) . '/imscp.conf';
    my $defaultConffile = "$FindBin::Bin/configs/debian/imscp.conf";
    my $newConffile = ( -f $distroConffile ) ? $distroConffile : $defaultConffile;

    # Load new configuration
    tie %main::imscpConfig, 'iMSCP::Config', fileName => $newConffile, readonly => 1, temporary => 1;

    # Load old configuration
    if ( -f "$::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) { # Recovering following an installation or upgrade failure
        tie %main::imscpOldConfig,
            'iMSCP::Config', fileName => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf", readonly => 1, temporary => 1;
    } elsif ( -f "$::imscpConfig{'CONF_DIR'}/imscp.conf" ) { # Upgrade case
        tie %main::imscpOldConfig,
            'iMSCP::Config', fileName => "$::imscpConfig{'CONF_DIR'}/imscp.conf", readonly => 1, temporary => 1;
    } else { # Fresh installation case
        %main::imscpOldConfig = %main::imscpConfig;
    }

    if ( tied( %main::imscpOldConfig ) ) {
        debug( 'Merging old configuration with new configuration...' );
        # Merge old configuration in new configuration, excluding upstream defined values
        while ( my ($key, $value) = each( %main::imscpOldConfig ) ) {
            next unless exists $::imscpConfig{$key};
            next if $key =~ /^(?:Build|Version|CodeName|PluginApi|THEME_ASSETS_VERSION)$/;
            $::imscpConfig{$key} = $value;
        }

        # Make sure that all configuration parameter exists
        while ( my ($param, $value) = each( %main::imscpConfig ) ) {
            $::imscpOldConfig{$param} = $value unless exists $::imscpOldConfig{$param};
        }
    }

    # Set system based values
    $::imscpConfig{'DISTRO_ID'} = lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ));
    $::imscpConfig{'DISTRO_CODENAME'} = lc( iMSCP::LsbRelease->getInstance()->getCodename( 'short' ));
    $::imscpConfig{'DISTRO_RELEASE'} = iMSCP::LsbRelease->getInstance()->getRelease( 'short', 'force_numeric' );

    $eventManager = iMSCP::EventManager->getInstance();
    undef;
}

=item build( )

 Process build tasks

 Return int 0 on success, other on failure

=cut

sub build
{
    newDebug( 'imscp-build.log' );

    if ( !iMSCP::Getopt->preseed && !( $::imscpConfig{'FRONTEND_SERVER'} && $::imscpConfig{'FTPD_SERVER'}
        && $::imscpConfig{'HTTPD_SERVER'} && $::imscpConfig{'NAMED_SERVER'} && $::imscpConfig{'MTA_SERVER'}
        && $::imscpConfig{'PHP_SERVER'} && $::imscpConfig{'PO_SERVER'} && $::imscpConfig{'SQL_SERVER'} )
    ) {
        iMSCP::Getopt->noprompt( 0 );
        $::skippackages = 0;
    }

    my $rs = 0;
    $rs = _installPreRequiredPackages() unless $::skippackages;
    return $rs if $rs;

    my $dialog = iMSCP::Dialog->getInstance();

    unless ( iMSCP::Getopt->noprompt || $::reconfigure ne 'none' ) {
        $rs = _showWelcomeMsg( $dialog );
        return $rs if $rs;

        if ( %main::imscpOldConfig ) {
            $rs = _showUpdateWarning( $dialog );
            return $rs if $rs;
        }

        $rs = _confirmDistro( $dialog );
        return $rs if $rs;
    }

    $rs = _askInstallerMode( $dialog ) unless iMSCP::Getopt->noprompt || $::buildonly
        || $::reconfigure ne 'none';

    my @steps = (
        ( $::skippackages ? () : [ \&_installDistroPackages, 'Installing distribution packages' ] ),
        [ \&_checkRequirements, 'Checking for requirements' ],
        [ \&_buildDistributionFiles, 'Building distribution files' ],
        [ \&_compileDaemon, 'Compiling daemon' ],
        [ \&_removeObsoleteFiles, 'Removing obsolete files' ],
        [ \&_savePersistentData, 'Saving persistent data' ]
    );

    $rs ||= $eventManager->trigger( 'preBuild', \@steps );
    $rs ||= _getDistroAdapter()->preBuild( \@steps );
    return $rs if $rs;

    my ($step, $nbSteps) = ( 1, scalar @steps );
    for ( @steps ) {
        $rs = step( @{$_}, $nbSteps, $step );
        error( 'An error occurred while performing build steps' ) if $rs && $rs != 50;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    $rs = $eventManager->trigger( 'postBuild' );
    $rs ||= _getDistroAdapter()->postBuild();
    return $rs if $rs;

    undef $autoinstallerAdapterInstance;

    # Clean build directory (remove any .gitkeep file)
    find(
        sub {
            return unless $_ eq '.gitkeep';
            unlink or fatal( sprintf( "Couldn't remove %s file: %s", $File::Find::name, $! ));
        },
        $::{'INST_PREF'}
    );

    $rs = $eventManager->trigger( 'afterPostBuild' );
    return $rs if $rs;

    my %confmap = (
        imscp    => \ %main::imscpConfig,
        imscpOld => \ %main::imscpOldConfig
    );

    # Write configuration
    while ( my ($name, $config) = each %confmap ) {
        if ( $name eq 'imscpOld' ) {
            local $UMASK = 027;
            iMSCP::File->new( filename => "$::{'SYSTEM_CONF'}/$name.conf" )->save();
        }

        tie my %config, 'iMSCP::Config', fileName => "$::{'SYSTEM_CONF'}/$name.conf";
        @config{ keys %{$config} } = values %{$config};
        untie %config;
    }
    undef %confmap;

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
        require "$FindBin::Bin/engine/setup/imscp-setup-methods.pl";
    }

    # Not really the right place to do that job but we have not really choice because this must be done before
    # installation of new files
    my $serviceMngr = iMSCP::Service->getInstance();
    if ( $serviceMngr->hasService( 'imscp_network' ) ) {
        $serviceMngr->remove( 'imscp_network' );
    }

    my $bootstrapper = iMSCP::Bootstrapper->getInstance();
    my @runningJobs = ();

    for ( 'imscp-backup-all', 'imscp-backup-imscp', 'imscp-dsk-quota', 'imscp-srv-traff', 'imscp-vrl-traff',
        'awstats_updateall.pl', 'imscp-disable-accounts', 'imscp'
    ) {
        next if $bootstrapper->lock( "/var/lock/$_.lock", 'nowait' );
        push @runningJobs, $_,
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
        [ \&main::setupInstallFiles, 'Installing distribution files' ],
        [ \&main::setupBoot, 'Bootstrapping installer' ],
        [ \&main::setupRegisterListeners, 'Registering servers/packages event listeners' ],
        [ \&main::setupDialog, 'Processing setup dialog' ],
        [ \&main::setupTasks, 'Processing setup tasks' ],
        [ \&main::setupDeleteBuildDir, 'Deleting build directory' ]
    );

    my $rs = $eventManager->trigger( 'preInstall', \@steps );
    $rs ||= _getDistroAdapter()->preInstall( \@steps );
    return $rs if $rs;

    my $step = 1;
    my $nbSteps = scalar @steps;
    for ( @steps ) {
        $rs = step( @{$_}, $nbSteps, $step );
        error( 'An error occurred while performing installation steps' ) if $rs;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    $rs = $eventManager->trigger( 'postInstall' );
    $rs ||= _getDistroAdapter()->postInstall();
    return $rs if $rs;

    require Net::LibIDN;
    Net::LibIDN->import( 'idn_to_unicode' );

    my $port = $::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://'
        ? $::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
        : $::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};
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
    my ($dialog) = @_;

    $dialog->msgbox( <<"EOF" );

\\Zb\\Z4i-MSCP - internet Multi Server Control Panel
============================================\\Zn\\ZB

Welcome to the i-MSCP setup dialog.

i-MSCP (internet Multi Server Control Panel) is a software (OSS) easing shared hosting environments management on Linux servers. It comes with a large choice of modules for various services such as Apache2, ProFTPd, Dovecot, Courier, Bind9, and can be easily extended through plugins, or listener files using its events-based API.

i-MSCP was designed for professional Hosting Service Providers (HSPs), Internet Service Providers (ISPs) and IT professionals.

\\Zb\\Z4License\\Zn\\ZB

Unless otherwise stated all code is licensed under GPL 2.0 and has the following copyright:

        \\ZbCopyright 2010-2019, Laurent Declercq (i-MSCP)
        All rights reserved\\ZB
EOF
}

=item _showUpdateWarning( \%dialog )

 Show update warning

 Return 0 on success, other on failure or when user is aborting

=cut

sub _showUpdateWarning
{
    my ($dialog) = @_;

    my $warning = '';
    if ( $::imscpConfig{'Version'} !~ /git/i ) {
        $warning = <<"EOF";

Before continue, be sure to have read the errata file:

    \\Zbhttps://github.com/i-MSCP/imscp/blob/1.5.x/docs/1.5.x_errata.md\\ZB
EOF

    } else {
        $warning = <<"EOF";

The installer detected that you intends to install i-MSCP \\ZbGit\\ZB version.

We would remind you that the Git version can be highly unstable and that the i-MSCP team do not provides any support for it.

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
    my ($dialog) = @_;

    $dialog->infobox( "\nDetecting target distribution..." );

    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    my $distroID = $lsbRelease->getId( 'short' );
    my $distroCodename = ucfirst( $lsbRelease->getCodename( 'short' ));
    my $distroRelease = $lsbRelease->getRelease( 'short' );

    if ( $distroID ne 'n/a' && $distroCodename ne 'n/a' && $distroID =~ /^(?:de(?:bi|vu)an|ubuntu)$/i ) {
        unless ( -f "$FindBin::Bin/autoinstaller/Packages/" . lc( $distroID ) . '-' . lc( $distroCodename ) . '.xml' ) {
            $dialog->msgbox( <<"EOF" );

\\Z1$distroID $distroCodename ($distroRelease) not supported yet\\Zn

We are sorry but your $distroID version is not supported.

Thanks for choosing i-MSCP.
EOF

            return 50;
        }

        my $rs = $dialog->yesno( <<"EOF" );

$distroID $distroCodename ($distroRelease) has been detected. Is this ok?
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
    my ($dialog) = @_;

    $dialog->set( 'cancel-label', 'Abort' );

    my ($rs, $mode) = $dialog->radiolist( <<"EOF", [ 'auto', 'manual' ], 'auto' );

Please choose the installer mode:

See https://wiki.i-mscp.net/doku.php?id=start:installer#installer_modes for a full description of the installer modes.
 
EOF

    return 50 if $rs;

    $::buildonly = $mode eq 'manual' ? 1 : 0;
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

 Return undef if all requirements are met, throw a fatal error otherwise

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
    my $distroLayout = "$FindBin::Bin/autoinstaller/Layout/" . iMSCP::LsbRelease->getInstance()->getId( 'short' ) . '.xml';
    my $defaultLayout = "$FindBin::Bin/autoinstaller/Layout/Debian.xml";
    _processXmlFile( -f $distroLayout ? $distroLayout : $defaultLayout );
}

=item _buildConfigFiles( )

 Build configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConfigFiles
{
    my $distroConfigDir = "$FindBin::Bin/configs/" . lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ));
    my $defaultConfigDir = "$FindBin::Bin/configs/debian";
    my $confDir = -d $distroConfigDir ? $distroConfigDir : $defaultConfigDir;

    local $CWD = $confDir;
    my $file = -f "$distroConfigDir/install.xml" ? "$distroConfigDir/install.xml" : "$defaultConfigDir/install.xml";
    my $rs = _processXmlFile( $file );
    return $rs if $rs;

    for ( iMSCP::Dir->new( dirname => $defaultConfigDir )->getDirs() ) {
        # Override sub config dir path if it is available in selected distro, else set it to default path
        $confDir = -d "$distroConfigDir/$_" ? "$distroConfigDir/$_" : "$defaultConfigDir/$_";

        local $CWD = $confDir;

        $file = -f "$distroConfigDir/$_/install.xml"
            ? "$distroConfigDir/$_/install.xml" : "$defaultConfigDir/$_/install.xml";

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

    for ( iMSCP::Dir->new( dirname => "$FindBin::Bin/engine" )->getDirs() ) {
        next unless -f "$FindBin::Bin/engine/$_/install.xml";
        local $CWD = "$FindBin::Bin/engine/$_";
        $rs = _processXmlFile( "$FindBin::Bin/engine/$_/install.xml" );
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
    iMSCP::Dir->new( dirname => "$FindBin::Bin/gui" )->rcopy( "$::{'SYSTEM_ROOT'}/gui", { preserve => 'no' } );
    0;
}

=item _compileDaemon( )

 Compile daemon

 Return int 0 on success, other on failure

=cut

sub _compileDaemon
{
    local $CWD = "$FindBin::Bin/daemon";

    my $rs = execute( 'make clean imscp_daemon', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    iMSCP::Dir->new( dirname => "$::{'SYSTEM_ROOT'}/daemon" )->make();
    $rs = iMSCP::File->new( filename => 'imscp_daemon' )->copyFile(
        "$::{'SYSTEM_ROOT'}/daemon", { preserve => 'no' }
    );
    $rs ||= iMSCP::Rights::setRights(
        "$::{'SYSTEM_ROOT'}/daemon/imscp_daemon",
        {
            user  => $::imscpConfig{'ROOT_GROUP'},
            group => $::imscpConfig{'ROOT_GROUP'},
            mode  => '0750'
        }
    )
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
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/themes/user_logos" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/ispLogos", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/themes/user_logos";

    # Save ISP logos (new location)
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/ispLogos" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/ispLogos", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/ispLogos";

    # Save GUI logs
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/logs" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/logs", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/logs";

    # Save persistent data
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent";
    
    # Save vendor data
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/vendor", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/vendor";

    # Save GUI bin directory
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/bin" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/bin", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/bin";

    # Save software (older path ./gui/data/software) to new path (./gui/data/persistent/software)
    iMSCP::Dir->new( dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/softwares" )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/softwares", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/softwares";

    # Save plugins
    iMSCP::Dir->new( dirname => "$::imscpConfig{'PLUGINS_DIR'}" )->rcopy(
        "$destdir$::imscpConfig{'PLUGINS_DIR'}", { preserve => 'no' }
    ) if -d $::imscpConfig{'PLUGINS_DIR'};

    if ( -d "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package" ) {
        # Save package handlers (cover uninstallation case)
        for my $packageTypeDir ( iMSCP::Dir->new( dirname => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package" )->getDirs() ) {
            for my $packageDir ( iMSCP::Dir->new( dirname => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir" )->getDirs() ) {
                for my $handlerFile (
                    iMSCP::Dir->new(
                        dirname  => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir/$packageDir",
                        fileType => qr/^Handler\.pm/
                    )->getFiles()
                ) {
                    my $rs = iMSCP::File->new(
                        filename => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir/$packageDir/$handlerFile"
                    )->copyFile( "$destdir$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir/$packageDir/$handlerFile" );
                    return $rs if $rs;
                }
            }
        }
    }

    0;
}

=item _removeObsoleteFiles( )

 Removes obsolete files

 Return int 0 on success, other on failure

=cut

sub _removeObsoleteFiles
{
    for( "$::imscpConfig{'CACHE_DATA_DIR'}/addons",
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
        "$::imscpConfig{'LOG_DIR'}/imscp-arpl-msgr",
        '/var/local/imscp/.composer',
        '/var/local/imscp/packages',
        "$::imscpConfig{'CONF_DIR'}/pma"
    ) {
        iMSCP::Dir->new( dirname => $_ )->remove();
    }

    for("$::imscpConfig{'CONF_DIR'}/apache/parts/domain_disabled_ssl.tpl",
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
        '/usr/local/lib/imscp_panel/imscp_panel_checkconf', # Removed in 1.4.x,
        '/var/local/imscp/composer.phar'
    ) {
        next unless -f;
        my $rs = iMSCP::File->new( filename => $_ )->delFile();
        return $rs if $rs;
    }

    0;
}

=item _processXmlFile( $filepath )

 Process an install.xml file or distribution layout.xml file

 Param string $filepath xml file path
 Return int 0 on success, other on failure ; A fatal error is raised in case a variable cannot be exported

=cut

sub _processXmlFile
{
    my ($file) = @_;

    unless ( -f $file ) {
        error( sprintf( "File %s doesn't exists", $file ));
        return 1;
    }

    eval "use XML::Simple; 1";
    fatal( "Couldn't load the XML::Simple perl module" ) if $@;
    my $xml = XML::Simple->new( ForceArray => 1, ForceContent => 1 );
    my $data = eval { $xml->XMLin( $file, VarAttr => 'export' ) };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Permissions hardening
    local $UMASK = 027;

    # Process xml 'folders' nodes if any
    for ( @{$data->{'folders'}} ) {
        $_->{'content'} = _expandVars( $_->{'content'} );
        $::{$_->{'export'}} = $_->{'content'} if defined $_->{'export'};
        my $rs = _processFolder( $_ );
        return $rs if $rs;
    }

    # Process xml 'copy_config' nodes if any
    for ( @{$data->{'copy_config'}} ) {
        $_->{'content'} = _expandVars( $_->{'content'} );
        my $rs = _copyConfig( $_ );
        return $rs if $rs;
    }

    # Process xml 'copy' nodes if any
    for ( @{$data->{'copy'}} ) {
        $_->{'content'} = _expandVars( $_->{'content'} );
        my $rs = _copy( $_ );
        return $rs if $rs;
    }

    # Process xml 'create_file' nodes if any
    for ( @{$data->{'create_file'}} ) {
        $_->{'content'} = _expandVars( $_->{'content'} );
        my $rs = _createFile( $_ );
        return $rs if $rs;
    }

    # Process xml 'chmod_file' nodes if any
    for ( @{$data->{'chmod_file'}} ) {
        $_->{'content'} = _expandVars( $_->{'content'} );
        my $rs = _chmodFile( $_ ) if $_->{'content'};
        return $rs if $rs;
    }

    # Process xml 'chmod_file' nodes if any
    for ( @{$data->{'chown_file'}} ) {
        $_->{'content'} = _expandVars( $_->{'content'} );
        my $rs = _chownFile( $_ );
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
    my ($string) = @_;
    $string //= '';

    while ( my ($var) = $string =~ /\$\{([^\}]+)\}/g ) {
        if ( defined $::{$var} ) {
            $string =~ s/\$\{$var\}/$::{$var}/g;
        } elsif ( defined $::imscpConfig{$var} ) {
            $string =~ s/\$\{$var\}/$::imscpConfig{$var}/g;
        } else {
            fatal( "Couldn't expand variable \${$var}. Variable not found." );
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
    my ($data) = @_;

    my $dir = iMSCP::Dir->new( dirname => $data->{'content'} );

    # Needed to be sure to not keep any file from a previous build that has failed
    if ( defined $::{'INST_PREF'} && $::{'INST_PREF'} eq $data->{'content'} ) {
        $dir->remove();
    }

    $dir->make(
        {
            user  => defined $data->{'user'} ? _expandVars( $data->{'owner'} ) : undef,
            group => defined $data->{'group'} ? _expandVars( $data->{'group'} ) : undef,
            mode  => defined $data->{'mode'} ? oct( $data->{'mode'} ) : undef
        }
    );
}

=item _copyConfig( \%data )

 Process a copy_config node from an install.xml file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _copyConfig
{
    my ($data) = @_;

    if ( defined $data->{'if'} && !eval _expandVars( $data->{'if'} ) ) {
        return 0 if $data->{'kept'};
        ( my $syspath = $data->{'content'} ) =~ s/^$::{'INST_PREF'}//;
        return 0 unless $syspath ne '/' && -f $syspath;
        return iMSCP::File->new( filename => $syspath )->delFile();
    }

    my ($name, $path) = fileparse( $data->{'content'} );
    my $distribution = lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ));
    ( my $alternativeFolder = $CWD ) =~ s/$distribution/debian/;
    my $source = -f $name ? $name : "$alternativeFolder/$name";

    if ( -d $source ) {
        iMSCP::Dir->new( dirname => $source )->rcopy( "$path/$name", { preserve => 'no' } );
    } else {
        my $rs = iMSCP::File->new( filename => $source )->copyFile( $path, { preserve => 'no' } );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'user'} || defined $data->{'group'} || defined $data->{'mode'};

    my $file = iMSCP::File->new( filename => -e "$path/$name" ? "$path/$name" : $path );

    if ( defined $data->{'user'} || defined $data->{'group'} ) {
        my $rs = $file->owner(
            ( defined $data->{'user'} ? _expandVars( $data->{'user'} ) : -1 ),
            ( defined $data->{'group'} ? _expandVars( $data->{'group'} ) : -1 )
        );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'mode'};

    $file->mode( oct( $data->{'mode'} ));
}

=item _copy( \%data )

 Process the copy node from an install.xml file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _copy
{
    my ($data) = @_;

    my ($name, $path) = fileparse( $data->{'content'} );

    if ( -d $name ) {
        iMSCP::Dir->new( dirname => $name )->rcopy( "$path/$name", { preserve => 'no' } );
    } else {
        my $rs = iMSCP::File->new( filename => $name )->copyFile( $path, { preserve => 'no' } );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'user'} || defined $data->{'group'} || defined $data->{'mode'};

    my $file = iMSCP::File->new( filename => -e "$path/$name" ? "$path/$name" : $path );

    if ( defined $data->{'user'} || defined $data->{'group'} ) {
        my $rs = $file->owner(
            ( defined $data->{'user'} ? _expandVars( $data->{'user'} ) : -1 ),
            ( defined $data->{'group'} ? _expandVars( $data->{'group'} ) : -1 )
        );
        return $rs if $rs;
    }

    return 0 unless defined defined $data->{'mode'};

    $file->mode( oct( $data->{'mode'} )) if defined $data->{'mode'};
}

=item _createFile( \%data )

 Create a file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _createFile
{
    my ($data) = @_;

    iMSCP::File->new( filename => $data->{'content'} )->save();
}

=item _chownFile( )

 Change file/directory owner and/or group recursively

 Return int 0 on success, other on failure

=cut

sub _chownFile
{
    my ($data) = @_;

    return 0 unless defined $data->{'owner'} && defined $data->{'group'};

    my $rs = execute( "chown $data->{'owner'}:$data->{'group'} $data->{'content'}", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _chmodFile( \%data )

 Process chmod_file from an install.xml file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _chmodFile
{
    my ($data) = @_;

    return 0 unless defined $data->{'mode'};

    my $rs = execute( "chmod $data->{'mode'} $data->{'content'}", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _getDistroAdapter( )

 Return distro autoinstaller adapter instance

 Return autoinstaller::Adapter::Abstract

=cut

sub _getDistroAdapter
{
    return $autoinstallerAdapterInstance if defined $autoinstallerAdapterInstance;

    my $distribution = iMSCP::LsbRelease->getInstance()->getId( 'short' );

    eval {
        my $file = "$FindBin::Bin/autoinstaller/Adapter/${distribution}Adapter.pm";
        my $adapterClass = "autoinstaller::Adapter::${distribution}Adapter";

        require $file;
        $autoinstallerAdapterInstance = $adapterClass->new()
    };

    fatal( sprintf( "Couldn't instantiate %s autoinstaller adapter: %s", $distribution, $@ )) if $@;
    $autoinstallerAdapterInstance;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
