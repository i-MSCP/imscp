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

=head1 DESCRIPTION

 i-MSCP PHP server implementation.

 TODO (Enterprise Edition):
 - Depending of selected Httpd server, customer should be able to choose between several SAPI:
  - Apache2 with MPM Event, Worker or Prefork: cgi or fpm
  - Apache2 with MPM ITK                     : apache2handler or fpm
  - Nginx (Implementation not available yet) : fpm
 - Customer should be able to select the PHP version to use (Merge of PhpSwitcher feature in core)
 
 FIXME:
 - We are too close to Debian distribution layout

=head1 PUBLIC METHODS

=over 4

=item factory( )

 Create and return a Servers::php instance

 Return Servers::php

=cut

sub factory
{
    __PACKAGE__->hasInstance() || __PACKAGE__->getInstance();
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
    my %choices = ( 'per_site', 'Per site (recommended)', 'per_domain', 'Per domain', 'per_user', 'Per user' );

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

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    return 0 unless $self->{'config'}->{'PHP_SAPI'} eq 'cgi';

    setRights( $self->{'config'}->{'PHP_FCGI_STARTER_DIR'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0555'
        }
    );
}

=item addDomain( \%moduleData )

 Process addDomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub addDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddDmn', $moduleData );
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

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpAddDmn', $moduleData );
}

=item disableDomain( \%moduleData )

 Process disableDomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub disableDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpdDisableDmn', $moduleData );
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

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpDisableDmn', $moduleData );
}

=item deleteDomain( \%moduleData )

 Process deleteDomain tasks

 Param hashref \%moduleData Data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub deleteDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDeleteDmn', $moduleData );
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

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdDeleteDmn', $moduleData );
}

=item addSubbdomain( \%moduleData )

 Process addSubbdomain tasks

 Param hashref \%moduleData Data as provided by SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub addSubbdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddSub', $moduleData );
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

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdDisableSub', $moduleData );
}

=item deleteSubdomain( \%moduleData )

 Process deleteSubdomain tasks

 Param hashref \%moduleData Data as provided by SubAlias|Subdomain modules
 Return int 0 on success, other on failure

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

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    70;
}

=item buildConfFile( $srcFile, $trgFile, [, \%moduleData = { } [, \%serverData [, \%parameters = { } ] ] ] )

 Build the given PHP configuration file

 Param string $srcFile Source file path (full path or path relative to the i-MSCP php configuration directory)
 Param string $trgFile Target file path
 Param hashref \%data OPTIONAL Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Param hashref \%data OPTIONAL Server data (Server data have higher precedence than modules data)
 Param hashref \%parameters OPTIONAL parameters:
  - user  : File owner (default: root)
  - group : File group (default: root
  - mode  : File mode (default: 0644)
  - cached : Whether or not loaded file must be cached in memory
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
    my ($self, $srcFile, $trgFile, $moduleData, $serverData, $parameters) = @_;
    $moduleData //= {};
    $serverData //= {};
    $parameters //= {};

    my ($filename, $path) = fileparse( $srcFile );
    my $cfgTpl;

    if ( $parameters->{'cached'} && exists $self->{'templates'}->{$filename} ) {
        $cfgTpl = $self->{'templates'}->{$filename};
    } else {
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'php', $filename, \ $cfgTpl, $moduleData, $serverData, $self->{'config'} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $srcFile = File::Spec->canonpath( "$self->{'cfgDir'}/$path/$filename" ) if index( $path, '/' ) != 0;
            $cfgTpl = iMSCP::File->new( filename => $srcFile )->get();
            unless ( defined $cfgTpl ) {
                error( sprintf( "Couldn't read the %s file", $srcFile ));
                return 1;
            }
        }

        $self->{'templates'}->{$filename} = $cfgTpl if $parameters->{'cached'};
    }

    my $rs = $self->{'eventManager'}->trigger(
        'beforePhpBuildConfFile', \$cfgTpl, $filename, \$trgFile, $moduleData, $serverData, $self->{'config'}, $parameters
    );
    return $rs if $rs;

    processByRef( $serverData, \$cfgTpl );
    processByRef( $moduleData, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger(
        'afterPhpdBuildConfFile', \$cfgTpl, $filename, \$trgFile, $moduleData, $serverData, $self->{'config'}, $parameters
    );
    return $rs if $rs;

    my $fh = iMSCP::File->new( filename => $trgFile );
    $fh->set( $cfgTpl );
    $rs ||= $fh->save();
    return $rs if $rs;

    if ( exists $parameters->{'user'} || exists $parameters->{'group'} ) {
        $rs = $fh->owner( $parameters->{'user'} // $main::imscpConfig{'ROOT_USER'}, $parameters->{'group'} // $main::imscpConfig{'ROOT_GROUP'} );
        return $rs if $rs;
    }

    if ( exists $parameters->{'mode'} ) {
        $rs = $fh->mode( $parameters->{'mode'} );
        return $rs if $rs;
    }

    # On configuration file change, schedule server reload
    $self->{'reload'} ||= 1;
    0;
}

=item start( [ $version = $self->{'config'}->{'PHP_VERSION'} ] )

 Start PHP FastCGI Process Manager 'PHP-FPM' for the given PHP version (default to selected PHP alternative for customers)

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

 Stop PHP FastCGI Process Manager 'PHP-FPM' for the given PHP version (default to selected PHP alternative for customers)

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

 Reload PHP FastCGI Process Manager 'PHP-FPM' for the given PHP version (default to selected PHP alternative for customers)

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

 Restart PHP FastCGI Process Manager 'PHP-FPM' for the given PHP version (default to selected PHP alternative for customers)

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

    @{$self}{qw/ start restart reload templates /} = ( 0, 0, 0, {} );
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/php.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/php.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => defined $main::execmode && $main::execmode eq 'setup';
    $self->{'eventManager'}->register( 'beforeApache2BuildConfFile', $self, 100 );
    $self->{'eventManager'}->register( 'afterApache2AddFiles', $self );
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

        $rs = $self->buildConfFile(
            'fpm/pool.conf', "$self->{'config'}->{'PHP_FPM_POOL_DIR_PATH'}/$poolName.conf", $moduleData, $serverData, { cached => 1 }
        );
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

        $rs = $self->buildConfFile(
            'cgi/php-fcgi-starter',
            "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName/php-fcgi-starter",
            $moduleData,
            $serverData,
            {
                user   => $moduleData->{'USER'},
                group  => $moduleData->{'GROUP'},
                mode   => 0550,
                cached => 1
            }
        );
        $rs ||= $self->buildConfFile(
            'cgi/php.ini.user',
            "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$configLevelName/php$self->{'config'}->{'PHP_VERSION'}/php.ini",
            $moduleData,
            $serverData,
            {
                user   => $moduleData->{'USER'},
                group  => $moduleData->{'GROUP'},
                mode   => 0440,
                cached => 1
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
 
 There are nothing special to do here. We trigger events for consistency reasons.

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
    $rs = $httpd->disableModules( qw/ fastcgi_imscp php5 php5_cgi php5filter php_fpm_imscp proxy_handler / );
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

=item beforeApache2BuildConfFile( $phpServer, \$cfgTpl, $filename, \$trgFile, \%moduleData, \%apache2ServerData, \%apache2ServerConfig, $parameters )

 Event listener that inject PHP configuration in Apache2 vhosts

 Param scalar $phpServer Servers::php instance
 Param scalar \$scalar Reference to Apache2 vhost content
 Param string $filename Apache2 template name
 Param scalar \$trgFile Target file path
 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Param hashref \%apache2ServerData Apache2 server data
 Param hashref \%apache2ServerConfig Apache2 server data
 Param hashref \%parameters OPTIONAL Parameters:
  - user  : File owner (default: root)
  - group : File group (default: root
  - mode  : File mode (default: 0644)
  - cached : Whether or not loaded file must be cached in memory
 Return int 0 on success, other on failure

=cut

sub beforeApache2BuildConfFile
{
    my ($phpServer, $cfgTpl, $filename, undef, $moduleData, $apache2ServerData) = @_;

    return 0 unless $filename eq 'domain.tpl' && grep( $_ eq $apache2ServerData->{'VHOST_TYPE'}, ( 'domain', 'domain_ssl' ) );

    debug( sprintf( 'Injecting PHP configuration in Apache2 vhost for the %s domain', $moduleData->{'DOMAIN_NAME'} ));

    my ($configLevel, $emailDomain);
    if ( $phpServer->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_user' ) {
        $configLevel = $moduleData->{'ROOT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'ROOT_DOMAIN_NAME'};
    } elsif ( $phpServer->{'config'}->{'PHP_CONFIG_LEVEL'} eq 'per_domain' ) {
        $configLevel = $moduleData->{'PARENT_DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    } else {
        $configLevel = $moduleData->{'DOMAIN_NAME'};
        $emailDomain = $moduleData->{'DOMAIN_NAME'};
    }

    if ( $phpServer->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
            @{$apache2ServerData}{qw/ EMAIL_DOMAIN TMPDIR /} = ( $emailDomain, $moduleData->{'HOME_DIR'} . '/phptmp' );

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

        return 0;
    }

    if ( $phpServer->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
            @{$apache2ServerData}{
                qw/ PHP_FCGI_STARTER_DIR FCGID_NAME PHP_FCGID_BUSY_TIMEOUT PHP_FCGID_MIN_PROCESSES_PER_CLASS PHP_FCGID_MAX_PROCESS_PER_CLASS /
            } = (
                $phpServer->{'config'}->{'PHP_FCGI_STARTER_DIR'},
                $configLevel,
                $moduleData->{'MAX_EXECUTION_TIME'}+10,
                $phpServer->{'config'}->{'PHP_FCGID_MIN_PROCESSES_PER_CLASS'} || 0,
                $phpServer->{'config'}->{'PHP_FCGID_MAX_PROCESS_PER_CLASS'} || 6
            );

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
            replaceBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", <<"EOF", $cfgTpl );
    # SECTION addons BEGIN.
@{[ getBlocByRef( "# SECTION addons BEGIN.\n", "# SECTION addons END.\n", $cfgTpl ) ]}
    FcgiBusyTimeout {PHP_FCGID_BUSY_TIMEOUT}
    FcgidMinProcessesPerClass {PHP_FCGID_MIN_PROCESSES_PER_CLASS}
    FcgidMaxProcessesPerClass {PHP_FCGID_MAX_PROCESS_PER_CLASS}
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

        return 0;
    }

    if ( $phpServer->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        if ( $moduleData->{'FORWARD'} eq 'no' && $moduleData->{'PHP_SUPPORT'} eq 'yes' ) {
            @{$apache2ServerData}{qw/ PROXY_FCGI_PATH PROXY_FCGI_URL PROXY_FCGI_RETRY PROXY_FCGI_CONNECTION_TIMEOUT PROXY_FCGI_TIMEOUT /} = (
                ( $phpServer->{'config'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds'
                    ? "unix:/run/php/php$phpServer->{'config'}->{'PHP_VERSION'}-fpm-$configLevel.sock|" : ''
                ),
                ( 'fcgi://' . ( $phpServer->{'config'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds'
                    ? $configLevel : '127.0.0.1:' . ( $phpServer->{'config'}->{'PHP_FPM_LISTEN_PORT_START'}+$moduleData->{'PHP_FPM_LISTEN_PORT'} ) )
                ),
                0,
                5,
                $moduleData->{'MAX_EXECUTION_TIME'}+10
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
    <Proxy "{PROXY_FCGI_PATH}{PROXY_FCGI_URL}" retry={PROXY_FCGI_RETRY}>
        ProxySet connectiontimeout={PROXY_FCGI_CONNECTION_TIMEOUT} timeout={PROXY_FCGI_TIMEOUT}
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

        return 0;
    }

    error( 'Unknown PHP SAPI' );
    return 1;
}

=item afterApache2AddFiles( \%moduleData )

 Event listener that create PHP (phptmp) directory in Web folders

 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub afterApache2AddFiles
{

    my (undef, $moduleData) = @_;

    return 0 unless $moduleData->{'DOMAIN_TYPE'} eq 'dmn';

    eval {
        iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/phptmp" )->make( {
            user  => $moduleData->{'USER'},
            group => $moduleData->{'GROUP'},
            mode  => 0750
        } )
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
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

        return 0 unless $instance && $instance->{'config'}->{'PHP_SAPI'} ne 'fpm'
            && ( my $action = $instance->{'restart'}
            ? 'restart' : ( $instance->{'reload'} ? 'reload' : ( $instance->{'start'} ? ' start' : undef ) ) );

        iMSCP::Service->getInstance()->registerDelayedAction(
            "php$instance->{'config'}->{'PHP_VERSION'}-fpm", [ $action, sub { $instance->$action(); } ], __PACKAGE__->getPriority()
        );
    }

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
