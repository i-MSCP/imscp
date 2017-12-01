=head1 NAME

 autoinstaller::Functions - Functions for the i-MSCP autoinstaller

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

package autoinstaller::Functions;

use strict;
use warnings;
use autouse 'iMSCP::Stepper' => qw/ step /;
use File::Basename;
use File::Find qw/ find /;
use iMSCP::Bootstrapper;
use iMSCP::Config;
use iMSCP::Cwd;
use iMSCP::Debug qw/ debug error newDebug endDebug /;
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

 Return void

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
    if ( -f "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) { # Recovering following an installation or upgrade failure
        tie %main::imscpOldConfig,
            'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf", readonly => 1, temporary => 1;
    } elsif ( -f "$main::imscpConfig{'CONF_DIR'}/imscp.conf" ) { # Upgrade case
        tie %main::imscpOldConfig,
            'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/imscp.conf", readonly => 1, temporary => 1;
    } else { # Frech installation case
        %main::imscpOldConfig = %main::imscpConfig;
    }

    if ( tied( %main::imscpOldConfig ) ) {
        debug( 'Merging old configuration with new configuration...' );
        # Merge old configuration in new configuration, excluding upstream defined values
        while ( my ($key, $value) = each( %main::imscpOldConfig ) ) {
            next unless exists $main::imscpConfig{$key};
            next if $key =~ /^(?:BuildDate|Version|CodeName|PluginApi|THEME_ASSETS_VERSION)$/;
            $main::imscpConfig{$key} = $value;
        }

        # Make sure that all configuration parameter exists
        while ( my ($param, $value) = each( %main::imscpConfig ) ) {
            $main::imscpOldConfig{$param} = $value unless exists $main::imscpOldConfig{$param};
        }
    }

    # Set system based values
    $main::imscpConfig{'DISTRO_ID'} = lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ));
    $main::imscpConfig{'DISTRO_CODENAME'} = lc( iMSCP::LsbRelease->getInstance()->getCodename( 'short' ));
    $main::imscpConfig{'DISTRO_RELEASE'} = iMSCP::LsbRelease->getInstance()->getRelease( 'short', 'force_numeric' );

    $eventManager = iMSCP::EventManager->getInstance();
}

=item build( )

 Process build tasks

 Return int 0 on success, other on failure

=cut

sub build
{
    newDebug( 'imscp-build.log' );

    if ( $main::imscpConfig{'FRONTEND_SERVER'} eq '' || $main::imscpConfig{'FTPD_SERVER'} eq ''
        || $main::imscpConfig{'HTTPD_SERVER'} eq '' || $main::imscpConfig{'NAMED_SERVER'} eq ''
        || $main::imscpConfig{'MTA_SERVER'} eq '' || $main::imscpConfig{'PHP_SERVER'} eq ''
        || $main::imscpConfig{'PO_SERVER'} eq '' || $main::imscpConfig{'SQL_SERVER'} eq ''
        || $main::imscpConfig{'FRONTEND_PACKAGE'} eq '' || $main::imscpConfig{'FTPD_PACKAGE'} eq ''
        || $main::imscpConfig{'HTTPD_PACKAGE'} eq '' || $main::imscpConfig{'NAMED_PACKAGE'} eq ''
        || $main::imscpConfig{'MTA_PACKAGE'} eq '' || $main::imscpConfig{'PHP_PACKAGE'} eq ''
        || $main::imscpConfig{'PO_PACKAGE'} eq '' || $main::imscpConfig{'SQL_PACKAGE'} eq ''
    ) {
        iMSCP::Getopt->noprompt( 0 ) unless iMSCP::Getopt->preseed;
        $main::skippackages = 0;
    }

    my $rs = 0;
    $rs = _installPreRequiredPackages() unless $main::skippackages;
    return $rs if $rs;

    my $dialog = iMSCP::Dialog->getInstance();

    unless ( iMSCP::Getopt->noprompt || $main::reconfigure ne 'none' ) {
        $rs = _showWelcomeMsg( $dialog );
        return $rs if $rs;

        if ( %main::imscpOldConfig ) {
            $rs = _showUpdateWarning( $dialog );
            return $rs if $rs;
        }

        $rs = _confirmDistro( $dialog );
        return $rs if $rs;
    }

    $rs = _askInstallerMode( $dialog ) unless iMSCP::Getopt->noprompt || $main::buildonly
        || $main::reconfigure ne 'none';

    my @steps = (
        [ \&_buildDistributionFiles, 'Building distribution files' ],
        ( $main::skippackages ? () : [ \&_installDistributionPackages, 'Installing distribution packages' ] ),
        [ \&_checkRequirements, 'Checking for requirements' ],
        [ \&_compileDaemon, 'Compiling daemon' ],
        [ \&_removeObsoleteFiles, 'Removing obsolete files' ],
        [ \&_savePersistentData, 'Saving persistent data' ]
    );

    $rs ||= $eventManager->trigger( 'preBuild', \@steps );
    $rs ||= _getDistributionAdapter()->preBuild( \@steps );
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
    $rs ||= _getDistributionAdapter()->postBuild();
    return $rs if $rs;

    undef $autoinstallerAdapterInstance;

    # Clean build directory (remove any .gitkeep file)
    find(
        sub {
            return unless $_ eq '.gitkeep';
            unlink or die( sprintf( "Couldn't remove %s file: %s", $File::Find::name, $! ));
        },
        $main::{'INST_PREF'}
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
            iMSCP::File->new( filename => "$main::{'SYSTEM_CONF'}/$name.conf" )->save();
        }

        tie my %config, 'iMSCP::Config', fileName => "$main::{'SYSTEM_CONF'}/$name.conf";
        @config{ keys %{$config} } = values %{$config};
        untie %config;
    }

    endDebug();
    0;
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
    $rs ||= _getDistributionAdapter()->preInstall( \@steps );
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
    $rs ||= _getDistributionAdapter()->postInstall();
    return $rs if $rs;

    require Net::LibIDN;
    Net::LibIDN->import( 'idn_to_unicode' );

    my $port = $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://'
        ? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
        : $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};
    my $vhost = idn_to_unicode( $main::imscpConfig{'BASE_SERVER_VHOST'}, 'utf-8' ) // '';

    iMSCP::Dialog->getInstance()->infobox( <<"EOF" );

\\Z1Congratulations\\Zn

i-MSCP has been successfully installed/updated.

Please connect to $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'}$vhost:$port and login with your administrator account.

Thank you for choosing i-MSCP.
EOF

    endDebug();
    0;
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
    _getDistributionAdapter()->installPreRequiredPackages();
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
============================================\\Zn

Welcome to the i-MSCP setup dialog.

i-MSCP (internet Multi Server Control Panel) is a software (OSS) easing shared hosting environments management on Linux servers. It comes with a large choice of modules for various services such as Apache2, ProFTPd, Dovecot, Courier, Bind9, and can be easily extended through plugins, or listener files using its events-based API.

i-MSCP was designed for professional Hosting Service Providers (HSPs), Internet Service Providers (ISPs) and IT professionals.

\\Zb\\Z4License\\Zn

Unless otherwise stated all code is licensed under GPL 2.0 and has the following copyright:

\\ZbCopyright 2010-2017, Laurent Declercq (i-MSCP)
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
    if ( $main::imscpConfig{'Version'} !~ /git/i ) {
        $warning = <<"EOF";

Before you continue, be sure to have read the errata file which is located at

    \\Zbhttps://github.com/i-MSCP/imscp/blob/1.5.x/docs/1.5.x_errata.md\\ZB
EOF

    } else {
        $warning = <<"EOF";

The installer detected that you intend to install an i-MSCP development version.

We would remind you that development versions can be highly unstable and that they are not supported by the i-MSCP team.

Before you continue, be sure to have read the errata file:

    \\Zbhttps://github.com/i-MSCP/imscp/blob/1.5.x/docs/1.5.x_errata.md\\ZB
EOF
    }

    return 0 if $warning eq '';

    $dialog->set( 'yes-label', 'Continue' );
    $dialog->set( 'no-label', 'Abort' );
    return 50 if $dialog->yesno( <<"EOF", 'abort_by_default' );

\\Zb\\Z1WARNING \\Z0PLEASE READ CAREFULLY \\Z1WARNING\\Zn
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

    if ( $main::imscpConfig{'DISTRO_ID'} =~ /^(?:de(?:bi|vu)an|ubuntu)$/
        && $main::imscpConfig{'DISTRO_RELEASE'} ne 'n/a'
        && $main::imscpConfig{'DISTRO_CODENAME'} ne 'n/a'
    ) {
        my $packagesFile = "$main::imscpConfig{'DISTRO_ID'}-$main::imscpConfig{'DISTRO_CODENAME'}.xml";

        unless ( -f "$FindBin::Bin/autoinstaller/Packages/$packagesFile" ) {
            $dialog->msgbox( <<"EOF" );

\\Z1@{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ]}  $main::imscpConfig{'DISTRO_RELEASE'}/@{[ $main::imscpConfig{'DISTRO_CODENAME'} =~ s/\b(\w)/\u$1/gr ]} not supported yet\\Zn

We are sorry but no packages file has been found for your @{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ]} version.

This either means that your distribution is far too recent or that support for it has been dropped.

Thanks for choosing i-MSCP.
EOF

            return 50;
        }

        my $rs = $dialog->yesno( <<"EOF" );

@{[ ucfirst $main::imscpConfig{'DISTRO_ID'} ]} $main::imscpConfig{'DISTRO_RELEASE'}/@{[ $main::imscpConfig{'DISTRO_CODENAME'} =~ s/\b(\w)/\u$1/gr ]} has been detected. Is this ok?
EOF

        $dialog->msgbox( <<"EOF" ) if $rs;

\\Z1Distribution not supported\\Zn

We are sorry but the installer has failed to detect your distribution.

Please report the problem to i-MSCP team.

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

    $main::buildonly = $mode eq 'manual' ? 1 : 0;
    $dialog->set( 'cancel-label', 'Back' );
    0;
}

=item _installDistributionPackages( )

 Install distribution packages

 Return int 0 on success, other on failure

=cut

sub _installDistributionPackages
{
    _getDistributionAdapter()->installPackages();
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
    my $rs = _buildConfigFiles();
    $rs ||= _buildEngineFiles();
    $rs ||= _buildFrontendFiles();
}

=item _buildConfigFiles( )

 Build configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConfigFiles
{
    my $masterConfDir = "$FindBin::Bin/configs/debian";
    my $distConfdir = $main::imscpConfig{'DISTRO_ID'} ne 'debian' &&
            -d "$FindBin::Bin/configs/$main::imscpConfig{'DISTRO_ID'}"
        ? "$FindBin::Bin/configs/$main::imscpConfig{'DISTRO_ID'}" : $masterConfDir;

    my $installFilePath = $distConfdir ne $masterConfDir && -f "$distConfdir/install.xml"
        ? "$distConfdir/install.xml" : "$masterConfDir/install.xml";

    my $rs = _processXmlInstallFile( $installFilePath );
    return $rs if $rs;

    for ( iMSCP::Dir->new( dirname => $masterConfDir )->getDirs() ) {
        $installFilePath = $distConfdir ne $masterConfDir && -f "$distConfdir/$_/install.xml"
            ? "$distConfdir/$_/install.xml" : "$masterConfDir/$_/install.xml";

        next unless -f $installFilePath;

        $rs = _processXmlInstallFile( $installFilePath );
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
    my $rs = _processXmlInstallFile( "$FindBin::Bin/engine/install.xml" );
    return $rs if $rs;

    for ( iMSCP::Dir->new( dirname => "$FindBin::Bin/engine" )->getDirs() ) {
        next unless -f "$FindBin::Bin/engine/$_/install.xml";
        $rs = _processXmlInstallFile( "$FindBin::Bin/engine/$_/install.xml" );
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
    iMSCP::Dir->new( dirname => "$FindBin::Bin/gui" )->rcopy( "$main::{'SYSTEM_ROOT'}/gui", { preserve => 'no' } );
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

    iMSCP::Dir->new( dirname => "$main::{'SYSTEM_ROOT'}/daemon" )->make();
    $rs = iMSCP::File->new( filename => 'imscp_daemon' )->copyFile(
        "$main::{'SYSTEM_ROOT'}/daemon", { preserve => 'no' }
    );
    $rs ||= iMSCP::Rights::setRights(
        "$main::{'SYSTEM_ROOT'}/daemon/imscp_daemon",
        {
            user  => $main::imscpConfig{'ROOT_GROUP'},
            group => $main::imscpConfig{'ROOT_GROUP'},
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
    my $destdir = $main::{'INST_PREF'};

    # Move old skel directory to new location
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/apache/skel" )->rcopy(
        "$main::imscpConfig{'CONF_DIR'}/skel", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'CONF_DIR'}/apache/skel";

    iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/skel" )->rcopy(
        "$destdir$main::imscpConfig{'CONF_DIR'}/skel", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'CONF_DIR'}/skel";

    # Move old listener files to new location
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/hooks.d" )->rcopy(
        "$main::imscpConfig{'CONF_DIR'}/listeners.d", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'CONF_DIR'}/hooks.d";

    # Save ISP logos (older location)
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos" )->rcopy(
        "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos";

    # Save ISP logos (new location)
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos" )->rcopy(
        "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos";

    # Save GUI logs
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/logs" )->rcopy(
        "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/logs", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'ROOT_DIR'}/gui/data/logs";

    # Save persistent data
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent" )->rcopy(
        "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent";

    # Save software (older path ./gui/data/softwares) to new path (./gui/data/persistent/softwares)
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/data/softwares" )->rcopy(
        "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/softwares", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'ROOT_DIR'}/gui/data/softwares";

    # Save plugins
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'PLUGINS_DIR'}" )->rcopy(
        "$destdir$main::imscpConfig{'PLUGINS_DIR'}", { preserve => 'no' }
    ) if -d $main::imscpConfig{'PLUGINS_DIR'};

    # Quick fix for #IP-1340 (Removes old filemanager directory which is no longer used)
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager" )->remove();

    # Save tools
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools" )->rcopy(
        "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/public/tools", { preserve => 'no' }
    ) if -d "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools";

    0;
}

=item _removeObsoleteFiles( )

 Removes obsolete files

 Return int 0 on success, other on failure

=cut

sub _removeObsoleteFiles
{
    for( "$main::imscpConfig{'CACHE_DATA_DIR'}/addons",
        "$main::imscpConfig{'CONF_DIR'}/apache/backup",
        "$main::imscpConfig{'CONF_DIR'}/apache/skel/alias/phptmp",
        "$main::imscpConfig{'CONF_DIR'}/apache/skel/subdomain/phptmp",
        "$main::imscpConfig{'CONF_DIR'}/apache/working",
        "$main::imscpConfig{'CONF_DIR'}/courier/backup",
        "$main::imscpConfig{'CONF_DIR'}/courier/working",
        "$main::imscpConfig{'CONF_DIR'}/cron.d",
        "$main::imscpConfig{'CONF_DIR'}/fcgi",
        "$main::imscpConfig{'CONF_DIR'}/hooks.d",
        "$main::imscpConfig{'CONF_DIR'}/init.d",
        "$main::imscpConfig{'CONF_DIR'}/nginx",
        "$main::imscpConfig{'CONF_DIR'}/php-fpm",
        "$main::imscpConfig{'CONF_DIR'}/postfix/backup",
        "$main::imscpConfig{'CONF_DIR'}/postfix/imscp",
        "$main::imscpConfig{'CONF_DIR'}/postfix/parts",
        "$main::imscpConfig{'CONF_DIR'}/postfix/working",
        "$main::imscpConfig{'CONF_DIR'}/skel/domain/domain_disable_page",
        "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/.composer",
        "$main::imscpConfig{'LOG_DIR'}/imscp-arpl-msgr"
    ) {
        iMSCP::Dir->new( dirname => $_ )->remove();
    }

    for( "$main::imscpConfig{'CONF_DIR'}/apache/parts/domain_disabled_ssl.tpl",
        "$main::imscpConfig{'CONF_DIR'}/apache/parts/domain_redirect.tpl",
        "$main::imscpConfig{'CONF_DIR'}/apache/parts/domain_redirect_ssl.tpl",
        "$main::imscpConfig{'CONF_DIR'}/apache/parts/domain_ssl.tpl",
        "$main::imscpConfig{'CONF_DIR'}/vsftpd/imscp_allow_writeable_root.patch",
        "$main::imscpConfig{'CONF_DIR'}/vsftpd/imscp_pthread_cancel.patch",
        "$main::imscpConfig{'CONF_DIR'}/apache/parts/php5.itk.ini",
        "$main::imscpConfig{'CONF_DIR'}/dovecot/dovecot.conf.2.0",
        "$main::imscpConfig{'CONF_DIR'}/dovecot/dovecot.conf.2.1",
        "$main::imscpConfig{'CONF_DIR'}/frontend/00_master.conf",
        "$main::imscpConfig{'CONF_DIR'}/frontend/00_master_ssl.conf",
        "$main::imscpConfig{'CONF_DIR'}/frontend/imscp_fastcgi.conf",
        "$main::imscpConfig{'CONF_DIR'}/frontend/imscp_php.conf",
        "$main::imscpConfig{'CONF_DIR'}/frontend/nginx.conf",
        "$main::imscpConfig{'CONF_DIR'}/frontend/php-fcgi-starter",
        "$main::imscpConfig{'CONF_DIR'}/listeners.d/README",
        "$main::imscpConfig{'CONF_DIR'}/php/fpm/logrotate.tpl",
        "$main::imscpConfig{'CONF_DIR'}/skel/domain/.htgroup",
        "$main::imscpConfig{'CONF_DIR'}/skel/domain/.htpasswd",
        "$main::imscpConfig{'IMSCP_HOMEDIR'}/composer.phar",
        "$main::imscpConfig{'IMSCP_HOMEDIR'}/packages/composer.phar",
        "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf",
        '/etc/default/imscp_panel',
        '/etc/init/php5-fpm.override',
        '/etc/logrotate.d/imscp',
        '/etc/systemd/system/php5-fpm.override',
        '/usr/local/lib/imscp_panel/imscp_panel_checkconf',
        '/usr/sbin/maillogconvert.pl'
    ) {
        next unless -l || -f _;
        my $rs = iMSCP::File->new( filename => $_ )->delFile();
        return $rs if $rs;
    }

    0;
}

=item _processXmlInstallFile( $installFilePath )

 Process an install.xml file or distribution layout.xml file

 Param string $installFilePath XML installation file path
 Return int 0 on success, other on failure

=cut

sub _processXmlInstallFile
{
    my ($installFilePath) = @_;

    eval "use XML::Simple; 1";
    die( sprintf( "Couldn't load the XML::Simple perl module: %s", $@ ) ) if $@;
    my $xml = XML::Simple->new( ForceArray => 1, ForceContent => 1 );
    my $node = eval { $xml->XMLin( $installFilePath, VarAttr => 'export' ) };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Jump in parent directory
    local $CWD = dirname( $installFilePath );

    # Permissions hardening
    local $UMASK = 027;

    # Process 'folder' nodes if any
    if ( $node->{'folder'} ) {
        for ( @{$node->{'folder'}} ) {
            $_->{'content'} = _expandVars( $_->{'content'} );
            $main::{$_->{'export'}} = $_->{'content'} if defined $_->{'export'};
            my $rs = _processFolderNode( $_ );
            return $rs if $rs;
        }
    }

    # Process 'copy_config' nodes if any
    if ( $node->{'copy_config'} ) {
        for ( @{$node->{'copy_config'}} ) {
            $_->{'content'} = _expandVars( $_->{'content'} );
            my $rs = _processCopyConfigNode( $_ );
            return $rs if $rs;
        }
    }

    # Process 'copy' nodes if any
    if ( $node->{'copy'} ) {
        for ( @{$node->{'copy'}} ) {
            $_->{'content'} = _expandVars( $_->{'content'} );
            my $rs = _processCopyNode( $_ );
            return $rs if $rs;
        }
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
        if ( defined $main::{$var} ) {
            $string =~ s/\$\{$var\}/$main::{$var}/g;
        } elsif ( defined $main::imscpConfig{$var} ) {
            $string =~ s/\$\{$var\}/$main::imscpConfig{$var}/g;
        } else {
            fatal( "Couldn't expand variable \${$var}. Variable not found." );
        }
    }

    $string;
}

=item _processFolderNode( \%node )

 Process the given folder node

 Node attributes:
  create_if: Create the folder only if the condition is met
  user: Target directory owner
  group: Target directory group
  mode: Target directory mode

 Param hashref \%node Node
 Return int 0 on success, other or die on failure

=cut

sub _processFolderNode
{
    my ($node) = @_;

    return 0 if defined $node->{'create_if'} && !eval _expandVars( $node->{'create_if'} );
    
    my $dir = iMSCP::Dir->new( dirname => $node->{'content'} );
    $dir->remove() if $node->{'pre_remove'};
    $dir->make(
        {
            user  => defined $node->{'user'} ? _expandVars( $node->{'owner'} ) : undef,
            group => defined $node->{'group'} ? _expandVars( $node->{'group'} ) : undef,
            mode  => defined $node->{'mode'} ? oct( $node->{'mode'} ) : undef
        }
    );
}

=item _processCopyConfigNode( \%node )

 Process the givencopy_config node

 Node attributes:
  copy_if: Copy the file or directory only if the condition is met, delete it
           otherwise, unless the keep_if_exists attribute is TRUE
  keep_if_exist If the file or directory, never delete it
  user: Target file or directory owner
  group: Target file or directory group
  mode: Target file or directory mode

 Param hashref \%node Node
 Return int 0 on success, other or die on failure

=cut

sub _processCopyConfigNode
{
    my ($node) = @_;

    if ( defined $node->{'copy_if'} && !eval _expandVars( $node->{'copy_if'} ) ) {
        return 0 if $node->{'keep_if_exist'};
        ( my $syspath = $node->{'content'} ) =~ s/^$main::{'INST_PREF'}//;
        return 0 unless $syspath ne '/' && -e $syspath;
        return iMSCP::Dir->new( dirname => $syspath )->remove() if -d _;
        return iMSCP::File->new( filename => $syspath )->delFile();
    }

    my ($name, $path) = fileparse( $node->{'content'} );

    # If $name isn't in current directory, take it from master configuration directory
    my $source = -e $name
        ? $name : $CWD =~ s%^($FindBin::Bin/configs/)$main::imscpConfig{'DISTRO_ID'}%${1}debian%r . "/$name";

    # Override target name if requested
    $name = $node->{'copy_as'} if defined $node->{'copy_as'};

    if ( -d $source ) {
        iMSCP::Dir->new( dirname => $source )->rcopy( "$path/$name", { preserve => 'no' } );
    } else {
        my $rs = iMSCP::File->new( filename => $source )->copyFile( "$path/$name", { preserve => 'no' } );
        return $rs if $rs;
    }

    return 0 unless defined $node->{'user'} || defined $node->{'group'} || defined $node->{'mode'};

    my $file = iMSCP::File->new( filename => -e "$path/$name" ? "$path/$name" : $path );

    if ( defined $node->{'user'} || defined $node->{'group'} ) {
        my $rs = $file->owner(
            ( defined $node->{'user'} ? _expandVars( $node->{'user'} ) : -1 ),
            ( defined $node->{'group'} ? _expandVars( $node->{'group'} ) : -1 )
        );
        return $rs if $rs;
    }

    defined $node->{'mode'} ? $file->mode( oct( $node->{'mode'} )) : 0;
}

=item _processCopyNode( \%node )

 Process the given copy node

 Node attributes:
  user: Target file or directory owner
  group: Target file or directory group
  mode: Target file or directory mode

 Param hashref \%node Node
 Return int 0 on success, other or die on failure

=cut

sub _processCopyNode
{
    my ($node) = @_;

    my ($name, $path) = fileparse( $node->{'content'} );

    if ( -d $name ) {
        iMSCP::Dir->new( dirname => $name )->rcopy( "$path/$name", { preserve => 'no' } );
    } else {
        my $rs = iMSCP::File->new( filename => $name )->copyFile( $path, { preserve => 'no' } );
        return $rs if $rs;
    }

    return 0 unless defined $node->{'user'} || defined $node->{'group'} || defined $node->{'mode'};

    my $file = iMSCP::File->new( filename => -e "$path/$name" ? "$path/$name" : $path );

    if ( defined $node->{'user'} || defined $node->{'group'} ) {
        my $rs = $file->owner(
            ( defined $node->{'user'} ? _expandVars( $node->{'user'} ) : -1 ),
            ( defined $node->{'group'} ? _expandVars( $node->{'group'} ) : -1 )
        );
        return $rs if $rs;
    }

    defined $node->{'mode'} ? $file->mode( oct( $node->{'mode'} )) : 0;
}

=item _getDistributionAdapter( )

 Return distribution autoinstaller adapter instance

 Return autoinstaller::Adapter::Abstract, die on failure

=cut

sub _getDistributionAdapter
{
    return $autoinstallerAdapterInstance if $autoinstallerAdapterInstance;

    my $adapterName = ucfirst( $main::imscpConfig{'DISTRO_ID'} ) . 'Adapter';
    my $file = "$FindBin::Bin/autoinstaller/Adapter/$adapterName.pm";
    my $adapterClass = "autoinstaller::Adapter::${adapterName}";

    require $file;
    $autoinstallerAdapterInstance = $adapterClass->new();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
