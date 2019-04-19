=head1 NAME

 Package::FrontEnd - i-MSCP FrontEnd package

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

package Package::FrontEnd;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Crypt qw/ apr1MD5 randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ error debug getMessageByType /;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Net;
use iMSCP::OpenSSL;
use iMSCP::ProgramFinder;
use iMSCP::Rights 'setRights';
use iMSCP::Stepper qw/ startDetail endDetail step /;
use iMSCP::SystemUser;
use iMSCP::TemplateParser qw/ replaceBloc process getBloc /;
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use Servers::httpd;
use Servers::mta;
use Servers::named;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Provides i-MSCP frontEnd.

=head1 PUBLIC METHODS

=over 4

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    100;
}

=item registerSetupListeners( \%events )

 Register setup event listeners

 Param iMSCP::EventManager \%events
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ( $self, $events ) = @_;

    my $rs = $events->registerOne( 'beforeSetupDialog', sub {
        push @{ $_[0] },
            sub { $self->_dialogForMasterAdminCredentials( @_ ) },
            sub { $self->_dialogForMasterAdminEmail( @_ ) },
            sub { $self->_dialogForHostname( @_ ) },
            sub { $self->_dialogForSSL( @_ ) },
            sub { $self->_dialogForHttpPorts( @_ ) };
        0;
    } );
    $rs ||= $events->registerOne( 'beforeSetupPreInstallServers', sub {
        $rs = setRights( $::imscpConfig{'ROOT_DIR'}, {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ROOT_GROUP'},
            mode  => '0755'
        } );
        $rs ||= $self->_createMasterWebUser();
        $rs ||= $self->setGuiPermissions();
    }, 20 );
    $rs ||= $events->registerOne( 'beforeSetupPreInstallServers', sub {
        return 0 if iMSCP::Getopt->skipComposerUpdate;

        eval {
            my $composer = iMSCP::Composer->new(
                user          => $::imscpConfig{'SYSTEM_USER_PREFIX'}
                    . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
                composer_home => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
            );

            my $stdRoutine = sub {
                return if $_[0] =~ /^package\s+[^\s]+\s+is\s+abandoned/i;
                chomp( $_[0] );
                debug( $_[0] );
                step( undef, <<"EOT", 1, 1 );
Installing/Updating i-MSCP frontEnd PHP dependencies...

$_[0]

Depending on your internet connection speed, this may take few seconds...
EOT
            };

            startDetail;
            $composer->setStdRoutines( $stdRoutine, $stdRoutine );
            $composer->installComposer( $::imscpConfig{'COMPOSER_VERSION'} );
            $composer->clearCache() if iMSCP::Getopt->clearComposerCache;
            $composer->update( TRUE, FALSE, 'imscp/*' );
            endDetail;
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        0;
    } );
}

=item preinstall( )

 Process pre-installation tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndPreInstall' );
    $rs ||= $self->stop();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndPreInstall' );
}

=item install( )

 Process installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndInstall' );
    $rs ||= $self->_setupMasterAdmin();
    $rs ||= $self->_setupSsl();
    $rs ||= $self->_setHttpdVersion();
    $rs ||= $self->_addMasterWebUser();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_copyPhpBinary();
    $rs ||= $self->_buildPhpConfig();
    $rs ||= $self->_buildHttpdConfig();
    $rs ||= $self->_addDnsZone();
    $rs ||= $self->_cleanup();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndInstall' );
}

=item postinstall( )

 Process post-installation tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndPostInstall' );
    return $rs if $rs;

    local $@;
    eval {
        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->enable( $self->{'config'}->{'HTTPD_SNAME'} );
        $serviceMngr->enable( 'imscp_panel' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'events'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [
                sub { $self->start(); },
                'i-MSCP FrontEnd services'
            ];
            0;
        },
        2
    );
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndPostInstall' );
}

=item dpkgPostInvokeTasks( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub dpkgPostInvokeTasks
{
    my ( $self ) = @_;

    if ( -f '/usr/local/sbin/imscp_panel' ) {
        unless ( -f $self->{'config'}->{'PHP_FPM_BIN_PATH'} ) {
            # Cover case where administrator removed the package
            # That should never occurs but...
            my $rs = $self->stopPhpFpm();
            $rs ||= iMSCP::File->new(
                filename => '/usr/local/sbin/imscp_panel'
            )->delFile();
            return $rs;
        }

        my $v1 = $self->getFullPhpVersionFor(
            $self->{'config'}->{'PHP_FPM_BIN_PATH'}
        );
        my $v2 = $self->getFullPhpVersionFor(
            '/usr/local/sbin/imscp_panel'
        );
        if ( $v1 eq $v2 ) {
            debug( sprintf(
                "i-MSCP frontEnd PHP-FPM binary is up-to-date: %s", $v2
            ));
            return 0;
        }

        debug( sprintf(
            "Updating i-MSCP frontEnd PHP-FPM binary from version %s to version %s",
            $v2,
            $v1
        ));
    }

    my $rs = $self->_copyPhpBinary();
    return $rs if $rs || !-f '/usr/local/etc/imscp_panel/php-fpm.conf';

    $rs ||= $self->startPhpFpm();
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndUninstall' );
    $rs ||= $self->_deconfigurePHP();
    $rs ||= $self->_deconfigureHTTPD();
    $rs ||= $self->_deleteMasterWebUser();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndUninstall' );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger(
        'beforeFrontEndSetEnginePermissions'
    );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_CONF_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $::imscpConfig{'ROOT_GROUP'},
        dirmode   => '0755',
        dirmode   => '0644',
        recursive => TRUE
    } );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $::imscpConfig{'ROOT_GROUP'},
        dirmode   => '0755',
        filemode  => '0640',
        recursive => TRUE
    } );
    return $rs if $rs;

    # Temporary directories as provided by nginx package (from Debian Team)
    if ( -d "$self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}" ) {
        $rs = setRights( $self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}, {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ROOT_GROUP'}
        } );

        for my $tmp ( 'body', 'fastcgi', 'proxy', 'scgi', 'uwsgi' ) {
            next unless -d "$self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}/$tmp";

            $rs = setRights(
                "$self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}/$tmp",
                {
                    user      => $self->{'config'}->{'HTTPD_USER'},
                    group     => $self->{'config'}->{'HTTPD_GROUP'},
                    dirmode   => '0700',
                    filemode  => '0640',
                    recursive => TRUE
                }
            );
            $rs ||= setRights(
                "$self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}/$tmp",
                {
                    user  => $self->{'config'}->{'HTTPD_USER'},
                    group => $::imscpConfig{'ROOT_GROUP'},
                    mode  => '0700'
                }
            );
            return $rs if $rs;
        }
    }

    # Temporary directories as provided by nginx package (from nginx Team)
    return 0 unless -d "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}";

    $rs = setRights( $self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}, {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_GROUP'}
    } );
    return $rs if $rs;

    for my $tmp (
        qw/ client_temp fastcgi_temp proxy_temp scgi_temp uwsgi_temp /
    ) {
        next unless -d "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}/$tmp";

        $rs = setRights(
            "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}/$tmp",
            {
                user      => $self->{'config'}->{'HTTPD_USER'},
                group     => $self->{'config'}->{'HTTPD_GROUP'},
                dirmode   => '0700',
                filemode  => '0640',
                recursive => TRUE
            }
        );
        $rs ||= setRights(
            "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}/$tmp",
            {
                user  => $self->{'config'}->{'HTTPD_USER'},
                group => $::imscpConfig{'ROOT_GROUP'},
                mode  => '0700'
            }
        );
        return $rs if $rs;
    }

    $self->{'events'}->trigger( 'afterFrontEndSetEnginePermissions' );
}

=item setGuiPermissions( )

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    my ( $self ) = @_;

    my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'}
        . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    my $rs = $self->{'events'}->trigger( 'beforeFrontendSetGuiPermissions' );
    $rs ||= setRights( $::imscpConfig{'GUI_ROOT_DIR'}, {
        user      => $ug,
        group     => $ug,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/bin", {
        filemode  => '0750',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/bin", {
        filemode  => '0750',
        recursive => TRUE
    } ) if -d "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/bin";
    $rs ||= $self->{'events'}->trigger( 'afterFrontendSetGuiPermissions' );
}

=item addUser( \%data )

 Process addUser tasks

 Whenever a customer's Web user is added, we need add the control panel Web user to its group.

 Param hash \%data user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my $data = $_[1];

    return 0 if $data->{'STATUS'} eq 'tochangepwd';

    iMSCP::SystemUser->new(
        username => $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
    )->addToGroup(
        $data->{'GROUP'}
    );
}

=item enableSites( @sites )

 Enable the given site(s)

 Param array @sites List of sites to enable
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
    my ( $self, @sites ) = @_;

    my $rs = $self->{'events'}->trigger(
        'beforeEnableFrontEndSites', \@sites
    );
    return $rs if $rs;

    for my $site ( @sites ) {
        my $target = "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site";
        my $link = $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'} . '/'
            . basename( $site, '.conf' );

        unless ( -f $target ) {
            error( sprintf( "Site '%s' doesn't exist", $site ));
            return 1;
        }

        next if -l $link;

        unless (
            symlink( File::Spec->abs2rel(
                $target,
                $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}
            ), $link )
        ) {
            error( sprintf( "Couldn't enable '%s' site: %s", $site, $! ));
            return 1;
        }

        $self->{'reload'} = TRUE;
    }

    $self->{'events'}->trigger( 'afterEnableFrontEndSites', @sites );
}

=item disableSites( @sites )

 Disable the given site(s)

 Param array @sites List of sites to disable
 Return int 0 on success, other on failure

=cut

sub disableSites
{
    my ( $self, @sites ) = @_;

    my $rs = $self->{'events'}->trigger(
        'beforeDisableFrontEndSites', \@sites
    );
    return $rs if $rs;

    for my $site ( @sites ) {
        my $link = $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'} . '/'
            . basename( $site, '.conf' );
        next unless -l $link;

        $rs = iMSCP::File->new( filename => $link )->delFile();
        return $rs if $rs;

        $self->{'reload'} = TRUE;
    }

    $self->{'events'}->trigger( 'afterDisableFrontEndSites', @sites );
}

=item start( )

 Start frontEnd

 Return int 0 on success, other on failure

=cut

sub start
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndStart' );
    $rs ||= $self->startPhpFpm();
    $rs ||= $self->startNginx();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndStart' );
}

=item stop( )

 Stop frontEnd

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndStop' );
    $rs ||= $self->stopPhpFpm();
    $rs ||= $self->stopNginx();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndStop' );
}

=item reload( )

 Reload frontEnd

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndReload' );
    $rs ||= $self->reloadPhpFpm();
    $rs ||= $self->reloadNginx();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndReload' );
}

=item restart( )

 Restart frontEnd

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndRestart' );
    $rs ||= $self->restartPhpFpm();
    $rs ||= $self->restartNginx();
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndRestart' );
}

=item startNginx( )

 Start frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub startNginx
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndStartNginx' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start(
        $self->{'config'}->{'HTTPD_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndStartNginx' );
}

=item stopNginx( )

 Stop frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub stopNginx
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndStopNginx' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop(
        "$self->{'config'}->{'HTTPD_SNAME'}"
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndStop' );
}

=item reloadNginx( )

 Reload frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub reloadNginx
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndReloadNginx' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload(
        $self->{'config'}->{'HTTPD_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndReloadNginx' );
}

=item restartNginx( )

 Restart frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub restartNginx
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndRestartNginx' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart(
        $self->{'config'}->{'HTTPD_SNAME'}
    ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndRestartNginx' );
}

=item startPhpFpm( )

 Start frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub startPhpFpm
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndStartPhpFpm' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->start( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndStartPhpFpm' );
}

=item stopPhpFpm( )

 Stop frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub stopPhpFpm
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndStopPhpFpm' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->stop( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndStopPhpFpm' );
}

=item reloadPhpFpm( )

 Reload frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub reloadPhpFpm
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndReloadPhpFpm' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->reload( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndReloadPhpFpm' );
}

=item restartPhpFpm( )

 Restart frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub restartPhpFpm
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndRestartPhpFpm' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'events'}->trigger( 'afterFrontEndRestartPhpFpm' );
}

=item buildConfFile( $file [, \%tplVars = { } [, \%options = { } ] ] )

 Build the given configuration file

 Param string $file Absolute config file path or config filename relative to the nginx configuration directory
 Param hash \%tplVars OPTIONAL Template variables
 Param hash \%options OPTIONAL Options such as destination, mode, user and group for final file
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
    my ( $self, $file, $tplVars, $options ) = @_;

    $tplVars ||= {};
    $options ||= {};

    my ( $filename, $path ) = fileparse( $file );
    my $rs = $self->{'events'}->trigger(
        'onLoadTemplate', 'frontend', $filename, \my $cfgTpl, $tplVars
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $file = "$self->{'cfgDir'}/$file" unless -d $path && $path ne './';
        $cfgTpl = iMSCP::File->new( filename => $file )->get();
        return 1 unless defined $cfgTpl;
    }

    $rs = $self->{'events'}->trigger(
        'beforeFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options
    );
    return $rs if $rs;

    $cfgTpl = $self->_buildConf( $cfgTpl, $filename, $tplVars );
    $cfgTpl =~ s/\n{2,}/\n\n/g; # Remove any duplicate blank lines

    $rs = $self->{'events'}->trigger(
        'afterFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options
    );
    return $rs if $rs;

    $options->{'destination'} ||=
        "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$filename";

    my $fileHandler = iMSCP::File->new(
        filename => $options->{'destination'}
    );
    $rs = $fileHandler->set( $cfgTpl );
    $rs ||= $fileHandler->save();
    $rs ||= $fileHandler->owner(
        ( $options->{'user'}
            ? $options->{'user'}
            : $::imscpConfig{'ROOT_USER'}
        ),
        ( $options->{'group'}
            ? $options->{'group'}
            : $::imscpConfig{'ROOT_GROUP'}
        )
    );
    $rs ||= $fileHandler->mode(
        $options->{'mode'} ? $options->{'mode'} : 0644
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Package::FrontEnd

=cut

sub _init
{
    my ( $self ) = @_;

    @{ $self }{qw/ start reload restart /} = ( FALSE, FALSE, FALSE );
    $self->{'events'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/frontend";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/frontend.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/frontend.data",
        readonly    => !( defined $::execmode && $::execmode eq 'setup' ),
        nodeferring => ( defined $::execmode && $::execmode eq 'setup' );
    $self;
}

=item _mergeConfig( )

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ( $self ) = @_;

    if ( -f "$self->{'cfgDir'}/frontend.data" ) {
        tie my %newConfig, 'iMSCP::Config',
            fileName => "$self->{'cfgDir'}/frontend.data.dist";
        tie my %oldConfig, 'iMSCP::Config',
            fileName => "$self->{'cfgDir'}/frontend.data", readonly => TRUE;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/frontend.data.dist"
    )->moveFile( "$self->{'cfgDir'}/frontend.data" ) == 0 or die(
        getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ) || 'Unknown error'
    );
}

# Installation routines

=item _dialogForMasterAdminCredentials( \%dialog )

 Setup dialog for the master administrator credentials

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub _dialogForMasterAdminCredentials
{
    my ( undef, $dialog ) = @_;

    my ( $username, $password ) = ( '', '' );

    my $db = iMSCP::Database->factory();

    local $@;
    eval { $db->useDatabase( ::setupGetQuestion( 'DATABASE_NAME' )); };
    $db = undef if $@;

    if ( iMSCP::Getopt->preseed ) {
        $username = ::setupGetQuestion( 'ADMIN_LOGIN_NAME' );
        $password = ::setupGetQuestion( 'ADMIN_PASSWORD' );
    } elsif ( $db ) {
        local $@;
        my $row = eval {
            my $dbh = $db->getRawDb();
            local $dbh->{'RaiseError'} = TRUE;
            $dbh->selectrow_hashref(
                "
                    SELECT admin_name, admin_pass
                    FROM admin
                    WHERE created_by = 0
                    AND admin_type = 'admin'
                "
            );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        } elsif ( $row ) {
            $username = $row->{'admin_name'} // '';
            $password = $row->{'admin_pass'} // '';
        }
    }

    ::setupSetQuestion( 'ADMIN_OLD_LOGIN_NAME', $username );

    if ( $::reconfigure =~ /^(?:admin|admin_credentials|all|forced)$/
        || !isValidUsername( $username )
        || $password eq ''
    ) {
        $password = '';
        my ( $rs, $msg ) = ( 0, '' );

        do {
            ( $rs, $username ) = $dialog->inputbox( <<"EOF", $username || 'admin' );

Please enter a username for the master administrator:$msg
EOF
            $msg = '';
            if ( !isValidUsername( $username ) ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            } elsif ( $db ) {
                local $@;
                my $row = eval {
                    my $dbh = $db->getRawDb();
                    local $dbh->{'RaiseError'} = TRUE;
                    $dbh->selectrow_hashref(
                        '
                            SELECT 1
                            FROM admin
                            WHERE admin_name = ?
                            AND created_by <> 0
                        ',
                        undef,
                        $username
                    );
                };
                if ( $@ ) {
                    error( $@ );
                    return 1;
                } elsif ( $row ) {
                    $msg = '\n\n\\Z1This username is not available.\\Zn\n\nPlease try again:'
                }
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        do {
            ( $rs, $password ) = $dialog->inputbox( <<"EOF", randomStr( 16, iMSCP::Crypt::ALNUM ));

Please enter a password for the master administrator:$msg
EOF
            $msg = isValidPassword( $password )
                ? ''
                : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    } else {
        $password = '' unless iMSCP::Getopt->preseed
    }

    ::setupSetQuestion( 'ADMIN_LOGIN_NAME', $username );
    ::setupSetQuestion( 'ADMIN_PASSWORD', $password );
    0;
}

=item _dialogForMasterAdminEmail( \%dialog )

 Setup dialog for the master administrator email address

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub _dialogForMasterAdminEmail
{
    my ( undef, $dialog ) = @_;

    my $email = ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' );

    if ( $::reconfigure =~ /^(?:admin|admin_email|all|forced)$/
        || !isValidEmail( $email )
    ) {
        my ( $rs, $msg ) = ( 0, '' );
        do {
            ( $rs, $email ) = $dialog->inputbox( <<"EOF", $email );

Please enter an email address for the master administrator:$msg
EOF
            $msg = isValidEmail( $email )
                ? ''
                : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'DEFAULT_ADMIN_ADDRESS', $email );
    0;
}

=item _dialogForHostname( \%dialog )

 Setup dialog for the frontEnd hostname

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub _dialogForHostname
{
    my ( undef, $dialog ) = @_;

    my $domainName = ::setupGetQuestion( 'BASE_SERVER_VHOST' );

    if ( $::reconfigure =~ /^(?:panel|panel_hostname|hostnames|all|forced)$/
        || !isValidDomain( $domainName )
    ) {
        unless ( $domainName ) {
            my @domainLabels = split /\./, ::setupGetQuestion( 'SERVER_HOSTNAME' );
            $domainName = 'panel.' . join( '.', @domainLabels[1 .. $#domainLabels] );
        }

        $domainName = idn_to_unicode( $domainName, 'utf-8' );
        my ( $rs, $msg ) = ( 0, '' );
        do {
            ( $rs, $domainName ) = $dialog->inputbox( <<"EOF", $domainName, 'utf-8' );

Please enter a domain name for the control panel:$msg
EOF
            $msg = isValidDomain( $domainName )
                ? ''
                : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'BASE_SERVER_VHOST', idn_to_ascii( $domainName, 'utf-8' ));
    0;
}

=item _dialogForSSL( \%dialog )

 Setup dialog for SSL

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub _dialogForSSL
{
    my ( undef, $dialog ) = @_;

    my $domainName = ::setupGetQuestion( 'BASE_SERVER_VHOST' );
    my $domainNameUnicode = idn_to_unicode( $domainName, 'utf-8' );
    my $sslEnabled = ::setupGetQuestion( 'PANEL_SSL_ENABLED' );
    my $selfSignedCertificate = ::setupGetQuestion( 'PANEL_SSL_SELFSIGNED_CERTIFICATE', 'no' );
    my $privateKeyPath = ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PATH', '/root' );
    my $passphrase = ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PASSPHRASE' );
    my $certificatePath = ::setupGetQuestion( 'PANEL_SSL_CERTIFICATE_PATH', '/root' );
    my $caBundlePath = ::setupGetQuestion( 'PANEL_SSL_CA_BUNDLE_PATH', '/root' );
    my $baseServerVhostPrefix = ::setupGetQuestion( 'BASE_SERVER_VHOST_PREFIX', 'http://' );
    my $openSSL = iMSCP::OpenSSL->new();

    if ( $::reconfigure =~ /^(?:panel|panel_ssl|ssl|all|forced)$/
        || $sslEnabled !~ /^(?:yes|no)$/
        || ( $sslEnabled eq 'yes' && $::reconfigure =~ /^(?:panel_hostname|hostnames)$/ )
    ) {
        my $rs = $dialog->yesno( <<'EOF', $sslEnabled eq 'no' ? 1 : 0 );

Do you want to enable SSL for the control panel?
EOF
        if ( $rs == 0 ) {
            $sslEnabled = 'yes';
            $rs = $dialog->yesno( <<"EOF", $selfSignedCertificate eq 'no' ? 1 : 0 );

Do you have a SSL certificate for the $domainNameUnicode domain?
EOF
            if ( $rs == 0 ) {
                my $msg = '';

                do {
                    $dialog->msgbox( <<'EOF' );

$msg
Please select your private key in next dialog.
EOF
                    do {
                        ( $rs, $privateKeyPath ) = $dialog->fselect( $privateKeyPath );
                    } while $rs < 30 && !( $privateKeyPath && -f $privateKeyPath );
                    return $rs if $rs >= 30;

                    ( $rs, $passphrase ) = $dialog->passwordbox( <<'EOF', $passphrase );

Please enter the passphrase for your private key if any:
EOF
                    return $rs if $rs >= 30;

                    $openSSL->{'private_key_container_path'} = $privateKeyPath;
                    $openSSL->{'private_key_passphrase'} = $passphrase;

                    $msg = '';
                    if ( $openSSL->validatePrivateKey() ) {
                        getMessageByType( 'error', {
                            amount => 1,
                            remove => TRUE
                        } );
                        $msg = "\n\\Z1Invalid private key or passphrase.\\Zn\n\nPlease try again.";
                    }
                } while $rs < 30 && $msg;
                return $rs if $rs >= 30;

                $rs = $dialog->yesno( <<'EOF' );

Do you have a SSL CA Bundle?
EOF
                if ( $rs == 0 ) {
                    do {
                        ( $rs, $caBundlePath ) = $dialog->fselect( $caBundlePath );
                    } while $rs < 30 && !( $caBundlePath && -f $caBundlePath );
                    return $rs if $rs >= 30;

                    $openSSL->{'ca_bundle_container_path'} = $caBundlePath;
                } else {
                    $openSSL->{'ca_bundle_container_path'} = '';
                }

                $dialog->msgbox( <<'EOF' );

Please select your SSL certificate in next dialog.
EOF
                $rs = 1;
                do {
                    $dialog->msgbox( <<"EOF" ) unless $rs;
                    
\\Z1Invalid SSL certificate.\\Zn

Please try again.
EOF
                    do {
                        ( $rs, $certificatePath ) = $dialog->fselect( $certificatePath );
                    } while $rs < 30 && !( $certificatePath && -f $certificatePath );
                    return $rs if $rs >= 30;

                    getMessageByType( 'error', {
                        amount => 1,
                        remove => TRUE
                    } );
                    $openSSL->{'certificate_container_path'} = $certificatePath;
                } while $rs < 30 && $openSSL->validateCertificate();
                return $rs if $rs >= 30;
            } else {
                $selfSignedCertificate = 'yes';
            }

            if ( $sslEnabled eq 'yes' ) {
                ( $rs, $baseServerVhostPrefix ) = $dialog->radiolist(
                    <<'EOF', [ 'https', 'http' ], $baseServerVhostPrefix eq 'https://' ? 'https' : 'http' );

Please choose the default HTTP access mode for the control panel:
EOF
                $baseServerVhostPrefix .= '://'
            }
        } else {
            $sslEnabled = 'no';
        }
    } elsif ( $sslEnabled eq 'yes' && !iMSCP::Getopt->preseed ) {
        $openSSL->{'private_key_container_path'} = "$::imscpConfig{'CONF_DIR'}/$domainName.pem";
        $openSSL->{'ca_bundle_container_path'} = "$::imscpConfig{'CONF_DIR'}/$domainName.pem";
        $openSSL->{'certificate_container_path'} = "$::imscpConfig{'CONF_DIR'}/$domainName.pem";

        if ( $openSSL->validateCertificateChain() ) {
            getMessageByType( 'error', {
                amount => 1,
                remove => TRUE
            } );
            $dialog->msgbox( <<'EOF' );

Your SSL certificate for the control panel is missing or invalid.
EOF
            ::setupSetQuestion( 'PANEL_SSL_ENABLED', '' );
            goto &{_dialogForSSL};
        }

        # In case the certificate is valid, we skip SSL setup process
        ::setupSetQuestion( 'PANEL_SSL_SETUP', 'no' );
    }

    ::setupSetQuestion( 'PANEL_SSL_ENABLED', $sslEnabled );
    ::setupSetQuestion( 'PANEL_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate );
    ::setupSetQuestion( 'PANEL_SSL_PRIVATE_KEY_PATH', $privateKeyPath );
    ::setupSetQuestion( 'PANEL_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase );
    ::setupSetQuestion( 'PANEL_SSL_CERTIFICATE_PATH', $certificatePath );
    ::setupSetQuestion( 'PANEL_SSL_CA_BUNDLE_PATH', $caBundlePath );
    ::setupSetQuestion( 'BASE_SERVER_VHOST_PREFIX', $sslEnabled eq 'yes' ? $baseServerVhostPrefix : 'http://' );
    0;
}

=item _dialogForHttpPorts( \%dialog )

 Setup dialog for HTTP ports

 Param iMSCP::Dialog \%dialog
 Return int 0 NEXT, 30 BACKUP, 50 ESC

=cut

sub _dialogForHttpPorts
{
    my ( undef, $dialog ) = @_;

    my $httpPort = ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT' );
    my $httpsPort = ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT' );
    my $ssl = ::setupGetQuestion( 'PANEL_SSL_ENABLED' );
    my ( $rs, $msg ) = ( 0, '' );

    if ( $::reconfigure =~ /^(?:panel|panel_ports|all|forced)$/
        || !isNumber( $httpPort )
        || !isNumberInRange( $httpPort, 1025, 65535 )
        || !isStringNotInList( $httpPort, $httpsPort )
    ) {
        do {
            ( $rs, $httpPort ) = $dialog->inputbox( <<"EOF", $httpPort ? $httpPort : 8880 );

Please enter the http port for the control panel:$msg
EOF
            $msg = '';
            if ( !isNumber( $httpPort )
                || !isNumberInRange( $httpPort, 1025, 65535 )
                || !isStringNotInList( $httpPort, $httpsPort )
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    ::setupSetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT', $httpPort );

    if ( $ssl eq 'yes' ) {
        if ( $::reconfigure =~ /^(?:panel|panel_ports|all|forced)$/
            || !isNumber( $httpsPort )
            || !isNumberInRange( $httpsPort, 1025, 65535 )
            || !isStringNotInList( $httpsPort, $httpPort )
        ) {
            do {
                ( $rs, $httpsPort ) = $dialog->inputbox( <<"EOF", $httpsPort ? $httpsPort : 8443 );

Please enter the https port for the control panel:$msg
EOF
                $msg = '';
                if ( !isNumber( $httpsPort )
                    || !isNumberInRange( $httpsPort, 1025, 65535 )
                    || !isStringNotInList( $httpsPort, $httpPort )
                ) {
                    $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
                }
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;
        }
    } else {
        $httpsPort ||= 8443;
    }

    ::setupSetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT', $httpsPort );
    0;
}

=item _createMasterWebUser( )

 Create master (control panel) Web user
 Return void, die on failure

=cut

sub _createMasterWebUser
{
    my ( $self ) = @_;

    my $ugOld = $::imscpOldConfig{'SYSTEM_USER_PREFIX'} . $::imscpOldConfig{'SYSTEM_USER_MIN_UID'};
    my $ugNew = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    iMSCP::SystemUser->new(
        username       => $ugOld,
        comment        => 'i-MSCP Control Panel Web User',
        home           => $::imscpConfig{'GUI_ROOT_DIR'},
        skipCreateHome => TRUE
    )->addSystemUser( $ugNew, $ugNew );

    # Add the control panel Web user (vu2000) into the i-MSCP master user group
    # The control panel needs read access the /etc/imscp/* files
    iMSCP::SystemUser->new()->addToGroup(
        $::imscpConfig{'IMSCP_GROUP'}, $ugNew
    );

    # Add the control panel Web user (vu2000) into the mailbox group (e.g: mail)
    # The control panel need access to customer maildirsize files to calculate quota (realtime quota)
    iMSCP::SystemUser->new()->addToGroup(
        Servers::mta->factory()->{'config'}->{'MTA_MAILBOX_GID_NAME'}, $ugNew
    );

    # Add the control panel Web user (vu2000) into the Web server group
    # FIXME: This is needed for ?
    iMSCP::SystemUser->new()->addToGroup(
        $ugNew, $self->{'config'}->{'HTTPD_USER'}
    );
}

=item _setupMasterAdmin( )

 Setup master administrator

 Return int 0 on success, other on failure

=cut

sub _setupMasterAdmin
{
    my $login = ::setupGetQuestion( 'ADMIN_LOGIN_NAME' );
    my $loginOld = ::setupGetQuestion( 'ADMIN_OLD_LOGIN_NAME' );
    my $password = ::setupGetQuestion( 'ADMIN_PASSWORD' );
    my $email = ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' );

    return 0 if $password eq '';

    $password = apr1MD5( $password );

    my $db = iMSCP::Database->factory();
    my $dbh = $db->getRawDb();

    local $@;
    eval {
        my $oldDbName = $db->useDatabase( ::setupGetQuestion( 'DATABASE_NAME' ));

        {
            local $dbh->{'RaiseError'} = TRUE;
            $dbh->begin_work();

            my $row = $dbh->selectrow_hashref(
                'SELECT admin_id FROM admin WHERE admin_name = ?',
                undef,
                $loginOld
            );

            if ( $row ) {
                $dbh->do(
                    '
                        UPDATE admin
                        SET admin_name = ?, admin_pass = ?, email = ?
                        WHERE admin_id = ?',
                    undef, $login, $password, $email, $row->{'admin_id'}
                );
            } else {
                $dbh->do(
                    '
                        INSERT INTO admin (admin_name, admin_pass, admin_type, email)
                        VALUES (?, ?, ?, ?)
                    ',
                    undef, $login, $password, 'admin', $email
                );
                $dbh->do( 'INSERT INTO user_gui_props SET user_id = LAST_INSERT_ID()' );
            }

            $dbh->commit();
        }

        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        $dbh->rollback();
        error( $@ );
        return 1;
    }

    0
}

=item _setupSsl( )

 Setup SSL

 Return int 0 on success, other on failure

=cut

sub _setupSsl
{
    my $sslEnabled = ::setupGetQuestion( 'PANEL_SSL_ENABLED' );
    my $oldCertificate = $::imscpOldConfig{'BASE_SERVER_VHOST'};
    my $domainName = ::setupGetQuestion( 'BASE_SERVER_VHOST' );

    # Remove old certificate if any (handle case where panel hostname has been changed)
    if ( $oldCertificate ne ''
        && $oldCertificate ne "$domainName.pem"
        && -f "$::imscpConfig{'CONF_DIR'}/$oldCertificate"
    ) {
        my $rs = iMSCP::File->new( filename => "$::imscpConfig{'CONF_DIR'}/$oldCertificate" )->delFile();
        return $rs if $rs;
    }

    if ( $sslEnabled eq 'no'
        || ::setupGetQuestion( 'PANEL_SSL_SETUP', 'yes' ) eq 'no'
    ) {
        if ( $sslEnabled eq 'no' && -f "$::imscpConfig{'CONF_DIR'}/$domainName.pem" ) {
            my $rs = iMSCP::File->new( filename => "$::imscpConfig{'CONF_DIR'}/$domainName.pem" )->delFile();
            return $rs if $rs;
        }

        return 0;
    }

    if ( ::setupGetQuestion( 'PANEL_SSL_SELFSIGNED_CERTIFICATE' ) eq 'yes' ) {
        return iMSCP::OpenSSL->new(
            certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
            certificate_chain_name         => $domainName
        )->createSelfSignedCertificate( {
            common_name => $domainName,
            email       => ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )
        } );
    }

    iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
        certificate_chain_name         => $domainName,
        private_key_container_path     => ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PATH' ),
        private_key_passphrase         => ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PASSPHRASE' ),
        certificate_container_path     => ::setupGetQuestion( 'PANEL_SSL_CERTIFICATE_PATH' ),
        ca_bundle_container_path       => ::setupGetQuestion( 'PANEL_SSL_CA_BUNDLE_PATH' )
    )->createCertificateChain();
}

=item _setHttpdVersion( )

 Set httpd version

 Return int 0 on success, other on failure

=cut

sub _setHttpdVersion( )
{
    my ( $self ) = @_;

    my $rs = execute( 'nginx -v', \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stderr !~ m%nginx/([\d.]+)% ) {
        error( "Couldn't guess Nginx version" );
        return 1;
    }

    $self->{'config'}->{'HTTPD_VERSION'} = $1;
    debug( sprintf( 'Nginx version set to: %s', $1 ));
    0;
}

=item _addMasterWebUser( )

 Add master Web user

 Return int 0 on success, other on failure

=cut

sub _addMasterWebUser
{
    my ( $self ) = @_;

    local $@;
    my $rs = eval {
        my $rs = $self->{'events'}->trigger( 'beforeFrontEndAddUser' );
        return $rs if $rs;

        my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'};
        my ( $uid, $gid ) = ( getpwnam( $ug ) )[2, 3];

        my $db = iMSCP::Database->factory();
        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;

        $db->useDatabase( ::setupGetQuestion( 'DATABASE_NAME' ));

        $dbh->do(
            "
                UPDATE admin
                SET admin_sys_name = ?, admin_sys_uid = ?, admin_sys_gname = ?,
                    admin_sys_gid = ?
                WHERE admin_type = 'admin'",
            undef, $ug, $uid, $ug, $gid
        );

        $self->{'events'}->trigger( 'afterFrontEndAddUser' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs;
}

=item _makeDirs( )

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndMakeDirs' );
    return $rs if $rs;

    my $rootUName = $::imscpConfig{'ROOT_USER'};
    my $rootGName = $::imscpConfig{'ROOT_GROUP'};

    my $nginxTmpDir = $self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'};
    $nginxTmpDir = $self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'} unless -d $nginxTmpDir;

    # Force re-creation of cache directory tree (needed to prevent any
    # permissions problem from an old installation)
    # See #IP-1530
    iMSCP::Dir->new( dirname => $nginxTmpDir )->remove();

    for ( [ $nginxTmpDir, $rootUName, $rootGName, 0755 ],
        [ $self->{'config'}->{'HTTPD_CONF_DIR'}, $rootUName, $rootGName, 0755 ],
        [ $self->{'config'}->{'HTTPD_LOG_DIR'}, $rootUName, $rootGName, 0755 ],
        [ $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}, $rootUName, $rootGName, 0755 ],
        [ $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}, $rootUName, $rootGName, 0755 ]
    ) {
        iMSCP::Dir->new( dirname => $_->[0] )->make( {
            user  => $_->[1],
            group => $_->[2],
            mode  => $_->[3]
        } );
    }

    if ( iMSCP::Service->getInstance->isSystemd() ) {
        iMSCP::Dir->new( dirname => '/run/imscp' )->make( {
            user  => $self->{'config'}->{'HTTPD_USER'},
            group => $self->{'config'}->{'HTTPD_GROUP'},
            mode  => 0755
        } );
    }

    $self->{'events'}->trigger( 'afterFrontEndMakeDirs' );
}

=item _copyPhpBinary( )

 Copy system PHP-FPM binary for imscp_panel service

 Return int 0 on success, other on failure

=cut

sub _copyPhpBinary
{
    my ( $self ) = @_;

    unless ( length $self->{'config'}->{'PHP_FPM_BIN_PATH'} ) {
        error( "PHP 'PHP_FPM_BIN_PATH' configuration parameter is not set." );
        return 1;
    }

    # service must be stopped. We can't copy over a busy file
    my $rs = $self->stopPhpFpm();
    $rs ||= iMSCP::File->new(
        filename => $self->{'config'}->{'PHP_FPM_BIN_PATH'}
    )->copyFile(
        '/usr/local/sbin/imscp_panel', { preserve => 'yes' }
    );
}

=item _buildPhpConfig( )

 Build PHP configuration

 Return int 0 on success, other on failure

=cut

sub _buildPhpConfig
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndBuildPhpConfig' );
    return $rs if $rs;

    my $user = $::imscpConfig{'SYSTEM_USER_PREFIX'}
        . $::imscpConfig{'SYSTEM_USER_MIN_UID'};
    my $group = $::imscpConfig{'SYSTEM_USER_PREFIX'}
        . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    $rs = $self->buildConfFile(
        "$self->{'cfgDir'}/php-fpm.conf",
        {
            CHKROOTKIT_LOG            => $::imscpConfig{'CHKROOTKIT_LOG'},
            CONF_DIR                  => $::imscpConfig{'CONF_DIR'},
            DOMAIN                    => ::setupGetQuestion( 'BASE_SERVER_VHOST' ),
            DISTRO_OPENSSL_CNF        => $::imscpConfig{'DISTRO_OPENSSL_CNF'},
            DISTRO_CA_BUNDLE          => $::imscpConfig{'DISTRO_CA_BUNDLE'},
            FRONTEND_FCGI_CHILDREN    => $self->{'config'}->{'PHP_FPM_MAX_CHILDREN'},
            FRONTEND_FCGI_MAX_REQUEST => $self->{'config'}->{'PHP_FPM_MAX_REQUESTS'},
            FRONTEND_GROUP            => $group,
            FRONTEND_USER             => $user,
            HOME_DIR                  => $::imscpConfig{'GUI_ROOT_DIR'},
            MTA_VIRTUAL_MAIL_DIR      => Servers::mta->factory()->{'config'}->{'MTA_VIRTUAL_MAIL_DIR'},
            PEAR_DIR                  => $self->{'config'}->{'PHP_PEAR_DIR'},
            RKHUNTER_LOG              => $::imscpConfig{'RKHUNTER_LOG'},
            TIMEZONE                  => ::setupGetQuestion( 'TIMEZONE' ),
            WEB_DIR                   => $::imscpConfig{'GUI_ROOT_DIR'}
        },
        {
            destination => "/usr/local/etc/imscp_panel/php-fpm.conf",
            user        => $::imscpConfig{'ROOT_USER'},
            group       => $::imscpConfig{'ROOT_GROUP'},
            mode        => 0640
        }
    );
    $rs ||= $self->buildConfFile(
        "$self->{'cfgDir'}/php.ini",
        {

            PEAR_DIR => $self->{'config'}->{'PHP_PEAR_DIR'},
            TIMEZONE => ::setupGetQuestion( 'TIMEZONE' )
        },
        {
            destination => '/usr/local/etc/imscp_panel/php.ini',
            user        => $::imscpConfig{'ROOT_USER'},
            group       => $::imscpConfig{'ROOT_GROUP'},
            mode        => 0640,
        }
    );
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndBuildPhpConfig' );
}

=item _buildHttpdConfig( )

 Build httpd configuration

 Return int 0 on success, other on failure

=cut

sub _buildHttpdConfig
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndBuildHttpdConfig' );
    return $rs if $rs;

    # Build main nginx configuration file
    $rs = $self->buildConfFile(
        "$self->{'cfgDir'}/nginx.nginx",
        {
            HTTPD_USER               => $self->{'config'}->{'HTTPD_USER'},
            HTTPD_WORKER_PROCESSES   => $self->{'config'}->{'HTTPD_WORKER_PROCESSES'},
            HTTPD_WORKER_CONNECTIONS => $self->{'config'}->{'HTTPD_WORKER_CONNECTIONS'},
            HTTPD_RLIMIT_NOFILE      => $self->{'config'}->{'HTTPD_RLIMIT_NOFILE'},
            HTTPD_LOG_DIR            => $self->{'config'}->{'HTTPD_LOG_DIR'},
            HTTPD_PID_FILE           => $self->{'config'}->{'HTTPD_PID_FILE'},
            HTTPD_CONF_DIR           => $self->{'config'}->{'HTTPD_CONF_DIR'},
            HTTPD_LOG_DIR            => $self->{'config'}->{'HTTPD_LOG_DIR'},
            HTTPD_SITES_ENABLED_DIR  => $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'}
        },
        {
            destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/nginx.conf",
            user        => $::imscpConfig{'ROOT_USER'},
            group       => $::imscpConfig{'ROOT_GROUP'},
            mode        => 0644
        }
    );

    # Build FastCGI configuration file
    $rs = $self->buildConfFile( "$self->{'cfgDir'}/imscp_fastcgi.nginx", {}, {
        destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf",
        user        => $::imscpConfig{'ROOT_USER'},
        group       => $::imscpConfig{'ROOT_GROUP'},
        mode        => 0644
    }
    );

    # Build PHP backend configuration file
    $rs = $self->buildConfFile( "$self->{'cfgDir'}/imscp_php.nginx", {}, {
        destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf",
        user        => $::imscpConfig{'ROOT_USER'},
        group       => $::imscpConfig{'ROOT_GROUP'},
        mode        => 0644
    } );
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndBuildHttpdConfig' );
    $rs ||= $self->{'events'}->trigger( 'beforeFrontEndBuildHttpdVhosts' );
    return $rs if $rs;

    # Build frontEnd site files
    my $baseServerIpVersion = iMSCP::Net->getInstance()->getAddrVersion(
        ::setupGetQuestion( 'BASE_SERVER_IP' )
    );
    my $httpsPort = ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT' );
    my $tplVars = {
        BASE_SERVER_VHOST            => ::setupGetQuestion( 'BASE_SERVER_VHOST' ),
        BASE_SERVER_IP               => ( $baseServerIpVersion eq 'ipv4' )
            ? ::setupGetQuestion( 'BASE_SERVER_IP' ) =~ s/^\Q0.0.0.0\E$/*/r
            : '[' . ::setupGetQuestion( 'BASE_SERVER_IP' ) . ']',
        BASE_SERVER_VHOST_HTTP_PORT  => ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT' ),
        BASE_SERVER_VHOST_HTTPS_PORT => $httpsPort,
        WEB_DIR                      => $::imscpConfig{'GUI_ROOT_DIR'},
        CONF_DIR                     => $::imscpConfig{'CONF_DIR'},
        PLUGINS_DIR                  => $::imscpConfig{'PLUGINS_DIR'}
    };

    $rs = $self->disableSites( 'default', '00_master.conf', '00_master_ssl.conf' );
    $rs ||= $self->{'events'}->register( 'beforeFrontEndBuildConf', sub {
        my ( $cfgTpl, $tplName ) = @_;

        return 0 unless grep ($_ eq $tplName, '00_master.nginx', '00_master_ssl.nginx');

        if ( $baseServerIpVersion eq 'ipv6'
            || !::setupGetQuestion( 'IPV6_SUPPORT' )
        ) {
            ${ $cfgTpl } = replaceBloc(
                '# SECTION IPv6 BEGIN.',
                '# SECTION IPv6 END.',
                '',
                ${ $cfgTpl }
            );
        }

        return 0 unless $tplName eq '00_master.nginx'
            && ::setupGetQuestion( 'BASE_SERVER_VHOST_PREFIX' ) eq 'https://';

        ${ $cfgTpl } = replaceBloc(
            "# SECTION custom BEGIN.\n",
            "# SECTION custom END.\n",
            "    # SECTION custom BEGIN.\n"
                . getBloc( "# SECTION custom BEGIN.\n", "# SECTION custom END.\n", ${ $cfgTpl } )
                . <<'EOF'
    return 302 https://{BASE_SERVER_VHOST}:{BASE_SERVER_VHOST_HTTPS_PORT}$request_uri;
EOF
                . "    # SECTION custom END.\n",
            ${ $cfgTpl }
        );

        0;
    } );
    $rs ||= $self->buildConfFile( '00_master.nginx', $tplVars, {
        destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf",
        user        => $::imscpConfig{'ROOT_USER'},
        group       => $::imscpConfig{'ROOT_GROUP'},
        mode        => 0644
    } );
    $rs ||= $self->enableSites( '00_master.conf' );
    return $rs if $rs;

    if ( ::setupGetQuestion( 'PANEL_SSL_ENABLED' ) eq 'yes' ) {
        $rs ||= $self->buildConfFile( '00_master_ssl.nginx', $tplVars, {
            destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf",
            user        => $::imscpConfig{'ROOT_USER'},
            group       => $::imscpConfig{'ROOT_GROUP'},
            mode        => 0644
        } );
        $rs ||= $self->enableSites( '00_master_ssl.conf' );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master_ssl.conf"
        )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf" ) {
        # Nginx package as provided by Nginx Team
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf" )->moveFile(
            "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
        );
        return $rs if $rs;
    }

    $self->{'events'}->trigger( 'afterFrontEndBuildHttpdVhosts' );
}

=item _addDnsZone( )

 Add DNS zone

 Return int 0 on success, other on failure

=cut

sub _addDnsZone
{
    my ( $self ) = @_;

    my $rs = $self->_deleteDnsZone();
    $rs ||= $self->{'events'}->trigger( 'beforeNamedAddMasterZone' );
    $rs ||= Servers::named->factory()->addDmn( {
        BASE_SERVER_VHOST     => ::setupGetQuestion( 'BASE_SERVER_VHOST' ),
        BASE_SERVER_IP        => ::setupGetQuestion( 'BASE_SERVER_IP' ),
        BASE_SERVER_PUBLIC_IP => ::setupGetQuestion( 'BASE_SERVER_PUBLIC_IP' ),
        DOMAIN_NAME           => ::setupGetQuestion( 'BASE_SERVER_VHOST' ),
        DOMAIN_IP             => ::setupGetQuestion( 'BASE_SERVER_IP' ),
        MAIL_ENABLED          => TRUE
    } );
    $rs ||= $self->{'events'}->trigger( 'afterNamedAddMasterZone' );
}

=item _deleteDnsZone( )

 Delete previous DNS zone if needed (i.e. case where BASER_SERVER_VHOST has been modified)

 Return int 0 on success, other on failure

=cut

sub _deleteDnsZone
{
    my ( $self ) = @_;

    return 0 unless $::imscpOldConfig{'BASE_SERVER_VHOST'}
        && $::imscpOldConfig{'BASE_SERVER_VHOST'} ne ::setupGetQuestion( 'BASE_SERVER_VHOST' );

    my $rs = $self->{'events'}->trigger( 'beforeNamedDeleteMasterZone' );
    $rs ||= Servers::named->factory()->deleteDmn( {
        DOMAIN_NAME    => $::imscpOldConfig{'BASE_SERVER_VHOST'},
        FORCE_DELETION => TRUE
    } );
    $rs ||= $self->{'events'}->trigger( 'afterNamedDeleteMasterZone' );
}

=item getFullPhpVersionFor( $binary )

 Get full PHP version for the given PHP binary

 Param string $binary Path to PHP binary
 Return int 0 on success, other on failure

=cut

sub getFullPhpVersionFor
{
    my ( undef, $binary ) = @_;

    my ( $stdout, $stderr );
    execute( [ $binary, '-nv' ], \$stdout, \$stderr ) == 0 && $stdout =~ /PHP\s+([^\s]+)/ or die(
        sprintf( "Couldn't retrieve PHP version: %s", $stderr || 'Unknown error' )
    );
    $1;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    my $rs = $self->{'events'}->trigger( 'beforeFrontEndCleanup' );
    $rs ||= iMSCP::File->new(
        filename => "$self->{'cfgDir'}/frontend.old.data"
    )->delFile() if -f "$self->{'cfgDir'}/frontend.old.data";
    $rs ||= $self->{'events'}->trigger( 'afterFrontEndCleanup' );
}

# Uninstallation routines

=item _deconfigurePHP( )

 Deconfigure PHP (imscp_panel service)

 Return int 0 on success, other on failure

=cut

sub _deconfigurePHP
{
    local $@;
    my $rs = eval {
        iMSCP::Service->getInstance()->remove( 'imscp_panel' );

        for my $file ( '/etc/default/imscp_panel',
            '/etc/tmpfiles.d/imscp_panel.conf',
            "$::imscpConfig{'LOGROTATE_CONF_DIR'}/imscp_panel",
            '/usr/local/sbin/imscp_panel',
            '/var/log/imscp_panel.log'
        ) {
            next unless -f $file;
            my $rs = iMSCP::File->new( filename => $file )->delFile();
            return $rs if $rs;
        }

        iMSCP::Dir->new( dirname => '/usr/local/lib/imscp_panel' )->remove();
        iMSCP::Dir->new( dirname => '/usr/local/etc/imscp_panel' )->remove();
        iMSCP::Dir->new( dirname => '/var/run/imscp' )->remove();
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item _deconfigureHTTPD( )

 Deconfigure HTTPD (nginx)

 Return int 0 on success, other on failure

=cut

sub _deconfigureHTTPD
{
    my ( $self ) = @_;

    my $rs = $self->disableSites( '00_master.conf' );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_master.conf"
        )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/imscp_fastcgi.conf"
        )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf" ) {
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/imscp_php.conf"
        )->delFile();
        return $rs if $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/default" ) {
        # Nginx as provided by Debian
        $rs = $self->enableSites( 'default' );
        return $rs if $rs;
    } elsif ( "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled" ) {
        # Nginx package as provided by Nginx
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
        )->moveFile(
            "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf"
        );
        return $rs if $rs;
    }

    0;
}

=item _deleteMasterWebUser( )

 Delete i-MSCP master Web user

 Return int 0 on success, other on failure

=cut

sub _deleteMasterWebUser
{
    my $rs = iMSCP::SystemUser->new( force => 'yes' )->delSystemUser(
        $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
    );
    $rs ||= iMSCP::SystemGroup->getInstance()->delSystemGroup(
        $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
    );
}

# Other routines


=item _buildConf( $cfgTpl, $filename [, \%tplVars ] )

 Build the given configuration template

 Param string $cfgTpl Temmplate content
 Param string $filename Template filename
 Param hash OPTIONAL \%tplVars Template variables
 Return string Template content

=cut

sub _buildConf
{
    my ( $self, $cfgTpl, $filename, $tplVars ) = @_;

    $tplVars ||= {};
    $self->{'events'}->trigger(
        'beforeFrontEndBuildConf', \$cfgTpl, $filename, $tplVars
    );
    $cfgTpl = process( $tplVars, $cfgTpl );
    $self->{'events'}->trigger(
        'afterFrontEndBuildConf', \$cfgTpl, $filename, $tplVars
    );
    $cfgTpl;
}

=item END

 Start, restart or reload frontEnd services: nginx or/and imscp_panel when required

 Return int Exit code

=cut

END
    {
        return if $?;

        if ( defined $::execmode ) {
            return if $::execmode eq 'setup';
            $? = Package::FrontEnd->getInstance()->restartNginx() if $::execmode eq 'uninstaller';
            return;
        }

        my $self = Package::FrontEnd->getInstance();
        if ( $self->{'start'} ) {
            $? = $self->start();
        } elsif ( $self->{'restart'} ) {
            $? = $self->restart();
        } elsif ( $self->{'reload'} ) {
            $? = $self->reload();
        }
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
