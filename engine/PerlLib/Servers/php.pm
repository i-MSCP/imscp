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
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
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
    $instance ||= __PACKAGE__->getInstance(
        eventManager => iMSCP::EventManager->getInstance(),
        httpd        => Servers::httpd->factory()
    );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure
 FIXME: We are too close to Debian layout here.

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
                $serviceMngr->stop( $service );
                if($main::imscpConfig{'HTTPD_PACKAGE'} ne 'Servers::httpd::apache_php_fpm'
                    || $self->{'httpd'}->{'phpConfig'}->{'PHP_VERSION'} ne $phpVersion
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
 FIXME: We are too close to Debian layout here.

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpInstall' );
    $rs ||= $self->{'eventManager'}->trigger( 'beforePhpdBuildConfFiles' );
    return $rs if $rs;

    eval {
        $self->{'httpd'}->setData(
            {
                HTTPD_USER                          => $self->{'httpd'}->{'config'}->{'HTTPD_USER'} // 'www-data',
                HTTPD_GROUP                         => $self->{'httpd'}->{'config'}->{'HTTPD_GROUP'} // 'www-data',
                PEAR_DIR                            => $self->{'httpd'}->{'phpConfig'}->{'PHP_PEAR_DIR'} // '/usr/share/php',
                PHP_FPM_LOG_LEVEL                   => $self->{'httpd'}->{'phpConfig'}->{'PHP_FPM_LOG_LEVEL'} || 'error',
                PHP_FPM_EMERGENCY_RESTART_THRESHOLD => $self->{'httpd'}->{'phpConfig'}->{'PHP_FPM_EMERGENCY_RESTART_THRESHOLD'} || 10,
                PHP_FPM_EMERGENCY_RESTART_INTERVAL  => $self->{'httpd'}->{'phpConfig'}->{'PHP_FPM_EMERGENCY_RESTART_INTERVAL'} || '1m',
                PHP_FPM_PROCESS_CONTROL_TIMEOUT     => $self->{'httpd'}->{'phpConfig'}->{'PHP_FPM_PROCESS_CONTROL_TIMEOUT'} || '60s',
                PHP_FPM_PROCESS_MAX                 => $self->{'httpd'}->{'phpConfig'}->{'PHP_FPM_PROCESS_MAX'} // 0,
                PHP_FPM_RLIMIT_FILES                => $self->{'httpd'}->{'phpConfig'}->{'PHP_FPM_RLIMIT_FILES'} // 4096,
                PHP_OPCODE_CACHE_ENABLED            => $self->{'httpd'}->{'phpConfig'}->{'PHP_OPCODE_CACHE_ENABLED'} // 0,
                PHP_OPCODE_CACHE_MAX_MEMORY         => $self->{'httpd'}->{'phpConfig'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'} // 32,
                PHP_APCU_CACHE_ENABLED              => $self->{'httpd'}->{'phpConfig'}->{'PHP_APCU_CACHE_ENABLED'} // 0,
                PHP_APCU_CACHE_MAX_MEMORY           => $self->{'httpd'}->{'phpConfig'}->{'PHP_APCU_CACHE_MAX_MEMORY'} // 32,
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
            $rs = $self->{'httpd'}->buildConfFile(
                "$self->{'httpd'}->{'phpCfgDir'}/fpm/php.ini",
                {},
                { destination => "/etc/php/$phpVersion/fpm/php.ini" }
            );
            $rs ||= $self->{'httpd'}->buildConfFile(
                "$self->{'httpd'}->{'phpCfgDir'}/fpm/php-fpm.conf",
                {},
                { destination => "/etc/php/$phpVersion/fpm/php-fpm.conf" }
            );
            $rs ||= $self->{'httpd'}->buildConfFile(
                "$self->{'httpd'}->{'phpCfgDir'}/fpm/pool.conf.default",
                {},
                { destination => "/etc/php/$phpVersion/fpm/pool.d/www.conf" }
            );
            # ITK
            $rs ||= $self->{'httpd'}->buildConfFile(
                "$self->{'httpd'}->{'phpCfgDir'}/apache/php.ini",
                {},
                { destination => "/etc/php/$phpVersion/apache2/php.ini" }
            );
            $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpdBuildConfFiles' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpInstall' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure
 FIXME: We are too close to Debian layout here.

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
                next unless $phpVersion =~ /^[\d.]+$/ && $self->{'httpd'}->{'phpConfig'}->{'PHP_VERSION'} eq $phpVersion;

                my $service = "php$phpVersion-fpm";

                iMSCP::Service->getInstance()->enable( $service );

                $self->{'eventManager'}->register(
                    'beforeSetupRestartServices',
                    sub {
                        push @{$_[0]},
                            [
                                sub {
                                    iMSCP::Service->getInstance()->start( $service );
                                    0;
                                },
                                "PHP-FPM $phpVersion"
                            ];
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
 FIXME: We are too close to Debian layout here.

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

=item getPriority( )

 Get server priority

 Return int Server priority

=cut

sub getPriority
{
    70;
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
