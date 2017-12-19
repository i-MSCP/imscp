=head1 NAME

 Servers::php::Debian - i-MSCP (Debian) PHP server implementation

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

package Servers::php::Debian;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isStringInList /;
use Class::Autouse qw/ :nostat iMSCP::Getopt iMSCP::ProgramFinder Servers::httpd /;
use iMSCP::Debug qw/ error getMessageByType /;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Service;
use Servers::php;
use parent 'Servers::php::Abstract';

=head1 DESCRIPTION

 i-MSCP (Debian) PHP server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self, $eventManager) = @_;

    my $rs = $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->askForPhpSapi( @_ ) };
            0;
        }
    );
    $rs ||= $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->askForPhpConfigLevel( @_ ) };
            0;
        }
    );
    $rs ||= $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->askForFastCGIconnectionType( @_ ) };
            0;
        }
    );
}

=item askForPhpSapi( \%dialog )

 Ask for PHP SAPI

 Param iMSCP::Dialog \%dialog
 Return int 0 to go on next question, 30 to go back to the previous question

=cut

sub askForPhpSapi
{
    my ($self, $dialog) = @_;

    my $value = main::setupGetQuestion( 'PHP_SAPI', $self->{'config'}->{'PHP_SAPI'} || ( iMSCP::Getopt->preseed ? 'fpm' : '' ));
    my %choices = ( 'fpm', 'PHP through PHP FastCGI Process Manager (fpm SAPI)' );

    if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::Apache2::Itk' ) {
        # Apache2 PHP module only works with Apache's prefork based MPM
        # We allow it only with the Apache's ITK MPM because the Apache's prefork MPM
        # doesn't allow to constrain each individual vhost to a particular system user/group.
        $choices{'apache2handler'} = 'PHP through Apache2 PHP module (apache2handler SAPI)';
    } else {
        # Apache2 Fcgid module doesn't work with Apache's ITK MPM
        # https://lists.debian.org/debian-apache/2013/07/msg00147.html
        $choices{'cgi'} = 'PHP through Apache2 Fcgid module (cgi SAPI)';
    }

    if ( isStringInList( $main::reconfigure, 'php', 'servers', 'all', 'forced' ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'fpm' );
\\Z4\\Zb\\ZuPHP configuration level\\Zn

Please choose the PHP SAPI for customers.
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'PHP_SAPI'} = $value;
    0;
}

=item askForPhpConfigLevel( \%dialog )

 Ask for PHP config level

 Param iMSCP::Dialog \%dialog
 Return int 0 to go on next question, 30 to go back to the previous question

=cut

sub askForPhpConfigLevel
{
    my ($self, $dialog) = @_;

    my $value = main::setupGetQuestion( 'PHP_CONFIG_LEVEL', $self->{'config'}->{'PHP_CONFIG_LEVEL'} || ( iMSCP::Getopt->preseed ? 'per_site' : '' ));
    my %choices = ( 'per_site', 'Per site (recommended)', 'per_domain', 'Per domain', 'per_user', 'Per user' );

    if ( isStringInList( $main::reconfigure, 'php', 'servers', 'all', 'forced' ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'per_site' );
\\Z4\\Zb\\ZuPHP configuration level\\Zn

Please choose the PHP configuration level for customers.

Available levels are:

\\Z4Per site  :\\Zn Different PHP configuration for each domain, including subdomains
\\Z4Per domain:\\Zn Identical PHP configuration for each domain, including subdomains
\\Z4Per user  :\\Zn Identical PHP configuration for all domains, including subdomains
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'PHP_CONFIG_LEVEL'} = $value;
    0;
}

=item askForFastCGIconnectionType( )

 Ask for FastCGI connection type (PHP-FPM)

 Param iMSCP::Dialog \%dialog
 Return int 0 to go on next question, 30 to go back to the previous question

=cut

sub askForFastCGIconnectionType
{
    my ($self, $dialog) = @_;

    return 0 unless $self->{'config'}->{'PHP_SAPI'} eq 'fpm';

    my $value = main::setupGetQuestion( 'PHP_FPM_LISTEN_MODE', $self->{'config'}->{'PHP_FPM_LISTEN_MODE'} || ( iMSCP::Getopt->preseed ? 'uds' : '' ));
    my %choices = ( 'tcp', 'TCP sockets over the loopback interface', 'uds', 'Unix Domain Sockets (recommended)' );

    if ( isStringInList( $main::reconfigure, 'php', 'servers', 'all', 'forced' ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<"EOF", \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'uds' );
\\Z4\\Zb\\ZuPHP-FPM - FastCGI address type\\Zn

Please choose the FastCGI connection type that you want use.
\\Z \\Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'PHP_FPM_LISTEN_MODE'} = $value;
    0;
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
        my $httpd = Servers::httpd->factory();

        # Disable i-MSCP Apache2 fcgid modules. It will be re-enabled in postinstall if needed
        $httpd->disableModules( qw/ fcgid_imscp / ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        # Disable default Apache2 conffile for CGI programs
        # FIXME: One administrator could rely on default configuration (outside of i-MSCP)
        $httpd->disableConfs( 'serve-cgi-bin.conf' ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        my $serviceMngr = iMSCP::Service->getInstance();

        # Disable PHP session cleaner services as we don't rely on them
        # FIXME: One administrator could rely on those services (outside of i-MSCP)
        for ( qw/ phpsessionclean phpsessionclean.timer / ) {
            next unless $serviceMngr->hasService( $_ );
            $serviceMngr->stop( $_ );
            $serviceMngr->disable( $_ );
        }

        for ( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
            next unless /^[\d.]+$/;

            # Tasks for apache2handler SAPI

            if ( $self->{'config'}->{'PHP_SAPI'} ne 'apache2handler' || $self->{'config'}->{'PHP_VERSION'} ne $_ ) {
                # Disable Apache2 PHP module if PHP version is other than selected PHP alternative for customers
                $httpd->disableModules( "php$_" ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }

            # Tasks for cgi SAPI

            # Disable default Apache2 conffile
            $httpd->disableConfs( "php$_-cgi.conf" ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            # Tasks for fpm SAPI

            if ( $serviceMngr->hasService( "php$_-fpm" ) ) {
                # Stop PHP-FPM instance
                $self->stop( $_ ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

                # Disable PHP-FPM service if selected SAPI for customer is not FPM or if PHP version
                # is other than selected PHP alternative for customers
                $serviceMngr->disable( "php$_-fpm" ) if $self->{'config'}->{'PHP_SAPI'} ne 'fpm' || $self->{'config'}->{'PHP_VERSION'} ne $_;
            }

            # Disable default Apache2 conffile
            $httpd->disableConfs( "php$_-fpm.conf " ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            # Reset PHP-FPM pool confdir
            for ( grep !/www\.conf$/, glob "/etc/php/$_/fpm/pool.d/*.conf" ) {
                iMSCP::File->new( filename => $_ )->delFile() == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );
            }
        }

        # Create/Reset/Remove FCGI starter rootdir, depending of selected PHP SAPI for customers
        my $dir = iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_FCGI_STARTER_DIR'} );
        $dir->remove();
        if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
            $dir->make( {
                user  => $main::imscpConfig{'ROOT_USER'},
                group => $main::imscpConfig{'ROOT_GROUP'},
                mode  => 0555
            } );
        }

        $self->SUPER::preinstall() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpPreinstall' );
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
        my $httpd = Servers::httpd->factory();
        my $serverData = {
            HTTPD_USER                          => $httpd->getRunningUser() || 'www-data',
            HTTPD_GROUP                         => $httpd->getRunningGroup() || 'www-data',
            PEAR_DIR                            => $self->{'config'}->{'PHP_PEAR_DIR'} || '/usr/share/php',
            PHP_APCU_CACHE_ENABLED              => $self->{'config'}->{'PHP_APCU_CACHE_ENABLED'} // 1,
            PHP_APCU_CACHE_MAX_MEMORY           => $self->{'config'}->{'PHP_APCU_CACHE_MAX_MEMORY'} || 32,
            PHP_CONF_DIR_PATH                   => '/etc/php',
            PHP_FPM_LOG_LEVEL                   => $self->{'config'}->{'PHP_FPM_LOG_LEVEL'} || 'error',
            PHP_FPM_EMERGENCY_RESTART_THRESHOLD => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_THRESHOLD'} || 10,
            PHP_FPM_EMERGENCY_RESTART_INTERVAL  => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_INTERVAL'} || '1m',
            PHP_FPM_PROCESS_CONTROL_TIMEOUT     => $self->{'config'}->{'PHP_FPM_PROCESS_CONTROL_TIMEOUT'} || '60s',
            PHP_FPM_PROCESS_MAX                 => $self->{'config'}->{'PHP_FPM_PROCESS_MAX'} || 0,
            PHP_FPM_RLIMIT_FILES                => $self->{'config'}->{'PHP_FPM_RLIMIT_FILES'} || 4096,
            PHP_OPCODE_CACHE_ENABLED            => $self->{'config'}->{'PHP_OPCODE_CACHE_ENABLED'} // 1,
            PHP_OPCODE_CACHE_MAX_MEMORY         => $self->{'config'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'} || 32,
            TIMEZONE                            => $main::imscpConfig{'TIMEZONE'} || 'UTC'
        };

        for my $phpVersion( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
            next unless $phpVersion =~ /^[\d.]+$/;

            @{$serverData}{qw/ PHP_FPM_POOL_DIR_PATH PHP_VERSION /} = ( "/etc/php/$phpVersion/fpm/pool.d", $phpVersion );

            # Master php.ini file for apache2handler, cli, cgi and fpm SAPIs
            for ( qw/ apache2 cgi cli fpm / ) {
                $rs = $self->buildConfFile( "$_/php.ini", "/etc/php/$phpVersion/$_/php.ini", undef, $serverData );
                last if $rs;
            }

            # Master conffile for fpm SAPI
            $rs ||= $self->buildConfFile( 'fpm/php-fpm.conf', "/etc/php/$phpVersion/fpm/php-fpm.conf", undef, $serverData );
            # Default pool conffile for fpm SAPI
            $rs ||= $self->buildConfFile( 'fpm/pool.conf.default', "/etc/php/$phpVersion/fpm/pool.d/www.conf", undef, $serverData );
            $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        # Build the Apache2 Fcgid module conffile
        $httpd->buildConfFile(
            "$self->{'cfgDir'}/cgi/apache_fcgid_module.conf",
            "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid_imscp.conf",
            undef,
            {
                PHP_FCGID_MAX_REQUESTS_PER_PROCESS => $self->{'config'}->{'PHP_FCGID_MAX_REQUESTS_PER_PROCESS'} || 900,
                PHP_FCGID_MAX_REQUEST_LEN          => $self->{'config'}->{'PHP_FCGID_MAX_REQUEST_LEN'} || 1073741824,
                PHP_FCGID_IO_TIMEOUT               => $self->{'config'}->{'PHP_FCGID_IO_TIMEOUT'} || 600,
                PHP_FCGID_MAX_PROCESS              => $self->{'config'}->{'PHP_FCGID_MAX_PROCESS'} || 1000
            }
        ) == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        my $file = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid.load" );
        my $cfgTpl = $file->getAsRef();
        defined $cfgTpl or die( sprintf( "Couldn't read the %s file", $file->{'filename'} ));

        $file = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid_imscp.load" );
        $file->set( "<IfModule !mod_fcgid.c>\n" . ${$cfgTpl} . "</IfModule>\n" );
        $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->_cleanup();
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
        my $httpd = Servers::httpd->factory();

        if ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
            # Enable Apache2 PHP module for selected PHP alternative for customers
            $httpd->enableModules( "php$self->{'config'}->{'PHP_VERSION'}" ) == 0 or die (
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
            # Enable Apache2 fcgid module
            $httpd->enableModules( qw/ fcgid fcgid_imscp / ) == 0 or die (
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
            # Enable proxy_fcgi module
            $httpd->enableModules( qw/ proxy_fcgi setenvif / ) == 0 or die (
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );

            # Enable PHP-FPM service for selected PHP alternative for customers
            iMSCP::Service->getInstance()->enable( "php$self->{'config'}->{'PHP_VERSION'}-fpm" );

            # Schedule start of PHP-FPM service for selected PHP alternative for customers
            $self->{'eventManager'}->register(
                'beforeSetupRestartServices',
                sub {
                    push @{$_[0]}, [ sub { $self->start(); }, "PHP-FPM $self->{'config'}->{'PHP_VERSION'}" ];
                    0;
                },
                3
            ) == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        } else {
            die( 'Unknown PHP SAPI' );
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

    my $httpd = Servers::httpd->factory();

    $rs = $httpd->disableModules( 'fcgid_imscp' ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    return $rs if $rs;

    for ( 'fcgid_imscp.conf', 'fcgid_imscp.load' ) {
        next unless -f "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_";
        $rs = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_" )->delFile();
        return $rs if $rs;
    }

    for ( iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
        next unless /^[\d.]+$/ && -f "/etc/init/php$_-fpm.override";
        $rs = iMSCP::File->new( filename => "/etc/init/php$_-fpm.override" )->delFile();
        last if $rs;
    }

    eval { iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_FCGI_STARTER_DIR'} )->remove(); };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpUninstall' );
}

=item addDomain( \%moduleData )

 See Servers::php::Abstract::addDomain()

=cut

sub addDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddDomain', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        $rs = $self->_buildApache2HandlerConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $rs = $self->_buildCgiConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        $rs = $self->_buildFpmConfig( $moduleData );
    } else {
        error( 'Unknown PHP SAPI' );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpAddDomain', $moduleData );
}

=item disableDomain( \%moduleData )

 See Servers::php::Abstract::disableDomain()

=cut

sub disableDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpdDisableDomain', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        if ( -f "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" )->delFile();
            $self->{'reload'} = 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpDisableDomain', $moduleData );
}

=item deleteDomain( \%moduleData )

 See Servers::php::Abstract::deleteDomain()

=cut

sub deleteDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDeleteDomain', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        if ( -f "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" )->delFile();
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdDeleteDomain', $moduleData );
}

=item addSubbdomain( \%moduleData )

 See Servers::php::Abstract::addSubbdomain()

=cut

sub addSubbdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddSubdomain', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        $rs = $self->_buildApache2HandlerConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $rs = $self->_buildCgiConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        $rs = $self->_buildFpmConfig( $moduleData );
    } else {
        error( 'Unknown PHP SAPI' );
        return 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpAddSubdomain', $moduleData );
}

=item disableSub( \%moduleData )

 See Servers::php::Abstract::disableSub()

=cut

sub disableSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDisableSubdomain', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        for ( glob "/etc/php/*/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => $_ )->delFile();
            last if $rs;
            $self->{'reload'} ||= 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdDisableSubdomain', $moduleData );
}

=item deleteSubdomain( \%moduleData )

 See Servers::php::Abstract::deleteSubdomain()

=cut

sub deleteSubdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDeleteSubdomain', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        for ( glob "/etc/php/*/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => $_ )->delFile();
            last if $rs;
            $self->{'reload'} ||= 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpDeleteSubdomain', $moduleData );
}

=item start( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 See Servers::php::Abstract::start()

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmStart', $version );
}

=item stop( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 See Servers::php::Abstract::stop()

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmStop', $version );
}

=item reload( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 See Servers::php::Abstract::reload()

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmReload', $version );
}

=item restart( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 See Servers::php::Abstract::restart()

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmRestart', $version );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _guessVariablesForSelectedPhpAlternative( )

 See Servers::php::Abstract::_guessVariablesForSelectedPhpAlternative()

=cut

sub _guessVariablesForSelectedPhpAlternative
{
    my ($self) = @_;

    my $phpPath = iMSCP::ProgramFinder::find( 'php' ) or die( "Couldn't find the PHP (CLI) command in search path for the selected PHP alternative" );

    my ($phpVersion) = `$phpPath -nv 2> /dev/null` =~ /^PHP\s+([\d.]+)/ or die( "Couldn't guess version for selected PHP alternative" );

    $self->{'config'}->{'PHP_VERSION_FULL'} = $phpVersion;
    $phpVersion =~ s/\.\d+$//;
    $self->{'config'}->{'PHP_VERSION'} = $phpVersion;

    my ($phpConfDir) = `$phpPath -ni 2> /dev/null | grep '(php.ini) Path'` =~ /([^\s]+)$/ or die(
        "Couldn't guess the PHP configuration directory path for the selected PHP alternative"
    );

    my $phpConfBaseDir = dirname( $phpConfDir );
    $self->{'config'}->{'PHP_CONF_DIR_PATH'} = $phpConfBaseDir;
    $self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'} = "$phpConfBaseDir/fpm/pool.d";

    unless ( -d $self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'} ) {
        $self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'} = '';
        die( sprintf( "Couldn't guess the `%s' PHP configuration parameter value for the selected PHP alternative: directory doesn't exist.", $_ ));
    }

    $self->{'config'}->{'PHP_CLI_BIN_PATH'} = iMSCP::ProgramFinder::find( "php$phpVersion" );
    $self->{'config'}->{'PHP_FCGI_BIN_PATH'} = iMSCP::ProgramFinder::find( "php-cgi$phpVersion" );
    $self->{'config'}->{'PHP_FPM_BIN_PATH'} = iMSCP::ProgramFinder::find( "php-fpm$phpVersion" );

    for ( qw/ PHP_CLI_BIN_PATH PHP_FCGI_BIN_PATH PHP_FPM_BIN_PATH / ) {
        $self->{'config'}->{$_} or die( sprintf( "Couldn't guess the `%s' PHP configuration parameter value for the selected PHP alternative.", $_ ));
    }
}

=item _cleanup( )

 See Servers::php::Abstract::_cleanup()

=cut

sub _cleanup
{
    my ($self) = @_;

    if ( "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" ) {
        my $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/php.old.data" )->delFile();
        return $rs if $rs;
    }

    eval { iMSCP::Dir->new( dirname => '/etc/php5' )->remove(); };
    if ( $@ ) {
        error( $@ );
        return $rs if $rs;
    }

    my $httpd = Servers::httpd->factory();
    $rs = $httpd->disableModules( qw/ fastcgi_imscp php5 php5_cgi php5filter php_fpm_imscp proxy_handler / );
    return $rs if $rs;

    for ( 'fastcgi_imscp.conf', 'fastcgi_imscp.load', 'php_fpm_imscp.conf', 'php_fpm_imscp.load' ) {
        next unless -f "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_";
        $rs = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_" )->delFile();
        return $rs if $rs;
    }

    $self->SUPER::_cleanup();
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item END

 Restart, reload or start PHP FastCGI process manager for selected PHP alternative when needed

=cut

END
    {
        return if $? || ( defined $main::execmode && $main::execmode eq 'setup' );

        my $instance = __PACKAGE__->hasInstance();

        return 0 unless $instance && $instance->{'config'}->{'PHP_SAPI'} eq 'fpm' && (
            my $action = $instance->{'restart'} ? 'restart' : ( $instance->{'reload'} ? 'reload' : ( $instance->{'start'} ? ' start' : undef ) )
        );

        iMSCP::Service->getInstance()->registerDelayedAction(
            "php$instance->{'config'}->{'PHP_VERSION'}-fpm", [ $action, sub { $instance->$action(); } ], Servers::php::getPriority()
        );
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
