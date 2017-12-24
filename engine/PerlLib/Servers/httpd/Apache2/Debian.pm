=head1 NAME

 Servers::httpd::Apache2::Debian - i-MSCP (Debian) Apache2 server implementation

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

package Servers::httpd::Apache2::Debian;

use strict;
use warnings;
use autouse 'iMSCP::Mount' => qw/ umount /;
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Service;
use parent $main::imscpConfig{'HTTPD_PACKAGE'}, 'Servers::httpd::Interface';

=head1 DESCRIPTION

 i-MSCP (Debian) Apache2 server implementation.

=head1 PUBLIC METHODS

=over 4

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs ||= $self->_setVersion();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_setupModules();
    $rs ||= $self->_configure();
    $rs ||= $self->_installLogrotate();
    $rs ||= $self->SUPER::install();
    $rs ||= $self->_cleanup();
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval { iMSCP::Service->getInstance()->enable( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Apache2' ];
            0;
        },
        3
    );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->_restoreDefaultConfig();
    $rs ||= $self->SUPER::uninstall();
}

=item enableSites( @sites )

 See Servers::httpd::Interface::enableSites()

=cut

sub enableSites
{
    my ($self, @sites) = @_;

    my $rs = execute( [ '/usr/sbin/a2ensite', @sites ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $self->{'reload'} ||= 1;
    0;
}

=item disableSites( @sites )

 See Servers::httpd::Interface::disableSites()

=cut

sub disableSites
{
    my ($self, @sites) = @_;

    execute( [ '/usr/sbin/a2dissite', @sites ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    $self->{'reload'} ||= 1;
    0;
}

=item enableModules( @modules )

 See Servers::httpd::Interface::enableModules()

=cut

sub enableModules
{
    my ($self, @modules) = @_;

    for ( @modules ) {
        next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";

        my $rs = execute( [ '/usr/sbin/a2enmod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    $self->{'restart'} ||= 1;
    0;
}

=item disableModules( @modules )

 See Servers::httpd::Interface::disableModules()

=cut

sub disableModules
{
    my ($self, @modules) = @_;

    execute( [ '/usr/sbin/a2dismod', @modules ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    $self->{'restart'} ||= 1;
    0;
}

=item enableConfs( @conffiles )

 See Servers::httpd::Interface::enableConfs()

=cut

sub enableConfs
{
    my ($self, @conffiles) = @_;

    my $rs = execute( [ '/usr/sbin/a2enconf', @conffiles ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $self->{'reload'} ||= 1;
    0;
}

=item disableConfs( @conffiles )

 See Servers::httpd::Interface::disableConfs()

=cut

sub disableConfs
{
    my ($self, @conffiles) = @_;

    execute( [ '/usr/sbin/a2disconf', @conffiles ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    debug( $stderr ) if $stderr;

    $self->{'reload'} ||= 1;
    0;
}

=item start( )

 See Servers::httpd::Interface::start()

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Start' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Start' );
}

=item stop( )

 See Servers::httpd::Interface::stop()

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Stop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Stop' );
}

=item restart( )

 See Servers::httpd::Interface::restart()

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Restart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Restart' );
}

=item reload( )

 See Servers::httpd::Interface::reload()

=cut

sub reload
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Reload' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterApache2Reload' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _setVersion( )

 Set Apache version

 Return int 0 on success, other on failure

=cut

sub _setVersion
{
    my ($self) = @_;

    my $rs = execute( [ '/usr/sbin/apache2ctl', '-v' ], \ my $stdout, \ my $stderr );
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    if ( $stdout !~ m%Apache/([\d.]+)% ) {
        error( "Couldnt' guess Apache2 version" );
        return 1;
    }

    $self->{'config'}->{'HTTPD_VERSION'} = $1;
    debug( sprintf( 'Apache2 version set to: %s', $1 ));
    0;
}

=item _makeDirs( )

 Create directories

 Return int 0 on success, other on failure

=cut

sub _makeDirs
{
    my ($self) = @_;

    eval {
        iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_LOG_DIR'} )->make( {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ADM_GROUP'},
            mode  => 0750
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
}

=item _setupModules( )

 See Apache2 module according selected MPM

 return 0 on success, other on failure
=cut

sub _setupModules
{
    my ($self) = @_;

    if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::Apache2::Event' ) {
        my $rs = $self->disableModules( qw/ mpm_itk mpm_prefork mpm_worker cgi / );
        $rs ||= $self->enableModules(
            qw/ mpm_event access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host authz_user autoindex
            cgid deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl suexec version /
        );
        return 0;
    }

    if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::Apache2::Itk' ) {
        my $rs = $self->disableModules( qw/ mpm_event mpm_worker cgid suexec / );
        $rs ||= $self->enableModules(
            qw/ mpm_prefork mpm_itk access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host
            authz_user autoindex cgi deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl version /
        );
        return 0;
    }

    if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::Apache2::Prefork' ) {
        my $rs = $self->disableModules( qw/ mpm_event mpm_itk mpm_worker cgid / );
        $rs ||= $self->enableModules(
            qw/ mpm_prefork access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host authz_user
            autoindex cgi deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl suexec version /
        );
        return 0;
    }

    if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::Apache2::Worker' ) {
        my $rs = $self->disableModules( qw/ mpm_event mpm_itk mpm_prefork cgi / );
        $rs ||= $self->enableModules(
            qw/ mpm_worker access_compat alias auth_basic auth_digest authn_core authn_file authz_core authz_groupfile authz_host authz_user autoindex
            cgid deflate dir env expires headers mime mime_magic negotiation proxy proxy_http rewrite ssl suexec version /
        );
        return 0;
    }

    error( 'Unknown Apache2  server implementation' );
    1;
}

=item _configure( )

 Configure Apache2

 Return int 0 on success, other on failure

=cut

sub _configure
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->registerOne(
        'beforeApache2BuildConfFile',
        sub {
            my ($cfgTpl) = @_;
            ${$cfgTpl} =~ s/^NameVirtualHost[^\n]+\n//gim;
            0;
        }
    );
    $rs ||= $self->buildConfFile( "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf", "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" );

    # Turn off default access log provided by Debian package
    $rs = $self->disableConfs( 'other-vhosts-access-log.conf' );
    return $rs if $rs;

    # Remove default access log file provided by Debian package
    if ( -f "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" )->delFile();
        return $rs if $rs;
    }

    my $serverData = {
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        HTTPD_ROOT_DIR         => $self->{'config'}->{'HTTPD_ROOT_DIR'},
        VLOGGER_CONF           => "$self->{'cfgDir'}/vlogger.conf"
    };

    $rs = $self->buildConfFile( '00_nameserver.conf', "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf", undef, $serverData );
    $rs ||= $self->enableSites( '00_nameserver.conf' );
    $rs ||= $self->buildConfFile( '00_imscp.conf', "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/00_imscp.conf", undef, $serverData );
    $rs ||= $self->enableConfs( '00_imscp.conf' );
    $rs ||= $self->disableSites( 'default', 'default-ssl', '000-default.conf', 'default-ssl.conf' );
}

=item _installLogrotate( )

 Install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
    my ($self) = @_;

    $self->buildConfFile(
        'logrotate.conf',
        "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2",
        undef,
        {
            ROOT_USER     => $main::imscpConfig{'ROOT_USER'},
            ADM_GROUP     => $main::imscpConfig{'ADM_GROUP'},
            HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'}
        }
    );
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    my $rs = $self->disableSites( 'imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf' );
    return $rs if $rs;

    if ( -f "$self->{'cfgDir'}/apache.old.data" ) {
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/apache.old.data" )->delFile();
        return $rs if $rs;
    }

    for ( 'imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf' ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_" )->delFile();
        return $rs if $rs;
    }

    eval { iMSCP::Dir->new( dirname => $_ )->remove() for '/var/log/apache2/backup', '/var/log/apache2/users', '/var/www/scoreboards'; };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    for ( glob "$main::imscpConfig{'USER_WEB_DIR'}/*/logs" ) {
        $rs = umount( $_ );
        return $rs if $rs;
    }

    $rs = execute( "rm -f $main::imscpConfig{'USER_WEB_DIR'}/*/logs/*.log", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _restoreDefaultConfig( )

 Restore default Apache2 configuration

 Return int 0 on success, other on failure

=cut

sub _restoreDefaultConfig
{
    my ($self) = @_;

    if ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf" ) {
        my $rs = $self->disableSites( '00_nameserver.conf' );
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/00_nameserver.conf" )->delFile();
        return $rs if $rs;
    }

    my $confDir = -d "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available"
        ? "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available" : "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf.d";

    if ( -f "$confDir/00_imscp.conf" ) {
        my $rs = $self->disableConfs( '00_imscp.conf' );
        $rs ||= iMSCP::File->new( filename => "$confDir/00_imscp.conf" )->delFile();
        return $rs if $rs;
    }

    eval {
        for ( glob( "$main::imscpConfig{'USER_WEB_DIR'}/*/domain_disable_page" ) ) {
            iMSCP::Dir->new( dirname => $_ )->remove();
        }

        iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} )->remove();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    for ( '000-default', 'default' ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
        my $rs = $self->enableSites( $_ );
        return $rs if $rs;
    }

    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
