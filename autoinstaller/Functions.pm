=head1 NAME

 autoinstaller::Functions - Functions for the i-MSCP autoinstaller

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright 2010-2016 by internet Multi Server Control Panel
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
use iMSCP::Debug;
use iMSCP::Config;
use iMSCP::Bootstrapper;
use iMSCP::Getopt;
use iMSCP::Dialog;
use iMSCP::LsbRelease;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Stepper;
use Cwd;
use File::Find;
use version;
use iMSCP::Getopt;
use parent 'Exporter';

our @EXPORT_OK = qw/loadConfig build install/;

my $autoinstallerAdapterInstance;
my $eventManager;

=head1 DESCRIPTION

 Common functions for the i-MSCP installer

=head1 PUBLIC FUNCTIONS

=over 4

=item loadConfig()

 Load main i-MSCP configuration

 Load both, the new imscp.conf file (upstream conffile) and the current imscp.conf file (old conffile) and merge them
together in the %main::imscpConfig variable. The old imscp.conf file is tied to the %main::imscpOldConfig variable
and set as readonly.

 Return undef

=cut

sub loadConfig
{
    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    my $distroConffile = "$FindBin::Bin/configs/".lc( $lsbRelease->getId( 1 ) ).'/imscp.conf';
    my $defaultConffile = "$FindBin::Bin/configs/debian/imscp.conf";

    # Load new imscp.conf conffile from i-MSCP upstream source
    tie my %imscpNewConfig, 'iMSCP::Config', fileName => -f $distroConffile ? $distroConffile : $defaultConffile;

    %main::imscpConfig = %imscpNewConfig;
    $main::imscpConfig{'DISTRO_ID'} = lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ) );
    $main::imscpConfig{'DISTRO_CODENAME'} = lc( iMSCP::LsbRelease->getInstance()->getCodename( 'short' ) );
    $main::imscpConfig{'DISTRO_RELEASE'} = iMSCP::LsbRelease->getInstance()->getRelease( 'short', 'force_numeric' );
    %main::imscpOldConfig = ();

    # Load old i-MSCP conffile as readonly if it exists
    if (-f "$imscpNewConfig{'CONF_DIR'}/imscp.conf") {
        tie %main::imscpOldConfig,
            'iMSCP::Config', fileName => "$imscpNewConfig{'CONF_DIR'}/imscp.conf", readonly => 1, nowarn => 1;

        # Merge old config with the new but do not write anything yet.
        for my $oldConf(keys %main::imscpOldConfig) {
            if (exists $main::imscpConfig{$oldConf}
                && !grep($_ eq $oldConf, ( 'BuildDate', 'Version', 'CodeName', 'THEME_ASSETS_VERSION', 'DISTRO_ID',
                'DISTRO_CODENAME', 'DISTRO_RELEASE' ))
            ) {
                $main::imscpConfig{$oldConf} = $main::imscpOldConfig{$oldConf};
            }
        }
    }

    $eventManager = iMSCP::EventManager->getInstance();
    undef;
}

=item build()

 Process build tasks

 Return int 0 on success, other on failure

=cut

sub build
{
    newDebug( 'imscp-build.log' );

    if ($main::skippackages && !iMSCP::Getopt->preseed && !$main::imscpConfig{'HTTPD_SERVER'}
        || !$main::imscpConfig{'PO_SERVER'} || !$main::imscpConfig{'MTA_SERVER'} || !$main::imscpConfig{'FTPD_SERVER'}
        || !$main::imscpConfig{'NAMED_SERVER'} || !$main::imscpConfig{'SQL_SERVER'}
    ) {
        iMSCP::Getopt->noprompt(0);
        $main::skippackages = 0;
    }

    my $rs = _installPreRequiredPackages() unless $main::skippackages;
    return $rs if $rs;

    my $dialog = iMSCP::Dialog->getInstance();
    $dialog->set( 'ok-label', 'Ok' );
    $dialog->set( 'yes-label', 'Yes' );
    $dialog->set( 'no-label', 'No' );
    $dialog->set( 'cancel-label', 'Back' );

    unless (iMSCP::Getopt->noprompt || $main::reconfigure ne 'none') {
        $rs = _showWelcomeMsg( $dialog );
        return $rs if $rs;

        if (%main::imscpOldConfig) {
            $rs = _showUpdateNotices( $dialog );
            return $rs if $rs;
        }

        $rs = _confirmDistro( $dialog );
        return $rs if $rs;
    }

    $rs = _askInstallMode( $dialog ) unless iMSCP::Getopt->noprompt || $main::buildonly || $main::reconfigure ne 'none';
    $rs ||= _getDistroAdapter()->preBuild();
    return $rs if $rs;

    my @steps = (
        [ \&_processDistroPackages, 'Processing distro packages' ],
        [ \&_testRequirements, 'Checking for requirements' ],
        [ \&_buildDistributionFiles, 'Building distribution files' ],
        [ \&_compileDaemon, 'Compiling daemon' ],
        [ \&_savePersistentData, 'Saving persistent data' ]
    );

    # Remove the distro packages step in case the --skippackages is set
    shift @steps if $main::skippackages;

    $rs = $eventManager->trigger( 'beforeBuild', \@steps );
    return $rs if $rs;

    my $step = 1;
    my $nbSteps = scalar @steps;

    for (@steps) {
        $rs = step( $_->[0], $_->[1], $nbSteps, $step );
        error( 'An error occurred while performing build steps' ) if $rs;
        return $rs if $rs;
        $step++;
    }

    $rs = $eventManager->trigger( 'afterBuild' );
    $rs ||= $eventManager->trigger( 'beforePostBuild' );
    $rs ||= _getDistroAdapter()->postBuild();
    return $rs if $rs;

    # Backup current configuration file if any
    if (-f "$main::imscpConfig{'CONF_DIR'}/imscp.conf") {
        $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf" )->copyFile(
            "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf"
        );
        return $rs if $rs;
    }

    # Write new config file
    my %imscpConf = %main::imscpConfig;
    tie %main::imscpConfig, 'iMSCP::Config', fileName => "$main::{'SYSTEM_CONF'}/imscp.conf";
    $main::imscpConfig{$_} = $imscpConf{$_} for keys %imscpConf;

    # Clean build directory (remove any .gitignore|empty-file)
    find( sub {
            return unless $_ eq '.gitignore' || $_ eq 'empty-file';
            unlink or fatal( sprintf( 'Could not remove %s file: %s', $File::Find::name, $! ) );
        },
        $main::{'INST_PREF'}
    );

    $rs = $eventManager->trigger( 'afterPostBuild' );
    return $rs if $rs;

    endDebug();
}

=item install()

 Process install tasks

 Return int 0 on success, other otherwise

=cut

sub install
{
    newDebug( 'imscp-setup.log' );

    # Not really the right place to do that job but we have not really choice because this must be done before
    # installation of new files
    require iMSCP::Service;
    my $serviceMngr = iMSCP::Service->getInstance();
    if ($serviceMngr->hasService( 'imscp_network' )) {
        $serviceMngr->remove( 'imscp_network' );

        for my $pFormat('/etc/init.d/%s', '/etc/init/%s.conf') {
            my $file = sprintf( $pFormat, 'imscp_network' );
            if (-f $file) {
                my $rs = iMSCP::File->new( filename => $file )->delFile();
                return $rs if $rs;
            }
        }
    }

    my $bootstrapper = iMSCP::Bootstrapper->getInstance();
    my @runningJobs = ();

    for my $job('imscp-backup-all', 'imscp-backup-imscp', 'imscp-dsk-quota', 'imscp-srv-traff', 'imscp-vrl-traff',
        'awstats_updateall.pl', 'imscp-disable-accounts', 'imscp'
    ) {
        next if $bootstrapper->lock( "/tmp/$job.lock", 'nowait' );
        push @runningJobs, $job,
    }

    if (@runningJobs) {
        iMSCP::Dialog->getInstance()->msgbox( <<"EOF" );

i-MSCP jobs are currently running on your system.

You must wait until the end of these jobs and re-run the installer.

Running jobs are: @runningJobs
EOF
        return 1;
    }

    my @steps = (
        [ \&_installFiles, 'Installing files' ],
        [ \&main::setupBoot, 'Setup bootstrapping' ],
        [ \&main::setupRegisterListeners, 'Registering servers/packages event listeners' ],
        [ \&main::setupDialog, 'Processing setup dialog' ],
        [ \&main::setupTasks, 'Processing setup tasks' ],
        [ \&_deleteBuildDir, 'Deleting Build directory' ]
    );

    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeInstall', \@steps );
    return $rs if $rs;

    my $step = 1;
    my $nbSteps = scalar @steps;

    for (@steps) {
        $rs = step( $_->[0], $_->[1], $nbSteps, $step );
        error( 'An error occurred while performing installation steps' ) if $rs;
        return $rs if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge() if iMSCP::Dialog->getInstance()->hasGauge();

    $rs = iMSCP::EventManager->getInstance()->trigger( 'afterInstall' );
    return $rs if $rs;

    require Net::LibIDN;
    Net::LibIDN->import('idn_to_unicode');

    my $port = $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://'
        ? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
        : $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'};
    my $vhost = idn_to_unicode($main::imscpConfig{'BASE_SERVER_VHOST'}, 'utf-8');

    iMSCP::Dialog->getInstance()->infobox( <<"EOF" );

\\Z1Congratulations\\Zn

i-MSCP has been successfully installed/updated.

Please connect to $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'}$vhost:$port and login with your administrator account.

Thank you for choosing i-MSCP.
EOF

    endDebug();
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _installPreRequiredPackages()

 Trigger pre-required package installation tasks from distro autoinstaller adapter

 Return int 0 on success, other otherwise

=cut

sub _installPreRequiredPackages
{
    _getDistroAdapter()->installPreRequiredPackages();
}

=item _showWelcomeMsg(\%dialog)

 Show welcome message

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, other otherwise

=cut

sub _showWelcomeMsg
{
    my $dialog = shift;

    $dialog->msgbox( <<"EOF" );

\\Zb\\Z4i-MSCP - internet Multi Server Control Panel
============================================\\Zn\\ZB

Welcome to the i-MSCP setup dialog.

i-MSCP (internet Multi Server Control Panel) is an open-source software which allows to manage shared hosting environments on Linux servers.

i-MSCP aims to provide an easy-to-use Web interface for end-users, and to manage servers without any manual intervention on the filesystem.

i-MSCP was designed for professional Hosting Service Providers (HSPs), Internet Service Providers (ISPs) and IT professionals.

\\Zb\\Z4License\\Zn\\ZB

Unless otherwise stated all code is licensed under GPL 2.0 and has the following copyright:

        \\ZbCopyright 2010-2016 by i-MSCP Team - All rights reserved\\ZB

\\Zb\\Z4Credits\\Zn\\ZB

i-MSCP is a project of i-MSCP | internet Multi Server Control Panel.
i-MSCP and the i-MSCP logo are trademarks of the i-MSCP | internet Multi Server Control Panel project team.
EOF
}

=item _showUpdateNotices(\%dialog)

 Show update notices

 Return 0 on success, other on failure or when user is aborting

=cut

sub _showUpdateNotices
{
    my $dialog = shift;

    eval "use Sort::Versions; 1";
    die( $@ ) if $@;

    my $noticesDir = "$FindBin::Bin/autoinstaller/UpdateNotices";
    my $imscpVersion = $main::imscpOldConfig{'Version'};
    my $notices = '';

    if ($imscpVersion !~ /git/i) {
        if ($imscpVersion =~ /^\d+\.\d+\.\d+/) {
            $imscpVersion = $1;

            my @noticeFiles = iMSCP::Dir->new( dirname => $noticesDir )->getFiles();

            if (@noticeFiles) {
                s/\.txt$// for @noticeFiles;
                @noticeFiles = sort { versioncmp( $a, $b ) } @noticeFiles;

                for my $noticeVersion(@noticeFiles) {
                    if (version->parse( $imscpVersion ) < version->parse( $noticeVersion )) {
                        my $noticeBody = iMSCP::File->new( filename => "$noticesDir/$noticeVersion.txt" )->get();
                        unless (defined $noticeBody) {
                            error( sprintf( 'Could not read %s file', "$noticesDir/$noticeVersion.txt" ) );
                            return 1;
                        }

                        $notices .= "\n$noticeBody";
                    }
                }
            }
        } else {
            error( 'Could not parse i-MSCP version from your imscp.conf file.' );
            return 1;
        }
    } else {
        $notices = <<"EOF";

The installer detected that you're using the \\ZbGit\\ZB version of i-MSCP. Before continue, be sure to have read the errata file:

    \\Zbhttps://github.com/i-MSCP/imscp/blob/1.2.x/docs/1.2.x_errata.md\\ZB

We would remind you that the Git version can be highly unstable and that the i-MSCP team do not provides any support for it.
EOF
    }

    unless ($notices eq '') {
        $dialog->set( 'yes-label', 'Continue' );
        $dialog->set( 'no-label', 'Abort' );
        return 50 if $dialog->yesno( <<"EOF" );

Please read carefully before continue.

\\ZbNote:\\ZB Use the \\ZbPage Down\\ZB key from your keyboard to scroll down.
$notices
You can now either continue or abort if needed.
EOF

        $dialog->resetLabels();
    }

    0;
}

=item _confirmDistro(\%dialog)

 Distribution confirmation dialog

 Param iMSCP::Dialog \%dialog
 Return 0 on success, other on failure on when user is aborting

=cut

sub _confirmDistro
{
    my $dialog = shift;

    $dialog->infobox( "\nDetecting target distribution..." );

    my $lsbRelease = iMSCP::LsbRelease->getInstance();
    my $distribution = $lsbRelease->getId( 'short' );
    my $codename = lc( $lsbRelease->getCodename( 'short' ) );
    my $release = $lsbRelease->getRelease( 'short' );
    my $description = $lsbRelease->getDescription( 'short' );
    my $packagesFile = "$FindBin::Bin/docs/$distribution/packages-$codename.xml";

    if ($distribution ne 'n/a'
        && (lc( $distribution ) eq 'debian' || lc( $distribution ) eq 'ubuntu')
        && $codename ne 'n/a'
    ) {
        unless (-f $packagesFile) {
            $dialog->msgbox( <<"EOF" );

\\Z1$distribution $release ($codename) not supported yet\\Zn

We are sorry but your $distribution version is not supported.

Thanks for choosing i-MSCP.
EOF

            return 50;
        }

        my $rs = $dialog->yesno( <<"EOF" );

$distribution $release ($codename) has been detected. Is this ok?
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

=item _askInstallMode(\%dialog)

 Asks for install mode

 Param iMSCP::Dialog \%dialog
 Return int 0 on success, 50 otherwise

=cut

sub _askInstallMode
{
    my $dialog = shift;

    $dialog->set( 'cancel-label', 'Abort' );

    my ($rs, $mode) = $dialog->radiolist( <<"EOF", [ 'Install', 'Build' ], 'Install' );

Please, choose an option:

\\Z4Install:\\Zn Choose this option if you want install or update i-MSCP.
  \\Z4Build:\\Zn Choose this option if you want install i-MSCP manually.
EOF

    $main::buildonly = $mode eq 'Build' ? 1 : 0;
    $dialog->set( 'cancel-label', 'Back' );
    return 50 if $rs;
    0;
}

=item _processDistroPackages()

 Trigger packages installation/uninstallation tasks from distro autoinstaller adapter

 Return int 0 on success, other on failure

=cut

sub _processDistroPackages
{
    my $rs = _getDistroAdapter()->installPackages();
    $rs ||= _getDistroAdapter()->uninstallPackages();
}

=item _testRequirements()

 Test for requirements

 Return undef if all requirements are meet, throw a fatal error otherwise

=cut

sub _testRequirements
{
    iMSCP::Requirements->new()->all();
}

=item _buildDistributionFiles

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

=item _buildLayout()

 Build layout

 Return int 0 on success, other on failure

=cut

sub _buildLayout
{
    my $distroLayout = "$FindBin::Bin/autoinstaller/Layout/".iMSCP::LsbRelease->getInstance()->getId( 'short' ).'.xml';
    my $defaultLayout = "$FindBin::Bin/autoinstaller/Layout/Debian.xml";
    _processXmlFile( -f $distroLayout ? $distroLayout : $defaultLayout );
}

=item _buildConfigFiles()

 Build configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConfigFiles
{
    # Possible config directory paths
    my $distroConfigDir = "$FindBin::Bin/configs/".lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ) );
    my $defaultConfigDir = "$FindBin::Bin/configs/debian";

    # Determine config directory to use
    my $confDir = -d $distroConfigDir ? $distroConfigDir : $defaultConfigDir;

    unless (chdir( $confDir )) {
        error( sprintf( 'Could not to change directory to %s: %s', $confDir, $! ) );
        return 1;
    }

    # Determine install.xml file to process
    my $file = -f "$distroConfigDir/install.xml" ? "$distroConfigDir/install.xml" : "$defaultConfigDir/install.xml";

    my $rs = _processXmlFile( $file );
    return $rs if $rs;

    # Get list of sub config dir from default config directory (debian)
    my $dirDH = iMSCP::Dir->new( dirname => $defaultConfigDir );
    my @configDirs = $dirDH->getDirs();

    for my $dir(@configDirs) {
        # Override sub config dir path if it is available in selected distro, else set it to default path
        $confDir = -d "$distroConfigDir/$dir" ? "$distroConfigDir/$dir" : "$defaultConfigDir/$dir";

        unless (chdir( $confDir )) {
            error( sprintf( 'Could not change directory to %s: %s', $confDir, $! ) );
            return 1;
        }

        $file = -f "$distroConfigDir/$dir/install.xml"
            ? "$distroConfigDir/$dir/install.xml"
            : "$defaultConfigDir/$dir/install.xml";

        next unless -f $file;

        $rs = _processXmlFile( $file );
        return $rs if $rs;
    }

    0;
}

=item _buildEngineFiles()

 Build engine files

 Return int 0 on success, other on failure

=cut

sub _buildEngineFiles
{
    unless (chdir "$FindBin::Bin/engine") {
        error( sprintf( 'Could not change dir to %s', "$FindBin::Bin/engine" ) );
        return 1;
    }

    my $rs = _processXmlFile( "$FindBin::Bin/engine/install.xml" );
    return $rs if $rs;

    my $dir = iMSCP::Dir->new( dirname => "$FindBin::Bin/engine" );
    my @configDirs = $dir->getDirs();

    for my $dir(@configDirs) {
        next unless -f "$FindBin::Bin/engine/$dir/install.xml";

        unless (chdir "$FindBin::Bin/engine/$dir") {
            error( sprintf( 'Could not change dir to %s', "$FindBin::Bin/engine/$dir" ) );
            return 1;
        }

        $rs = _processXmlFile( "$FindBin::Bin/engine/$dir/install.xml" );
        return $rs if $rs;
    }

    0;
}

=item _buildFrontendFiles()

 Build frontEnd files

 Return int 0 on success, other on failure

=cut

sub _buildFrontendFiles
{
    my $rs = execute( "cp -fR $FindBin::Bin/gui $main::{'SYSTEM_ROOT'}", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    $rs;
}

=item _compileDaemon()

 Compile daemon

 Return int 0 on success, other on failure

=cut

sub _compileDaemon
{
    unless (chdir "$FindBin::Bin/daemon") {
        error( sprintf( 'Could not change dir to %s', "$FindBin::Bin/daemon" ) );
        return 1;
    }

    my $rs = execute( 'make clean imscp_daemon', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    error( 'Could not build i-MSCP daemon' ) if $rs;
    $rs ||= iMSCP::Dir->new( dirname => "$main::{'SYSTEM_ROOT'}/daemon" )->make();
    $rs ||= iMSCP::File->new( filename => 'imscp_daemon' )->copyFile( "$main::{'SYSTEM_ROOT'}/daemon" );
}

=item _savePersistentData()

 Save persistent data

 Return int 0 on success, other on failure

=cut

sub _savePersistentData
{
    my $destdir = $main::{'INST_PREF'};

    ## Config files

    # Save Web directories skeletons

    # Remove deprecated phptmp directories
    for my $skelDir("alias", "subdomain") {
        my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/apache/skel/$skelDir/phptmp" )->remove();
        return $rs if $rs;
    }

    # Move old skel directory to new location
    if (-d "$main::imscpConfig{'CONF_DIR'}/apache/skel") {
        my $rs = execute(
            "mv $main::imscpConfig{'CONF_DIR'}/apache/skel $main::imscpConfig{'CONF_DIR'}/skel", \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    if (-d "$main::imscpConfig{'CONF_DIR'}/skel") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'CONF_DIR'}/skel $destdir$main::imscpConfig{'CONF_DIR'}/skel",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Move old listener files to new location
    if (-d "$main::imscpConfig{'CONF_DIR'}/hooks.d") {
        my $rs = execute(
            "mv $main::imscpConfig{'CONF_DIR'}/hooks.d $main::imscpConfig{'CONF_DIR'}/listeners.d", \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Remove old README file
    if (-f "$main::imscpConfig{'CONF_DIR'}/listeners.d/README") {
        my $rs = execute( "rm -f $main::imscpConfig{'CONF_DIR'}/listeners.d/README", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    ## Remove obsolete locations

    if (-d  "$main::imscpConfig{'CACHE_DATA_DIR'}") {
        my $rs = execute("rm -fR $main::imscpConfig{'CACHE_DATA_DIR'}/addons");
        return $rs if $rs;
    }

    ## GUI files

    # Save ISP logos (older location)
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'ROOT_DIR'}/gui/themes/user_logos ".
                "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Save ISP logos (new location)
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/ispLogos ".
                "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/ispLogos",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Save GUI logs
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/logs") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/logs $destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/logs",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Save persistent data
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/persistent ".
                "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Save software (older path ./gui/data/softwares) to new path (./gui/data/persistent/softwares)
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/data/softwares") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'ROOT_DIR'}/gui/data/softwares ".
                "$destdir$main::imscpConfig{'ROOT_DIR'}/gui/data/persistent/softwares",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Save plugins
    if (-d $main::imscpConfig{'PLUGINS_DIR'}) {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'PLUGINS_DIR'} $destdir$main::imscpConfig{'PLUGINS_DIR'}",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Quick fix for #IP-1340 ( Removes old filemanager directory which is no longer used )
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager") {
        my $rs = execute( "rm -rf $main::imscpConfig{'ROOT_DIR'}/gui/public/tools/filemanager", \my $stdout,
            \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Save tools
    if (-d "$main::imscpConfig{'ROOT_DIR'}/gui/public/tools") {
        my $rs = execute(
            "cp -fRT $main::imscpConfig{'ROOT_DIR'}/gui/public/tools $destdir$main::imscpConfig{'ROOT_DIR'}/gui/public/tools",
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    0;
}

=item _installFiles()

 Install files from build directory

 Return int 0 on success, other on failure

=cut

sub _installFiles
{
    # i-MSCP daemon must be stopped before changing any file on the files system
    iMSCP::Service->getInstance()->stop( 'imscp_daemon' );

    # Process cleanup to avoid any security risks and conflicts
    for(qw/daemon engine gui/) {
        my $rs = execute( "rm -fR $main::imscpConfig{'ROOT_DIR'}/$_", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    # Install new i-MSCP files on the files system
    my $rs = execute( "rsync -OKa $main::{'INST_PREF'}/* /", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    $rs;
}

=item _deleteBuildDir()

 Delete build directory

 Return int 0 on success, other on failure

=cut

sub _deleteBuildDir
{
    return 0 unless $main::{'INST_PREF'} && -d $main::{'INST_PREF'};

    my $rs = execute( "rm -fR $main::{'INST_PREF'}", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    $rs;
}

=item _processXmlFile($filepath)

 Process an install.xml file or distribution layout.xml file

 Param string $filepath xml file path
 Return int 0 on success, other on failure ; A fatal error is raised in case a variable cannot be exported

=cut

sub _processXmlFile
{
    my $file = shift;

    unless (-f $file) {
        error( sprintf( "File %s doesn't exists", $file ) );
        return 1;
    }

    eval "use XML::Simple; 1";
    fatal( 'Could not load the XML::Simple perl module' ) if $@;
    my $xml = XML::Simple->new( ForceArray => 1, ForceContent => 1 );
    my $data = eval { $xml->XMLin( $file, VarAttr => 'export' ) };
    if ($@) {
        error( $@ );
        return 1;
    }

    # Process xml 'folders' nodes if any
    for my $folder(@{$data->{'folders'}}) {
        $folder->{'content'} = _expandVars( $folder->{'content'} );
        $main::{$folder->{'export'}} = $folder->{'content'} if $folder->{'export'};
        my $rs = _processFolder( $folder );
        return $rs if $rs;
    }

    # Process xml 'copy_config' nodes if any
    for my $conffile(@{$data->{'copy_config'}}) {
        $conffile->{'content'} = _expandVars( $conffile->{'content'} );
        my $rs = _copyConfig( $conffile );
        return $rs if $rs;
    }

    # Process xml 'copy' nodes if any
    for my $node(@{$data->{'copy'}}) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        my $rs = _copy( $node );
        return $rs if $rs;
    }

    # Process xml 'create_file' nodes if any
    for my $file(@{$data->{'create_file'}}) {
        $file->{'content'} = _expandVars( $file->{'content'} );
        my $rs = _createFile( $file );
        return $rs if $rs;
    }

    # Process xml 'chmod_file' nodes if any
    for my $file(@{$data->{'chmod_file'}}) {
        $file->{'content'} = _expandVars( $file->{'content'} );
        my $rs = _chmodFile( $file ) if $file->{'content'};
        return $rs if $rs;
    }

    # Process xml 'chmod_file' nodes if any
    for my $file(@{$data->{'chown_file'}}) {
        $file->{'content'} = _expandVars( $file->{'content'} );
        my $rs = _chownFile( $file );
        return $rs if $rs;
    }

    0;
}

=item _expandVars($string)

 Expand variables in the given string

 Param string $string string containing variables to expands
 Return string

=cut

sub _expandVars
{
    my $string = shift || '';

    while (my ($var) = $string =~ /\$\{([^\}]+)\}/g) {
        if (exists $main::{$var}) {
            $string =~ s/\$\{$var\}/$main::{$var}/g;
        } elsif (exists $main::imscpConfig{$var}) {
            $string =~ s/\$\{$var\}/$main::imscpConfig{$var}/g;
        } else {
            fatal( "Could not expand variable \${$var}. Variable not found." );
        }
    }

    $string;
}

=item _processFolder(\%data)

 Process a folder node from an install.xml file

 Process the xml folder node by creating the described directory.

 Return int 0 on success, other on failure

=cut

sub _processFolder
{
    my $data = shift;

    my $dir = iMSCP::Dir->new( dirname => $data->{'content'} );

    # Needed to be sure to not keep any file from a previous build that has failed
    if (defined $main::{'INST_PREF'} && $main::{'INST_PREF'} eq $data->{'content'}) {
        my $rs = $dir->remove();
        return $rs if $rs;
    }

    my $options = { };
    $options->{'mode'} = oct( $data->{'mode'} ) if exists $data->{'mode'};
    $options->{'user'} = _expandVars( $data->{'owner'} ) if exists $data->{'owner'};
    $options->{'group'} = _expandVars( $data->{'group'} ) if exists $data->{'group'};
    $dir->make( $options );
}

=item _copyConfig(\%data)

 Process a copy_config node from an install.xml file

 Return int 0 on success, other on failure

=cut

sub _copyConfig
{
    my $data = shift;

    if ($data->{'if'}) {
        return 0 unless eval _expandVars( $data->{if} );
    }

    my @parts = split '/', $data->{'content'};
    my $name = pop( @parts );
    my $path = join '/', @parts;
    my $distribution = lc( iMSCP::LsbRelease->getInstance()->getId( 'short' ) );

    my $alternativeFolder = getcwd();
    $alternativeFolder =~ s/$distribution/debian/;

    my $source = -f $name ? $name : "$alternativeFolder/$name";

    if (-d $source) {
        my $rs = execute( "cp -fR $source $path", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    } else {
        my $rs = execute( "cp -f $source $path", \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
        return $rs if $rs;
    }

    return 0 unless $data->{'user'} || $data->{'group'} || $data->{'mode'};

    my $file = iMSCP::File->new( filename => -e "$path/$name" ? "$path/$name" : $path );
    my $rs = $file->mode( oct( $data->{'mode'} ) ) if $data->{'mode'};
    return $rs if $rs;

    return 0 unless $data->{'user'} || $data->{'group'};

    $file->owner(
            $data->{'user'} ? _expandVars( $data->{'user'} ) : -1,
            $data->{'group'} ? _expandVars( $data->{'group'} ) : -1
    )
}

=item _copy(\%data)

 Process the copy node from an install.xml file

 Return int 0 on success, other on failure

=cut

sub _copy
{
    my $data = shift;

    my @parts = split '/', $data->{'content'};
    my $name = pop( @parts );
    my $path = join '/', @parts;

    my $rs = execute( "cp -fR $name $path", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    return 0 unless $data->{'user'} || $data->{'group'} || $data->{'mode'};

    my $file = iMSCP::File->new( filename => -e "$path/$name" ? "$path/$name" : $path );
    $rs = $file->mode( oct( $data->{'mode'} ) ) if $data->{'mode'};
    return $rs if $rs;

    return 0 unless $data->{'user'} || $data->{'group'};

    $file->owner(
            $data->{'user'} ? _expandVars( $data->{'user'} ) : -1,
            $data->{'group'} ? _expandVars( $data->{'group'} ) : -1
    );
}

=item _createFile(\%$data)

 Create a file

 Return int 0 on success, other on failure

=cut

sub _createFile
{
    iMSCP::File->new( filename => $_[0]->{'content'} )->save();
}

=item _chownFile()

 Change file/directory owner and/or group recursively

 Return int 0 on success, other on failure

=cut

sub _chownFile
{
    my $data = shift;

    return 0 unless $data->{'owner'} && $data->{'group'};

    my $rs = execute( "chown $data->{'owner'}:$data->{'group'} $data->{'content'}", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    $rs;
}

=item _chmodFile(\%data)

 Process chmod_file from an install.xml file

 Return int 0 on success, other on failure

=cut

sub _chmodFile
{
    my $data = shift;

    return 0 unless exists $data->{'mode'};

    my $rs = execute( "chmod $data->{'mode'} $data->{'content'}", \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $stderr && $rs;
    $rs;
}

=item _getDistroAdapter()

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
        $autoinstallerAdapterInstance = $adapterClass->getInstance()
    };

    fatal( sprintf( 'Could not instantiate %s autoinstaller adapter: %s', $distribution, $@ ) ) if $@;
    $autoinstallerAdapterInstance;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
