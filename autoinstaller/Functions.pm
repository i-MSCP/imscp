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
use File::Basename 'fileparse';
use File::Find 'find';
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Config;
use iMSCP::Cwd '$CWD';
use iMSCP::Debug qw/ debug error output /;
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

our @EXPORT_OK = qw/
    loadConfig install prepareDistFiles distributionCheckDialog
    showGitVersionWarnDialog showWelcomeDialog writeMasterConfigFile
/;

=head1 DESCRIPTION

 Common functions for the i-MSCP installer

=head1 PUBLIC FUNCTIONS

=over 4

=item loadConfig( )

 Load main i-MSCP configuration

 Return void, die on failure

=cut

sub loadConfig
{
    my $lsb = iMSCP::LsbRelease->getInstance();
    my $distConf = "$FindBin::Bin/configs/" . lc( $lsb->getId( TRUE ))
        . '/imscp.conf';
    my $defaultConf = "$FindBin::Bin/configs/debian/imscp.conf";
    my $newConf = -f $distConf ? $distConf : $defaultConf;

    # Load new configuration
    tie %::imscpConfig, 'iMSCP::Config',
        fileName  => $newConf,
        readonly  => TRUE,
        temporary => TRUE;

    # Load old configuration
    if ( -f "$::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) {
        # Recovering following an installation or upgrade failure
        tie %::imscpOldConfig,
            'iMSCP::Config',
            fileName  => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf",
            readonly  => TRUE,
            temporary => TRUE;
    } elsif ( -f "$::imscpConfig{'CONF_DIR'}/imscp.conf" ) {
        # Upgrade case
        tie %::imscpOldConfig,
            'iMSCP::Config',
            fileName  => "$::imscpConfig{'CONF_DIR'}/imscp.conf",
            readonly  => TRUE,
            temporary => TRUE;
    } else { # Fresh installation case
        %::imscpOldConfig = %::imscpConfig;
    }

    if ( tied( %::imscpOldConfig ) ) {
        debug( 'Merging old configuration with new configuration...' );
        # Merge old configuration in new configuration, excluding upstream defined values
        while ( my ( $key, $value ) = each( %::imscpOldConfig ) ) {
            next unless exists $::imscpConfig{$key};
            next if $key =~ /^(?:Build|Version|CodeName|PluginApi|THEME_ASSETS_VERSION|COMPOSER_VERSION)$/;
            $::imscpConfig{$key} = $value;
        }

        # Make sure that all configuration parameters exist
        while ( my ( $param, $value ) = each( %::imscpConfig ) ) {
            next if exists $::imscpOldConfig{$param};
            $::imscpOldConfig{$param} = $value;
        }
    }

    # Set system based values
    @{main::imscpConfig}{qw/ DISTRO_ID DISTRO_CODENAME DISTRO_RELEASE /} = (
        lc $lsb->getId( TRUE ), lc $lsb->getCodename( TRUE ),
        $lsb->getRelease( TRUE, TRUE )
    );
}

=item install( )

 Process install tasks

 Return int 0 on success, other otherwise

=cut

sub install
{
    #@type autoinstaller::Adapter::AbstractAdapter
    my $distAdapter = eval {
        my $distID = ucfirst $::imscpConfig{'DISTRO_ID'};
        my $file = "$FindBin::Bin/autoinstaller/Adapter/${distID}Adapter.pm";
        my $distAdapter = "autoinstaller::Adapter::${distID}Adapter";
        require $file;
        $distAdapter->new();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # No event is triggered before/after the preinstall tasks as the required
    # Perl packages might not be installed yet
    my $rs = $distAdapter->preinstall();
    return $rs if $rs;

    iMSCP::Requirements->new()->all();

    my $events = iMSCP::EventManager->getInstance();
    $rs = $events->trigger( 'beforeInstall' );
    $rs ||= $distAdapter->install();
    $rs ||= $events->trigger( 'afterInstall' );
    $rs ||= $events->trigger( 'beforePostInstall' );
    $rs ||= $distAdapter->postinstall();
    $rs ||= $events->trigger( 'afterPostInstall' );
    return $rs if $rs;

    require Net::LibIDN;
    Net::LibIDN->import( 'idn_to_unicode' );

    if ( iMSCP::Getopt->noprompt ) {
        if ( iMSCP::Getopt->verbose ) {
            print output( 'i-MSCP has been successfully installed/updated.', 'ok' );
        }
    } else {
        my $dialog = iMSCP::Dialog->getInstance();

        # Disable backup feature for last dialog
        $dialog->backup( FALSE );

        # Override default button label
        local $dialog->{'_opts'}->{
            $dialog->{'program'} eq 'dialog' ? 'ok-label' : 'ok-button'
        } = 'Ok';

        $dialog->note( <<"EOF" );
\\Z1\\ZbCongratulations\\Zn

i-MSCP has been successfully installed/updated.

You can login at $::questions{'BASE_SERVER_VHOST_PREFIX'}@{ [
    idn_to_unicode( $::questions{'BASE_SERVER_VHOST'}, 'utf-8' )
] }:@{ [ $::questions{'BASE_SERVER_VHOST_PREFIX'} eq 'http://'
    ? $::questions{'BASE_SERVER_VHOST_HTTP_PORT'}
    : $::questions{'BASE_SERVER_VHOST_HTTPS_PORT'}
] } with the master administrator account credentials.

Thank you for choosing i-MSCP.
EOF
        system( 'clear' );
    }

    0;
}

=item showWelcomeDialog( \%dialog )

 Show welcome dialog

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back), 50 (Abort)

=cut

sub showWelcomeDialog
{
    my ( $dialog ) = @_;

    return 20 if iMSCP::Getopt->noprompt;

    # Override default button labels
    local @{ $dialog->{'_opts'} }{
        $dialog->{'program'} eq 'dialog'
            ? qw/ yes-label no-label /
            : qw/ yes-button no-button /
    } = qw/ Continue Abort /;

    exit 50 if $dialog->boolean( <<"EOF" );
Welcome to the \\Z1\\Zbi-MSCP $::imscpConfig{'Version'}\\Zn installer.

i-MSCP (internet Multi Server Control Panel) is an open source software (OSS) for shared hosting environments management on Linux servers. It comes with a large choice of modules for various services such as Apache2, ProFTPd, Dovecot, Courier, Bind9, and can be easily extended through plugins, or listener files using its events-based API.

i-MSCP has been designed for professional Hosting Service Providers (HSPs), Internet Service Providers (ISPs) and IT professionals.

\\Zb\\Z4LICENSE\\Zn\\ZB

Unless otherwise stated, all source code and material is licensed under LGPL 2.1 and has the following copyright:

 \\Zb© 2010-@{[ (localtime)[5]+1900 ]}, Laurent Declercq (i-MSCP™)
 All rights reserved\\ZB

The design material and the "\\Zbi-MSCP\\ZB" trademark is the property of their authors. Reuse of them without prior consent of their respective authors is strictly prohibited.
EOF
}

=item showGitVersionWarnDialog

 Show warning dialog if Git version is detected

 Return int 0 (Next), 20 (Skip), 30 (Back), 50 (Abort)

=cut

sub showGitVersionWarnDialog
{
    my ( $dialog ) = @_;

    return 20 if iMSCP::Getopt->noprompt || index( lc $::imscpConfig{'Version'}, 'git' ) == -1;

    # Override default button labels
    local @{ $dialog->{'_opts'} }{
        $dialog->{'program'} eq 'dialog'
            ? qw/ ok-label extra-label /
            : qw/ yes-button no-button /
    } = qw/ Continue Abort /;

    my $ret = $dialog->boolean( <<"EOF", TRUE );
\\Zb\\Z1i-MSCP Git (unstable) version has been detected\\Zn\\ZB

The installer detected that you intends to @{ [ !%::imscpOldConfig ? 'install' : 'update your installation to' ]} the i-MSCP \\ZbGit\\ZB version.

We would remind you that the Git version can be \\Zuhighly unstable\\ZU and that the i-MSCP team \\Zudoesn't provide any support\\ZU for it.
EOF
    return 50 if $ret == 1;
    $ret;
}

=item distributionCheckDialog( \%dialog )

 Distribution check dialog

 Param iMSCP::Dialog \%dialog
 Return 20 (Skip), 50 (Abort)

=cut

sub distributionCheckDialog
{
    my ( $dialog ) = @_;

    if ( $::imscpConfig{'DISTRO_ID'} ne 'n/a'
        && $::imscpConfig{'DISTRO_CODENAME'} ne 'n/a'
        && $::imscpConfig{'DISTRO_ID'} =~ /^(?:de(?:bi|vu)an|ubuntu)$/
    ) {
        unless ( -f "$FindBin::Bin/autoinstaller/Packages/"
            . $::imscpConfig{'DISTRO_ID'} . '-'
            . $::imscpConfig{'DISTRO_CODENAME'} . '.xml'
        ) {
            $dialog->error( <<"EOF" );
\\Z1Distribution not supported\\Zn

We are sorry but your \\Zb@{ [ ucfirst $::imscpConfig{'DISTRO_ID'} ] }\\ZB version is not supported. This can be due to one of the following reasons:

 - Your @{ [ ucfirst $::imscpConfig{'DISTRO_ID'} ] } version has reached its end of life and its support has been dropped
 - Your @{ [ ucfirst $::imscpConfig{'DISTRO_ID'} ] } version has not been released yet and its support hasn't been added yet
 - Your @{ [ ucfirst $::imscpConfig{'DISTRO_ID'} ] } version is a non-LTS Ubuntu version
EOF
            return 50;
        }
    } else {
        $dialog->error( <<"EOF" );
\\Z1Operating system not supported\\Zn

We are sorry but your operating system is not supported.

At this time, only \\ZuDebian\\Zn, \\ZuDevuan\\Zn and \\ZuUbuntu\\Zn operating systems are supported.
EOF
        return 50;
    }

    20;
}

=item prepareDistFiles( )

 Prepare distribution files
 
 Return int 0 on success, other on failure

=cut

sub prepareDistFiles
{
    my $rs = _buildLayout();
    $rs ||= _buildConfigFiles();
    $rs ||= _buildEngineFiles();
    $rs ||= _buildFrontendFiles();
    $rs ||= _compileDaemon();
    $rs ||= _removeObsoleteFiles();
    $rs ||= _savePersistentData();
    return $rs if $rs;

    eval {
        # Clean build directory
        find(
            sub {
                return unless $_ eq '.gitkeep';
                unlink or die( sprintf(
                    "Couldn't remove %s file: %s", $File::Find::name, $!
                ));
            },
            $::{'INST_PREF'}
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item writeMasterConfigFile( )

 Write master configuration
 
 Return int 0 on success, other on failure

=cut

sub writeMasterConfigFile
{
    eval {
        my %confMap = (
            imscp    => \%::imscpConfig,
            imscpOld => \%::imscpOldConfig
        );

        # Write configuration
        while ( my ( $name, $config ) = each %confMap ) {
            if ( $name eq 'imscpOld' ) {
                local $UMASK = 027;
                iMSCP::File->new(
                    filename => "$::{'SYSTEM_CONF'}/$name.conf"
                )->save();
            }

            tie my %config, 'iMSCP::Config', fileName => "$::{'SYSTEM_CONF'}/$name.conf";
            @config{ keys %{ $config } } = values %{ $config };
            untie %config;
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _buildLayout( )

 Build layout

 Return int 0 on success, other on failure

=cut

sub _buildLayout
{
    my $distroLayout = "$FindBin::Bin/autoinstaller/Layout/"
        . $::imscpConfig{'DISTRO_ID'} . '.xml';
    my $defaultLayout = "$FindBin::Bin/autoinstaller/Layout/Debian.xml";

    _processXmlFile( -f $distroLayout ? $distroLayout : $defaultLayout );
}

=item _buildConfigFiles( )

 Build configuration files

 Return int 0 on success, other on failure

=cut

sub _buildConfigFiles
{
    my $distroConfigDir = "$FindBin::Bin/configs/$::imscpConfig{'DISTRO_ID'}";
    my $defaultConfigDir = "$FindBin::Bin/configs/debian";
    my $confDir = -d $distroConfigDir ? $distroConfigDir : $defaultConfigDir;

    local $CWD = $confDir;
    my $file = -f "$distroConfigDir/install.xml"
        ? "$distroConfigDir/install.xml" : "$defaultConfigDir/install.xml";
    my $rs = _processXmlFile( $file );
    return $rs if $rs;

    for my $dir ( iMSCP::Dir->new(
        dirname => $defaultConfigDir
    )->getDirs() ) {
        # Override sub config dir path if it is available in selected distro,
        # else set it to default path
        $confDir = -d "$distroConfigDir/$dir"
            ? "$distroConfigDir/$dir"
            : "$defaultConfigDir/$dir";

        local $CWD = $confDir;

        $file = -f "$distroConfigDir/$dir/install.xml"
            ? "$distroConfigDir/$dir/install.xml"
            : "$defaultConfigDir/$dir/install.xml";

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

    for my $dir (
        iMSCP::Dir->new( dirname => "$FindBin::Bin/engine" )->getDirs()
    ) {
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
    iMSCP::Dir->new(
        dirname => "$FindBin::Bin/gui"
    )->rcopy(
        "$::{'SYSTEM_ROOT'}/gui", { preserve => 'no' }
    );
}

=item _compileDaemon( )

 Compile daemon

 Return int 0 on success, other on failure

=cut

sub _compileDaemon
{
    local $CWD = "$FindBin::Bin/daemon";

    my $rs = execute( 'make clean imscp_daemon', \my $stdout, \my $stderr );
    debug( $stdout ) if length $stdout;
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

    # Move old skeleton directory to new location
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'CONF_DIR'}/apache/skel"
    )->rcopy(
        "$::imscpConfig{'CONF_DIR'}/skel", { preserve => 'no' }
    ) if -d "$::imscpConfig{'CONF_DIR'}/apache/skel";

    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'CONF_DIR'}/skel"
    )->rcopy(
        "$destdir$::imscpConfig{'CONF_DIR'}/skel", { preserve => 'no' }
    ) if -d "$::imscpConfig{'CONF_DIR'}/skel";

    # Move old listener files to new location
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'CONF_DIR'}/hooks.d"
    )->rcopy(
        "$::imscpConfig{'CONF_DIR'}/listeners.d", { preserve => 'no' }
    ) if -d "$::imscpConfig{'CONF_DIR'}/hooks.d";

    # Save ISP logos (older location)
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/themes/user_logos"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/ispLogos",
        { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/themes/user_logos";

    # Save ISP logos (new location)
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/ispLogos"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/ispLogos",
        { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/ispLogos";

    # Save GUI logs
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/logs"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/logs",
        { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/logs";

    # Save persistent data
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent",
        { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent";

    # Save RainLoop data directory to new location if any
    if ( !-l "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/rainloop" &&
        -d "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/rainloop/data"
    ) {
        iMSCP::Dir->new(
            dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/public/tools/rainloop/data"
        )->rcopy(
            "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/rainloop",
            { preserve => 'no' }
        );

        my $dataDir;
        if ( -d "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/rainloop/_data_11c052c218cd2a2febbfb268624efdc1" ) {
            $dataDir = "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/rainloop/_data_11c052c218cd2a2febbfb268624efdc1";
        } else {
            $dataDir = "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/rainloop/_data_";
        }

        if ( -d "$dataDir/_default_" ) {
            iMSCP::Dir->new(
                dirname => "$dataDir/_default_"
            )->moveDir(
                "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/rainloop/imscp"
            );
        }

        iMSCP::Dir->new( dirname => $dataDir )->remove();
    }

    # Save software (older path ./gui/data/software) to new path
    # (./gui/data/persistent/software)
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/softwares"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/softwares",
        { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/data/softwares";

    # Save vendor data
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/vendor", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/vendor";

    # Save GUI bin directory
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/bin"
    )->rcopy(
        "$destdir$::imscpConfig{'GUI_ROOT_DIR'}/bin", { preserve => 'no' }
    ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/bin";

    # Save plugins
    iMSCP::Dir->new(
        dirname => "$::imscpConfig{'PLUGINS_DIR'}"
    )->rcopy(
        "$destdir$::imscpConfig{'PLUGINS_DIR'}", { preserve => 'no' }
    ) if -d $::imscpConfig{'PLUGINS_DIR'};

    # Save package handlers if any
    if ( -d "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package" ) {
        for my $packageTypeDir (
            iMSCP::Dir->new(
                dirname => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package"
            )->getDirs()
        ) {
            for my $packageDir (
                iMSCP::Dir->new(
                    dirname => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir"
                )->getDirs()
            ) {
                for my $handlerFile (
                    iMSCP::Dir->new(
                        dirname  => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir/$packageDir",
                        fileType => qr/^Handler\.pm/
                    )->getFiles()
                ) {
                    my $rs = iMSCP::File->new(
                        filename => "$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir/$packageDir/$handlerFile"
                    )->copyFile(
                        "$destdir$::imscpConfig{'ENGINE_ROOT_DIR'}/PerlLib/Package/$packageTypeDir/$packageDir/$handlerFile"
                    );
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
        "$::imscpConfig{'LOG_DIR'}/imscp-arpl-msgr",
        '/var/local/imscp/.composer',
        '/var/local/imscp/packages',
        "$::imscpConfig{'CONF_DIR'}/pma",
        "$::imscpConfig{'CONF_DIR'}/roundcube",
        "$::imscpConfig{'CONF_DIR'}/rainloop"
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
        # Due to a mistake in previous i-MSCP versions (Upstart conffile copied
        # into systemd confdir)
        "/etc/systemd/system/php5-fpm.override",
        "/etc/init/php5-fpm.override", # Removed in 1.4.x
        "$::imscpConfig{'CONF_DIR'}/imscp.old.conf",
        '/usr/local/lib/imscp_panel/imscp_panel_checkconf', # Removed in 1.4.x,
        '/var/local/imscp/composer.phar'
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
 Return int 0 on success, other on failure

=cut

sub _processXmlFile
{
    my ( $file ) = @_;

    unless ( -f $file ) {
        error( sprintf( "File %s doesn't exists", $file ));
        return 1;
    }

    my $data = eval {
        require XML::Simple;
        XML::Simple->new( ForceArray => TRUE, ForceContent => TRUE )->XMLin(
            $file, VarAttr => 'export'
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Permissions hardening
    local $UMASK = 027;

    # Process xml 'folders' nodes if any
    for my $node ( @{ $data->{'folders'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        $::{$node->{'export'}} = $node->{'content'}
            if defined $node->{'export'};
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

    # Process xml 'create_file' nodes if any
    for my $node ( @{ $data->{'create_file'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        my $rs = _createFile( $node );
        return $rs if $rs;
    }

    # Process xml 'chmod_file' nodes if any
    for my $node ( @{ $data->{'chmod_file'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        my $rs = _chmodFile( $node ) if $node->{'content'};
        return $rs if $rs;
    }

    # Process xml 'chmod_file' nodes if any
    for my $node ( @{ $data->{'chown_file'} } ) {
        $node->{'content'} = _expandVars( $node->{'content'} );
        my $rs = _chownFile( $node );
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
    if ( defined $::{'INST_PREF'} && $::{'INST_PREF'} eq $data->{'content'} ) {
        $dir->remove();
    }

    $dir->make( {
        user  => defined $data->{'user'}
            ? _expandVars( $data->{'owner'} ) : undef,
        group => defined $data->{'group'}
            ? _expandVars( $data->{'group'} ) : undef,
        mode  => defined $data->{'mode'}
            ? oct( $data->{'mode'} ) : undef
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
    my $distribution = $::imscpConfig{'DISTRO_ID'};
    ( my $alternativeFolder = $CWD ) =~ s/$distribution/debian/;
    my $source = -f $name ? $name : "$alternativeFolder/$name";

    if ( -d $source ) {
        iMSCP::Dir->new( dirname => $source )->rcopy(
            "$path/$name", { preserve => 'no' }
        );
    } else {
        my $rs = iMSCP::File->new( filename => $source )->copyFile(
            $path, { preserve => 'no' }
        );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'user'}
        || defined $data->{'group'} || defined $data->{'mode'};

    my $file = iMSCP::File->new(
        filename => -e "$path/$name" ? "$path/$name" : $path
    );

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
    my ( $data ) = @_;

    my ( $name, $path ) = fileparse( $data->{'content'} );

    if ( -d $name ) {
        iMSCP::Dir->new( dirname => $name )->rcopy(
            "$path/$name", { preserve => 'no' }
        );
    } else {
        my $rs = iMSCP::File->new( filename => $name )->copyFile(
            $path, { preserve => 'no' }
        );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'user'}
        || defined $data->{'group'} || defined $data->{'mode'};

    my $file = iMSCP::File->new(
        filename => -e "$path/$name" ? "$path/$name" : $path
    );

    if ( defined $data->{'user'} || defined $data->{'group'} ) {
        my $rs = $file->owner(
            ( defined $data->{'user'} ? _expandVars( $data->{'user'} ) : -1 ),
            ( defined $data->{'group'} ? _expandVars( $data->{'group'} ) : -1 )
        );
        return $rs if $rs;
    }

    return 0 unless defined $data->{'mode'};

    $file->mode( oct( $data->{'mode'} )) if defined $data->{'mode'};
}

=item _createFile( \%data )

 Create a file

 Param hashref %data
 Return int 0 on success, other on failure

=cut

sub _createFile
{
    my ( $data ) = @_;

    iMSCP::File->new( filename => $data->{'content'} )->save();
}

=item _chownFile( )

 Change file/directory owner and/or group recursively

 Return int 0 on success, other on failure

=cut

sub _chownFile
{
    my ( $data ) = @_;

    return 0 unless defined $data->{'owner'} && defined $data->{'group'};

    my $rs = execute(
        "chown $data->{'owner'}:$data->{'group'} $data->{'content'}",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
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
    my ( $data ) = @_;

    return 0 unless defined $data->{'mode'};

    my $rs = execute(
        "chmod $data->{'mode'} $data->{'content'}", \my $stdout, \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
