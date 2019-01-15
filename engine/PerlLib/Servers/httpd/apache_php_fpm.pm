=head1 NAME

 Servers::httpd::apache_php_fpm - i-MSCP Apache2/PHP-FPM Server implementation

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

package Servers::httpd::apache_php_fpm;

use strict;
use warnings;
use autouse 'Date::Format' => qw/ time2str /;
use Class::Autouse qw/ :nostat Servers::httpd::apache_php_fpm::installer Servers::httpd::apache_php_fpm::uninstaller /;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Ext2Attributes qw/ setImmutable clearImmutable isImmutable /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Mount qw/ mount umount isMountpoint addMountEntry removeMountEntry /;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::Rights 'setRights';
use iMSCP::Service;
use iMSCP::TemplateParser qw/ process replaceBloc /;
use iMSCP::Umask '$UMASK';
use List::MoreUtils 'uniq';
use Try::Tiny;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache2/PHP-FPM Server implementation.

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

    Servers::httpd::apache_php_fpm::installer->getInstance()->registerSetupListeners( $em );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdPreInstall', 'apache_php_fpm' );
    #$rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdPreInstall', 'apache_php_fpm' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdInstall', 'apache_php_fpm' );
    $rs ||= Servers::httpd::apache_php_fpm::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdInstall', 'apache_php_fpm' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdPostInstall', 'apache_php_fpm' );
        return $rs if $rs;

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->enable( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
        $serviceMngr->enable( $self->{'config'}->{'HTTPD_SNAME'} );
        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                push @{ $_[0] }, [ sub { $self->start(); }, 'Httpd (Apache2/php-fpm)' ];
                0;
            },
            3
        );
        $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdPostInstall', 'apache_php_fpm' );
    } catch {
        error( $_ );
        1;
    };
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdUninstall', 'apache_php_fpm' );
    $rs ||= Servers::httpd::apache_php_fpm::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdUninstall', 'apache_php_fpm' );

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'HTTPD_SNAME'} ) ) {
        $self->{'restart'} = TRUE;
        return $rs;
    }

    @{ $self }{qw/ start restart /} = ( FALSE, FALSE );
    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ( $self ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdSetEnginePermissions' );
    $rs ||= setRights( '/usr/local/sbin/vlogger', {
        user  => $::imscpConfig{'ROOT_USER'},
        group => $::imscpConfig{'ROOT_GROUP'},
        mode  => '0750'
    } );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'}, {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $::imscpConfig{'ROOT_GROUP'},
        dirmode   => '0755',
        filemode  => '0644',
        recursive => iMSCP::Getopt->fixPermissions
    } );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'}, {
        group => $::imscpConfig{'ADM_GROUP'},
        mode  => '0750'
    } );
    $rs ||= setRights( "$::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages", {
        user      => $::imscpConfig{'ROOT_USER'},
        group     => $self->{'config'}->{'HTTPD_GROUP'},
        dirmode   => '0550',
        filemode  => '0440',
        recursive => iMSCP::Getopt->fixPermissions
    } );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdSetEnginePermissions' );
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ( $self, $data ) = @_;

    return 0 if $data->{'STATUS'} eq 'tochangepwd';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddUser', $data );
    $self->setData( $data );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->addToGroup( $data->{'GROUP'} );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddUser', $data );
    $self->{'restart'} = TRUE unless $rs;
    $rs;
}

=item deleteUser( \%data )

 Process deleteUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub deleteUser
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelUser', $data );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->removeFromGroup( $data->{'GROUP'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDelUser', $data );
    $self->{'restart'} = TRUE unless $rs;
    $rs;
}

=item addDmn( \%data )

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddDmn', $data );
    $self->setData( $data );
    $rs ||= $self->_addCfg( $data );
    $rs ||= $self->_addFiles( $data );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddDmn', $data );
    $self->{'restart'} = TRUE unless $rs;
    $rs;
}

=item restoreDmn( \%data )

 Process restoreDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub restoreDmn
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestoreDmn', $data );
    $self->setData( $data );
    $rs ||= $self->_addFiles( $data );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdRestoreDmn', $data );
}

=item disableDmn( \%data )

 Process disableDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ( $self, $data ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableDmn', $data );
        return $rs if $rs;

        for my $dir ( $self->_dmnFolders( $data ) ) {
            iMSCP::Dir->new( dirname => $dir->[0] )->make( {
                user  => $dir->[1],
                group => $dir->[2],
                mode  => $dir->[3]
            } );
        }

        $self->setData( $data );

        my $net = iMSCP::Net->getInstance();
        my @domainIPs = ( $data->{'DOMAIN_IP'} );

        $rs = $self->{'eventManager'}->trigger( 'onAddHttpdVhostIps', $data, \@domainIPs );
        return $rs if $rs;

        # Remove duplicate IP if any and map the INADDR_ANY IP to *
        @domainIPs = uniq( map { $net->normalizeAddr( $_ ) =~ s/^\Q0.0.0.0\E$/*/r } @domainIPs );

        $self->setData( {
            DOMAIN_IPS      => join( ' ', map { ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ? $_ : "[$_]" ) . ':80' } @domainIPs ),
            HTTP_URI_SCHEME => 'http://',
            HTTPD_LOG_DIR   => $self->{'config'}->{'HTTPD_LOG_DIR'},
            USER_WEB_DIR    => $::imscpConfig{'USER_WEB_DIR'},
            SERVER_ALIASES  => "www.$data->{'DOMAIN_NAME'}"
        } );

        # Create http vhost

        if ( $data->{'HSTS_SUPPORT'} ) {
            $self->setData( {
                FORWARD      => "https://$data->{'DOMAIN_NAME'}/",
                FORWARD_TYPE => '301'
            } );
            $data->{'VHOST_TYPE'} = 'domain_disabled_fwd';
        } else {
            $data->{'VHOST_TYPE'} = 'domain_disabled';
        }

        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain_disabled.tpl", $data, {
            destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf"
        } );

        $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}.conf" );
        return $rs if $rs;

        # Create https vhost (or delete it if SSL is disabled)

        if ( $data->{'SSL_SUPPORT'} ) {
            $self->setData( {
                CERTIFICATE     => "$::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem",
                DOMAIN_IPS      => join( ' ', map { ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ? $_ : "[$_]" ) . ':443' } @domainIPs ),
                HTTP_URI_SCHEME => 'https://'
            } );
            $data->{'VHOST_TYPE'} = 'domain_disabled_ssl';
            $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain_disabled.tpl", $data, {
                destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf"
            } );
            $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
            return $rs if $rs;
        } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf" ) {
            $rs = $self->disableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
            $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf" )->delFile();
            return $rs if $rs;
        }

        # Ensure that custom httpd conffile exists (cover case where file has been removed for any reasons)
        unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf" ) {
            $data->{'SKIP_TEMPLATE_CLEANER'} = TRUE;
            $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/custom.conf.tpl", $data, {
                destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf"
            } );
            return $rs if $rs;
        }

        # Transitional - Remove deprecated 'domain_disable_page' directory if any
        if ( $data->{'DOMAIN_TYPE'} eq 'dmn' && -d $data->{'WEB_DIR'} ) {
            clearImmutable( $data->{'WEB_DIR'} );
            iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/domain_disable_page" )->remove();
            setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        }

        $self->flushData();
        $self->{'eventManager'}->trigger( 'afterHttpdDisableDmn', $data );
    } catch {
        error( $_ );
        1;
    };
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ( $self, $data ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelDmn', $data );
        $rs ||= $self->disableSites( "$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;

        for my $file ( "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf",
            "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf",
            "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf",
            "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/$data->{'DOMAIN_NAME'}.conf"
        ) {
            next unless -f $file;
            $rs = iMSCP::File->new( filename => $file )->delFile();
            return $rs if $rs;
        }

        $rs = $self->umountLogsFolder( $data );
        return $rs if $rs;

        unless ( $data->{'SHARED_MOUNT_POINT'} || !-d $data->{'WEB_DIR'} ) {
            ( my $userWebDir = $::imscpConfig{'USER_WEB_DIR'} ) =~ s%/+$%%;
            my $parentDir = dirname( $data->{'WEB_DIR'} );

            clearImmutable( $parentDir );
            clearImmutable( $data->{'WEB_DIR'}, 'recursive' );
            iMSCP::Dir->new( dirname => $data->{'WEB_DIR'} )->remove();

            if ( $parentDir ne $userWebDir ) {
                my $dir = iMSCP::Dir->new( dirname => $parentDir );
                if ( $dir->isEmpty() ) {
                    clearImmutable( dirname( $parentDir ));
                    $dir->remove();
                }
            }

            if ( $data->{'WEB_FOLDER_PROTECTION'} eq 'yes' && $parentDir ne $userWebDir ) {
                do { setImmutable( $parentDir ) if -d $parentDir; } while ( $parentDir = dirname( $parentDir ) ) ne $userWebDir;
            }
        }

        iMSCP::Dir->new( dirname => "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}" )->remove();
        iMSCP::Dir->new( dirname => "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();

        $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelDmn', $data );
        $self->{'restart'} = TRUE unless $rs;
        $rs;
    } catch {
        error( $_ );
        1;
    };
}

=item addSub( \%data )

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddSub', $data );
    $self->setData( $data );
    $rs ||= $self->_addCfg( $data );
    $rs ||= $self->_addFiles( $data );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddSub', $data );
    $self->{'restart'} = TRUE unless $rs;
    $rs;
}

=item restoreSub( \%data )

 Process restoreSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub restoreSub
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestoreSub', $data );
    $self->setData( $data );
    $rs ||= $self->_addFiles( $data );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdRestoreSub', $data );
}

=item disableSub( \%data )

 Process disableSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableSub', $data );
    $rs ||= $self->disableDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDisableSub', $data );
}

=item deleteSub( \%data )

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelSub', $data );
    $rs ||= $self->deleteDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDelSub', $data );
}

=item addHtpasswd( \%data )

 Process addHtpasswd tasks

 Param hash \%data Htpasswd entry data
 Return int 0 on success, other on failure

=cut

sub addHtpasswd
{
    my ( $self, $data ) = @_;

    my $filepath = "$data->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filepath );
    $file->set( '' ) unless -f $filepath;
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddHtpasswd', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;
    ${ $fileC } .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdAddHtpasswd', $fileC, $data );
    return $rs if $rs;

    local $UMASK = 027;
    $rs = $file->save();
    $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    0;
}

=item deleteHtpasswd( \%data )

 Process deleteHtpasswd tasks

 Param hash \%data Htpasswd entry data
 Return int 0 on success, other on failure

=cut

sub deleteHtpasswd
{
    my ( $self, $data ) = @_;

    my $filepath = "$data->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
    return 0 unless -f $filepath;

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filepath );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelHtpasswd', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelHtpasswd', $fileC, $data );
    $rs ||= $file->save();
    return $rs if $rs;

    setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    0;
}

=item addHtgroup( \%data )

 Process addHtgroup tasks

 Param hash \%data Htgroup data
 Return int 0 on success, other on failure

=cut

sub addHtgroup
{
    my ( $self, $data ) = @_;

    my $filepath = "$data->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filepath );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddHtgroup', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;
    ${ $fileC } .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdAddHtgroup', $fileC, $data );
    return $rs if $rs;

    local $UMASK = 027;
    $rs = $file->save();
    $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    0;
}

=item deleteHtgroup( \%data )

 Process deleteHtgroup tasks

 Param hash \%data Htgroup data
 Return int 0 on success, other on failure

=cut

sub deleteHtgroup
{
    my ( $self, $data ) = @_;

    my $filepath = "$data->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";

    return 0 unless -f $filepath;

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filepath );
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelHtgroup', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelHtgroup', $fileC, $data );
    $rs ||= $file->save();
    $rs ||= $file->owner( $::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    0;
}

=item addHtaccess( \%data )

 Process addHtaccess tasks

 Param hash \%data Htaccess data
 Return int 0 on success, other on failure

=cut

sub addHtaccess
{
    my ( $self, $data ) = @_;

    # Here we process only if AUTH_PATH directory exists
    # Note: It's temporary fix for 1.1.0-rc2 (See #749)
    return 0 unless -d $data->{'AUTH_PATH'};

    my $filepath = "$data->{'AUTH_PATH'}/.htaccess";

    my $isImmutable = isImmutable( $data->{'AUTH_PATH'} );
    clearImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;

    my $file = iMSCP::File->new( filename => $filepath );
    $file->set( '' ) unless -f $filepath;
    my $fileC = $file->getAsRef();
    return 1 unless defined $fileC;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddHtaccess', $fileC, $data );
    return $rs if $rs;

    my $bTag = "### START i-MSCP PROTECTION ###\n";
    my $eTag = "### END i-MSCP PROTECTION ###\n";
    my $tagContent = <<"EOF";
AuthType $data->{'AUTH_TYPE'}\nAuthName "$data->{'AUTH_NAME'}"
AuthUserFile $data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}
EOF
    if ( $data->{'HTUSERS'} eq '' ) {
        $tagContent .= <<"EOF";
AuthGroupFile $data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}
Require group $data->{'HTGROUPS'}
EOF
    } else {
        $tagContent .= "Require user $data->{'HTUSERS'}\n";
    }

    ${ $fileC } = replaceBloc( $bTag, $eTag, '', ${ $fileC } );
    ${ $fileC } = $bTag . $fileC . $eTag . ${ $fileC };

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdAddHtaccess', $fileC, $data );
    return $rs if $rs;

    local $UMASK = 027;
    $rs = $file->save();
    $rs ||= $file->owner( $data->{'USER'}, $data->{'GROUP'} );
    $rs ||= $file->mode( 0640 );
    return $rs if $rs;

    setImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;
    0;
}

=item deleteHtaccess( \%data )

 Process deleteHtaccess tasks

 Param hash \%data Htaccess data
 Return int 0 on success, other on failure

=cut

sub deleteHtaccess
{
    my ( $self, $data ) = @_;

    # We process only if AUTH_PATH directory exists
    # Note: It's temporary fix for 1.1.0-rc2 (See #749)
    return 0 unless -d $data->{'AUTH_PATH'};

    my $filepath = "$data->{'AUTH_PATH'}/.htaccess";
    return 0 unless -f $filepath;

    my $isImmutable = isImmutable( $data->{'AUTH_PATH'} );
    clearImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;

    my $file = iMSCP::File->new( filename => $filepath );
    my $fileC = $file->getAsRef();

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelHtaccess', $fileC, $data );
    return $rs if $rs;

    ${ $fileC } = replaceBloc( "### START i-MSCP PROTECTION ###\n", "### END i-MSCP PROTECTION ###\n", '', ${ $fileC } );

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelHtaccess', $fileC, $data );
    return $rs if $rs;

    if ( length ${ $fileC } ) {
        $rs = $file->save();
        $rs ||= $file->owner( $data->{'USER'}, $data->{'GROUP'} );
        $rs ||= $file->mode( 0640 );
        return $rs if $rs;
    } else {
        $rs = $file->delFile();
        return $rs if $rs;
    }

    setImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;
    0;
}

=item buildConf( \$cfgTpl, $file [, \%data ] )

 Build the given configuration template

 Param scalarref \$cfgTpl Template content
 Param string $file Template filename
 Param hashref \%data OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Return void, die on failure

=cut

sub buildConf
{
    my ( $self, $cfgTpl, $file, $data ) = @_;
    $data ||= {};

    ref $cfgTpl eq 'SCALAR' && length ${ $cfgTpl } or die( 'Invalid $cfgTpl parameter. SCALAR reference expected' );
    ref $file eq '' && length $file or die( 'Invalid $file parameter. Not empty string expected' );
    ref $data eq 'HASH' or die( 'Invalid $data parameter. HASH reference expected' );

    if ( grep ( $_ eq $file, 'domain.tpl', 'domain_disabled.tpl' ) ) {
        if ( grep ( $_ eq $data->{'VHOST_TYPE'}, 'domain', 'domain_disabled' ) ) {
            # Remove ssl and forward sections
            ${ $cfgTpl } = replaceBloc( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', ${ $cfgTpl } );
            ${ $cfgTpl } = replaceBloc( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', ${ $cfgTpl } );
        } elsif ( grep ( $_ eq $data->{'VHOST_TYPE'}, 'domain_fwd', 'domain_ssl_fwd', 'domain_disabled_fwd' ) ) {
            # Remove ssl section if needed
            $cfgTpl = replaceBloc( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', ${ $cfgTpl } ) unless $data->{'VHOST_TYPE'} eq 'domain_ssl_fwd';
            # Remove domain section
            $cfgTpl = replaceBloc( "# SECTION dmn BEGIN.\n", "# SECTION dmn END.\n", '', ${ $cfgTpl } );
        } elsif ( grep ( $_ eq $data->{'VHOST_TYPE'}, 'domain_ssl', 'domain_disabled_ssl' ) ) {
            # Remove forward section
            $cfgTpl = replaceBloc( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', ${ $cfgTpl } );
        }
    }

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConf', $cfgTpl, $file, $data );
    $rs || ( ${ $cfgTpl } = process( $self->{'data'}, ${ $cfgTpl } ) );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdBuildConf', $cfgTpl, $file, $data );
    $rs == 0 or die( getMessageByType( 'error ', { amount => 1, remove => TRUE } ));
}

=item buildConfFile( $file [, \%data = { } [, \%options = { } ] ] )

 Build the given configuration file

 Param string $file Absolute configuration file path, or a configuration file path relative to the i-MSCP Apache configuration directory
 Param hashref \%data OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Param hashref \%options OPTIONAL Options:
  - destination: Destination file path (default to $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/<filebasename>)
  - user: File owner
  - group: File group
  - mode:  File mode
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
    my ( $self, $file, $data, $options ) = @_;
    $data //= {};
    $options //= {};

    try {
        ref $file eq '' && length $file or die( 'Invalid $file parameter. Not empty string expected' );
        ref $data eq 'HASH' or die( 'Invalid $data parameter. HASH reference expected' );
        ref $options eq 'HASH' or die( 'Invalid $data parameter. HASH reference expected' );

        ( $file, my $path ) = fileparse( $file );
        my $cfgTpl = \my $cfgTplC;

        $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apache_fcgid', $file, $cfgTpl, $data, $options ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => TRUE } )
        );

        unless ( defined $cfgTplC ) {
            if ( $path eq './' ) {
                $path = $self->{'apacheCfgDir'};
            } elsif ( index( $path, '/' ) != 0 ) {
                $path = File::Spec->catdir( $self->{'apacheCfgDir'}, $path );
            }

            defined( $cfgTpl = iMSCP::File->new( filename => File::Spec->catfile( $path, $file ))->getAsRef()) or die(
                getMessageByType( 'error', { amount => 1, remove => TRUE } )
            );
        }

        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConfFile', $cfgTpl, $file, $data, $options );
        $rs || ( ${ $cfgTpl } = $self->buildConf( $cfgTpl, $file, $data ) );
        $rs = $self->{'eventManager'}->trigger( 'afterHttpdBuildConfFile', $cfgTpl, $file, $data, $options );
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));

        local $UMASK = 022;
        $file = iMSCP::File->new( filename => $options->{'destination'} // "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$file" );
        $rs ||= $file->save();
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));

        if ( defined $options->{'user'} || defined $options->{'group'} ) {
            $file->owner( $options->{'user'} // $::imscpConfig{'ROOT_USER'}, $options->{'group'} // $::imscpConfig{'ROOT_GROUP'} ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => TRUE } )
            );
        }

        if ( defined $options->{'mode'} && $options->{'mode'} != 0644 ) {
            $file->mode( $options->{'mode'} ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
        }

        0;
    } catch {
        error( sprintf( "Couldn't build configuration file: %s", $_ ));
        return 1;
    };
}

=item getData( )

 Get server data
 Return hashref Server data

=cut

sub getData
{
    my ( $self ) = @_;

    $self->{'data'};
}

=item setData( \%data )

 Make the given data available for this server

 Param hash \%data Server data
 Return int 0

=cut

sub setData
{
    my ( $self, $data ) = @_;

    @{ $self->{'data'} }{keys %{ $data }} = values %{ $data };
    0;
}

=item flushData( )

 Flush all data set via the setData( ) method

 Return int 0

=cut

sub flushData
{
    my ( $self ) = @_;

    delete $self->{'data'};
    0;
}

=item getTraffic( $trafficDb )

 Get httpd traffic data

 Param hashref \%trafficDb Traffic database
 Return void, die on failure

=cut

sub getTraffic
{
    my ( undef, $trafficDb ) = @_;

    debug( sprintf( 'Collecting HTTP traffic data' ));

    try {
        iMSCP::Database->factory()->getConnector()->txn( fixup => sub {
            my ( $dbh ) = @_;
            my $ldate = time2str( '%Y%m%d', time());
            my $sth = $dbh->prepare( 'SELECT vhost, bytes FROM httpd_vlogger WHERE ldate <= ? FOR UPDATE' );
            $sth->execute( $ldate );
            while ( my $row = $sth->fetchrow_hashref() ) {
                next unless exists $trafficDb->{$row->{'vhost'}};
                $trafficDb->{$row->{'vhost'}} += $row->{'bytes'};
            }
            $dbh->do( 'DELETE FROM httpd_vlogger WHERE ldate <= ?', undef, $ldate );
        } );
    } catch {
        %{ $trafficDb } = ();
        die $_;
    };
}

=item getRunningUser( )

 Get user name under which the Apache server is running

 Return string User name under which the apache server is running

=cut

sub getRunningUser
{
    my ( $self ) = @_;

    $self->{'config'}->{'HTTPD_USER'};
}

=item getRunningGroup( )

 Get group name under which the Apache server is running

 Return string Group name under which the apache server is running

=cut

sub getRunningGroup
{
    my ( $self ) = @_;

    $self->{'config'}->{'HTTPD_GROUP'};
}

=item enableSites( @sites )

 Enable the given sites

 Param array @sites List of sites to enable
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
    my ( $self, @sites ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableSites', \@sites );
    return $rs if $rs;

    for my $site ( @sites ) {
        unless ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site" ) {
            warning( sprintf( "Site %s doesn't exists", $site ));
            next;
        }

        $rs = execute( [ 'a2ensite', $site ], \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdEnableSites', @sites );
}

=item disableSites( @sites )

 Disable the given sites

 Param array @sites List of sites to disable
 Return int 0 on sucess, other on failure

=cut

sub disableSites
{
    my ( $self, @sites ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableSites', \@sites );
    return $rs if $rs;

    for my $site ( @sites ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$site";
        $rs = execute( [ 'a2dissite', $site ], \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdDisableSites', @sites );
}

=item enableModules( @modules )

 Enable the given Apache modules

 Param array $modules List of modules to enable
 Return int 0 on sucess, other on failure

=cut

sub enableModules
{
    my ( $self, @modules ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableModules', \@modules );
    return $rs if $rs;

    for my $module ( @modules ) {
        next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$module.load";
        $rs = execute( [ 'a2enmod', $module ], \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdEnableModules', @modules );
}

=item disableModules( @modules )

 Disable the given Apache modules

 Param array @modules List of modules to disable
 Return int 0 on sucess, other on failure

=cut

sub disableModules
{
    my ( $self, @modules ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableModules', \@modules );
    return $rs if $rs;

    for my $module ( @modules ) {
        next unless -l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/$module.load";
        $rs = execute( [ 'a2dismod', $module ], \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdDisableModules', @modules );
}

=item enableConfs( @conffiles )

 Enable the given configuration files

 Param array @conffiles List of configuration files to enable
 Return int 0 on sucess, other on failure

=cut

sub enableConfs
{
    my ( $self, @conffiles ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableConfs', \@conffiles );
    return $rs if $rs;

    for my $conffile ( @conffiles ) {
        unless ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$conffile" ) {
            warning( sprintf( "Configuration file %s doesn't exists", $conffile ));
            next;
        }

        $rs = execute( [ 'a2enconf', $conffile ], \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdEnableConfs', @conffiles );
}

=item disableConfs( @conffiles )

 Disable the given configuration files

 Param array @conffiles Lilst of configuration files to disable
 Return int 0 on sucess, other on failure

=cut

sub disableConfs
{
    my ( $self, @conffiles ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableConfs', \@conffiles );
    return $rs if $rs;

    for my $conffile ( @conffiles ) {
        next unless -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$conffile";
        $rs = execute( [ 'a2disconf', $conffile ], \my $stdout, \my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = TRUE;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdDisableConfs', @conffiles );
}

=item start( )

 Start httpd service

 Return int 0 on success, other on failure

=cut

sub start
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdStart' );
        return $rs if $rs;

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->start( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
        $serviceMngr->start( $self->{'config'}->{'HTTPD_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterHttpdStart' );
    } catch {
        error( $_ );
        1;
    };
}

=item stop( )

 Stop httpd service

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdStop' );
        return $rs if $rs;

        my $serviceMngr = iMSCP::Service->getInstance();
        $serviceMngr->stop( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
        $serviceMngr->stop( $self->{'config'}->{'HTTPD_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterHttpdStop' );
    } catch {
        error( $_ );
        1;
    };
}

=item forceRestart( )

 Force httpd service to be restarted

 Return int 0

=cut

sub forceRestart
{
    my ( $self ) = @_;

    $self->{'forceRestart'} = TRUE;
    0;
}

=item restart( )

 Restart or reload httpd service

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ( $self ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestart' );
        return $rs if $rs;

        my $serviceMngr = iMSCP::Service->getInstance();
        if ( $self->{'forceRestart'} ) {
            $serviceMngr->restart( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
            $serviceMngr->restart( $self->{'config'}->{'HTTPD_SNAME'} );
            return;
        }

        $serviceMngr->reload( sprintf( 'php%s-fpm', $self->{'phpConfig'}->{'PHP_VERSION'} ));
        $serviceMngr->reload( $self->{'config'}->{'HTTPD_SNAME'} );
        $self->{'eventManager'}->trigger( 'afterHttpdRestart' );
    } catch {
        error( $_ );
        1;
    };
}

=item mountLogsFolder( \%data )

 Mount logs folder which belong to the given domain into customer's logs folder

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub mountLogsFolder
{
    my ( $self, $data ) = @_;

    try {
        my $fsSpec = File::Spec->canonpath( "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}" );
        my $fsFile = File::Spec->canonpath( "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}" );
        my $fields = { fs_spec => $fsSpec, fs_file => $fsFile, fs_vfstype => 'none', fs_mntops => 'bind' };
        my $rs = $self->{'eventManager'}->trigger( 'beforeMountLogsFolder', $data, $fields );
        return $rs if $rs;

        iMSCP::Dir->new( dirname => $fsFile )->make();
        $rs = addMountEntry( "$fields->{'fs_spec'} $fields->{'fs_file'} $fields->{'fs_vfstype'} $fields->{'fs_mntops'}" );
        $rs ||= mount( $fields ) unless isMountpoint( $fields->{'fs_file'} );
        $rs ||= $self->{'eventManager'}->trigger( 'afterMountLogsFolder', $data, $fields );
    } catch {
        error( $_ );
        1;
    };
}

=item umountLogsFolder( \%data )

 Umount logs folder which belong to the given domain from customer's logs folder

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub umountLogsFolder
{
    my ( $self, $data ) = @_;

    my $recursive = TRUE;
    my $fsFile = "$data->{'HOME_DIR'}/logs";

    # We operate recursively only if domain type is 'dmn' (full account)
    if ( $data->{'DOMAIN_TYPE'} ne 'dmn' ) {
        $recursive = FALSE;
        $fsFile .= "/$data->{'DOMAIN_NAME'}";
    }

    $fsFile = File::Spec->canonpath( $fsFile );

    my $rs = $self->{'eventManager'}->trigger( 'beforeUnmountLogsFolder', $data, $fsFile );
    $rs ||= removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
    $rs ||= umount( $fsFile, $recursive );
    $rs ||= $self->{'eventManager'}->trigger( 'afterUmountMountLogsFolder', $data, $fsFile );
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
    my ( $self ) = @_;

    $self->{'start'} = FALSE;
    $self->{'restart'} = FALSE;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'apacheCfgDir'} = "$::imscpConfig{'CONF_DIR'}/apache";
    $self->{'apacheTplDir'} = "$self->{'apacheCfgDir'}/parts";

    $self->_mergeConfig( $self->{'apacheCfgDir'}, 'apache.data' ) if -f "$self->{'apacheCfgDir'}/apache.data.dist";
    tie %{ $self->{'config'} },
        'iMSCP::Config',
        fileName    => "$self->{'apacheCfgDir'}/apache.data",
        readonly    => $::execmode ne 'setup',
        nodeferring => $::execmode eq 'setup';

    $self->{'phpCfgDir'} = "$::imscpConfig{'CONF_DIR'}/php";

    $self->_mergeConfig( $self->{'phpCfgDir'}, 'php.data' ) if -f "$self->{'phpCfgDir'}/php.data.dist";
    tie %{ $self->{'phpConfig'} },
        'iMSCP::Config',
        fileName    => "$self->{'phpCfgDir'}/php.data",
        readonly    => $::execmode ne 'setup',
        nodeferring => $::execmode eq 'setup';

    $self->{'eventManager'}->register( 'afterHttpdBuildConfFile', sub { $self->_cleanTemplate( @_ ) } );
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
    my ( undef, $confDir, $confName ) = @_;

    if ( -f "$confDir/$confName" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$confDir/$confName.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$confDir/$confName", readonly => TRUE;
        debug( 'Merging old configuration with new configuration...' );
        while ( my ( $key, $value ) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }
        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$confDir/$confName.dist" )->moveFile( "$confDir/$confName" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
    );
}

=item _addCfg( \%data )

 Add configuration files for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addCfg
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddCfg', $data );
    #$rs = $self->disableSites( "$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf" );
    return $rs if $rs;

    $self->setData( $data );

    my $confLevel = $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'};
    if ( $confLevel eq 'per_user' ) { # One php.ini file for all domains
        $confLevel = $data->{'ROOT_DOMAIN_NAME'};
    } elsif ( $confLevel eq 'per_domain' ) { # One php.ini file for each domains (including subdomains)
        $confLevel = $data->{'PARENT_DOMAIN_NAME'};
    } else { # One php.ini file for each domain
        $confLevel = $data->{'DOMAIN_NAME'};
    }

    my $net = iMSCP::Net->getInstance();
    my $phpVersion = $self->{'phpConfig'}->{'PHP_VERSION'};
    my @domainIPs = ( $data->{'DOMAIN_IP'} );

    $rs = $self->{'eventManager'}->trigger( 'onAddHttpdVhostIps', $data, \@domainIPs );
    return $rs if $rs;

    # Remove duplicate IP if any and map the INADDR_ANY IP to *
    @domainIPs = uniq( map { $net->normalizeAddr( $_ ) =~ s/^\Q0.0.0.0\E$/*/r } @domainIPs );

    $self->setData( {
        DOMAIN_IPS             => join( ' ', map { ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ? $_ : "[$_]" ) . ':80' } @domainIPs ),
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        PHP_VERSION            => $phpVersion,
        POOL_NAME              => $confLevel,
        PROXY_FCGI_PATH        => $self->{'phpConfig'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds' ? "unix:/run/php/php$phpVersion-fpm-$confLevel.sock|" : '',
        PROXY_FCGI_URL         => 'fcgi://' . ( $self->{'phpConfig'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds'
            ? $confLevel : '127.0.0.1:' . ( $self->{'phpConfig'}->{'PHP_FPM_LISTEN_PORT_START'}+$data->{'PHP_FPM_LISTEN_PORT'} )
        ),
        SERVER_ALIASES         => "www.$data->{'DOMAIN_NAME'}"
    } );

    # Create http vhost

    if ( $data->{'HSTS_SUPPORT'} ) {
        $self->setData( {
            FORWARD      => "https://$data->{'DOMAIN_NAME'}/",
            FORWARD_TYPE => '301'
        } );
        $data->{'VHOST_TYPE'} = 'domain_fwd';
    } elsif ( $data->{'FORWARD'} ne 'no' ) {
        if ( $data->{'FORWARD_TYPE'} eq 'proxy' ) {
            $self->setData( {
                X_FORWARDED_PROTOCOL => 'http',
                X_FORWARDED_PORT     => 80
            } );
        }

        $data->{'VHOST_TYPE'} = 'domain_fwd';
    } else {
        $data->{'VHOST_TYPE'} = 'domain';
    }

    $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain.tpl", $data, {
        destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf"
    } );
    $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $data->{'SSL_SUPPORT'} ) {
        $self->setData( {
            CERTIFICATE   => "$::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem",
            DOMAIN_IPS    => join( ' ', map { ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ? $_ : "[$_]" ) . ':443' } @domainIPs ),
            FASTCGI_CLASS => $data->{'DOMAIN_NAME'} . '-ssl'
        } );

        if ( $data->{'FORWARD'} ne 'no' ) {
            $self->setData( {
                FORWARD      => $data->{'FORWARD'},
                FORWARD_TYPE => $data->{'FORWARD_TYPE'}
            } );

            if ( $data->{'FORWARD_TYPE'} eq 'proxy' ) {
                $self->setData( {
                    X_FORWARDED_PROTOCOL => 'https',
                    X_FORWARDED_PORT     => 443
                } );
            }

            $data->{'VHOST_TYPE'} = 'domain_ssl_fwd';
        } else {
            $data->{'VHOST_TYPE'} = 'domain_ssl';
        }

        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain.tpl", $data, {
            destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf"
        } );
        $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf" )->delFile();
        return $rs if $rs;
    }

    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf" ) {
        $data->{'SKIP_TEMPLATE_CLEANER'} = TRUE;
        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/custom.conf.tpl", $data, {
            destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf"
        } );
    }

    $rs ||= $self->_buildPHPConfig( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddCfg', $data );
}

=item _dmnFolders( \%data )

 Get Web folders list to create for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return array List of Web folders to create

=cut

sub _dmnFolders
{
    my ( $self, $data ) = @_;

    my @folders = ();

    $self->{'eventManager'}->trigger( 'beforeHttpdDmnFolders', \@folders );
    push( @folders, [
        "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}", $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ADM_GROUP'}, 0755
    ] );
    $self->{'eventManager'}->trigger( 'afterHttpdDmnFolders', \@folders );
    @folders;
}

=item _addFiles( \%data )

 Add default directories and files for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _addFiles
{
    my ( $self, $data ) = @_;

    try {
        my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddFiles', $data );
        return $rs if $rs;

        for my $dir ( $self->_dmnFolders( $data ) ) {
            iMSCP::Dir->new( dirname => $dir->[0] )->make( {
                user  => $dir->[1],
                group => $dir->[2],
                mode  => $dir->[3]
            } );
        }

        # Whether or not permissions must be fixed recursively
        my $fixPermissions = iMSCP::Getopt->fixPermissions || $data->{'ACTION'} =~ /^restore(?:Dmn|Sub)$/;

        # Prepare Web folder
        my $skelDir;
        if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
            $skelDir = "$::imscpConfig{'CONF_DIR'}/skel/domain";
        } elsif ( $data->{'DOMAIN_TYPE'} eq 'als' ) {
            $skelDir = "$::imscpConfig{'CONF_DIR'}/skel/alias";
        } else {
            $skelDir = "$::imscpConfig{'CONF_DIR'}/skel/subdomain";
        }

        # Copy skeleton in tmp dir
        my $tmpDir = File::Temp->newdir();
        iMSCP::Dir->new( dirname => $skelDir )->rcopy( $tmpDir, { preserve => 'no' } );

        # Build default page if the document root doesn't exist or is empty
        if ( !-d "$data->{'WEB_DIR'}/htdocs" || iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/htdocs" )->isEmpty() ) {
            if ( -d "$tmpDir/htdocs" ) {
                # Test needed in case admin removed the index.html file from the skeleton
                if ( -f "$tmpDir/htdocs/index.html" ) {
                    $data->{'SKIP_TEMPLATE_CLEANER'} = TRUE;
                    my $fileSource = "$tmpDir/htdocs/index.html";
                    $rs = $self->buildConfFile( $fileSource, $data, { destination => $fileSource } );
                    return $rs if $rs;
                }
            } else {
                error( "Web folder skeleton must provides the 'htdocs' directory." );
                return 1;
            }

            # Force recursive permissions for newly created Web folders
            $fixPermissions = TRUE;
        } else {
            iMSCP::Dir->new( dirname => "$tmpDir/htdocs" )->remove();
        }

        if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
            if ( -d "$data->{'WEB_DIR'}/errors" && !iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/errors" )->isEmpty() ) {
                iMSCP::Dir->new( dirname => "$tmpDir/errors" )->remove();
            } elsif ( !-d "$tmpDir/errors" ) {
                error( "The 'domain' Web folder skeleton must provides the 'errors' directory." );
                return 1;
            } else {
                $fixPermissions = TRUE;
            }
        }

        my $parentDir = dirname( $data->{'WEB_DIR'} );

        # Make sure that parent folder of alssub Web folder exists
        # See #IP-1327
        if ( $data->{'DOMAIN_TYPE'} eq 'alssub' && !-d $parentDir ) {
            clearImmutable( dirname( $parentDir ));
            iMSCP::Dir->new( dirname => $parentDir )->make( {
                user  => $data->{'USER'},
                group => $data->{'GROUP'},
                mode  => 0750
            } );
        } else {
            clearImmutable( $parentDir );
        }

        clearImmutable( $data->{'WEB_DIR'} ) if -d $data->{'WEB_DIR'};

        if ( $data->{'DOMAIN_TYPE'} eq 'dmn' && $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} ne 'yes' ) {
            $rs = $self->umountLogsFolder( $data );
            return $rs if $rs;

            iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/logs" )->remove();
            iMSCP::Dir->new( dirname => "$tmpDir/logs" )->remove();
        } elsif ( $data->{'DOMAIN_TYPE'} eq 'dmn' && !-d "$tmpDir/logs" ) {
            error( "Web folder skeleton must provides the 'logs' directory." );
            return 1;
        }

        # Copy Web folder
        iMSCP::Dir->new( dirname => $tmpDir )->rcopy( $data->{'WEB_DIR'}, { preserve => 'no' } );

        # Cleanup (Transitional)
        if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
            # Remove deprecated 'domain_disable_page' directory if any
            iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/domain_disable_page" )->remove();
        } elsif ( !$data->{'SHARED_MOUNT_POINT'} ) {
            # Remove deprecated phptmp directory if any
            iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/phptmp" )->remove();
            iMSCP::Dir->new( dirname => "$tmpDir/phptmp" )->remove();
        }

        # Set ownership and permissions for Web folder root
        # Web folder root vuxxx:vuxxx 0750 (no recursive)
        $rs = setRights( $data->{'WEB_DIR'}, {
            user  => $data->{'USER'},
            group => $data->{'GROUP'},
            mode  => '0750'
        } );
        return $rs if $rs;

        # Set ownership and permissions for Root Web folder
        # Root Web folder vuxxx:vuxxx 0750 (no recursive)
        $rs = setRights( $data->{'WEB_DIR'}, {
            user  => $data->{'USER'},
            group => $data->{'GROUP'},
            mode  => '0750'
        } );
        return $rs if $rs;

        # Set ownership and permissions for first Web folder depth, e.g:
        # 00_private vuxxx:vuxxx 0750 -- recursive with --fix-permissions --  Web folder only
        # backups    skipped -- recursive with --fix-permissions -- Root Web folder only
        # cgi-bin    vuxxx:vuxxx 0750 -- recursive with --fix-permissions -- Root Web folder only
        # error      vuxxx:vuxxx 0750 -- recursive with --fix-permissions -- Root Web folder only
        # htdocs     vuxxx:vuxxx 0750 -- recursive with --fix-permissions --
        # logs       skipped -- Root Web folder only
        # phptmp     vuxxx:vuxxx 0750 -- recursive with --fix-permissions -- Root Web folder only
        # .htgroup   root:www-data 0640 -- Root Web folder only
        # .htpasswd  root:www-data 0640 -- Root Web folder only
        for my $file (
            $data->{'DOMAIN_TYPE'} eq 'dmn'
                ? grep ( !/^backups|logs$/, iMSCP::Dir->new( dirname => $skelDir )->getAll() )
                : iMSCP::Dir->new( dirname => $skelDir )->getAll()
        ) {
            next unless -e "$data->{'WEB_DIR'}/$file";
            $rs = setRights( "$data->{'WEB_DIR'}/$file", {
                user      => grep ( $_ eq $file, '.htgroup', '.htpasswd') ? $::imscpConfig{'ROOT_USER'} : $data->{'USER'},
                group     => grep ( $_ eq $file, '.htgroup', '.htpasswd') ? $self->{'config'}->{'HTTPD_GROUP'} : $data->{'GROUP'},
                dirmode   => '0750',
                filemode  => '0640',
                recursive => $fixPermissions
            } );
            return $rs if $rs;
        }

        if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
            # Set ownership and permissions for backups directory
            # backups root:vuxxx (no recursive)
            $rs = setRights( "$data->{'WEB_DIR'}/logs", {
                user  => $::imscpConfig{'ROOT_USER'},
                group => $data->{'GROUP'},
                mode  => '02750'
            } );
            return $rs if $rs;

            # Set ownership for logs directory if any
            # logs root:vuxxx (no recursive)
            if ( -d "$data->{'WEB_DIR'}/logs" ) {
                $rs = setRights( "$data->{'WEB_DIR'}/backup", {
                    user  => $::imscpConfig{'ROOT_USER'},
                    group => $data->{'GROUP'},
                } );
                return $rs if $rs;
            }
        }

        if ( $data->{'WEB_FOLDER_PROTECTION'} eq 'yes' ) {
            my $dir = $data->{'WEB_DIR'};
            my $userWebDir = File::Spec->canonpath( $::imscpConfig{'USER_WEB_DIR'} );
            do { setImmutable( $dir ); } while ( $dir = dirname( $dir ) ) ne $userWebDir;
        }

        $rs = $self->mountLogsFolder( $data ) if $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} eq 'yes';
        $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddFiles', $data );
    } catch {
        error( $_ );
        1;
    };
}

=item _buildPHPConfig( \%data )

 Build PHP related configuration files

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _buildPHPConfig
{
    my ( $self, $data ) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildPhpConf', $data );
    return $rs if $rs;

    my $confLevel = $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'};
    my $domainType = $data->{'DOMAIN_TYPE'};
    my $phpVersion = $self->{'phpConfig'}->{'PHP_VERSION'};

    my ( $poolName, $emailDomain );
    if ( $confLevel eq 'per_user' ) {
        # One pool configuration file per user
        $poolName = $data->{'ROOT_DOMAIN_NAME'};
        $emailDomain = $data->{'ROOT_DOMAIN_NAME'};
    } elsif ( $confLevel eq 'per_domain' ) {
        # One pool configuration file perl domains (including subdomains)
        $poolName = $data->{'PARENT_DOMAIN_NAME'};
        $emailDomain = $data->{'DOMAIN_NAME'};
    } else {
        # One pool configuration file per domain
        $poolName = $data->{'DOMAIN_NAME'};
        $emailDomain = $data->{'DOMAIN_NAME'};
    }

    if ( $data->{'FORWARD'} eq 'no' && $data->{'PHP_SUPPORT'} eq 'yes' ) {
        $self->setData( {
            EMAIL_DOMAIN                 => $emailDomain,
            PHP_FPM_LISTEN_ENDPOINT      => ( $self->{'phpConfig'}->{'PHP_FPM_LISTEN_MODE'} eq 'uds' )
                ? "/run/php/php$phpVersion-fpm-$poolName.sock"
                : '127.0.0.1:' . ( $self->{'phpConfig'}->{'PHP_FPM_LISTEN_PORT_START'}+$data->{'PHP_FPM_LISTEN_PORT'} ),
            PHP_FPM_MAX_CHILDREN         => $self->{'phpConfig'}->{'PHP_FPM_MAX_CHILDREN'} || 6,
            PHP_FPM_MAX_REQUESTS         => $self->{'phpConfig'}->{'PHP_FPM_MAX_REQUESTS'} || 1000,
            PHP_FPM_MAX_SPARE_SERVERS    => $self->{'phpConfig'}->{'PHP_FPM_MAX_SPARE_SERVERS'} || 2,
            PHP_FPM_MIN_SPARE_SERVERS    => $self->{'phpConfig'}->{'PHP_FPM_MIN_SPARE_SERVERS'} || 1,
            PHP_FPM_PROCESS_IDLE_TIMEOUT => $self->{'phpConfig'}->{'PHP_FPM_PROCESS_IDLE_TIMEOUT'} || '60s',
            PHP_FPM_PROCESS_MANAGER_MODE => $self->{'phpConfig'}->{'PHP_FPM_PROCESS_MANAGER_MODE'} || 'ondemand',
            PHP_FPM_START_SERVERS        => $self->{'phpConfig'}->{'PHP_FPM_START_SERVERS'} || 1,
            PHP_VERSION                  => $phpVersion,
            POOL_NAME                    => $poolName,
            TMPDIR                       => $data->{'HOME_DIR'} . '/phptmp'
        } );

        $rs = $self->buildConfFile( "$self->{'phpCfgDir'}/fpm/pool.conf", $data, {
            destination => "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/$poolName.conf"
        } );
        return $rs if $rs;
    } elsif ( ( $data->{'PHP_SUPPORT'} ne 'yes' || $confLevel eq 'per_user' && $domainType ne 'dmn'
        || $confLevel eq 'per_domain' && $domainType !~ /^(?:dmn|als)$/ || $confLevel eq 'per_site' )
        && -f "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/$data->{'DOMAIN_NAME'}.conf"
    ) {
        $rs = iMSCP::File->new( filename => "$self->{'phpConfig'}->{'PHP_FPM_POOL_DIR_PATH'}/$data->{'DOMAIN_NAME'}.conf" )->delFile();
        return $rs if $rs;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdBuildPhpConf', $data );
}

=item _cleanTemplate( \$tpl, $filename, \%data )

 Event listener which is responsible to cleanup production configuration files

 Param string \$tpl Template content
 Param string $name Template name
 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0

=cut

sub _cleanTemplate
{
    my ( undef, $tpl, $name, $data ) = @_;

    if ( $data->{'SKIP_TEMPLATE_CLEANER'} ) {
        delete $data->{'SKIP_TEMPLATE_CLEANER'};
        return 0;
    }

    if ( $name eq 'domain.tpl' ) {
        if ( $data->{'VHOST_TYPE'} !~ /fwd/ ) {
            unless ( $data->{'CGI_SUPPORT'} eq 'yes' ) {
                ${ $tpl } = replaceBloc( "# SECTION suexec BEGIN.\n", "# SECTION suexec END.\n", '', ${ $tpl } );
                ${ $tpl } = replaceBloc( "# SECTION cgi BEGIN.\n", "# SECTION cgi END.\n", '', ${ $tpl } );
            }

            if ( $data->{'PHP_SUPPORT'} eq 'yes' ) {
                ${ $tpl } = replaceBloc( "# SECTION php_off BEGIN.\n", "# SECTION php_off END.\n", '', ${ $tpl } );
            } else {
                ${ $tpl } = replaceBloc( "# SECTION php_on BEGIN.\n", "# SECTION php_on END.\n", '', ${ $tpl } );
            }

            ${ $tpl } = replaceBloc( "# SECTION fcgid BEGIN.\n", "# SECTION fcgid END.\n", '', ${ $tpl } );
            ${ $tpl } = replaceBloc( "# SECTION itk BEGIN.\n", "# SECTION itk END.\n", '', ${ $tpl } );
        } elsif ( $data->{'FORWARD'} ne 'no' ) {
            if ( $data->{'FORWARD_TYPE'} eq 'proxy' && ( !$data->{'HSTS_SUPPORT'} || $data->{'VHOST_TYPE'} =~ /ssl/ ) ) {
                ${ $tpl } = replaceBloc( "# SECTION std_fwd BEGIN.\n", "# SECTION std_fwd END.\n", '', ${ $tpl } );

                if ( index( $data->{'FORWARD'}, 'https' ) != 0 ) {
                    ${ $tpl } = replaceBloc( "# SECTION ssl_proxy BEGIN.\n", "# SECTION ssl_proxy END.\n", '', ${ $tpl } );
                }
            } else {
                ${ $tpl } = replaceBloc( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', ${ $tpl } );
            }
        } else {
            ${ $tpl } = replaceBloc( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', ${ $tpl } );
        }
    }

    ${ $tpl } =~ s/^\s*(?:[#;].*)?\n//gm;
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
