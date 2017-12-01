=head1 NAME

 Servers::php - i-MSCP PHP server implementation

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

package Servers::php;

use strict;
use warnings;
use File::Basename;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::ProgramFinder;
use iMSCP::Service;
use Servers::httpd;
use parent 'Common::SingletonClass';

# php server instance
my $instance;

=head1 DESCRIPTION

 i-MSCP PHP server implementation.

=head1 PUBLIC METHODS

=over 4

=item factory( )

 Create and return php server instance

 Return Servers::php

=cut

sub factory
{
    $instance ||= __PACKAGE__->getInstance();
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpPreinstall' );
    return $rs if $rs;

    eval {
        my $serviceMngr = iMSCP::Service->getInstance();

        # Disable PHP session cleaner services as we don't rely on them
        for my $service( qw/ phpsessionclean phpsessionclean.timer / ) {
            next unless $serviceMngr->hasService( $service );
            $serviceMngr->stop( $service );
            $serviceMngr->disable( $service );
        }

        for my $phpVersion( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
            next unless $phpVersion =~ /^[\d.]+$/;

            my $service = "php$phpVersion-fpm";
            if ( $serviceMngr->hasService( $service ) ) {
                $self->stop( $phpVersion ) == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );

                if ( $main::imscpConfig{'HTTPD_PACKAGE'} ne 'Servers::httpd::apache_php_fpm'
                    || $self->{'config'}->{'PHP_VERSION'} ne $phpVersion
                ) {
                    $serviceMngr->disable( $service );
                }
            }

            # Reset pool configuration directories
            for my $conffile( grep !/www\.conf$/, glob "/etc/php/$phpVersion/fpm/pool.d/*.conf" ) {
                iMSCP::File->new( filename => $conffile )->delFile() == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );
            }
        }

        $self->_guessVariablesForSelectedPhpAlternative();
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpPreinstall' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpInstall' );
    return $rs if $rs;

    eval {
        $self->{'httpd'}->setData(
            {
                HTTPD_USER                          => $self->{'httpd'}->{'config'}->{'HTTPD_USER'} || 'www-data',
                HTTPD_GROUP                         => $self->{'httpd'}->{'config'}->{'HTTPD_GROUP'} || 'www-data',
                PEAR_DIR                            => $self->{'config'}->{'PHP_PEAR_DIR'} || '/usr/share/php',
                PHP_FPM_LOG_LEVEL                   => $self->{'config'}->{'PHP_FPM_LOG_LEVEL'} || 'error',
                PHP_FPM_EMERGENCY_RESTART_THRESHOLD => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_THRESHOLD'} // 10,
                PHP_FPM_EMERGENCY_RESTART_INTERVAL  => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_INTERVAL'} || '1m',
                PHP_FPM_PROCESS_CONTROL_TIMEOUT     => $self->{'config'}->{'PHP_FPM_PROCESS_CONTROL_TIMEOUT'} || '60s',
                PHP_FPM_PROCESS_MAX                 => $self->{'config'}->{'PHP_FPM_PROCESS_MAX'} // 0,
                PHP_FPM_RLIMIT_FILES                => $self->{'config'}->{'PHP_FPM_RLIMIT_FILES'} // 4096,
                PHP_OPCODE_CACHE_ENABLED            => $self->{'config'}->{'PHP_OPCODE_CACHE_ENABLED'} // 0,
                PHP_OPCODE_CACHE_MAX_MEMORY         => $self->{'config'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'} // 32,
                PHP_APCU_CACHE_ENABLED              => $self->{'config'}->{'PHP_APCU_CACHE_ENABLED'} // 0,
                PHP_APCU_CACHE_MAX_MEMORY           => $self->{'config'}->{'PHP_APCU_CACHE_MAX_MEMORY'} // 32,
                TIMEZONE                            => $main::imscpConfig{'TIMEZONE'}
            }
        );

        for my $phpVersion( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
            next unless $phpVersion =~ /^[\d.]+$/;
            # FPM
            $self->{'httpd'}->setData(
                {
                    PHP_CONF_DIR_PATH     => "/etc/php/$phpVersion/fpm/*.conf",
                    PHP_FPM_POOL_DIR_PATH => "/etc/php/$phpVersion/fpm/pool.d",
                    PHP_VERSION           => $phpVersion
                }
            );
            $rs = $self->{'httpd'}->buildConfFile( "$self->{'phpCfgDir'}/fpm/php.ini", {}, { destination => "/etc/php/$phpVersion/fpm/php.ini" } );
            $rs ||= $self->{'httpd'}->buildConfFile(
                "$self->{'phpCfgDir'}/fpm/php-fpm.conf", {}, { destination => "/etc/php/$phpVersion/fpm/php-fpm.conf" }
            );
            $rs ||= $self->{'httpd'}->buildConfFile(
                "$self->{'phpCfgDir'}/fpm/pool.conf.default", {}, { destination => "/etc/php/$phpVersion/fpm/pool.d/www.conf" }
            );
            # ITK
            $rs ||= $self->{'httpd'}->buildConfFile(
                "$self->{'phpCfgDir'}/apache/php.ini", {}, { destination => "/etc/php/$phpVersion/apache2/php.ini" }
            );
            $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpInstall' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpPostInstall' );
    return $rs if $rs;

    eval {
        if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::apache_php_fpm' ) {
            for my $phpVersion( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
                # We enable and start only selected PHP alternative as other PHP versions are managed through
                # OPTIONAL PhpSwitcher plugin
                next unless $phpVersion =~ /^[\d.]+$/ && $self->{'config'}->{'PHP_VERSION'} eq $phpVersion;

                iMSCP::Service->getInstance()->enable( "php$phpVersion-fpm" );

                $self->{'eventManager'}->register(
                    'beforeSetupRestartServices',
                    sub {
                        push @{$_[0]}, [ sub { $self->start(); }, "PHP-FPM $phpVersion" ];
                        0;
                    },
                    3
                ) == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }
        }
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpPostInstall' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpUninstall' );
    return $rs if $rs;

    for ( iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
        next unless /^[\d.]+$/ && -f "/etc/init/php$_-fpm.override";
        $rs = iMSCP::File->new( filename => "/etc/init/php$_-fpm.override" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterPhpUninstall' );
}

=item start( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Start PHP-FPM instance

 Param string $version OPTIONAL PHP-FPM version to start
 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self, $version) = @_;

    $version ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmStart', $version );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( "php$version-fpm" ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpFpmStart', $version );
}

=item stop( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Stop PHP-FPM instance

 Param string $version OPTIONAL PHP-FPM version to stop
 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self, $version) = @_;

    $version ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmStop', $version );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( "php$version-fpm" ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpFpmStop', $version );
}

=item reload( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Reload PHP-FPM instance

 Param string $version OPTIONAL PHP-FPM version to reload
 Return int 0

=cut

sub reload
{
    my ($self, $version) = @_;

    $version ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmReload', $version );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( "php$version-fpm" ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpFpmReload', $version );
    0;
}

=item restart( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Restart PHP-FPM instance

 Param string $version OPTIONAL PHP-FPM version to restart
 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self, $version) = @_;

    $version ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmRestart', $version );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( "php$version-fpm" ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpFpmRestart', $version );
}

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    70;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::httpd::apache_php_fpm

=cut

sub _init
{
    my ($self) = @_;

    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'httpd'} = Servers::httpd->factory();
    $self->{'phpCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php";
    $self->_mergeConfig( $self->{'phpCfgDir'}, 'php.data' ) if -f "$self->{'phpCfgDir'}/php.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'phpCfgDir'}/php.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => ( defined $main::execmode && $main::execmode eq 'setup' );
    $self;
}

=item _mergeConfig( $confDir, $confName )

 Merge distribution configuration with production configuration

 Param string $confDir Configuration directory
 Param string $confName Configuration filename
 Die on failure

=cut

sub _mergeConfig
{
    my (undef, $confDir, $confName) = @_;

    if ( -f "$confDir/$confName" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$confDir/$confName.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$confDir/$confName", readonly => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$confDir/$confName.dist" )->moveFile( "$confDir/$confName" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _guessVariablesForSelectedPhpAlternative( )

 Guess variable for the selected PHP alternative

 Return int 0 on success, die on failure

=cut

sub _guessVariablesForSelectedPhpAlternative
{
    my ($self) = @_;

    my $phpPath = iMSCP::ProgramFinder::find( 'php' ) or die( "Couldn't find the PHP (CLI) command in search path" );

    my ($phpVersion) = `$phpPath -nv 2> /dev/null` =~ /^PHP\s+([\d.]+)/ or die( "Couldn't guess system PHP version" );

    $self->{'config'}->{'PHP_VERSION_FULL'} = $phpVersion;
    $phpVersion =~ s/\.\d+$//;
    $self->{'config'}->{'PHP_VERSION'} = $phpVersion;

    my ($phpConfDir) = `$phpPath -ni 2> /dev/null | grep '(php.ini) Path'` =~ /([^\s]+)$/ or die(
        "Couldn't guess system PHP configuration directory path"
    );

    my $phpConfBaseDir = dirname( $phpConfDir );
    $self->{'config'}->{'PHP_CONF_DIR_PATH'} = $phpConfBaseDir;
    $self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'} = "$phpConfBaseDir/fpm/pool.d";

    unless ( -d $self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'} ) {
        $self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'} = '';
        die( sprintf( "Couldn't guess `%s' PHP configuration parameter value: directory doesn't exist.", $_ ));
    }

    $self->{'config'}->{'PHP_CLI_BIN_PATH'} = iMSCP::ProgramFinder::find( "php$phpVersion" );
    $self->{'config'}->{'PHP_FCGI_BIN_PATH'} = iMSCP::ProgramFinder::find( "php-cgi$phpVersion" );
    $self->{'config'}->{'PHP_FPM_BIN_PATH'} = iMSCP::ProgramFinder::find( "php-fpm$phpVersion" );

    for ( qw/ PHP_CLI_BIN_PATH PHP_FCGI_BIN_PATH PHP_FPM_BIN_PATH / ) {
        $self->{'config'}->{$_} ne '' or die( sprintf( "Couldn't guess `%s' PHP configuration parameter value.", $_ ));
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__DATA__
#!/usr/bin/perl

use strict;
use warnings;
use lib '/usr/local/src/imscp/engine/PerlLib', '/usr/local/src/imscp/engine/PerlVendor';
use iMSCP::Bootstrapper;
use iMSCP::Debug qw/ setVerbose output /;
use iMSCP::EventManager;
use Servers::php;

setVerbose(1);

iMSCP::Bootstrapper->getInstance()->boot();

my $phpSrv = Servers::php->factory();
$phpSrv->preinstall();
$phpSrv->install();
$phpSrv->postinstall();

iMSCP::EventManager->getInstance()->trigger( 'beforeSetupRestartServices', \my @stack );

for(@stack) {
    print output("Starting $_->[1] service...", 'info');
    $_->[0]->();
}

1;
__END__
