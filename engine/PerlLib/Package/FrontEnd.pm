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
use Class::Autouse qw/ :nostat Package::FrontEnd::Installer Package::FrontEnd::Uninstaller /;
use File::Basename;
use File::Spec;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Debug qw/ error debug getMessageByType /;
use iMSCP::EventManager;
use iMSCP::Rights 'setRights';
use iMSCP::Service;
use iMSCP::SystemUser;
use iMSCP::TemplateParser 'process';
use Try::Tiny;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP FrontEnd package.

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

    Package::FrontEnd::Installer->getInstance()->registerSetupListeners( $em );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndPreInstall' );
    $rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndPreInstall' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndInstall' );
    $rs ||= Package::FrontEnd::Installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndInstall' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndPostInstall' );
        return $rs if $rs;

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->enable( $self->{'config'}->{'HTTPD_SNAME'} );
        $serviceMngr->enable( 'imscp_panel' );

        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                push @{ $_[0] }, [ sub { $self->start(); }, 'i-MSCP FrontEnd services' ];
                0;
            },
            2
        );
        $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndPostInstall' );
    } catch {
        error( $_ );
        1;
    };
}

=item dpkgPostInvokeTasks( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub dpkgPostInvokeTasks
{
    Package::FrontEnd::Installer->getInstance()->dpkgPostInvokeTasks();
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndUninstall' );
    $rs ||= Package::FrontEnd::Uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndUninstall' );
}

=item getPriority( )

 Get package priority

 Return int package priority

=cut

sub getPriority
{
    100;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = setRights( $self->{'config'}->{'HTTPD_CONF_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $::imscpConfig{'ROOT_GROUP'},
        dirmode   => '0755',
        filemode  => '0644',
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
            $rs = setRights( "$self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}/$tmp", {
                user      => $self->{'config'}->{'HTTPD_USER'},
                group     => $self->{'config'}->{'HTTPD_GROUP'},
                dirmode   => '0700',
                filemode  => '0640',
                recursive => TRUE
            } );
            $rs ||= setRights( "$self->{'config'}->{'HTTPD_CACHE_DIR_DEBIAN'}/$tmp", {
                user  => $self->{'config'}->{'HTTPD_USER'},
                group => $::imscpConfig{'ROOT_GROUP'},
                mode  => '0700'
            } );
            return $rs if $rs;
        }
    }

    # Temporary directories as provided by nginx package (from nginx Team)
    if ( -d "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}" ) {
        $rs = setRights( $self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}, {
            user  => $::imscpConfig{'ROOT_USER'},
            group => $::imscpConfig{'ROOT_GROUP'}
        } );
        return $rs if $rs;

        for my $tmp ( 'client_temp', 'fastcgi_temp', 'proxy_temp', 'scgi_temp', 'uwsgi_temp' ) {
            next unless -d "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}/$tmp";
            $rs = setRights( "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}/$tmp", {
                user      => $self->{'config'}->{'HTTPD_USER'},
                group     => $self->{'config'}->{'HTTPD_GROUP'},
                dirmode   => '0700',
                filemode  => '0640',
                recursive => TRUE
            } );
            $rs ||= setRights( "$self->{'config'}->{'HTTPD_CACHE_DIR_NGINX'}/$tmp", {
                user  => $self->{'config'}->{'HTTPD_USER'},
                group => $::imscpConfig{'ROOT_GROUP'},
                mode  => '0700'
            } );
            return $rs if $rs;
        }
    }

    0;
}

=item setGuiPermissions( )

 Set gui permissions

 Return int 0 on success, other on failure

=cut

sub setGuiPermissions
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontendSetGuiPermissions' );
    return $rs if $rs;

    my $ug = $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'};

    $rs = setRights( $::imscpConfig{'GUI_ROOT_DIR'}, {
        user      => $ug,
        group     => $ug,
        dirmode   => '0550',
        filemode  => '0440',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/themes", {
        user      => $ug,
        group     => $ug,
        dirmode   => '0550',
        filemode  => '0440',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/data", {
        user      => $ug,
        group     => $ug,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent", {
        user      => $ug,
        group     => $ug,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= setRights( "$::imscpConfig{'GUI_ROOT_DIR'}/i18n", {
        user      => $ug,
        group     => $ug,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= setRights( $::imscpConfig{'PLUGINS_DIR'}, {
        user      => $ug,
        group     => $ug,
        dirmode   => '0750',
        filemode  => '0640',
        recursive => TRUE
    } );
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontendSetGuiPermissions' );
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data user data as provided by Modules::FtpUser module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ( undef, $data ) = @_;

    return 0 if $data->{'STATUS'} eq 'tochangepwd';

    iMSCP::SystemUser->new( username => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'} )->addToGroup(
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

    my $rs = $self->{'eventManager'}->trigger( 'beforeEnableFrontEndSites', \@sites );
    return $rs if $rs;

    for my $site ( @sites ) {
        my $target = "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site";
        my $link = $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'} . '/' . basename( $site, '.conf' );

        unless ( -f $target ) {
            error( sprintf( "Site '%s' doesn't exist", $site ));
            return 1;
        }

        next if -l $link;

        unless ( symlink( File::Spec->abs2rel( $target, $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'} ), $link ) ) {
            error( sprintf( "Couldn't enable `%s` site: %s", $site, $! ));
            return 1;
        }

        $self->{'reload'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterEnableFrontEndSites', @sites );
}

=item disableSites( @sites )

 Disable the given site(s)

 Param array @sites List of sites to disable
 Return int 0 on success, other on failure

=cut

sub disableSites
{
    my ( $self, @sites ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeDisableFrontEndSites', \@sites );
    return $rs if $rs;

    for my $site ( @sites ) {
        my $link = $self->{'config'}->{'HTTPD_SITES_ENABLED_DIR'} . '/' . basename( $site, '.conf' );
        next unless -l $link;

        $rs = iMSCP::File->new( filename => $link )->delFile();
        return $rs if $rs;

        $self->{'reload'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterDisableFrontEndSites', @sites );
}

=item start( )

 Start frontEnd

 Return int 0 on success, other on failure

=cut

sub start
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndStart' );
    $rs ||= $self->startPhpFpm();
    $rs ||= $self->startNginx();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndStart' );
}

=item stop( )

 Stop frontEnd

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndStop' );
    $rs ||= $self->stopPhpFpm();
    $rs ||= $self->stopNginx();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndStop' );
}

=item reload( )

 Reload frontEnd

 Return int 0 on success, other on failure

=cut

sub reload
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndReload' );
    $rs ||= $self->reloadPhpFpm();
    $rs ||= $self->reloadNginx();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndReload' );
}

=item restart( )

 Restart frontEnd

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndRestart' );
    $rs ||= $self->restartPhpFpm();
    $rs ||= $self->restartNginx();
    $rs ||= $self->{'eventManager'}->trigger( 'afterFrontEndRestart' );
}

=item startNginx( )

 Start frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub startNginx
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndStartNginx' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->start( $self->{'config'}->{'HTTPD_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterFrontEndStartNginx' );
    } catch {
        error( $_ );
        1;
    };
}

=item stopNginx( )

 Stop frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub stopNginx
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndStopNginx' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->stop( "$self->{'config'}->{'HTTPD_SNAME'}" );
        $self->{'eventManager'}->trigger( 'afterFrontEndStopNginx' );
    } catch {
        error( $_ );
        1;
    };
}

=item reloadNginx( )

 Reload frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub reloadNginx
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndReloadNginx' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->reload( $self->{'config'}->{'HTTPD_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterFrontEndReloadNginx' );
    } catch {
        error( $_ );
        1;
    };
}

=item restartNginx( )

 Restart frontEnd (Nginx only)

 Return int 0 on success, other on failure

=cut

sub restartNginx
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndRestartNginx' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->restart( $self->{'config'}->{'HTTPD_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterFrontEndRestartNginx' );
    } catch {
        error( $_ );
        1;
    };
}

=item startPhpFpm( )

 Start frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub startPhpFpm
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndStartPhpFpm' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->start( 'imscp_panel' );
        $self->{'eventManager'}->trigger( 'afterFrontEndStartPhpFpm' );
    } catch {
        error( $_ );
        1;
    };
}

=item stopPhpFpm( )

 Stop frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub stopPhpFpm
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndStopPhpFpm' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->stop( 'imscp_panel' );
        $self->{'eventManager'}->trigger( 'afterFrontEndStopPhpFpm' );
    } catch {
        error( $_ );
        1;
    };
}

=item reloadPhpFpm( )

 Reload frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub reloadPhpFpm
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndReloadPhpFpm' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->reload( 'imscp_panel' );
        $self->{'eventManager'}->trigger( 'afterFrontEndReloadPhpFpm' );
    } catch {
        error( $_ );
        1;
    };
}

=item restartPhpFpm( )

 Restart frontEnd (PHP-FPM instance only)

 Return int 0 on success, other on failure

=cut

sub restartPhpFpm
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndRestartPhpFpm' );
        return $rs if $rs;

        iMSCP::Service->getInstance()->restart( 'imscp_panel' );
        $self->{'eventManager'}->trigger( 'afterFrontEndRestartPhpFpm' );
    } catch {
        error( $_ );
        1;
    };
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

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'frontend', $filename, \my $cfgTpl, $tplVars );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $file = "$self->{'cfgDir'}/$file" unless -d $path && $path ne './';
        $cfgTpl = iMSCP::File->new( filename => $file )->get();
        return 1 unless defined $cfgTpl;
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options );
    return $rs if $rs;

    $cfgTpl = $self->_buildConf( $cfgTpl, $filename, $tplVars );
    $cfgTpl =~ s/\n{2,}/\n\n/g; # Remove any duplicate blank lines

    $rs = $self->{'eventManager'}->trigger( 'afterFrontEndBuildConfFile', \$cfgTpl, $filename, $tplVars, $options );
    return $rs if $rs;

    my $fh = iMSCP::File->new( filename => $options->{'destination'} // "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$filename" );
    $rs = $fh->set( $cfgTpl );
    $rs ||= $fh->save();
    $rs ||= $fh->owner(
        ( $options->{'user'} ? $options->{'user'} : $::imscpConfig{'ROOT_USER'} ),
        ( $options->{'group'} ? $options->{'group'} : $::imscpConfig{'ROOT_GROUP'} )
    );
    $rs ||= $fh->mode( $options->{'mode'} ? $options->{'mode'} : 0644 );
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
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$::imscpConfig{'CONF_DIR'}/frontend";
    $self->_mergeConfig() if -f "$self->{'cfgDir'}/frontend.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/frontend.data",
        readonly    => $::execmode ne 'setup',
        nodeferring => $::execmode eq 'setup';
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
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/frontend.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/frontend.data", readonly => TRUE;
        debug( 'Merging old configuration with new configuration...' );
        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }
        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/frontend.data.dist" )->moveFile( "$self->{'cfgDir'}/frontend.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
}

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
    $self->{'eventManager'}->trigger( 'beforeFrontEndBuildConf', \$cfgTpl, $filename, $tplVars );
    $cfgTpl = process( $tplVars, $cfgTpl );
    $self->{'eventManager'}->trigger( 'afterFrontEndBuildConf', \$cfgTpl, $filename, $tplVars );
    $cfgTpl;
}

=item END

 Start, restart or reload frontEnd services: nginx or/and imscp_panel when required

 Return int Exit code

=cut

END
    {
        return if $? || $::execmode eq 'setup';

        if ( $::execmode eq 'uninstaller' ) {
            $? = Package::FrontEnd->getInstance()->restartNginx();
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
