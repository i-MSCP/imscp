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
use File::Spec;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isStringInList /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use Class::Autouse qw/ :nostat iMSCP::Getopt iMSCP::ProgramFinder Servers::httpd /;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser qw/ processByRef getBlocByRef replaceBlocByRef /;
use parent 'Common::SingletonClass';

# php server instance
my $INSTANCE;

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
    $INSTANCE ||= __PACKAGE__->getInstance();
}

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
            push @{$_[0]}, sub { $self->factory()->askForPhpSapi( @_ ) };
            0;
        }
    );
    $rs ||= $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->factory()->askForPhpConfigLevel( @_ ) };
            0;
        }
    );
    $rs ||= $eventManager->register(
        'beforeSetupDialog',
        sub {
            push @{$_[0]}, sub { $self->factory()->askForFastCGIconnectionType( @_ ) };
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
    my %choices = ( 'fpm', 'PHP through PHP fastCGI Process Manager (fpm SAPI)' );

    # Apache2 Fcgid module doesn't work with Apache's ITK MPM
    # https://lists.debian.org/debian-apache/2013/07/msg00147.html
    if ( $main::imscpConfig{'HTTPD_PACKAGE'} ne 'Servers::httpd::Apache2::Itk' ) {
        $choices{'cgi'} = 'PHP through Apache2 Fcgid module (cgi SAPI)';
    }

    # Apache2 PHP module only works with Apache's prefork MPM
    # We allow it only with the Apache's ITK MPM because the Apache's prefork MPM
    # doesn't allow to constrain each individual vhost to a particular system user and group.
    if ( $main::imscpConfig{'HTTPD_PACKAGE'} eq 'Servers::httpd::Apache2::Itk' ) {
        $choices{'apache2handler'} = 'PHP through Apache2 PHP module (apache2handler SAPI)';
    }

    if ( isStringInList( $main::reconfigure, 'php', 'servers', 'all', 'forced' )
        || !isStringInList( $value, keys %choices )
    ) {
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
    my %choices = ( 'per_site', 'Per site', 'per_domain', 'Per domain', 'per_user', 'Per user' );

    if ( isStringInList( $main::reconfigure, 'php', 'servers', 'all', 'forced' )
        || !isStringInList( $value, keys %choices )
    ) {
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

    if ( isStringInList( $main::reconfigure, 'php', 'servers', 'all', 'forced' )
        || !isStringInList( $value, keys %choices )
    ) {
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

        # Disable Apache2 modules. If one is required, it will be re-enabled in postinstall
        $httpd->disableModules( qw/ fastcgi fcgid fcgid_imscp php5 php5_cgi php5filter proxy_fcgi proxy_handler / ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        # Disable default Apache2 conffile for CGI programs 
        $httpd->disableConfs( 'serve-cgi-bin.conf' ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        my $serviceMngr = iMSCP::Service->getInstance();

        # Disable PHP session cleaner services as we don't rely on them
        for ( qw/ phpsessionclean phpsessionclean.timer / ) {
            next unless $serviceMngr->hasService( $_ );
            $serviceMngr->stop( $_ );
            $serviceMngr->disable( $_ );
        }

        for ( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
            next unless /^[\d.]+$/;

            # Tasks for apache2handler SAPI

            if ( $self->{'config'}->{'PHP_SAPI'} ne 'apache2handler' || $self->{'config'}->{'PHP_VERSION'} ne $_ ) {
                # Disable Apache2 PHP module if PHP version is other than selected
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
                # is other than selected
                $serviceMngr->disable( "php$_-fpm" ) if $self->{'config'}->{'PHP_SAPI'} ne 'fpm' || $self->{'config'}->{'PHP_VERSION'} ne $_;
            }

            # Disable default Apache2 conffile file
            $httpd->disableConfs( "php$_-fpm.conf " ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            # Reset PHP-FPM pool confdir
            for ( grep !/www\.conf$/, glob "/etc/php/$_/fpm/pool.d/*.conf" ) {
                iMSCP::File->new( filename => $_ )->delFile() == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );
            }
        }

        # Create/Reset/Remove FCGI starter rootdir, depending of selected PHP SAPI
        my $dir = iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_FCGI_STARTER_DIR'} );
        $dir->remove();
        if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
            $dir->make( {
                user  => $main::imscpConfig{'ROOT_USER'},
                group => $main::imscpConfig{'ROOT_GROUP'},
                mode  => 0555
            } );
        }

        $self->_guessVariablesForSelectedPhpAlternative();
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
            PHP_FPM_LOG_LEVEL                   => $self->{'config'}->{'PHP_FPM_LOG_LEVEL'} || 'error',
            PHP_FPM_EMERGENCY_RESTART_THRESHOLD => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_THRESHOLD'},
            PHP_FPM_EMERGENCY_RESTART_INTERVAL  => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_INTERVAL'},
            PHP_FPM_PROCESS_CONTROL_TIMEOUT     => $self->{'config'}->{'PHP_FPM_PROCESS_CONTROL_TIMEOUT'},
            PHP_FPM_PROCESS_MAX                 => $self->{'config'}->{'PHP_FPM_PROCESS_MAX'},
            PHP_FPM_RLIMIT_FILES                => $self->{'config'}->{'PHP_FPM_RLIMIT_FILES'},
            PHP_OPCODE_CACHE_ENABLED            => $self->{'config'}->{'PHP_OPCODE_CACHE_ENABLED'},
            PHP_OPCODE_CACHE_MAX_MEMORY         => $self->{'config'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'},
            PHP_APCU_CACHE_ENABLED              => $self->{'config'}->{'PHP_APCU_CACHE_ENABLED'},
            PHP_APCU_CACHE_MAX_MEMORY           => $self->{'config'}->{'PHP_APCU_CACHE_MAX_MEMORY'},
            TIMEZONE                            => $main::imscpConfig{'TIMEZONE'},
            PHP_CONF_DIR_PATH                   => '/etc/php'
        };

        for my $phpVersion( sort iMSCP::Dir->new( dirname => '/etc/php' )->getDirs() ) {
            next unless $phpVersion =~ /^[\d.]+$/;

            @{$serverData}{qw/ PHP_FPM_POOL_DIR_PATH PHP_VERSION /} = ( "/etc/php/$phpVersion/fpm/pool.d", $phpVersion );

            # Master php.ini file for apache2handler, cli, cgi and fpm SAPIs
            for ( qw/ apache2 cgi cli fpm / ) {
                $rs = $self->_buildConfFile( "$_/php.ini", "/etc/php/$phpVersion/$_/php.ini", {}, $serverData );
                last if $rs;
            }

            # Master conffile for fpm SAPI
            $rs ||= $self->_buildConfFile( 'fpm/php-fpm.conf', "/etc/php/$phpVersion/fpm/php-fpm.conf", {}, $serverData );
            # Primary pool conffile for fpm SAPI
            $rs ||= $self->_buildConfFile( 'fpm/pool.conf.default', "/etc/php/$phpVersion/fpm/pool.d/www.conf", {}, $serverData );
            $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }
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
            # Enable Apache2 PHP module for selected PHP version
            $httpd->enableModules( "php$self->{'config'}->{'PHP_VERSION'}" ) == 0 or die (
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
            # Build apache2 conffile for Fcgid module
            $httpd->buildConfFile( "$self->{'cfgDir'}/cgi/apache_fcgid_module.conf", {},
                { destination => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid_imscp.conf" }
            ) == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            my $file = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid.load" );
            my $cfgTpl = $file->get();
            defined $cfgTpl or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));

            $file = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/fcgid_imscp.load" );
            $cfgTpl = "<IfModule !mod_fcgid.c>\n" . $cfgTpl . "</IfModule>\n";
            $file->set( $cfgTpl );
            $rs = $file->save();
            $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
            $rs ||= $file->mode( 0644 );
            $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            # Enable fcgid module
            $httpd->enableModules( qw/ fcgid fcgid_imscp / ) == 0 or die (
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
            # Enable PHP-FPM service for selected PHP version
            iMSCP::Service->getInstance()->enable( "php$self->{'config'}->{'PHP_VERSION'}-fpm" );

            # Schedule start of PHP-FPM service for selected PHP version
            $self->{'eventManager'}->register(
                'beforeSetupRestartServices',
                sub {
                    push @{$_[0]}, [ sub { $self->start(); }, "PHP-FPM $self->{'config'}->{'PHP_VERSION'}" ];
                    0;
                },
                3
            ) == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            # Enable proxy_fcgi module
            $httpd->enableModules( qw/ proxy_fcgi setenvif / ) == 0 or die ( getMessageByType( 'error',
                { amount => 1, remove => 1 } ) || 'Unknown error' );
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

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpSetEnginePermissions' );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $rs = setRights( $self->{'config'}->{'PHP_FCGI_STARTER_DIR'},
            {
                user  => $main::imscpConfig{'ROOT_USER'},
                group => $main::imscpConfig{'ROOT_GROUP'},
                mode  => '0555'
            }
        );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpSetEnginePermissions' );
}

=item addDmn( \%moduleData )

 Process addDmn tasks

 Param hashref \%moduleData Data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddDmn', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        $rs = $self->_buildFpmConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $rs = $self->_buildCgiConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        $rs = $self->_buildApache2HandlerConfig( $moduleData );
    } else {
        error( 'Unknown PHP SAPI' );
        return 1;
    }

    $rs ||= $self->{'eventManager'}->registerOne(
        'afterApache2AddFiles',
        sub {
            eval {
                iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/phptmp" )->make( {
                    user  => $moduleData->{'USER'},
                    group => $moduleData->{'GROUP'},
                    perm  => 0750
                } )
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            0;
        }
    ) if $moduleData->{'DOMAIN_TYPE'} eq 'dmn';

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpAddDmn', $moduleData );
}

=item disableDmn( \%moduleData )

 Process disableDmn tasks

 Param hashref \%moduleData Data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpdDisableDmn', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        if ( -f "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" )->delFile();
            $self->{'reload'} = 1;
        }
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpDisableDmn', $moduleData );
}

=item deleteDmn( \%moduleData )

 Process deleteDmn tasks

 Param hashref \%moduleData Data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDelDmn', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        if ( -f "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" )->delFile();
        }
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdDelDmn', $moduleData );
}

=item addSub( \%moduleData )

 Process addSub tasks

 Param hashref \%moduleData Data as provided by SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddSub', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        $rs = $self->_buildFpmConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $rs = $self->_buildCgiConfig( $moduleData );
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        $rs = $self->_buildApache2HandlerConfig( $moduleData );
    } else {
        error( 'Unknown PHP SAPI' );
        return 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpAddSub', $moduleData );
}

=item disableSub( \%moduleData )

 Process disableSub tasks

 Param hashref \%moduleData Data as provided by SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDisableSub', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        for ( glob "/etc/php/*/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => $_ )->delFile();
            last if $rs;
        }

        $self->{'reload'} ||= 1;
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdDisableSub', $moduleData );
}

=item deleteSub( \%moduleData )

 Process deleteSub tasks

 Param hashref \%moduleData Data as provided by SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDelSub', $moduleData );
    return $rs if $rs;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        for ( glob "/etc/php/*/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf" ) {
            $rs = iMSCP::File->new( filename => $_ )->delFile();
            last if $rs;
        }

        $self->{'reload'} ||= 1;
    } elsif ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove() };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpDelSub', $moduleData );
}

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    70;
}

=item start( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Start PHP FastCGI Process Manager 'PHP-FPM'

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmStart', $version );
}

=item stop( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Stop PHP FastCGI Process Manager 'PHP-FPM'

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmStop', $version );
}

=item reload( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Reload PHP FastCGI Process Manager 'PHP-FPM'

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmReload', $version );
}

=item restart( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Restart PHP FastCGI Process Manager 'PHP-FPM'

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
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmRestart', $version );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::php

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ start restart reload /} = ( 0, 0, 0 );
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/php.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/php.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => defined $main::execmode && $main::execmode eq 'setup';
    $self->{'eventManager'}->register( 'beforeApache2BuildConfFile', \&_buildApache2Config, 100 );
    $self;
}

=item _mergeConfig()

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/php.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/php.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/php.data", readonly => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/php.data.dist" )->moveFile( "$self->{'cfgDir'}/php.data" ) == 0 or die(
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

    0;
}

=item _buildConfFile( $srcFile, $trgFile, [, \%moduleData = { } [, \%serverData [, \%parameters = { } ] ] ] )

 Build the given PHP configuration file

 Param string $srcFile Source file path relative to the i-MSCP php configuration directory
 Param string $trgFile Target file path
 Param hashref \%data OPTIONAL Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Param hashref \%data OPTIONAL Server data (Server data have higher precedence than modules data)
 Param hashref \%parameters OPTIONAL parameters:
  - user  : File owner (default: root)
  - group : File group (default: root
  - mode  : File mode (default: 0644)
 Return int 0 on success, other on failure

=cut

sub _buildConfFile
{
    my ($self, $srcFile, $trgFile, $moduleData, $serverData, $parameters) = @_;
    $moduleData //= {};
    $serverData //= {};
    $parameters //= {};

    my ($filename, $path) = fileparse( $srcFile );

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'php', $filename, \ my $cfgTpl, $moduleData, $serverData );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $srcFile = File::Spec->canonpath( "$self->{'cfgDir'}/$path/$filename" );
        $cfgTpl = iMSCP::File->new( filename => $srcFile )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", $srcFile ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforePhpBuildConfFile', \$cfgTpl, $filename, $moduleData, $serverData );
    return $rs if $rs;

    processByRef( $serverData, \$cfgTpl );
    processByRef( $moduleData, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger( 'afterPhpdBuildConfFile', \$cfgTpl, $filename, $moduleData, $serverData );
    return $rs if $rs;

    my $fh = iMSCP::File->new( filename => $trgFile );
    $rs = $fh->set( $cfgTpl );
    $rs ||= $fh->save();
    return $rs unless %{$parameters};

    $rs = $fh->owner( $parameters->{'user'} // $main::imscpConfig{'ROOT_USER'}, $parameters->{'group'} // $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $fh->mode( $parameters->{'mode'} // 0644 );
}

=item _buildFpmConfig( \%moduleData )

 Build PHP fpm configuration for the given domain

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on sucess, other on failure

=cut

sub _buildFpmConfig
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmSapiBuildConf', $moduleData );
    return $rs if $rs;

    my ($poolName, $emailDomain);
    if ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_user' ) {
        $poolName = $moduleData->{'ROOT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'ROOT_DOMAIN_NAME'};
    } elsif ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_domain' ) {
        $poolName = $moduleData->{'PARENT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    } else {
        $poolName = $moduleData->{'DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    }

    if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
        my $serverData = {
            EMAIL_DOMAIN                 => $emailDomain,
            PHP_FPM_LISTEN_ENDPOINT      => ( $self->{'config'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds' )
                ? "/run/php/php$self->{'config'}->{'PHP_VERSION'}-fpm-$poolName.sock"
                : '127.0.0.1:' . ( $self->{'config'}->{'PHP_FPM_LISTEN_PORT_START'}+$moduleData->{'PHP_FPM_LISTEN_PORT'} ),
            PHP_FPM_MAX_CHILDREN         => $self->{'config'}->{'PHP_FPM_MAX_CHILDREN'} // 6,
            PHP_FPM_MAX_REQUESTS         => $self->{'config'}->{'PHP_FPM_MAX_REQUESTS'} // 1000,
            PHP_FPM_MAX_SPARE_SERVERS    => $self->{'config'}->{'PHP_FPM_MAX_SPARE_SERVERS'} // 2,
            PHP_FPM_MIN_SPARE_SERVERS    => $self->{'config'}->{'PHP_FPM_MIN_SPARE_SERVERS'} // 1,
            PHP_FPM_PROCESS_IDLE_TIMEOUT => $self->{'config'}->{'PHP_FPM_PROCESS_IDLE_TIMEOUT'} || '60s',
            PHP_FPM_PROCESS_MANAGER_MODE => $self->{'config'}->{'PHP_FPM_PROCESS_MANAGER_MODE'} || 'ondemand',
            PHP_FPM_START_SERVERS        => $self->{'config'}->{'PHP_FPM_START_SERVERS'} // 1,
            PHP_VERSION                  => $self->{'config'}->{'PHP_VERSION'},
            POOL_NAME                    => $poolName,
            TMPDIR                       => "$moduleData->{'HOME_DIR'}/phptmp"
        };

        $rs = $self->_buildConfFile( 'fpm/pool.conf', "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$poolName.conf", $moduleData, $serverData );
        $self->{'reload'} ||= 1;
    } elsif ( ( $moduleData->{'PHP_SUPPORT'} ne 'yes'
        || ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_user' && $moduleData->{'DOMAIN_TYPE'} ne 'dmn' )
        || ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_domain' && $moduleData->{'DOMAIN_TYPE'} !~ /^(?:dmn|als)$/ )
        || ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_site' ) )
        && -f "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf"
    ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$moduleData->{'DOMAIN_NAME'}.conf" )->delFile();
        $self->{'reload'} ||= 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmSapiBuildConf', $moduleData );
}

=item _buildCgiConfig( \%moduleData )

 Build PHP cgi configuration for the given domain

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on sucess, other on failure

=cut

sub _buildCgiConfig
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpCgiSapiBuildConf', $moduleData );
    return $rs if $rs;

    my ($configLevelName, $emailDomain);
    if ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_user' ) {
        $configLevelName = $moduleData->{'ROOT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'ROOT_DOMAIN_NAME'};
    } elsif ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_domain' ) {
        $configLevelName = $moduleData->{'PARENT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'PARENT_DOMAIN_NAME'};
    } else {
        $configLevelName = $moduleData->{'DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    }

    if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
        eval {
            iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName" )->remove();
            iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_FCGI_STARTER_DIR'} )->make( {
                user  => $main::imscpConfig{'ROOT_USER'},
                group => $main::imscpConfig{'ROOT_GROUP'},
                mode  => 0555
            } );

            for ( "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName",
                "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName/php$self->{'config'}->{'PHP_VERSION'}"
            ) {
                iMSCP::Dir->new( dirname => $_ )->make( {
                    user  => $moduleData->{'USER'},
                    group => $moduleData->{'GROUP'},
                    mode  => 0550
                } );
            }
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        my $serverData = {
            EMAIL_DOMAIN          => $emailDomain,
            FCGID_NAME            => $configLevelName,
            PHP_VERSION           => $self->{'config'}->{'PHP_VERSION'},
            PHP_FCGI_BIN_PATH     => $self->{'config'}->{'PHP_FCGI_BIN_PATH'},
            PHP_FCGI_CHILDREN     => $self->{'config'}->{'PHP_FCGI_CHILDREN'},
            PHP_FCGI_MAX_REQUESTS => $self->{'config'}->{'PHP_FCGI_MAX_REQUESTS'},
            TMPDIR                => $moduleData->{'HOME_DIR'} . '/phptmp'
        };

        $rs = $self->_buildConfFile(
            'cgi/php-fcgi-starter',
            "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName/php-fcgi-starter",
            $moduleData,
            $serverData,
            {
                user  => $moduleData->{'USER'},
                group => $moduleData->{'GROUP'},
                mode  => 0550
            }
        );
        $rs ||= $self->_buildConfFile(
            'cgi/php.ini.user',
            "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName/php$self->{'config'}->{'PHP_VERSION'}/php.ini",
            $moduleData,
            $serverData,
            {
                user  => $moduleData->{'USER'},
                group => $moduleData->{'GROUP'},
                mode  => 0440
            }
        );
        return $rs if $rs;
    } elsif ( $moduleData->{'PHP_SUPPORT'} ne 'yes'
        || ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_user' && $moduleData->{'DOMAIN_TYPE'} ne 'dmn' )
        || ( $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_domain' && $moduleData->{'DOMAIN_TYPE'} !~ /^(?:dmn|als)$/ )
        || $self->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_site'
    ) {
        eval { iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove(); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    $self->{'eventManager'}->trigger( 'afterPhpCgiSapiBuildConf', $moduleData );
}

=item _buildApache2HandlerConfig( \%moduleData )

 Build PHP apache2handler configuration for the given domain
 
 There are nothing special to do here. We trigger event for consistency reasons.

 Param hashref \%moduleData Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on sucess, other on failure

=cut

sub _buildApache2HandlerConfig
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpApache2HandlerSapiBuildConf', $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpApache2HandlerSapiBuildConf', $moduleData );
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpCleanup' );
    return $rs if $rs;

    for ( "$self->{'cfgDir'}/php.old.data", "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" ) {
        next unless -f;
        $rs = iMSCP::File->new( filename => "$self->{'cfgDir'}/php.old.data" )->delFile();
        return $rs if $rs;
    }

    eval { iMSCP::Dir->new( dirname => '/etc/php5' )->remove(); };
    if ( $@ ) {
        error( $@ );
        return $rs if $rs;
    }

    my $httpd = Servers::httpd->factory();
    $rs = $httpd->disableModules( 'php_fpm_imscp', 'fastcgi_imscp' );
    return $rs if $rs;

    for ( 'fastcgi_imscp.conf', 'fastcgi_imscp.load', 'php_fpm_imscp.conf', 'php_fpm_imscp.load' ) {
        next unless -f "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_";
        $rs = iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterPhpCleanup' );
}

=back

=head1 EVENT LISTENERS

=over 4

=item _buildApache2Config( \$cfgTpl, $filename, \%moduleData, \%serverData )

 Event listener that inject PHP configuration in Apache2 vhosts

 Param scalar \$scalar Reference to Apache2 vhost content
 Param string $filename Apache2 template name
 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Param hashref \%serverData Server data
 Return int 0 on success, other on failure

=cut

sub _buildApache2Config
{
    my ($cfgTpl, $filename, $moduleData, $serverData) = @_;

    return 0 unless $INSTANCE && $filename eq 'domain.tpl'
        && grep( $_ eq $serverData->{'VHOST_TYPE'}, ( 'domain', 'domain_ssl' ) );

    my ($configLevel, $emailDomain);
    if ( $INSTANCE->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_user' ) {
        $configLevel = $moduleData->{'ROOT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'ROOT_DOMAIN_NAME'};
    } elsif ( $INSTANCE->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_domain' ) {
        $configLevel = $moduleData->{'PARENT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    } else {
        $configLevel = $moduleData->{'DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    }

    if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
        debug( sprintf( 'Injecting PHP configuration in Apache2 vhost for the %s domain', $moduleData->{'DOMAIN_NAME'} ));
    }

    if ( $INSTANCE->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
            @{$serverData}{qw/ PROXY_FCGI_PATH PROXY_FCGI_URL PROXY_TIMEOUT /} = (
                    $INSTANCE->{'config'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds'
                    ? "unix:/run/php/php$INSTANCE->{'config'}->{'PHP_VERSION'}-fpm-$configLevel.sock|" : '',
                'fcgi://' . ( $INSTANCE->{'config'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds'
                    ? $configLevel : '127.0.0.1:' . ( $INSTANCE->{'config'}->{'PHP_FPM_LISTEN_PORT_START'}+$moduleData->{'PHP_FPM_LISTEN_PORT'} ) ),
                $moduleData->{'MAX_EXECUTION_TIME'}+10 # See IP-1762
            );

            replaceBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", <<"EOF", $cfgTpl );
        # SECTION document root addons BEGIN.
@{[ getBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", $cfgTpl ) ]}
        # SECTION php_fpm BEGIN.
        AllowOverride All
        DirectoryIndex index.php
        <If "%{REQUEST_FILENAME} =~ /\.ph(?:p[3457]?|t|tml)\$/ && -f %{REQUEST_FILENAME}">
        SetEnvIfNoCase ^Authorization\$ "(.+)" HTTP_AUTHORIZATION=\$1
        SetHandler proxy:{PROXY_FCGI_URL}
        </If>
        # SECTION php_fpm END.
        # SECTION document root addons END.
EOF
            replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{[ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ]}
    # SECTION php_fpm_proxy BEGIN.
    <Proxy "{PROXY_FCGI_PATH}{PROXY_FCGI_URL}" retry=0>
        ProxySet connectiontimeout=5 timeout={PROXY_TIMEOUT}
    </Proxy>
    # SECTION php_fpm_proxy END.
    # SECTION addons END.
EOF
        } else {
            replaceBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", <<"EOF", $cfgTpl );
        # SECTION document root addons BEGIN.
@{[ getBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", $cfgTpl ) ]}
        AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
          Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
EOF
            replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{[ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ]}
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    # SECTION addons END.
EOF
        }
    } elsif ( $INSTANCE->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
            @{$serverData}{qw/ PHP_FCGI_STARTER_DIR FCGID_NAME /} = ( $INSTANCE->{'config'}->{'PHP_FCGI_STARTER_DIR'}, $configLevel );

            replaceBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", <<"EOF", $cfgTpl );
        # SECTION document root addons BEGIN.
@{[ getBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", $cfgTpl ) ]}
        # SECTION php_cgi BEGIN.
        AllowOverride All
        DirectoryIndex index.php
        Options +ExecCGI
        FCGIWrapper {PHP_FCGI_STARTER_DIR}/{FCGID_NAME}/php-fcgi-starter
        # SECTION php_cgi END.
        # SECTION document root addons END.
EOF
        } else {
            replaceBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", <<"EOF", $cfgTpl );
        # SECTION document root addons BEGIN.
@{[ getBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", $cfgTpl ) ]}
        AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
          Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
EOF
            replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{[ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ]}
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    # SECTION addons END.
EOF
        }
    } elsif ( $INSTANCE->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
            @{$serverData}{qw/ EMAIL_DOMAIN TMPDIR /} = ( $emailDomain, $moduleData->{'HOME_DIR'} . '/phptmp' );

            replaceBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", <<"EOF", $cfgTpl );
        # SECTION document root addons BEGIN.
@{[ getBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", $cfgTpl ) ]}
        # SECTION php_apache2handler BEGIN.
        AllowOverride All
        DirectoryIndex index.php
        php_admin_value open_basedir "{HOME_DIR}/:{PEAR_DIR}/:dev/random:/dev/urandom"
        php_admin_value upload_tmp_dir "{TMPDIR}"
        php_admin_value session.save_path "{TMPDIR}"
        php_admin_value soap.wsdl_cache_dir "{TMPDIR}"
        php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f webmaster\@{EMAIL_DOMAIN}"
        php_admin_value max_execution_time {MAX_EXECUTION_TIME}
        php_admin_value max_input_time {MAX_INPUT_TIME}
        php_admin_value memory_limit "{MEMORY_LIMIT}M"
        php_flag display_errors {DISPLAY_ERRORS}
        php_admin_value post_max_size "{POST_MAX_SIZE}M"
        php_admin_value upload_max_filesize "{UPLOAD_MAX_FILESIZE}M"
        php_admin_flag allow_url_fopen {ALLOW_URL_FOPEN}
        # SECTION php_apache2handler END.
        # SECTION document root addons END.
EOF
        } else {
            replaceBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", <<"EOF", $cfgTpl );
      # SECTION document root addons BEGIN.
@{[ getBlocByRef( "# SECTION document root addons BEGIN.\n", "# SECTION document root addons END.\n", $cfgTpl ) ]}
      AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
        Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
EOF
            replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{[ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ]}
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    php_admin_flag engine off
    # SECTION addons END.
EOF
        }
    } else {
        error( 'Unknown PHP SAPI' );
        return 1;
    }

    0;
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item END

 Restart, reload or start PHP-FPM when needed

=cut

END
    {
        return if $? || !$INSTANCE || $INSTANCE->{'config'}->{'PHP_SAPI'} ne 'fpm' || ( defined $main::execmode && $main::execmode eq 'setup' );

        if ( $INSTANCE->{'restart'} ) {
            iMSCP::Service->getInstance()->registerDelayedAction(
                "php$INSTANCE->{'config'}->{'PHP_VERSION'}-fpm", [ 'restart', sub { $INSTANCE->restart(); } ], __PACKAGE__->getPriority()
            );
        } elsif ( $INSTANCE->{'reload'} ) {
            iMSCP::Service->getInstance()->registerDelayedAction(
                "php$INSTANCE->{'config'}->{'PHP_VERSION'}-fpm", [ 'reload', sub { $INSTANCE->reload(); } ], __PACKAGE__->getPriority()
            );
        } elsif ( $INSTANCE->{'start'} ) {
            iMSCP::Service->getInstance()->registerDelayedAction(
                "php$INSTANCE->{'config'}->{'PHP_VERSION'}-fpm", [ 'start', sub { $INSTANCE->start(); } ], __PACKAGE__->getPriority()
            );
        }
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
