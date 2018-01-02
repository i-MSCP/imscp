=head1 NAME

 iMSCP::Servers::Php::Debian - i-MSCP (Debian) PHP server implementation

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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

package iMSCP::Servers::Php::Debian;

use strict;
use warnings;
use File::Basename;
use File::Spec;
use autouse 'iMSCP::Dialog::InputValidation' => qw/ isOneOfStringsInList isStringInList /;
use Class::Autouse qw/ :nostat iMSCP::Getopt iMSCP::ProgramFinder iMSCP::Servers::Httpd /;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::Servers::Php;
use Scalar::Defer;
use parent 'iMSCP::Servers::Php::Abstract';

=head1 DESCRIPTION

 i-MSCP (Debian) PHP server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners()

 Register setup event listeners

 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my ($self) = @_;

    for my $dialogRoutine( qw/ askForPhpVersion askForPhpSapi askForFastCGIconnectionType / ) {
        my $rs = $self->{'eventManager'}->register(
            'beforeSetupDialog',
            sub {
                push @{$_[0]}, sub { $self->$dialogRoutine( @_ ) };
                0;
            }
        );
        return $rs if $rs;
    }

    0;
}

=item askForPhpVersion( \%dialog )

 Ask for PHP version (PHP version for customers)

 Param iMSCP::Dialog \%dialog
 Return int 0 to go on next question, 30 to go back to the previous question, die on failure

=cut

sub askForPhpVersion
{
    my ($self, $dialog) = @_;

    ( my @availablePhpVersions = sort grep( /\d+.\d+/, iMSCP::Dir->new( dirname => '/etc/php' )->getDirs()) ) or die(
        "Couldn't guess list of available PHP versions"
    );

    my %choices;
    @{choices}{@availablePhpVersions} = map { "PHP $_" } @availablePhpVersions;

    my $value = main::setupGetQuestion( 'PHP_VERSION', $self->{'config'}->{'PHP_VERSION'} || ( iMSCP::Getopt->preseed ? ( keys %choices )[0] : '' ));

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'php', 'servers', 'all', 'forced' ] ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<'EOF', \%choices, ( grep( $value eq $_, keys %choices ) )[0] || ( keys %choices )[0] );
\Z4\Zb\ZuPHP version for customers\Zn

Please choose the PHP version for the customers.
\Z \Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'PHP_AVAILABLE_VERSIONS'} = "@availablePhpVersions";
    $self->{'config'}->{'PHP_VERSION'} = $value;
    0;
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

    my $httpd = iMSCP::Servers::Httpd->factory();
    if ( $httpd->{'config'}->{'APACHE2_MPM'} eq 'itk' ) {
        # Apache2 PHP module only works with Apache's prefork based MPM
        # We allow it only with the Apache's ITK MPM because the Apache's prefork MPM
        # doesn't allow to constrain each individual vhost to a particular system user/group.
        $choices{'apache2handler'} = 'PHP through Apache2 PHP module (apache2handler SAPI)';
    } else {
        # Apache2 Fcgid module doesn't work with Apache's ITK MPM
        # https://lists.debian.org/debian-apache/2013/07/msg00147.html
        $choices{'cgi'} = 'PHP through Apache2 Fcgid module (cgi SAPI)';
    }

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'php', 'servers', 'all', 'forced' ] ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<'EOF', \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'fpm' );
\Z4\Zb\ZuPHP SAPI for customers\Zn

Please choose the PHP SAPI for the customers.
\Z \Zn
EOF
        return $rs unless $rs < 30;
    }

    $self->{'config'}->{'PHP_SAPI'} = $value;
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

    if ( isOneOfStringsInList( iMSCP::Getopt->reconfigure, [ 'php', 'servers', 'all', 'forced' ] ) || !isStringInList( $value, keys %choices ) ) {
        ( my $rs, $value ) = $dialog->radiolist( <<'EOF', \%choices, ( grep( $value eq $_, keys %choices ) )[0] || 'uds' );
\Z4\Zb\ZuPHP-FPM - FastCGI connection type\Zn

Please choose the FastCGI connection type that you want use.
\Z \Zn
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

    eval {
        $self->SUPER::preinstall() == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        my $httpd = iMSCP::Servers::Httpd->factory();

        # Disable i-MSCP Apache2 fcgid modules. It will be re-enabled in postinstall if needed
        $httpd->disableModules( 'fcgid_imscp' ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

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

        for ( split /\s+/, $self->{'config'}->{'PHP_AVAILABLE_VERSIONS'} ) {
            # Tasks for apache2handler SAPI

            if ( $self->{'config'}->{'PHP_SAPI'} ne 'apache2handler' || $self->{'config'}->{'PHP_VERSION'} ne $_ ) {
                # Disable Apache2 PHP module if PHP version is other than selected PHP alternative
                $httpd->disableModules( "php$_" ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }

            # Tasks for cgi SAPI

            # Disable default Apache2 conffile
            $httpd->disableConfs( "php$_-cgi.conf" ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            # Tasks for fpm SAPI

            if ( $serviceMngr->hasService( "php$_-fpm" ) ) {
                # Stop PHP-FPM instance
                $self->stop( $_ ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

                # Disable PHP-FPM service if selected SAPI for customer is not fpm or if PHP version
                # is other than selected PHP alternative
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
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    eval {
        $self->SUPER::install() == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        # Set default alternatives according PHP version for customers
        my ($stdout, $stderr);
        execute( [ 'update-alternatives', '--set', 'php', "/usr/bin/php$self->{'PHP_VERSION'}" ], \$stdout, \$stderr ) == 0 or die(
            $stderr || 'Unknown error'
        );
        execute( [ 'update-alternatives', '--set', 'phar', "/usr/bin/phar$self->{'PHP_VERSION'}" ], \$stdout, \$stderr ) == 0 or die(
            $stderr || 'Unknown error'
        );
        execute( [ 'update-alternatives', '--set', 'phar.phar', "/usr/bin/phar.phar$self->{'PHP_VERSION'}" ], \$stdout, \$stderr ) == 0 or die(
            $stderr || 'Unknown error'
        );

        my $httpd = iMSCP::Servers::Httpd->factory();
        my $serverData = {
            HTTPD_USER                          => $httpd->getRunningUser(),
            HTTPD_GROUP                         => $httpd->getRunningGroup(),
            PHP_APCU_CACHE_ENABLED              => $self->{'config'}->{'PHP_APCU_CACHE_ENABLED'} // 1,
            PHP_APCU_CACHE_MAX_MEMORY           => $self->{'config'}->{'PHP_APCU_CACHE_MAX_MEMORY'} || 32,
            PHP_FPM_EMERGENCY_RESTART_THRESHOLD => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_THRESHOLD'} || 10,
            PHP_FPM_EMERGENCY_RESTART_INTERVAL  => $self->{'config'}->{'PHP_FPM_EMERGENCY_RESTART_INTERVAL'} || '1m',
            PHP_FPM_LOG_LEVEL                   => $self->{'config'}->{'PHP_FPM_LOG_LEVEL'} || 'error',
            PHP_FPM_PROCESS_CONTROL_TIMEOUT     => $self->{'config'}->{'PHP_FPM_PROCESS_CONTROL_TIMEOUT'} || '60s',
            PHP_FPM_PROCESS_MAX                 => $self->{'config'}->{'PHP_FPM_PROCESS_MAX'} || 0,
            PHP_FPM_RLIMIT_FILES                => $self->{'config'}->{'PHP_FPM_RLIMIT_FILES'} || 4096,
            PHP_OPCODE_CACHE_ENABLED            => $self->{'config'}->{'PHP_OPCODE_CACHE_ENABLED'} // 1,
            PHP_OPCODE_CACHE_MAX_MEMORY         => $self->{'config'}->{'PHP_OPCODE_CACHE_MAX_MEMORY'} || 32,
            TIMEZONE                            => $main::imscpConfig{'TIMEZONE'} || 'UTC'
        };

        # Configure all PHP alternatives
        for ( split /\s+/, $self->{'config'}->{'PHP_AVAILABLE_VERSIONS'} ) {

            $serverData->{'PHP_VERSION'} = $_;

            # Master php.ini file for apache2handler, cli, cgi and fpm SAPIs
            for my $sapiDir( qw/ apache2 cgi cli fpm / ) {
                my $rs = $self->buildConfFile( "$sapiDir/php.ini", "/etc/php/$_/$sapiDir/php.ini", undef, $serverData );
                last if $rs;
            }

            # Master conffile for fpm SAPI
            my $rs = $self->buildConfFile( 'fpm/php-fpm.conf', "/etc/php/$_/fpm/php-fpm.conf", undef, $serverData );
            # Default pool conffile for fpm SAPI
            $rs ||= $self->buildConfFile( 'fpm/pool.conf.default', "/etc/php/$_/fpm/pool.d/www.conf", undef, $serverData );
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
        my $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
        $rs ||= $file->mode( 0644 );
        $rs == 0 or die ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    eval {
        my $httpd = iMSCP::Servers::Httpd->factory();

        if ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
            # Enable Apache2 PHP module for selected PHP alternative
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

            # Enable PHP-FPM service for selected PHP alternative
            iMSCP::Service->getInstance()->enable( "php$self->{'config'}->{'PHP_VERSION'}-fpm" );

            # Schedule start of PHP-FPM service for selected PHP alternative
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
        return 1;
    }

    0;
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    eval {
        my $httpd = iMSCP::Servers::Httpd->factory();

        $httpd->disableModules( 'fcgid_imscp' ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        for ( 'fcgid_imscp.conf', 'fcgid_imscp.load' ) {
            next unless -f "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_";
            iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_" )->delFile() == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        }

        for ( split /\s+/, $self->{'config'}->{'PHP_AVAILABLE_VERSIONS'} ) {
            next unless -f "/etc/init/php$_-fpm.override";
            iMSCP::File->new( filename => "/etc/init/php$_-fpm.override" )->delFile() == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        }

        iMSCP::Dir->new( dirname => $self->{'config'}->{'PHP_FCGI_STARTER_DIR'} )->remove();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item addDomain( \%moduleData )

 See iMSCP::Servers::Php::Abstract::addDomain()

=cut

sub addDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddDomain', $moduleData );
    return $rs if $rs;

    eval { $self->_buildPhpConfig( $moduleData ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpAddDomain', $moduleData );
}

=item disableDomain( \%moduleData )

 See iMSCP::Servers::Php::Abstract::disableDomain()

=cut

sub disableDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpdDisableDomain', $moduleData );
    return $rs if $rs;

    eval { $self->_deletePhpConfig( $moduleData, 0 ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpDisableDomain', $moduleData );
}

=item deleteDomain( \%moduleData )

 See iMSCP::Servers::Php::Abstract::deleteDomain()

=cut

sub deleteDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDeleteDomain', $moduleData );
    return $rs if $rs;

    eval { $self->_deletePhpConfig( $moduleData, 0 ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpdDeleteDomain', $moduleData );
}

=item addSubdomain( \%moduleData )

 See iMSCP::Servers::Php::Abstract::addSubdomain()

=cut

sub addSubdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpAddSubdomain', $moduleData );
    return $rs if $rs;

    eval { $self->_buildPhpConfig( $moduleData ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpAddSubdomain', $moduleData );
}

=item disableSubdomain( \%moduleData )

 See iMSCP::Servers::Php::Abstract::disableSubdomain()

=cut

sub disableSubdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDisableSubdomain', $moduleData );
    return $rs if $rs;

    eval { $self->_deletePhpConfig( $moduleData, 0 ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpdDisableSubdomain', $moduleData );
}

=item deleteSubdomain( \%moduleData )

 See iMSCP::Servers::Php::Abstract::deleteSubdomain()

=cut

sub deleteSubdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpDeleteSubdomain', $moduleData );
    return $rs if $rs;

    eval { $self->_deletePhpConfig( $moduleData, 0 ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterPhpDeleteSubdomain', $moduleData );
}

=item enableModules( \@modules [, $phpVersion = $self->{'config'}->{'PHP_VERSION'} [, $phpSapi = $self->{'config'}->{'PHP_SAPI'} ] ] )

 See iMSCP::Servers::Php::Abstract::enableModules()

=cut

sub enableModules
{
    my ($self, $modules, $phpVersion, $phpSapi) = @_;
    $phpVersion ||= $self->{'config'}->{'PHP_VERSION'};
    $phpSapi ||= $self->{'config'}->{'PHP_SAPI'};
    $phpSapi = 'apache2' if $phpSapi eq 'apache2handler';

    ref $modules eq 'ARRAY' or die( 'Invalid $module parameter. Array expected' );

    my $rs = execute( [ '/usr/sbin/phpenmod', '-v', $phpVersion, '-s', $phpSapi, @{$modules} ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    $self->{'restart'}->{$phpVersion} ||= 1 unless $rs;
    $rs;
}

=item disableModules( \@modules [, $phpVersion = $self->{'config'}->{'PHP_VERSION'} [, $phpSapi = $self->{'config'}->{'PHP_SAPI'} ] ] )

 See iMSCP::Servers::Php::Abstract::disableModules()

=cut

sub disableModules
{
    my ($self, $modules, $phpVersion, $phpSapi) = @_;
    $phpVersion ||= $self->{'config'}->{'PHP_VERSION'};
    $phpSapi ||= $self->{'config'}->{'PHP_SAPI'};
    $phpSapi = 'apache2' if $phpSapi eq 'apache2handler';

    ref $modules eq 'ARRAY' or die( 'Invalid $module parameter. Array expected' );

    my $rs = execute( [ '/usr/sbin/phpdismod', '-v', $phpVersion, '-s', $phpSapi, @{$modules} ], \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;

    $self->{'restart'}->{$phpVersion} ||= 1 unless $rs;
    $rs;
}

=item start( [ $phpVersion = $self->{'config'}->{'PHP_VERSION'} ] )

 See iMSCP::Servers::Php::Abstract::start()

=cut

sub start
{
    my ($self, $phpVersion) = @_;
    $phpVersion ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmStart', $phpVersion );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( "php$phpVersion-fpm" ); };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmStart', $phpVersion );
}

=item stop( [ $phpVersion = $self->{'config'}->{'PHP_VERSION'} ] )

 See iMSCP::Servers::Php::Abstract::stop()

=cut

sub stop
{
    my ($self, $phpVersion) = @_;
    $phpVersion ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmStop', $phpVersion );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( "php$phpVersion-fpm" ); };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmStop', $phpVersion );
}

=item reload( [ $phpVersion = $self->{'config'}->{'PHP_VERSION'} ] )

 See iMSCP::Servers::Php::Abstract::reload()

=cut

sub reload
{
    my ($self, $phpVersion) = @_;
    $phpVersion ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmReload', $phpVersion );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->reload( "php$phpVersion-fpm" ); };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmReload', $phpVersion );
}

=item restart( [ $phpVersion = $self->{'config'}->{'PHP_VERSION'} ] )

 See iMSCP::Servers::Php::Abstract::restart()

=cut

sub restart
{
    my ($self, $phpVersion) = @_;
    $phpVersion ||= $self->{'config'}->{'PHP_VERSION'};

    my $rs = $self->{'eventManager'}->trigger( 'beforePhpFpmRestart', $phpVersion );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->restart( "php$phpVersion-fpm" ); };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterPhpFpmRestart', $phpVersion );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return iMSCP::Servers::Php::Abstract

=cut

sub _init
{
    my ($self) = @_;

    # Define properties that are expected by parent package
    @{$self}{qw/ PHP_FPM_POOL_DIR PHP_FPM_RUN_DIR PHP_PEAR_DIR /} = (
        # We are deferring evaluation because the PHP version can be
        # overriden by 3rd-party components.
        ( defer { "/etc/php/$self->{'config'}->{'PHP_VERSION'}/fpm/pool.d" } ),
        '/run/php',
        '/usr/share/php'
    );

    $self->SUPER::_init();
}

=item _buildPhpConfig( \$moduleData )

 Build PHP config for a domain or subdomain

 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return void, die on failure

=cut

sub _buildPhpConfig
{
    my ($self, $moduleData) = @_;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'apache2handler' ) {
        $self->_buildApache2HandlerConfig( $moduleData );
        return;
    }

    $self->_deletePhpConfig( $moduleData );

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $self->_buildCgiConfig( $moduleData );
        return;
    }

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        $self->_buildFpmConfig( $moduleData );
        return;
    }
}

=item _deletePhpConfig( \%moduleData [, $checkContext = TRUE ] )

 Delete PHP config for a domain or subdomain

 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Param bool $checkContext Whether or not context must be checked
 Return void, die on failure

=cut

sub _deletePhpConfig
{
    my ($self, $moduleData, $checkContext) = @_;
    $checkContext //= 1;

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'cgi' ) {
        $self->_deleteCgiConfig( $moduleData, 0 );
        return;
    }

    if ( $self->{'config'}->{'PHP_SAPI'} eq 'fpm' ) {
        $self->_deleteFpmConfig( $moduleData, 0 );
    }
}

=item _deleteCgiConfig( \%moduleData [, $checkContext = TRUE ] )

 Delete CGI/FastCGI configuration for a domain or subdomain

 Param hashref \%module Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Param bool $checkContext Whether or not context must be checked
 return void, die on failure

=cut

sub _deleteCgiConfig
{
    my ($self, $moduleData, $checkContext) = @_;
    $checkContext //= 1;

    iMSCP::Dir->new( dirname => "$self->{'config'}->{'PHP_FCGI_STARTER_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->remove();
}

=item _deleteFpmConfig( \%moduleData [, $checkContext = TRUE ] )

 Delete PHP-FPM configuration for a domain or subdomain

 Param hashref \%module Data as provided by Alias|Domain|SubAlias|Subdomain modules
 Param bool $checkContext Whether or not context must be checked
 return void, die on failure

=cut

sub _deleteFpmConfig
{
    my ($self, $moduleData, $checkContext) = @_;
    $checkContext //= 1;

    for ( split /\s+/, $self->{'config'}->{'PHP_AVAILABLE_VERSIONS'} ) {
        if ( $checkContext
            && ( $self->{'config'}->{'PHP_VERSION'} eq $_
            && ( $moduleData->{'PHP_SUPPORT'} eq 'yes'
            && ( $moduleData->{'PHP_CONFIG_LEVEL'} eq 'per_user' && $moduleData->{'DOMAIN_TYPE'} eq 'dmn' )
            || ( $moduleData->{'PHP_CONFIG_LEVEL'} eq 'per_domain' && grep($moduleData->{'DOMAIN_TYPE'} eq $_, 'dmn', 'als') )
            || ( $moduleData->{'PHP_CONFIG_LEVEL'} eq 'per_site' && $moduleData->{'FORWARD'} eq 'no' ) ) )
        ) {
            next;
        }

        next unless -f "/etc/php/$_/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf";

        debug( sprintf( 'Deleting the %s FPM pool configuration file', "/etc/php/$_/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf" ));

        iMSCP::File->new( filename => "/etc/php/$_/fpm/pool.d/$moduleData->{'DOMAIN_NAME'}.conf" )->delFile() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unkown error'
        );

        if ( $self->{'config'}->{'PHP_VERSION'} ne $_
            && $self->{'config'}->{'PHP_FPM_LISTEN_MODE'} eq 'tcp'
            && ( !defined $main::execmode || $main::execmode ne 'setup' )
        ) {
            # In TCP mode, we need reload the FPM instance immediately, else,
            # one FPM instance could fail to reload due to port already in use
            iMSCP::Service->getInstance()->reload( "php$_-fpm" );
            next;
        }

        $self->{'reload'}->{$_} ||= 1;
    }
}

=item _setFullVersion()

 See iMSCP::Servers::Php::Abstract::_setFullVersion()

=cut

sub _setFullVersion
{
    my ($self) = @_;

    ( $self->{'config'}->{'PHP_VERSION_FULL'} ) = `/usr/bin/php -nv 2> /dev/null` =~ /^PHP\s+([\d.]+)/ or die(
        "Couldn't guess PHP version for the selected PHP alternative"
    );
}

=item _cleanup( )

 See iMSCP::Servers::Php::Abstract::_cleanup()

=cut

sub _cleanup
{
    my ($self) = @_;

    $self->SUPER::_cleanup();

    if ( -f "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" ) {
        iMSCP::File->new( filename => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/php5-fpm" )->delFile() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
    }

    iMSCP::Dir->new( dirname => '/etc/php5' )->remove();

    my $httpd = iMSCP::Servers::Httpd->factory();

    $httpd->disableModules( qw/ fastcgi_imscp php5 php5_cgi php5filter php_fpm_imscp proxy_handler / ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );

    for ( 'fastcgi_imscp.conf', 'fastcgi_imscp.load', 'php_fpm_imscp.conf', 'php_fpm_imscp.load' ) {
        next unless -f "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_";

        iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_" )->delFile() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
    }
}

=back

=head1 SHUTDOWN TASKS

=over 4

=item shutdown( $priority )

 Restart, reload or start PHP FastCGI process manager for selected PHP alternative when needed

 This method is called automatically before the program exit.

 Param int $priority Server priority
 Return void

=cut

sub shutdown
{
    my ($self, $priority) = @_;

    return unless $self->{'config'}->{'PHP_SAPI'} eq 'fpm';

    my $serviceMngr = iMSCP::Service->getInstance();

    for my $action( qw/ start reload restart / ) {
        for my $phpVersion( keys %{$self->{$action}} ) {
            # Check for actions precedence. The 'reload' and 'restart' actions both have higher precedence than the 'start' action
            next if $action eq 'start' && ( $self->{'reload'}->{$phpVersion} || $self->{'restart'}->{$phpVersion} );
            # Check for actions precedence. The 'restart' action has higher precedence than the 'reload' action
            next if $action eq 'reload' && $self->{'restart'}->{$phpVersion};
            # Do not act if the PHP version is not enabled
            next unless $serviceMngr->isEnabled( "php$phpVersion-fpm" );

            $serviceMngr->registerDelayedAction( "php$phpVersion-fpm", [ $action, sub { $self->$action(); } ], $priority );
        }
    }
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
