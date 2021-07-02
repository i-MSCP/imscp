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
use iMSCP::Crypt qw/ ALNUM apr1MD5 randomStr /;
use iMSCP::Cwd '$CWD';
use iMSCP::Database;
use iMSCP::Debug qw/ error debug getMessageByType /;
use iMSCP::Dialog::InputValidation qw/
    $LAST_VALIDATION_ERROR
    isValidUsername isValidPassword isValidEmail
    isValidDomain isNumber isNumberInRange
    isStringNotInList
/;
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
            sub { $self->_dialogForMasterAdminUsername( @_ ); },
            sub { $self->_dialogForMasterAdminPassword( @_ ); },
            sub { $self->_dialogForMasterAdminEmail( @_ ); },
            sub { $self->_dialogForCpHostname( @_ ); },
            sub { $self->_dialogForCpSSL( @_ ); },
            sub { $self->_dialogForCpHttpAccessMode( @_ ); },
            sub { $self->_dialogForHttpPort( @_ ); },
            sub { $self->_dialogForHttpsPort( @_ ); };
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
    $rs ||= $self->_createSymlinkForBcCompatibility();
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
        my $service = iMSCP::Service->getInstance();
        $service->enable( $self->{'config'}->{'HTTPD_SNAME'} );
        $service->enable( 'imscp_panel' );
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

=item _dialogForMasterAdminUsername( \%dialog )

 Setup dialog for the master administrator username

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForMasterAdminUsername
{
    my ( undef, $dialog ) = @_;

    my ( $value, $db ) = (
        ::setupGetQuestion( 'ADMIN_LOGIN_NAME', 'admin' ),
        iMSCP::Database->factory()
    );

    local $@;
    eval { $db->useDatabase( ::setupGetQuestion( 'DATABASE_NAME' )); };
    $db = undef if $@;

    if ( $db ) {
        local $@;
        my $row = eval {
            $db->getRawDb()->selectrow_hashref(
                "
                    SELECT `admin_name`
                    FROM `admin`
                    WHERE `admin_type` = 'admin'
                    AND `created_by` = 0
                    LIMIT 1
                "
            );
        };

        if ( $row ) {
            ::setupSetQuestion( 'ADMIN_OLD_LOGIN_NAME', $row->{'admin_name'} );
            $value = $row->{'admin_name'} unless length $value;
        }
    }

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ admin admin_username admin_credentials all / )
        && isValidUsername( $value )
    ) {
        ::setupSetQuestion( 'ADMIN_LOGIN_NAME', $value );
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );

    do {
        ( $ret, $value ) = $dialog->string(
            <<"EOF", length $value ? $value : 'admin' );
${msg}Please enter a username for the master administrator:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;

            if ( !isValidUsername( $value ) ) {
                $msg = $LAST_VALIDATION_ERROR;
            } elsif ( $db ) {
                local $@;
                my $row = eval {
                    $db->getRawDb()->selectrow_hashref(
                        "
                            SELECT 1
                            FROM admin
                            WHERE admin_name = ?
                            AND admin_type <>  'admin'
                            AND created_by <> 0
                        ",
                        undef,
                        $value
                    );
                };
                $msg = $row ? "\\Z1This username is not available.\\Zn\n\n" : '';
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'ADMIN_LOGIN_NAME', $value );
    0;
}

=item _dialogForMasterAdminPassword( \%dialog )

 Dialog for the master administrator password

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForMasterAdminPassword
{
    my ( undef, $dialog ) = @_;

    my ( $value, $isset, $db ) = (
        ::setupGetQuestion( 'ADMIN_PASSWORD' ),
        FALSE,
        iMSCP::Database->factory()
    );

    unless ( length $value ) {
        local $@;
        eval { $db->useDatabase( ::setupGetQuestion( 'DATABASE_NAME' )); };
        $db = undef if $@;

        if ( $db ) {
            local $@;
            my $row = eval {
                $db->getRawDb()->selectrow_hashref(
                    "
                        SELECT `admin_pass`
                        FROM `admin`
                        WHERE `admin_type` = 'admin'
                        AND `created_by` = 0
                        LIMIT 1
                    "
                );
            };
            $isset = $row ? TRUE : FALSE;
        }
    }

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ admin admin_password admin_credentials all / )
        && ( $isset || isValidPassword( $value ) )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );

    do {
        ( $ret, $value ) = $dialog->string(
            <<"EOF", randomStr( 16, ALNUM ));
${msg}Please enter a password for the master (@{ [ ::setupGetQuestion( 'ADMIN_LOGIN_NAME' ) ] }) administrator:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;
            $msg = isValidPassword( $value ) ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'ADMIN_PASSWORD', $value );
    0;
}

=item _dialogForMasterAdminEmail( \%dialog )

 Dialog for the master administrator email address

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForMasterAdminEmail
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ admin admin_email all / )
        && isValidEmail( $value )
    ) {
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", $value );
${msg}Please enter an email address for the master administrator:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;
            $msg = isValidEmail( $value ) ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'DEFAULT_ADMIN_ADDRESS', $value );
    0;
}

=item _dialogForCpHostname( \%dialog )

 Dialog for the control panel hostname

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForCpHostname
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'BASE_SERVER_VHOST' );

    if ( !length $value ) {
        my @domainLabels = split /\./, ::setupGetQuestion( 'SERVER_HOSTNAME' );
        $value = 'panel.' . join( '.', @domainLabels[1 .. $#domainLabels] );
    }

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ panel panel_hostname hostnames all / )
        && isValidDomain( $value )
    ) {
        ::setupSetQuestion( 'BASE_SERVER_VHOST', $value );
        return 20;
    }

    unless ( length $value ) {
        my @domainLabels = split /\./, ::setupGetQuestion( 'SERVER_HOSTNAME' );
        $value = 'panel.' . join( '.', @domainLabels[1 .. $#domainLabels] );
    }

    $value = idn_to_unicode( $value, 'utf-8' );

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", $value );
${msg}Please enter a domain name for the control panel:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;
            $msg = isValidDomain( $value ) ? '' : $LAST_VALIDATION_ERROR;
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BASE_SERVER_VHOST', idn_to_ascii( $value, 'utf-8' ));
    0;
}

=item _dialogForCpSSL( \%dialog )

 Dialof for the control panel SSL certificate

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForCpSSL
{
    my ( undef, $dialog ) = @_;

    my $hostname = ::setupGetQuestion( 'BASE_SERVER_VHOST' );
    my $idn = idn_to_unicode( $hostname, 'utf-8' );
    my $ssl = ::setupGetQuestion( 'PANEL_SSL_ENABLED', 'yes' );
    my $selfSignedCrt = ::setupGetQuestion(
        'PANEL_SSL_SELFSIGNED_CERTIFICATE', 'yes'
    );
    my $pkPath = ::setupGetQuestion(
        'PANEL_SSL_PRIVATE_KEY_PATH',
        iMSCP::Getopt->preseed ? '' : "$::imscpConfig{'CONF_DIR'}/$hostname.pem"
    );
    my $passphrase = ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PASSPHRASE' );
    my $crtPath = ::setupGetQuestion(
        'PANEL_SSL_CERTIFICATE_PATH',
        iMSCP::Getopt->preseed ? '' : "$::imscpConfig{'CONF_DIR'}/$hostname.pem"
    );
    my $caPath = ::setupGetQuestion(
        'PANEL_SSL_CA_BUNDLE_PATH',
        iMSCP::Getopt->preseed
            ? '' : "$::imscpConfig{'CONF_DIR'}/$hostname.pem"
    );
    my $openSSL = iMSCP::OpenSSL->new();

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ panel panel_ssl ssl all / ) ) {
        goto CHECK_SSL_CHAIN if $ssl eq 'yes';
        return 20 if $ssl eq 'no';
    }

    SSL_DIALOG:
    my $ret = $dialog->boolean( <<'EOF', $ssl eq 'no' );
Do you want to enable the secure connections (SSL) for the control panel?
EOF
    return 30 if $ret == 30;

    if ( $ret == 1 ) {
        ::setupSetQuestion( 'PANEL_SSL_ENABLED', 'no' );
        return 0;
    }

    ::setupSetQuestion( 'PANEL_SSL_ENABLED', 'yes' );

    SSL_SELF_SIGNED_DIALOG:
    $ret = $dialog->boolean( <<"EOF", $selfSignedCrt eq 'no' );
Do you have an SSL certificate for the $idn domain?
    
If you say 'no', a self-signed SSL certificate will be generated.
EOF
    goto SSL_DIALOG if $ret == 30;

    if ( $ret == 1 ) {
        ::setupSetQuestion( 'PANEL_SSL_SELFSIGNED_CERTIFICATE', 'yes' );
        ::setupSetQuestion( 'PANEL_SSL_HAS_VALID_CHAIN', 'no' );
        return 0;
    }

    ::setupSetQuestion( 'PANEL_SSL_SELFSIGNED_CERTIFICATE', 'no' );

    my $msg = '';
    SSL_PK_DIALOG:
    do {
        ( $ret, $pkPath ) = $dialog->string( <<"EOF", $pkPath );
${msg}Please enter a path for the SSL certificate private key. Leave this field blank if you don't have one:
EOF
        if ( $ret != 30 ) {
            $pkPath =~ s/^\s+|\s+$//g;
            $msg = !length $pkPath || !-f $pkPath
                ? "\\Z1Invalid SSL certificate private key path.\\Zn\n\n" : '';
        }
    } while $ret != 30 && length $msg;
    goto SSL_SELF_SIGNED_DIALOG if $ret == 30;

    ::setupSetQuestion( 'PANEL_SSL_PRIVATE_KEY_PATH', $pkPath );

    SSL_PK_PASSPHRASE_DIALOG:
    do {
        ( $ret, $passphrase ) = $dialog->password( <<"EOF", $passphrase );
${msg}Please enter the passphrase for the SSL certificate private key. Leave this field blank if you don't have one:
EOF
        if ( $ret != 30 ) {
            $passphrase =~ s/^\s+|\s+$//g;
            @{ $openSSL }{qw/
                private_key_container_path private_key_passphrase
            /} = (
                $pkPath, $passphrase
            );

            unless ( $openSSL->validatePrivateKey() ) {
                $msg = "\\Z1" . getMessageByType( 'error', {
                    amount => 1,
                    remove => TRUE
                } ) . "\\Zn\n\n";
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    goto SSL_PK_DIALOG if $ret == 30;

    ::setupSetQuestion( 'PANEL_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase );

    SSL_CA_BUNDLE_DIALOG:
    do {
        ( $ret, $caPath ) = $dialog->string( <<"EOF", $caPath );
${msg}Please enter a path for the SSL certificate CA bundle. Leave this field blank if you don't have one:
EOF
        if ( $ret != 30 ) {
            $caPath =~ s/^\s+|\s+$//g;
            $msg = length $caPath && !-f $caPath
                ? "\\Z1Invalid SSL certificate CA bundle path.\\Zn\n\n" : '';
        }
    } while $ret != 30 && length $msg;
    goto SSL_PK_PASSPHRASE_DIALOG if $ret == 30;

    ::setupSetQuestion( 'PANEL_SSL_CA_BUNDLE_PATH', $caPath );

    do {
        ( $ret, $crtPath ) = $dialog->string( <<"EOF", $crtPath );
${msg}Please enter a path for the SSL certificate:
EOF
        if ( $ret != 30 ) {
            $crtPath =~ s/^\s+|\s+$//g;
            @{ $openSSL }{qw/
                ca_bundle_container_path certificate_container_path
            /} = (
                $caPath, $crtPath
            );

            unless ( $openSSL->validateCertificate()
                && $openSSL->validateCertKeyMatching()
            ) {
                $msg = "\\Z1" . getMessageByType( 'error', {
                    amount => 1,
                    remove => TRUE
                } ) . "\\Zn\n\n";
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    goto SSL_CA_BUNDLE_DIALOG if $ret == 30;

    ::setupSetQuestion( 'PANEL_SSL_CERTIFICATE_PATH', $crtPath );
    ::setupSetQuestion( 'PANEL_SSL_HAS_VALID_CHAIN', 'no' );
    goto END_SSL_DIALOG;

    CHECK_SSL_CHAIN:
    
    if ( $selfSignedCrt eq 'yes' ) {
        ::setupSetQuestion( 'PANEL_SSL_ENABLED', $ssl );
        ::setupSetQuestion(
            'PANEL_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCrt
        );
        return 20;
    }

    @{ $openSSL }{qw/
        private_key_container_path
        ca_bundle_container_path
        certificate_container_path
    /} = (
        $pkPath, $caPath, $crtPath
    );

    unless ( $openSSL->validateCertificateChain() ) {
        local $dialog->{'_opts'}->{
            $dialog->{'program'} eq 'dialog' ? 'ok-label' : 'ok-button'
        } = 'Reconfigure';
        $msg = getMessageByType( 'error', {
            amount => 1,
            remove => TRUE
        } );
        $dialog->error( <<"EOF" );
Your SSL certificate chain for the control panel is missing or invalid.

Error was: \\Z1$msg\\Zn
EOF
        ::setupSetQuestion( 'PANEL_SSL_ENABLED', '' );
        goto &{_dialogForCpSSL};
    }

    ::setupSetQuestion( 'PANEL_SSL_HAS_VALID_CHAIN', 'yes' );

    END_SSL_DIALOG:
    0;
}

=item _dialogForCpHttpAccessMode

 Dialog for the control panel HTTP access mode

 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForCpHttpAccessMode
{
    my ( undef, $dialog ) = @_;

    if ( ::setupGetQuestion( 'PANEL_SSL_ENABLED' ) eq 'no' ) {
        ::setupSetQuestion( 'BASE_SERVER_VHOST_PREFIX', 'http://' );
        return 20;
    }

    my $value = ::setupGetQuestion(
        'BASE_SERVER_VHOST_PREFIX',
        ::setupGetQuestion( 'PANEL_SSL_ENABLED' ) ne 'yes'
            ? 'http://' : 'https://'
    );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ panel panel_ssl ssl all / )
        && grep ( $value eq $_, 'https://', 'http://' )
    ) {
        ::setupSetQuestion( 'BASE_SERVER_VHOST_PREFIX', $value );
        return 20;
    }

    my %choices = (
        'Secure connection (SSL)' => 'https',
        'Insecure connection'     => 'http'
    );

    ( my $ret, $value ) = $dialog->select(
        <<'EOF', \%choices, $value eq 'http://' ? 'http' : 'https' );
Please choose the default access mode for the control panel:
EOF
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BASE_SERVER_VHOST_PREFIX', $value . '://' );
    0;
}

=item _dialogForHttpPort( \%dialog )

 Setup dialog for HTTP port

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForHttpPort
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT', 8880 );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ panel panel_ports all / )
        && isNumber( $value )
        && isNumberInRange( $value, 1025, 65535 )
        && isStringNotInList( $value, ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT', 8443 ))
    ) {
        ::setupSetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT', $value );
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", $value || 8880 );
${msg}Please enter the HTTP port for the control panel:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;

            if ( !isNumber( $value )
                || !isNumberInRange( $value, 1025, 65535 )
                || !isStringNotInList( $value, ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT', 8443 ))
            ) {
                $msg = $LAST_VALIDATION_ERROR;
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT', $value );
    0;
}

=item _dialogForHttpsPort( \%dialog )

 Setup dialog for HTTPs port

 Param iMSCP::Dialog \%dialog
 Return int 0 (Next), 20 (Skip), 30 (Back)

=cut

sub _dialogForHttpsPort
{
    my ( undef, $dialog ) = @_;

    my $value = ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT', 8443 );

    if ( $dialog->executeRetval != 30
        && !grep ( $_ eq iMSCP::Getopt->reconfigure, qw/ panel panel_ports all / )
        && isNumber( $value )
        && isNumberInRange( $value, 1025, 65535 )
        && isStringNotInList( $value, ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT' ))
    ) {
        ::setupSetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT', $value );
        return 20;
    }

    my ( $ret, $msg ) = ( 0, '' );
    do {
        ( $ret, $value ) = $dialog->string( <<"EOF", $value || 8443 );
${msg}Please enter the HTTPS port for the control panel:
EOF
        if ( $ret != 30 ) {
            $value =~ s/^\s+|\s+$//g;

            if ( !isNumber( $value )
                || !isNumberInRange( $value, 1025, 65535 )
                || !isStringNotInList( $value, ::setupGetQuestion( 'BASE_SERVER_VHOST_HTTP_PORT' ))
            ) {
                $msg = $LAST_VALIDATION_ERROR;
            } else {
                $msg = '';
            }
        }
    } while $ret != 30 && length $msg;
    return 30 if $ret == 30;

    ::setupSetQuestion( 'BASE_SERVER_VHOST_HTTPS_PORT', $value );
    0;
}

=item _createMasterWebUser( )

 Create master (control panel) Web user
 Return void, die on failure

=cut

sub _createMasterWebUser
{
    my ( $self ) = @_;

    my $ugOld = $::imscpOldConfig{'SYSTEM_USER_PREFIX'}
        . $::imscpOldConfig{'SYSTEM_USER_MIN_UID'};
    my $ugNew = $::imscpConfig{'SYSTEM_USER_PREFIX'}
        . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

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
    my $newLogin = ::setupGetQuestion( 'ADMIN_LOGIN_NAME' );
    my $oldLogin = ::setupGetQuestion( 'ADMIN_OLD_LOGIN_NAME' );
    my $password = ::setupGetQuestion( 'ADMIN_PASSWORD' );
    my $email = ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' );

    my $db = iMSCP::Database->factory();
    my $dbh = $db->getRawDb();

    local $@;
    eval {
        my $oldDatabase = $db->useDatabase(
            ::setupGetQuestion( 'DATABASE_NAME' )
        );

        {
            $dbh->begin_work();

            # Create or update master administrator account
            $dbh->do(
                '
                    INSERT INTO `admin` (
                        `admin_name`, `admin_pass`, `admin_type`, `email`
                    ) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE
                        `admin_id` = LAST_INSERT_ID(`admin_id`),
                        `admin_name` = ?,
                        `admin_pass` = IF(LENGTH(?) > 0, ?, `admin_pass`),
                        `email` = ?
                ',
                undef,
                # Insert parameters
                $newLogin, apr1MD5( $password ), 'admin', $email,
                # On duplicate parameters
                $newLogin,
                $password, length $password ? apr1MD5( $password ) : '',
                $email,
            );

            if ( $newLogin ne $oldLogin ) {
                $dbh->do(
                    '
                        INSERT INTO `user_gui_props`
                        SET `user_id` = LAST_INSERT_ID()
                    '
                );
            }

            $dbh->commit();
        }

        if ( length $oldDatabase ) {
            $db->useDatabase( $oldDatabase );
        }
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
    my $hostname = ::setupGetQuestion( 'BASE_SERVER_VHOST' );
    my $oldHostname = $::imscpOldConfig{'BASE_SERVER_VHOST'};

    # If the panel hostname has been updated, we need remove the old SSL
    # certificate if one exists
    if ( length $oldHostname
        && $oldHostname ne $hostname
        && -f "$::imscpConfig{'CONF_DIR'}/$oldHostname.pem"
    ) {
        my $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'CONF_DIR'}/$oldHostname.pem"
        )->delFile();
        return $rs if $rs;
    }

    # SSL is disabled. We need remove the SSL certificate if any and
    # return early
    if ( $sslEnabled eq 'no' ) {
        if ( -f "$::imscpConfig{'CONF_DIR'}/$hostname.pem" ) {
            return iMSCP::File->new(
                filename => "$::imscpConfig{'CONF_DIR'}/$hostname.pem"
            )->delFile();
        }

        return 0;
    }

    # If the current SSL certificate chain is valid, we return early
    if ( ::setupGetQuestion( 'PANEL_SSL_HAS_VALID_CHAIN', 'no' ) eq 'yes' ) {
        return 0;
    }

    # If no SSL certificate has been provided, we need generate a self-signed
    # SSL certificate
    if ( ::setupGetQuestion( 'PANEL_SSL_SELFSIGNED_CERTIFICATE' ) eq 'yes' ) {
        return iMSCP::OpenSSL->new(
            certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
            certificate_chain_name         => $hostname
        )->createSelfSignedCertificate( {
            common_name => $hostname,
            email       => ::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )
        } ) ? 0 : 1;
    }

    iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $::imscpConfig{'CONF_DIR'},
        certificate_chain_name         => $hostname,
        private_key_container_path     =>
            ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PATH' ),
        private_key_passphrase         =>
            ::setupGetQuestion( 'PANEL_SSL_PRIVATE_KEY_PASSPHRASE' ),
        certificate_container_path     =>
            ::setupGetQuestion( 'PANEL_SSL_CERTIFICATE_PATH' ),
        ca_bundle_container_path       =>
            ::setupGetQuestion( 'PANEL_SSL_CA_BUNDLE_PATH' )
    )->createCertificateChain() ? 0 : 1;
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
    $nginxTmpDir = $self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}
        unless -d $nginxTmpDir;

    local $@;
    eval {
        # Force re-creation of cache directory tree (needed to prevent any
        # permissions problem from an old installation)
        # See #IP-1530
        iMSCP::Dir->new( dirname => $nginxTmpDir )->remove();

        for my $dir (
            [
                $nginxTmpDir,
                $rootUName,
                $rootGName,
                0755
            ],
            [
                $self->{'config'}->{'HTTPD_CONF_DIR'},
                $rootUName,
                $rootGName,
                0755
            ],
            [
                $self->{'config'}->{'HTTPD_LOG_DIR'},
                $rootUName,
                $rootGName,
                0755
            ],
            [
                $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'},
                $rootUName,
                $rootGName,
                0755
            ],
            [
                $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'},
                $rootUName,
                $rootGName,
                0755
            ]
        ) {
            iMSCP::Dir->new( dirname => $dir->[0] )->make( {
                user  => $dir->[1],
                group => $dir->[2],
                mode  => $dir->[3]
            } );
        }

        if ( iMSCP::Service->getInstance->isSystemd() ) {
            iMSCP::Dir->new( dirname => '/run/imscp' )->make( {
                user  => $self->{'config'}->{'HTTPD_USER'},
                group => $self->{'config'}->{'HTTPD_GROUP'},
                mode  => 0755
            } );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
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
            HTTPD_SITES_ENABLED_DIR  => $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'},
            HTTPD_SSL_PROTOCOLS      => (
                version->declare( $self->{'config'}->{'HTTPD_VERSION'} ) < version->declare( '1.13.0')
              ) ? 'TLSv1.2' : 'TLSv1.2 TLSv1.3'
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
            || ::setupGetQuestion( 'IPV6_SUPPORT' )
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
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf"
        )->moveFile(
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

=item _createSymlinkForBcCompatibility 

 Create the ./gui/library/imscp-lib.php symlink to ./gui/include/imscp-lib.php
 symlink for backward compatibility with plugins.

 Return int 0 on success, other on failure

=cut

sub _createSymlinkForBcCompatibility
{
    eval {
        my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

        iMSCP::Dir->new(
            dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/library"
        )->make(
            user  => $ug,
            group => $ug,
            mode  => 0750
        );

        local $CWD = "$::imscpConfig{'GUI_ROOT_DIR'}/library";
        
        if ( -l "./imscp-lib.php" ) {
            unlink( "./imscp-lib.php" ) or die( sprintf(
                "Couldn't unlink the %s symlink: $!", "$CWD/imscp-lib.php"
            ));
        }

        symlink( '../include/imscp-lib.php', './imscp-lib.php' ) or die(
            sprintf(
                "Couldn't create the %s symlink to %s: $!",
                "$CWD/imscp-lib.php",
                "$::imscpConfig{'GUI_ROOT_DIR'}/include/imscp-lib.php"
            )
        );

        iMSCP::File->new( filename => './imscp-lib.php' )->owner(
            $ug, $ug
        ) == 0 or die( getMessageByType( 'error', {
            amount => 1, remove => TRUE
        } ));
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=cut

=item _deleteDnsZone( )

 Delete previous DNS zone if needed (i.e. case where BASER_SERVER_VHOST has been modified)

 Return int 0 on success, other on failure

=cut

sub _deleteDnsZone
{
    my ( $self ) = @_;

    return 0 unless length $::imscpOldConfig{'BASE_SERVER_VHOST'}
        && $::imscpOldConfig{'BASE_SERVER_VHOST'} ne ::setupGetQuestion( 'BASE_SERVER_VHOST' );

    my $rs = $self->{'events'}->trigger( 'beforeNamedDeleteMasterZone' );
    $rs ||= Servers::named->factory()->deleteDmn( {
        PARENT_DOMAIN_NAME => $::imscpOldConfig{'BASE_SERVER_VHOST'},
        DOMAIN_NAME        => $::imscpOldConfig{'BASE_SERVER_VHOST'},
        FORCE_DELETION     => TRUE
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
    execute(
        [ $binary, '-nv' ],
        \$stdout,
        \$stderr
    ) == 0 && $stdout =~ /PHP\s+([^\s]+)/ or die( sprintf(
        "Couldn't retrieve PHP version: %s", $stderr || 'Unknown error'
    ));
    $1;
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ( $self ) = @_;

    iMSCP::File->new(
        filename => "$self->{'cfgDir'}/frontend.old.data"
    )->delFile() if -f "$self->{'cfgDir'}/frontend.old.data";
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

    my $rs = $self->disableSites( '00_master.conf', '00_master_ssl.conf' );
    return $rs if $rs;

    for my $file ( qw/ 00_master.conf 00_master_ssl.conf / ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file";
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file"
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
        return $rs;
    }

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled" ) {
        # Nginx package as provided by Nginx
        $rs = iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf.disabled"
        )->moveFile(
            "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d/default.conf"
        );
        return $rs;
    }

    0;
}

=item _deleteMasterWebUser( )

 Delete i-MSCP master Web user

 Return int 0 on success, other on failure

=cut

sub _deleteMasterWebUser
{
    my $rs = iMSCP::SystemUser->new( force => TRUE )->delSystemUser(
        $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
    );
    $rs ||= iMSCP::SystemGroup->getInstance()->delSystemGroup(
        $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
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
