=head1 NAME

 Servers::httpd::Apache2::Abstract - i-MSCP Apache2 server abstract class

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

package Servers::httpd::Apache2::Abstract;

use strict;
use warnings;
use Array::Utils qw/ unique /;
use autouse 'Date::Format' => qw/ time2str /;
use autouse 'iMSCP::Crypt' => qw/ ALNUM randomStr /;
use Class::Autouse qw/ :nostat iMSCP::Database Servers::sqld /;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Config;
use iMSCP::Debug qw/ debug error getMessageByType warning /;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ execute /;
use iMSCP::Ext2Attributes qw/ setImmutable clearImmutable isImmutable /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Mount qw/ mount umount isMountpoint addMountEntry removeMountEntry /;
use iMSCP::Net;
use iMSCP::Rights;
use iMSCP::Service;
use iMSCP::SystemUser;
use iMSCP::TemplateParser qw/ process replaceBloc /;
use iMSCP::Umask;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Abstract class for i-MSCP Apache2 server implementations.

=head1 PUBLIC METHODS

=over 4

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdPreInstall', ref $self );
    $rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdPreInstall', ref $self );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdInstall', ref $self );
    $rs ||= $self->_setVersion();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_copyDomainDisablePages();
    $rs ||= $self->_setupModules();
    $rs ||= $self->_configure();
    $rs ||= $self->_installLogrotate();
    $rs ||= $self->_setupVlogger();
    $rs ||= $self->_cleanup();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdInstall', ref $self );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdPostInstall', ref $self );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->enable( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Apache2' ];
            0;
        },
        3
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdPostInstall', ref $self );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdUninstall', ref $self );
    $rs ||= $self->_removeVloggerSqlUser();
    $rs ||= $self->_removeDirs();
    $rs ||= $self->_restoreDefaultConfig();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdUninstall', ref $self );

    if ( $rs || !iMSCP::Service->getInstance()->hasService( 'apache2' ) ) {
        $self->{'start'} = 0;
        $self->{'restart'} = 0;
        return $rs;
    }

    $self->{'restart'} ||= 1;
    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdSetEnginePermissions', ref $self );
    $rs ||= setRights( '/usr/local/sbin/vlogger',
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0750'
        }
    );
    # Fix permissions on root log dir (e.g: /var/log/apache2) in any cases
    # Fix permissions on root log dir (e.g: /var/log/apache2) content only with --fix-permissions option
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ROOT_GROUP'},
            dirmode   => '0755',
            filemode  => '0644',
            recursive => 1
        }
    );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'},
        {
            group => $main::imscpConfig{'ADM_GROUP'},
            mode  => '0750'
        }
    );
    $rs ||= setRights( "$main::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages",
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $self->{'config'}->{'HTTPD_GROUP'},
            dirmode   => '0550',
            filemode  => '0440',
            recursive => 1
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdSetEnginePermissions', ref $self );
}

=item addUser( \%moduleData )

 Process addUser tasks

 Param hash \%moduleData User data as provided by User module
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $moduleData) = @_;

    return 0 if $moduleData->{'STATUS'} eq 'tochangepwd';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddUser', $moduleData );
    $self->setData( $moduleData );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->addToGroup( $moduleData->{'GROUP'} );
    $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddUser', $moduleData );
    $self->{'restart'} ||= 1;
    $rs;
}

=item deleteUser( \%moduleData )

 Process deleteUser tasks

 Param hash \%moduleData User data as provided by User module
 Return int 0 on success, other on failure

=cut

sub deleteUser
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelUser', $moduleData );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->removeFromGroup( $moduleData->{'GROUP'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDelUser', $moduleData );
    $self->{'restart'} ||= 1;
    $rs;
}

=item addDmn( \%moduleData )

 Process addDmn tasks

 Param hash \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddDmn', $moduleData );
    $self->setData( $moduleData );
    $rs ||= $self->_addCfg( $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddDmn', $moduleData );
    $self->{'restart'} ||= 1;
    $rs;
}

=item restoreDmn( \%moduleData )

 Process restoreDmn tasks

 Param hash \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub restoreDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestoreDmn', $moduleData );
    $self->setData( $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdRestoreDmn', $moduleData );
}

=item disableDmn( \%moduleData )

 Process disableDmn tasks

 Param hash \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub disableDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableDmn', $moduleData );
    return $rs if $rs;

    # Ensure that all required directories are present
    eval {
        for ( $self->_dmnFolders( $moduleData ) ) {
            iMSCP::Dir->new( dirname => $_->[0] )->make( {
                user  => $_->[1],
                group => $_->[2],
                mode  => $_->[3]
            } );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->setData( $moduleData );

    my $net = iMSCP::Net->getInstance();
    my @domainIPs = ( $moduleData->{'DOMAIN_IP'}, ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' ? $moduleData->{'BASE_SERVER_IP'} : () ) );

    $rs = $self->{'eventManager'}->trigger( 'onAddHttpdVhostIps', $moduleData, \@domainIPs );
    return $rs if $rs;

    # If INADDR_ANY is found, map it to the wildcard sign and discard any other
    # IP, else, remove any duplicate IP address from the list
    @domainIPs = grep($_ eq '0.0.0.0', @domainIPs) ? ( '*' ) : unique( map { $net->normalizeAddr( $_ ) } @domainIPs );

    $self->setData( {
        DOMAIN_IPS      => join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':80' } @domainIPs ),
        HTTP_URI_SCHEME => 'http://',
        HTTPD_LOG_DIR   => $self->{'config'}->{'HTTPD_LOG_DIR'},
        USER_WEB_DIR    => $main::imscpConfig{'USER_WEB_DIR'},
        SERVER_ALIASES  => "www.$moduleData->{'DOMAIN_NAME'}" . ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes'
            ? " $moduleData->{'ALIAS'}.$main::imscpConfig{'BASE_SERVER_VHOST'}" : ''
        )
    } );

    # Create http vhost

    if ( $moduleData->{'HSTS_SUPPORT'} ) {
        $self->setData( {
            FORWARD      => "https://$moduleData->{'DOMAIN_NAME'}/",
            FORWARD_TYPE => '301'
        } );
        $moduleData->{'VHOST_TYPE'} = 'domain_disabled_fwd';
    } else {
        $moduleData->{'VHOST_TYPE'} = 'domain_disabled';
    }

    $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain_disabled.tpl", $moduleData,
        { destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" }
    );

    $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $moduleData->{'SSL_SUPPORT'} ) {
        $self->setData( {
            CERTIFICATE     => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$moduleData->{'DOMAIN_NAME'}.pem",
            DOMAIN_IPS      => join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':443' } @domainIPs ),
            HTTP_URI_SCHEME => 'https://'
        } );
        $moduleData->{'VHOST_TYPE'} = 'domain_disabled_ssl';
        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain_disabled.tpl", $moduleData,
            { destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" }
        );
        $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" )->delFile();
        return $rs if $rs;
    }

    # Ensure that custom httpd conffile exists (cover case where file has been removed for any reasons)
    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
        $moduleData->{'SKIP_TEMPLATE_CLEANER'} = 1;
        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/custom.conf.tpl", $moduleData,
            { destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" }
        );
        return $rs if $rs;
    }

    # Transitional - Remove deprecated `domain_disable_page' directory if any
    if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' && -d $moduleData->{'WEB_DIR'} ) {
        clearImmutable( $moduleData->{'WEB_DIR'} );
        eval { iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/domain_disable_page" )->remove(); };
        if ( $@ ) {
            error( $@ );
            $rs = 1;
        }

        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        return $rs if $rs;
    }

    $self->flushData();
    $self->{'eventManager'}->trigger( 'afterHttpdDisableDmn', $moduleData );
}

=item deleteDmn( \%moduleData )

 Process deleteDmn tasks

 Param hash \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelDmn', $moduleData );
    $rs ||= $self->disableSites( "$moduleData->{'DOMAIN_NAME'}.conf", "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
    return $rs if $rs;

    for ( "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf",
        "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf",
        "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf"
    ) {
        next unless -f $_;
        $rs = iMSCP::File->new( filename => $_ )->delFile();
        return $rs if $rs;
    }

    $rs = $self->umountLogsFolder( $moduleData );
    return $rs if $rs;

    unless ( $moduleData->{'SHARED_MOUNT_POINT'} || !-d $moduleData->{'WEB_DIR'} ) {
        ( my $userWebDir = $main::imscpConfig{'USER_WEB_DIR'} ) =~ s%/+$%%;
        my $parentDir = dirname( $moduleData->{'WEB_DIR'} );

        clearImmutable( $parentDir );
        clearImmutable( $moduleData->{'WEB_DIR'}, 'recursive' );

        eval { iMSCP::Dir->new( dirname => $moduleData->{'WEB_DIR'} )->remove(); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( $parentDir ne $userWebDir ) {
            eval {
                my $dir = iMSCP::Dir->new( dirname => $parentDir );
                if ( $dir->isEmpty() ) {
                    clearImmutable( dirname( $parentDir ));
                    $dir->remove();
                }
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }
        }

        if ( $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes' && $parentDir ne $userWebDir ) {
            do {
                setImmutable( $parentDir ) if -d $parentDir;
            } while ( $parentDir = dirname( $parentDir ) ) ne $userWebDir;
        }
    }

    eval {
        for ( "$moduleData->{'HOME_DIR'}/logs/$moduleData->{'DOMAIN_NAME'}",
            "$self->{'config'}->{'HTTPD_LOG_DIR'}/moduleDatadata->{'DOMAIN_NAME'}"
        ) {
            iMSCP::Dir->new( dirname => $_ )->remove();
        }
    };
    if ( $@ ) {
        return 1;
    }

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelDmn', $moduleData );
    $self->{'restart'} ||= 1;
    $rs;
}

=item addSub( \%moduleData )

 Process addSub tasks

 Param hash \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddSub', $moduleData );
    $self->setData( $moduleData );
    $rs ||= $self->_addCfg( $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddSub', $moduleData );
    $self->{'restart'} ||= 1;
    $rs;
}

=item restoreSub( \%moduleData )

 Process restoreSub tasks

 Param hash \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub restoreSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestoreSub', $moduleData );
    $self->setData( $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdRestoreSub', $moduleData );
}

=item disableSub( \%moduleData )

 Process disableSub tasks

 Param hash \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub disableSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableSub', $moduleData );
    $rs ||= $self->disableDmn( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDisableSub', $moduleData );
}

=item deleteSub( \%moduleData )

 Process deleteSub tasks

 Param hash \%moduleData Subdomain data as provided by Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelSub', $moduleData );
    $rs ||= $self->deleteDmn( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDelSub', $moduleData );
}

=item addHtpasswd( \%moduleData )

 Process addHtpasswd tasks

 Param hash \%moduleData Htpasswd entry data as provided by htpasswd module
 Return int 0 on success, other on failure

=cut

sub addHtpasswd
{
    my ($self, $moduleData) = @_;

    eval {
        clearImmutable( $moduleData->{'WEB_DIR'} );

        my $filePath = "$moduleData->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = -f $filePath ? $file->get() : '';

        $self->{'eventManager'}->trigger( 'beforeHttpdAddHtpasswd', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTUSER_NAME'}:[^\n]*\n//gim;
        $fileContent .= "$moduleData->{'HTUSER_NAME'}:$moduleData->{'HTUSER_PASS'}\n";
        $self->{'eventManager'}->trigger( 'afterHttpdAddHtpasswd', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        local $UMASK = 027;
        $file->set( $fileContent );
        my $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    };
    if ( $@ ) {
        error( $@ );
        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        return 1;
    }

    0;
}

=item deleteHtpasswd( \%moduleData )

 Process deleteHtpasswd tasks

 Param hash \%moduleData Htpasswd entry data as provided by Htpasswd module
 Return int 0 on success, other on failure

=cut

sub deleteHtpasswd
{
    my ($self, $moduleData) = @_;

    eval {
        my $filePath = "$moduleData->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
        return unless -f $filePath;

        clearImmutable( $moduleData->{'WEB_DIR'} );

        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = $file->get() // '';

        $self->{'eventManager'}->trigger( 'beforeHttpdDelHtpasswd', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTUSER_NAME'}:[^\n]*\n//gim;
        $self->{'eventManager'}->trigger( 'afterHttpdDelHtpasswd', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        $file->set( $fileContent );
        my $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    };
    if ( $@ ) {
        error( $@ );
        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        return 1;
    }

    0;
}

=item addHtgroup( \%moduleData )

 Process addHtgroup tasks

 Param hash \%moduleData Htgroup data as provided by Htgroup module
 Return int 0 on success, other on failure

=cut

sub addHtgroup
{
    my ($self, $moduleData) = @_;

    eval {
        clearImmutable( $moduleData->{'WEB_DIR'} );

        my $filePath = "$moduleData->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = -f $filePath ? $file->get() : '';

        $self->{'eventManager'}->trigger( 'beforeHttpdAddHtgroup', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTGROUP_NAME'}:[^\n]*\n//gim;
        $fileContent .= "$moduleData->{'HTGROUP_NAME'}:$moduleData->{'HTGROUP_USERS'}\n";
        $self->{'eventManager'}->trigger( 'afterHttpdAddHtgroup', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        local $UMASK = 027;
        $file->set( $fileContent );
        my $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    };
    if ( $@ ) {
        error( $@ );
        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        return 1;
    }

    0;
}

=item deleteHtgroup( \%moduleData )

 Process deleteHtgroup tasks

 Param hash \%moduleData Htgroup data as provided by Htgroup module
 Return int 0 on success, other on failure

=cut

sub deleteHtgroup
{
    my ($self, $moduleData) = @_;

    eval {
        my $filePath = "$moduleData->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
        return 0 unless -f $filePath;

        clearImmutable( $moduleData->{'WEB_DIR'} );

        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = $file->get() // '';

        $self->{'eventManager'}->trigger( 'beforeHttpdDelHtgroup', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTGROUP_NAME'}:[^\n]*\n//gim;
        $self->{'eventManager'}->trigger( 'afterHttpdDelHtgroup', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        $file->set( $fileContent );
        my $rs = $file->save();
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    };
    if ( $@ ) {
        error( $@ );
        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'WEB_DIR'} ) if $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes';
        return 1;
    }

    0;
}

=item addHtaccess( \%moduleData )

 Process addHtaccess tasks

 Param hash \%moduleData Htaccess data as provided by Htaccess module
 Return int 0 on success, other on failure

=cut

sub addHtaccess
{
    my ($self, $moduleData) = @_;

    return 0 unless -d $moduleData->{'AUTH_PATH'};

    my $isImmutable = isImmutable( $moduleData->{'AUTH_PATH'} );

    eval {
        clearImmutable( $moduleData->{'AUTH_PATH'} ) if $isImmutable;

        my $filePath = "$moduleData->{'AUTH_PATH'}/.htaccess";
        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = -f $filePath ? $file->get() : '';

        $self->{'eventManager'}->trigger( 'beforeHttpdAddHtaccess', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        my $bTag = "### START i-MSCP PROTECTION ###\n";
        my $eTag = "### END i-MSCP PROTECTION ###\n";
        my $tagContent = <<"EOF";
AuthType $moduleData->{'AUTH_TYPE'}
AuthName "$moduleData->{'AUTH_NAME'}"
AuthBasicProvider file
AuthUserFile $moduleData->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}
EOF

        if ( $moduleData->{'HTUSERS'} eq '' ) {
            $tagContent .= <<"EOF";
AuthGroupFile $moduleData->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}
Require group $moduleData->{'HTGROUPS'}
EOF
        } else {
            $tagContent .= <<"EOF";
Require user $moduleData->{'HTUSERS'}
EOF
        }

        $fileContent = replaceBloc( $bTag, $eTag, '', $fileContent );
        $fileContent = $bTag . $tagContent . $eTag . $fileContent;

        $self->{'eventManager'}->trigger( 'afterHttpdAddHtaccess', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        local $UMASK = 027;
        $file->set( $fileContent );
        my $rs = $file->save();
        $rs ||= $file->owner( $moduleData->{'USER'}, $moduleData->{'GROUP'} );
        $rs ||= $file->mode( 0640 );
        $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        setImmutable( $moduleData->{'AUTH_PATH'} ) if $isImmutable;
    };
    if ( $@ ) {
        error( $@ );
        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'AUTH_PATH'} ) if $isImmutable;
        return 1;
    }

    0;
}

=item deleteHtaccess( \%moduleData )

 Process deleteHtaccess tasks

 Param hash \%moduleData Htaccess data as provided by Htaccess module
 Return int 0 on success, other on failure

=cut

sub deleteHtaccess
{
    my ($self, $moduleData) = @_;

    return 0 unless -d $moduleData->{'AUTH_PATH'};

    my $filePath = "$moduleData->{'AUTH_PATH'}/.htaccess";
    return 0 unless -f $filePath;

    my $isImmutable = isImmutable( $moduleData->{'AUTH_PATH'} );

    eval {
        clearImmutable( $moduleData->{'AUTH_PATH'} ) if $isImmutable;

        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = $file->get() // '';
        $fileContent = '' unless defined $fileContent;

        $self->{'eventManager'}->trigger( 'beforeHttpdDelHtaccess', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent = replaceBloc( "### START i-MSCP PROTECTION ###\n", "### END i-MSCP PROTECTION ###\n", '', $fileContent );
        $self->{'eventManager'}->trigger( 'afterHttpdDelHtaccess', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        if ( $fileContent ne '' ) {
            $file->set( $fileContent );
            my $rs = $file->save();
            $rs ||= $file->owner( $moduleData->{'USER'}, $moduleData->{'GROUP'} );
            $rs ||= $file->mode( 0640 );
            $rs == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        } elsif ( -f $filePath ) {
            $file->delFile() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        setImmutable( $moduleData->{'AUTH_PATH'} ) if $isImmutable;
    };
    if ( $@ ) {
        error( $@ );
        # Set immutable bit if needed (even on error)
        setImmutable( $moduleData->{'AUTH_PATH'} ) if $isImmutable;
        return 1;
    }

    0;
}

=item buildConf( $cfgTpl, $filename [, \%moduleData ] )

 Build the given configuration template

 Param string $cfgTpl Template content
 Param string $filename Template filename
 Param hash \%moduleData OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Return string Template content, undef on failure

=cut

sub buildConf
{
    my ($self, $cfgTpl, $filename, $moduleData) = @_;

    $moduleData ||= {};

    if ( grep( $_ eq $filename, ( 'domain.tpl', 'domain_disabled.tpl' ) ) ) {
        if ( grep( $_ eq $moduleData->{'VHOST_TYPE'}, ( 'domain', 'domain_disabled' ) ) ) {
            $cfgTpl = replaceBloc( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', $cfgTpl );
            $cfgTpl = replaceBloc( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', $cfgTpl );
        } elsif ( grep( $_ eq $moduleData->{'VHOST_TYPE'}, ( 'domain_fwd', 'domain_ssl_fwd', 'domain_disabled_fwd' ) ) ) {
            $cfgTpl = replaceBloc( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', $cfgTpl ) if $moduleData->{'VHOST_TYPE'} ne 'domain_ssl_fwd';
            $cfgTpl = replaceBloc( "# SECTION dmn BEGIN.\n", "# SECTION dmn END.\n", '', $cfgTpl );
        } elsif ( grep( $_ eq $moduleData->{'VHOST_TYPE'}, ( 'domain_ssl', 'domain_disabled_ssl' ) ) ) {
            $cfgTpl = replaceBloc( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', $cfgTpl );
        }
    }

    $self->{'eventManager'}->trigger( 'beforeHttpdBuildConf', \$cfgTpl, $filename, $moduleData );
    $cfgTpl = process( $self->{'data'}, $cfgTpl );
    $self->{'eventManager'}->trigger( 'afterHttpdBuildConf', \$cfgTpl, $filename, $moduleData );
    $cfgTpl;
}

=item buildConfFile( $file [, \%moduleData = { } [, \%options = { } ] ] )

 Build the given configuration file

 Param string $file Absolute path to config file or config filename relative to the i-MSCP apache config directory
 Param hash \%moduleData OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Param hash \%options OPTIONAL Options:
  - destination: Destination file path (default to $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/<filebasename>)
  - user: File owner
  - group: File group
  - mode:  File mode
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
    my ($self, $file, $moduleData, $options) = @_;

    $moduleData ||= {};
    $options ||= {};

    my ($filename, $path) = fileparse( $file );

    my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', ref $self, $filename, \ my $cfgTpl, $moduleData, $options );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $file = File::Spec->canonpath( "$self->{'cfgDir'}/$filename" ) if $path eq './';
        $cfgTpl = iMSCP::File->new( filename => $file )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", $file ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConfFile', \$cfgTpl, $filename, $moduleData, $options );
    return $rs if $rs;
    $cfgTpl = $self->buildConf( $cfgTpl, $filename, $moduleData );
    $rs = $self->{'eventManager'}->trigger( 'afterHttpdBuildConfFile', \$cfgTpl, $filename, $moduleData, $options );
    return $rs if $rs;

    my $fileHandler = iMSCP::File->new( filename => $options->{'destination'} || "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$filename" );
    $rs = $fileHandler->set( $cfgTpl );
    $rs ||= $fileHandler->save();
    $rs ||= $fileHandler->owner( $options->{'user'} // $main::imscpConfig{'ROOT_USER'}, $options->{'group'} // $main::imscpConfig{'ROOT_GROUP'} );
    $rs ||= $fileHandler->mode( $options->{'mode'} // 0644 );
}

=item setData( \%data )

 Make the given data available for this server

 Param hash \%data Server data
 Return void

=cut

sub setData
{
    my ($self, $data) = @_;

    $self->{'data'} = { %{$self->{'data'}}, %{$data} };
}

=item flushData( )

 Flush all data set via the setData( ) method

 Return void

=cut

sub flushData
{
    my ($self) = @_;

    $self->{'data'} = {};
}

=item getTraffic( \%trafficDb )

 Get httpd traffic data

 Param hashref \%trafficDb Traffic database
 Die on failure

=cut

sub getTraffic
{
    my $trafficDb = $_[1];

    my $ldate = time2str( '%Y%m%d', time());
    my $dbh = iMSCP::Database->factory()->getRawDb();

    debug( sprintf( 'Collecting HTTP traffic data' ));

    eval {
        local $dbh->{'RaiseError'} = 1;
        $dbh->begin_work();
        my $sth = $dbh->prepare( 'SELECT vhost, bytes FROM httpd_vlogger WHERE ldate <= ? FOR UPDATE' );
        $sth->execute( $ldate );

        while ( my $row = $sth->fetchrow_hashref() ) {
            next unless exists $trafficDb->{$row->{'vhost'}};
            $trafficDb->{$row->{'vhost'}} += $row->{'bytes'};
        }

        $dbh->do( 'DELETE FROM httpd_vlogger WHERE ldate <= ?', undef, $ldate );
        $dbh->commit();
    };
    if ( $@ ) {
        $dbh->rollback();
        %{$trafficDb} = ();
        die( sprintf( "Couldn't collect traffic data: %s", $@ ));
    }

    0;
}

=item getRunningUser( )

 Get user name under which the Apache server is running

 Return string User name under which the apache server is running

=cut

sub getRunningUser
{
    my ($self) = @_;

    $self->{'config'}->{'HTTPD_USER'};
}

=item getRunningGroup( )

 Get group name under which the Apache server is running

 Return string Group name under which the apache server is running

=cut

sub getRunningGroup
{
    my ($self) = @_;

    $self->{'config'}->{'HTTPD_GROUP'};
}

=item enableSites( @sites )

 Enable the given sites

 Param array @sites List of sites to enable
 Return int 0 on sucess, other on failure

=cut

sub enableSites
{
    my ($self, @sites) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableSites', \@sites );
    return $rs if $rs;

    for ( @sites ) {
        unless ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_" ) {
            warning( sprintf( "Site %s doesn't exist", $_ ));
            next;
        }

        $rs = execute( [ '/usr/sbin/a2ensite', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdEnableSites', @sites );
}

=item disableSites( @sites )

 Disable the given sites

 Param array @sites List of sites to disable
 Return int 0 on sucess, other on failure

=cut

sub disableSites
{
    my ($self, @sites) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableSites', \@sites );
    return $rs if $rs;

    for ( @sites ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
        $rs = execute( [ '/usr/sbin/a2dissite', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDisableSites', @sites );
}

=item enableModules( @modules )

 Enable the given Apache modules

 Param array $modules List of modules to enable
 Return int 0 on sucess, other on failure

=cut

sub enableModules
{
    my ($self, @modules) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableModules', \@modules );
    return $rs if $rs;

    for ( @modules ) {
        next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";
        $rs = execute( [ '/usr/sbin/a2enmod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdEnableModules', @modules );
}

=item disableModules( @modules )

 Disable the given Apache modules

 Param array @modules List of modules to disable
 Return int 0 on sucess, other on failure

=cut

sub disableModules
{
    my ($self, @modules) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableModules', \@modules );
    return $rs if $rs;

    for ( @modules ) {
        next unless -l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/$_.load";
        $rs = execute( [ '/usr/sbin/a2dismod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDisableModules', @modules );
}

=item enableConfs( @conffiles )

 Enable the given configuration files

 Param array @conffiles List of configuration files to enable
 Return int 0 on sucess, other on failure

=cut

sub enableConfs
{
    my ($self, @conffiles) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableConfs', \@conffiles );
    return $rs if $rs;

    for ( @conffiles ) {
        unless ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$_" ) {
            warning( sprintf( "Configuration file %s doesn't exist", $_ ));
            next;
        }

        $rs = execute( [ '/usr/sbin/a2enconf', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdEnableConfs', @conffiles );
}

=item disableConfs( @conffiles )

 Disable the given configuration files

 Param array @conffiles Lilst of configuration files to disable
 Return int 0 on sucess, other on failure

=cut

sub disableConfs
{
    my ($self, @conffiles) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableConfs', \@conffiles );
    return $rs if $rs;

    for ( @conffiles ) {
        next unless -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$_";
        $rs = execute( [ '/usr/sbin/a2disconf', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1;
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDisableConfs', @conffiles );
}

=item start( )

 Start httpd service

 Return int 0 on success, other on failure

=cut

sub start
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdStart' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->start( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdStart' );
}

=item stop( )

 Stop httpd service

 Return int 0 on success, other on failure

=cut

sub stop
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdStop' );
    return $rs if $rs;

    eval { iMSCP::Service->getInstance()->stop( 'apache2' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdStop' );
}

=item forceRestart( )

 Force httpd service to be restarted

 Return void

=cut

sub forceRestart
{
    my ($self) = @_;

    $self->{'forceRestart'} ||= 1;
}

=item restart( )

 Restart or reload httpd service

 Return int 0 on success, other on failure

=cut

sub restart
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestart' );
    return $rs if $rs;

    eval {
        if ( $self->{'forceRestart'} ) {
            iMSCP::Service->getInstance()->restart( 'apache2' );
            return;
        }

        iMSCP::Service->getInstance()->reload( 'apache2' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdRestart' );
}

=item mountLogsFolder( \%moduleData )

 Mount logs folder which belong to the given domain into customer's logs folder

 Param hash \%moduleData Domain data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub mountLogsFolder
{
    my ($self, $moduleData) = @_;

    my $fsSpec = File::Spec->canonpath( "$self->{'config'}->{'HTTPD_LOG_DIR'}/$moduleData->{'DOMAIN_NAME'}" );
    my $fsFile = File::Spec->canonpath( "$moduleData->{'HOME_DIR'}/logs/$moduleData->{'DOMAIN_NAME'}" );
    my $fields = { fs_spec => $fsSpec, fs_file => $fsFile, fs_vfstype => 'none', fs_mntops => 'bind' };
    my $rs = $self->{'eventManager'}->trigger( 'beforeMountLogsFolder', $moduleData, $fields );
    return $rs if $rs;

    eval { iMSCP::Dir->new( dirname => $fsFile )->make(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = addMountEntry( "$fields->{'fs_spec'} $fields->{'fs_file'} $fields->{'fs_vfstype'} $fields->{'fs_mntops'}" );
    $rs ||= mount( $fields ) unless isMountpoint( $fields->{'fs_file'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMountLogsFolder', $moduleData, $fields );
}

=item umountLogsFolder( \%moduleData )

 Umount logs folder which belong to the given domain from customer's logs folder

 Param hash \%moduleData Domain data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub umountLogsFolder
{
    my ($self, $moduleData) = @_;

    my $recursive = 1;
    my $fsFile = "$moduleData->{'HOME_DIR'}/logs";

    # We operate recursively only if domain type is 'dmn' (full account)
    if ( $moduleData->{'DOMAIN_TYPE'} ne 'dmn' ) {
        $recursive = 0;
        $fsFile .= "/$moduleData->{'DOMAIN_NAME'}";
    }

    $fsFile = File::Spec->canonpath( $fsFile );
    my $rs = $self->{'eventManager'}->trigger( 'beforeUnmountLogsFolder', $moduleData, $fsFile );
    $rs ||= removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
    $rs ||= umount( $fsFile, $recursive );
    $rs ||= $self->{'eventManager'}->trigger( 'afterUmountMountLogsFolder', $moduleData, $fsFile );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Servers::httpd::Apache2::Abstract

=cut

sub _init
{
    my ($self) = @_;

    $self->{'data'} = {};
    @{$self}{qw/ start restart /} = ( 0, 0 );
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
    $self->{'apacheTplDir'} = "$self->{'cfgDir'}/parts";
    $self->_mergeConfig() if defined $main::execmode && $main::execmode eq 'setup' && -f "$self->{'cfgDir'}/apache.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'cfgDir'}/apache.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => defined $main::execmode && $main::execmode eq 'setup';
    $self;
}

=item _mergeConfig()

 Merge distribution configuration with production configuration

 Die on failure

=cut

sub _mergeConfig
{
    my ($self) = @_;

    if ( -f "$self->{'cfgDir'}/apache.data" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/apache.data.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$self->{'cfgDir'}/apache.data", readonly => 1;

        debug( 'Merging old configuration with new configuration ...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$self->{'cfgDir'}/apache.data.dist" )->moveFile( "$self->{'cfgDir'}/apache.data" ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
}

=item _addCfg( \%data )

 Add configuration files for the given domain

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addCfg
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddCfg', $moduleData );
    $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}.conf", "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
    return $rs if $rs;

    $self->setData( $moduleData );

    my $net = iMSCP::Net->getInstance();
    my @domainIPs = ( $moduleData->{'DOMAIN_IP'}, ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' ? $moduleData->{'BASE_SERVER_IP'} : () ) );

    $rs = $self->{'eventManager'}->trigger( 'onAddHttpdVhostIps', $moduleData, \@domainIPs );
    return $rs if $rs;

    # If INADDR_ANY is found, map it to the wildcard sign and discard any other
    # IP, else, remove any duplicate IP address from the list
    @domainIPs = grep($_ eq '0.0.0.0', @domainIPs) ? ( '*' ) : unique( map { $net->normalizeAddr( $_ ) } @domainIPs );

    $self->setData( {
        DOMAIN_IPS             => join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':80' } @domainIPs ),
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        SERVER_ALIASES         => "www.$moduleData->{'DOMAIN_NAME'}" . ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes'
            ? " $moduleData->{'ALIAS'}.$main::imscpConfig{'BASE_SERVER_VHOST'}" : ''
        )
    } );

    # Create http vhost

    if ( $moduleData->{'HSTS_SUPPORT'} ) {
        $self->setData( {
            FORWARD      => "https://$moduleData->{'DOMAIN_NAME'}/",
            FORWARD_TYPE => '301'
        } );
        $moduleData->{'VHOST_TYPE'} = 'domain_fwd';
    } elsif ( $moduleData->{'FORWARD'} ne 'no' ) {
        $moduleData->{'VHOST_TYPE'} = 'domain_fwd';

        if ( $moduleData->{'FORWARD_TYPE'} eq 'proxy' ) {
            $self->setData( {
                X_FORWARDED_PROTOCOL => 'http',
                X_FORWARDED_PORT     => 80
            } );
        }
    } else {
        $moduleData->{'VHOST_TYPE'} = 'domain';
    }

    $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain.tpl", $moduleData,
        { destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" }
    );
    $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $moduleData->{'SSL_SUPPORT'} ) {
        $self->setData( {
            CERTIFICATE => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$moduleData->{'DOMAIN_NAME'}.pem",
            DOMAIN_IPS  => join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':443' } @domainIPs ),
        } );

        if ( $moduleData->{'FORWARD'} ne 'no' ) {
            $self->setData( {
                FORWARD      => $moduleData->{'FORWARD'},
                FORWARD_TYPE => $moduleData->{'FORWARD_TYPE'}
            } );
            $moduleData->{'VHOST_TYPE'} = 'domain_ssl_fwd';

            if ( $moduleData->{'FORWARD_TYPE'} eq 'proxy' ) {
                $self->setData( {
                    X_FORWARDED_PROTOCOL => 'https',
                    X_FORWARDED_PORT     => 443
                } );
            }
        } else {
            $moduleData->{'VHOST_TYPE'} = 'domain_ssl';
        }

        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/domain.tpl", $moduleData,
            { destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" }
        );
        $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" )->delFile();
        return $rs if $rs;
    }

    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
        $moduleData->{'SKIP_TEMPLATE_CLEANER'} = 1;
        $rs = $self->buildConfFile( "$self->{'apacheTplDir'}/custom.conf.tpl", $moduleData,
            { destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" }
        );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddCfg', $moduleData );
}

=item _dmnFolders( \%moduleData )

 Get Web folders list to create for the given domain

 Param hash \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return array List of Web folders to create

=cut

sub _dmnFolders
{
    my ($self, $moduleData) = @_;

    $self->{'eventManager'}->trigger( 'beforeHttpdDmnFolders', \my @folders );

    push(
        @folders,
        [
            "$self->{'config'}->{'HTTPD_LOG_DIR'}/$moduleData->{'DOMAIN_NAME'}",
            $main::imscpConfig{'ROOT_USER'},
            $main::imscpConfig{'ADM_GROUP'},
            0755
        ]
    );

    $self->{'eventManager'}->trigger( 'afterHttpdDmnFolders', \@folders );
    @folders;
}

=item _addFiles( \%moduleData )

 Add default directories and files for the given domain

 Param hash \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _addFiles
{
    my ($self, $moduleData) = @_;

    eval {
        $self->{'eventManager'}->trigger( 'beforeHttpdAddFiles', $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        for ( $self->_dmnFolders( $moduleData ) ) {
            iMSCP::Dir->new( dirname => $_->[0] )->make( {
                user  => $_->[1],
                group => $_->[2],
                mode  => $_->[3]
            } );
        }

        # Whether or not permissions must be fixed recursively
        my $fixPermissions = iMSCP::Getopt->fixPermissions || $moduleData->{'ACTION'} =~ /^restore(?:Dmn|Sub)$/;

        # Prepare Web folder
        my $skelDir;
        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            $skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/domain";
        } elsif ( $moduleData->{'DOMAIN_TYPE'} eq 'als' ) {
            $skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/alias";
        } else {
            $skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/subdomain";
        }

        # Copy skeleton in tmp dir
        my $tmpDir = File::Temp->newdir();
        iMSCP::Dir->new( dirname => $skelDir )->rcopy( $tmpDir, { preserve => 'no' } );

        # Build default page if needed (if htdocs doesn't exist or is empty)
        if ( !-d "$moduleData->{'WEB_DIR'}/htdocs" || iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/htdocs" )->isEmpty() ) {
            -d "$tmpDir/htdocs" or die( "Web folder skeleton must provides the `htdocs' directory." );

            # Test needed in case admin removed the index.html file from the skeleton
            if ( -f "$tmpDir/htdocs/index.html" ) {
                $moduleData->{'SKIP_TEMPLATE_CLEANER'} = 1;
                my $fileSource = "$tmpDir/htdocs/index.html";
                $self->buildConfFile( $fileSource, $moduleData, { destination => $fileSource } ) == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );
            }

            # Force recursive permissions for newly created Web folders
            $fixPermissions = 1;
        } else {
            iMSCP::Dir->new( dirname => "$tmpDir/htdocs" )->remove();
        }

        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            -d "$moduleData->{'WEB_DIR'}/errors" or die( "The `domain' Web folder skeleton must provides the `errors' directory." );

            if ( !iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/errors" )->isEmpty() ) {
                iMSCP::Dir->new( dirname => "$tmpDir/errors" )->remove();
            } else {
                $fixPermissions = 1;
            }

            if ( $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} ne 'yes' ) {
                $self->umountLogsFolder( $moduleData ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
                iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/logs" )->remove();
                iMSCP::Dir->new( dirname => "$tmpDir/logs" )->remove();
            } elsif ( !-d "$tmpDir/logs" ) {
                die( "Web folder skeleton must provides the `logs' directory." );
            }
        }

        my $parentDir = dirname( $moduleData->{'WEB_DIR'} );

        # Fix #IP-1327 - Ensure that parent Web folder exists
        unless ( -d $parentDir ) {
            clearImmutable( dirname( $parentDir ));
            iMSCP::Dir->new( dirname => $parentDir )->make( {
                user  => $moduleData->{'USER'},
                group => $moduleData->{'GROUP'},
                mode  => 0750
            } );
        } else {
            clearImmutable( $parentDir );
        }

        clearImmutable( $moduleData->{'WEB_DIR'} ) if -d $moduleData->{'WEB_DIR'};

        # Copy Web folder
        iMSCP::Dir->new( dirname => $tmpDir )->rcopy( $moduleData->{'WEB_DIR'}, { preserve => 'no' } );

        # Cleanup (Transitional)
        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            # Remove deprecated `domain_disable_page' directory if any
            iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/domain_disable_page" )->remove();
        }
        #    elsif ( !$moduleData->{'SHARED_MOUNT_POINT'} ) {
        #        # Remove deprecated phptmp directory if any
        #        iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/phptmp" )->remove();
        #        iMSCP::Dir->new( dirname => "$tmpDir/phptmp" )->remove();
        #    }

        # Set ownership and permissions

        # Set ownership and permissions for Web folder root
        # Web folder root vuxxx:vuxxx 0750 (no recursive)
        setRights( $moduleData->{'WEB_DIR'},
            {
                user  => $moduleData->{'USER'},
                group => $moduleData->{'GROUP'},
                mode  => '0750'
            }
        ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        # Get list of files inside Web folder root
        my @files = iMSCP::Dir->new( dirname => $skelDir )->getAll();

        # Set ownership for first Web folder depth, e.g:
        # 00_private vuxxx:vuxxx (recursive with --fix-permissions) -- main domain Web folder only
        # backups    vuxxx:vuxxx (recursive with --fix-permissions) -- main domain Web folder only
        # cgi-bin    vuxxx:vuxxx (recursive with --fix-permissions) -- main domain Web folder only
        # error      vuxxx:vuxxx (recursive with --fix-permissions) -- main domain Web folder only
        # htdocs     vuxxx:vuxxx (recursive with --fix-permissions)
        # logs       skipped -- main domain Web folder only
        # phptmp     vuxxx:vuxxx (recursive with --fix-permissions) -- main domain Web folder only
        for my $file( grep( $_ ne 'logs', @files ) ) {
            next unless -e "$moduleData->{'WEB_DIR'}/$file";

            setRights( "$moduleData->{'WEB_DIR'}/$file",
                {
                    user      => $moduleData->{'USER'},
                    group     => $moduleData->{'GROUP'},
                    recursive => $fixPermissions
                }
            ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            # Set ownership and permissions for .htgroup and .htpasswd files if any
            # .htgroup  root:www-data
            # .htpasswd root:www-data
            for my $file( qw/ .htgroup .htpasswd / ) {
                next unless -f "$moduleData->{'WEB_DIR'}/$file";
                setRights( "$moduleData->{'WEB_DIR'}/$file",
                    {
                        user  => $main::imscpConfig{'ROOT_USER'},
                        group => $self->{'config'}->{'HTTPD_GROUP'},
                        mode  => '0640'
                    }
                ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }

            # Set ownership for logs directory if any
            # logs root:vuxxx (no recursive)
            if ( -d "$moduleData->{'WEB_DIR'}/logs" ) {
                setRights( "$moduleData->{'WEB_DIR'}/logs",
                    {
                        user  => $main::imscpConfig{'ROOT_USER'},
                        group => $moduleData->{'GROUP'}
                    }
                ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }
        }

        # Set permissions for first Web folder depth, e.g:
        # 00_private 0750 (no recursive) -- main domain Web folder only
        # backups    0750 (recursive with --fix-permissions) -- main domain Web folder only
        # cgi-bin    0750 (no recursive) -- main domain Web folder only
        # error      0750 (recursive with --fix-permissions) -- main domain Web folder only
        # htdocs     0750 (no recursive)
        # logs       0750 (no recursive) -- main domain Web folder only
        # phptmp     0750 (recursive with --fix-permissions) -- main domain Web folder only
        for my $file ( @files ) {
            next unless -e "$moduleData->{'WEB_DIR'}/$file";
            setRights( "$moduleData->{'WEB_DIR'}/$file",
                {
                    dirmode   => '0750',
                    filemode  => '0640',
                    recursive => $file =~ /^(?:00_private|cgi-bin|logs|htdocs)$/ ? 0 : $fixPermissions
                }
            ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        if ( $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} eq 'yes' ) {
            $self->mountLogsFolder( $moduleData ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        $self->{'eventManager'}->trigger( 'afterHttpdAddFiles', $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        # Set immutable bit if needed
        if ( $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes' ) {
            my $dir = $moduleData->{'WEB_DIR'};
            my $userWebDir = File::Spec->canonpath( $main::imscpConfig{'USER_WEB_DIR'} );
            do {
                setImmutable( $dir );
            } while ( $dir = dirname( $dir ) ) ne $userWebDir;
        }
    };
    if ( $@ ) {
        error( $@ );

        # Set immutable bit if needed (even on error)
        if ( $moduleData->{'WEB_FOLDER_PROTECTION'} eq 'yes' ) {
            my $dir = $moduleData->{'WEB_DIR'};
            my $userWebDir = File::Spec->canonpath( $main::imscpConfig{'USER_WEB_DIR'} );
            do {
                setImmutable( $dir );
            } while ( $dir = dirname( $dir ) ) ne $userWebDir;
        }

        return 1;
    }

    0;
}

#
## Installation routines
#

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

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdMakeDirs' );
    return $rs if $rs;

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

    $self->{'eventManager'}->trigger( 'afterHttpdMakeDirs' );
}

=item _copyDomainDisablePages( )

 Copy pages for disabled domains

 Return int 0 on success, other on failure

=cut

sub _copyDomainDisablePages
{
    eval {
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'CONF_DIR'}/skel/domain_disabled_pages" )->rcopy(
            "$main::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages", { preserve => 'no' }
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
    0;
}

=item _setupModules( )

 Setup Apache2 modules

 Return int 0 on success, other on failure

=cut

sub _setupModules
{
    my ($self) = @_;

    die( sprintf( 'The %s package must implement the _setupModules() method.', ref $self ));
}

=item _configure( )

 Configure Apache2

 Return int 0 on success, other on failure

=cut

sub _configure
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeConfigureApache2', ref $self );
    return $rs if $rs;

    if ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" ) {
        $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apache_fcgid', 'ports.conf', \my $cfgTpl, {} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $cfgTpl = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" )->get();
            unless ( defined $cfgTpl ) {
                error( sprintf( "Couldn't read %s file", "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" ));
                return 1;
            }
        }

        $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConfFile', \$cfgTpl, 'ports.conf' );
        return $rs if $rs;
        $cfgTpl =~ s/^NameVirtualHost[^\n]+\n//gim;
        $rs = $self->{'eventManager'}->trigger( 'afterHttpdBuildConfFile', \$cfgTpl, 'ports.conf' );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_CONF_DIR'}/ports.conf" );
        $file->set( $cfgTpl );
        $rs = $file->save();
        $rs ||= $file->mode( 0644 );
        return $rs if $rs;
    }

    # Turn off default access log provided by Debian package
    $rs = $self->disableConfs( 'other-vhosts-access-log.conf' );
    return $rs if $rs;

    # Remove default access log file provided by Debian package
    if ( -f "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" ) {
        $rs = iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_LOG_DIR'}/other_vhosts_access.log" )->delFile();
        return $rs if $rs;
    }

    $self->setData( {
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        HTTPD_ROOT_DIR         => $self->{'config'}->{'HTTPD_ROOT_DIR'},
        VLOGGER_CONF           => "$self->{'cfgDir'}/vlogger.conf"
    } );

    $rs = $self->buildConfFile( '00_nameserver.conf' );
    $rs ||= $self->enableSites( '00_nameserver.conf' );
    $rs ||= $self->buildConfFile( '00_imscp.conf', {}, { destination => "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/00_imscp.conf" } );
    $rs ||= $self->enableConfs( '00_imscp.conf' );
    $rs ||= $self->disableSites( 'default', 'default-ssl', '000-default.conf', 'default-ssl.conf' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterConfigureApache2', ref $self );
}

=item _installLogrotate( )

 Install Apache logrotate file

 Return int 0 on success, other on failure

=cut

sub _installLogrotate
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdInstallLogrotate' );
    return $rs if $rs;

    $self->setData( {
        ROOT_USER     => $main::imscpConfig{'ROOT_USER'},
        ADM_GROUP     => $main::imscpConfig{'ADM_GROUP'},
        HTTPD_LOG_DIR => $self->{'config'}->{'HTTPD_LOG_DIR'}
    } );

    $rs = $self->buildConfFile( 'logrotate.conf', {}, { destination => "$main::imscpConfig{'LOGROTATE_CONF_DIR'}/apache2" } );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdInstallLogrotate' );
}

=item _setupVlogger( )

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
    my ($self) = @_;

    my $host = main::setupGetQuestion( 'DATABASE_HOST' );
    $host = $host eq 'localhost' ? '127.0.0.1' : $host;
    my $port = main::setupGetQuestion( 'DATABASE_PORT' );
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $user = 'vlogger_user';
    my $userHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    $userHost = '127.0.0.1' if $userHost eq 'localhost';
    my $oldUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $pass = randomStr( 16, ALNUM );

    my $db = iMSCP::Database->factory();
    my $rs = main::setupImportSqlSchema( $db, "$self->{'cfgDir'}/vlogger.sql" );
    return $rs if $rs;

    eval {
        my $sqlServer = Servers::sqld->factory();

        for ( $userHost, $oldUserHost, 'localhost' ) {
            next unless $_;
            $sqlServer->dropUser( $user, $_ );
        }

        $sqlServer->createUser( $user, $userHost, $pass );

        my $dbh = iMSCP::Database->factory()->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        my $qDbName = $dbh->quote_identifier( $dbName );
        $dbh->do( "GRANT SELECT, INSERT, UPDATE ON $qDbName.httpd_vlogger TO ?\@?", undef, $user, $userHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->setData( {
        DATABASE_NAME     => $dbName,
        DATABASE_HOST     => $host,
        DATABASE_PORT     => $port,
        DATABASE_USER     => $user,
        DATABASE_PASSWORD => $pass
    } );
    $self->buildConfFile( "$self->{'cfgDir'}/vlogger.conf.tpl", { SKIP_TEMPLATE_CLEANER => 1 }, { destination => "$self->{'cfgDir'}/vlogger.conf" } );
}

=item _cleanup( )

 Process cleanup tasks

 Return int 0 on success, other on failure

=cut

sub _cleanup
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdCleanup' );
    $rs ||= $self->disableSites( 'imscp.conf', '00_modcband.conf', '00_master.conf', '00_master_ssl.conf' );
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
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdCleanup' );
}

#
## Uninstallation routines
#

=item _removeVloggerSqlUser( )

 Remove vlogger SQL user

 Return int 0

=cut

sub _removeVloggerSqlUser
{
    if ( $main::imscpConfig{'DATABASE_USER_HOST'} eq 'localhost' ) {
        return Servers::sqld->factory()->dropUser( 'vlogger_user', '127.0.0.1' );
    }

    Servers::sqld->factory()->dropUser( 'vlogger_user', $main::imscpConfig{'DATABASE_USER_HOST'} );
}

=item _removeDirs( )

 Remove non-default Apache2 directories

 Return int 0 on success, other on failure

=cut

sub _removeDirs
{
    my ($self) = @_;

    eval { iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} )->remove(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
    0;
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

    eval { iMSCP::Dir->new( dirname => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'} )->remove(); };
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
