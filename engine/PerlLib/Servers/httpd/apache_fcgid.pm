=head1 NAME

 Servers::httpd::apache_fcgid - i-MSCP Apache2/FastCGI Server implementation

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

package Servers::httpd::apache_fcgid;

use strict;
use warnings;
use autouse 'Date::Format' => qw/ time2str /;
use Class::Autouse qw/ :nostat Servers::httpd::apache_fcgid::installer Servers::httpd::apache_fcgid::uninstaller /;
use File::Basename;
use File::Spec;
use File::Temp;
use iMSCP::Config;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::Ext2Attributes qw/ setImmutable clearImmutable isImmutable /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Mount qw/ mount umount isMountpoint addMountEntry removeMountEntry /;
use iMSCP::Net;
use iMSCP::ProgramFinder;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use iMSCP::Service;
use iMSCP::Umask;
use List::MoreUtils qw/ uniq /;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP Apache2/FastCGI Server implementation.

=head1 PUBLIC METHODS

=over 4

=item registerSetupListeners( \%eventManager )

 Register setup event listeners

 Param iMSCP::EventManager \%eventManager
 Return int 0 on success, other on failure

=cut

sub registerSetupListeners
{
    my (undef, $eventManager) = @_;

    Servers::httpd::apache_fcgid::installer->getInstance()->registerSetupListeners( $eventManager );
}

=item preinstall( )

 Process preinstall tasks

 Return int 0 on success, other on failure

=cut

sub preinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdPreinstall', 'apache_fcgid' );
    $rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdPreinstall', 'apache_fcgid' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdInstall', 'apache_fcgid' );
    $rs ||= Servers::httpd::apache_fcgid::installer->getInstance()->install();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdInstall', 'apache_fcgid' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdPostInstall', 'apache_fcgid' );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->enable( $self->{'config'}->{'HTTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $rs = $self->{'eventManager'}->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]}, [ sub { $self->start(); }, 'Httpd (Apache2/Fcgid)' ];
            0;
        },
        3
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdPostInstall', 'apache_fcgid' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdUninstall', 'apache_fcgid' );
    $rs ||= Servers::httpd::apache_fcgid::uninstaller->getInstance()->uninstall();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdUninstall', 'apache_fcgid' );

    unless ( $rs || !iMSCP::Service->getInstance()->hasService( $self->{'config'}->{'HTTPD_SNAME'} ) ) {
        $self->{'restart'} = 1;
    } else {
        $self->{'start'} = 0;
        $self->{'restart'} = 0;
    }

    $rs;
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdSetEnginePermissions' );
    $rs ||= setRights(
        $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'},
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0555'
        }
    );
    $rs ||= setRights(
        '/usr/local/sbin/vlogger',
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0750'
        }
    );
    # Fix permissions on root log dir (e.g: /var/log/apache2) in any cases
    # Fix permissions on root log dir (e.g: /var/log/apache2) content only with --fix-permissions option
    $rs ||= setRights(
        $self->{'config'}->{'HTTPD_LOG_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ROOT_GROUP'},
            dirmode   => '0755',
            filemode  => '0644',
            recursive => 1
        }
    );
    $rs ||= setRights(
        $self->{'config'}->{'HTTPD_LOG_DIR'},
        {
            group => $main::imscpConfig{'ADM_GROUP'},
            mode  => '0750'
        }
    );
    $rs ||= setRights(
        "$main::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages",
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $self->{'config'}->{'HTTPD_GROUP'},
            dirmode   => '0550',
            filemode  => '0440',
            recursive => 1
        }
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdSetEnginePermissions' );
}

=item addUser( \%data )

 Process addUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub addUser
{
    my ($self, $data) = @_;

    return 0 if $data->{'STATUS'} eq 'tochangepwd';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddUser', $data );
    $self->setData( $data );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->addToGroup( $data->{'GROUP'} );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddUser', $data );
    $self->{'restart'} = 1 unless $rs;
    $rs;
}

=item deleteUser( \%data )

 Process deleteUser tasks

 Param hash \%data User data
 Return int 0 on success, other on failure

=cut

sub deleteUser
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelUser', $data );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->removeFromGroup(
        $data->{'GROUP'}
    );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDelUser', $data );
    $self->{'restart'} = 1 unless $rs;
    $rs;
}

=item addDmn(\%data)

 Process addDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub addDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddDmn', $data );
    $self->setData( $data );
    $rs ||= $self->_addCfg( $data );
    $rs ||= $self->_addFiles( $data );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddDmn', $data );
    $self->{'restart'} = 1 unless $rs;
    $rs;
}

=item restoreDmn( )

 Process restoreDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub restoreDmn
{
    my ($self, $data) = @_;

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
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableDmn', $data );
    return $rs if $rs;

    # Ensure that all needed directories are present
    for ( $self->_dmnFolders( $data ) ) {
        iMSCP::Dir->new( dirname => $_->[0] )->make(
            {
                user  => $_->[1],
                group => $_->[2],
                mode  => $_->[3]
            }
        );
    }

    $self->setData( $data );

    my $net = iMSCP::Net->getInstance();
    my @domainIPs = (
        $data->{'DOMAIN_IP'},
        ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes' ? $data->{'BASE_SERVER_IP'} : () )
    );

    $rs = $self->{'eventManager'}->trigger( 'onAddHttpdVhostIps', $data, \@domainIPs );
    return $rs if $rs;

    # Remove duplicate IP if any and map the INADDR_ANY IP to *
    @domainIPs = uniq( map { $net->normalizeAddr( $_ ) =~ s/^\Q0.0.0.0\E$/*/r } @domainIPs );

    $self->setData(
        {
            DOMAIN_IPS      => join(
                ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':80' } @domainIPs
            ),
            HTTP_URI_SCHEME => 'http://',
            HTTPD_LOG_DIR   => $self->{'config'}->{'HTTPD_LOG_DIR'},
            USER_WEB_DIR    => $main::imscpConfig{'USER_WEB_DIR'},
            SERVER_ALIASES  => "www.$data->{'DOMAIN_NAME'}" . ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes'
                ? " $data->{'ALIAS'}.$main::imscpConfig{'BASE_SERVER_VHOST'}" : ''
            )
        }
    );

    # Create http vhost

    if ( $data->{'HSTS_SUPPORT'} ) {
        $self->setData(
            {
                FORWARD      => "https://$data->{'DOMAIN_NAME'}/",
                FORWARD_TYPE => '301'
            }
        );
        $data->{'VHOST_TYPE'} = 'domain_disabled_fwd';
    } else {
        $data->{'VHOST_TYPE'} = 'domain_disabled';
    }

    $rs = $self->buildConfFile(
        "$self->{'apacheTplDir'}/domain_disabled.tpl",
        $data,
        {
            destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf"
        }
    );
    $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $data->{'SSL_SUPPORT'} ) {
        $self->setData(
            {
                CERTIFICATE     => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem",
                DOMAIN_IPS      => join(
                    ' ',
                    map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':443' } @domainIPs
                ),
                HTTP_URI_SCHEME => 'https://'
            }
        );
        $data->{'VHOST_TYPE'} = 'domain_disabled_ssl';
        $rs = $self->buildConfFile(
            "$self->{'apacheTplDir'}/domain_disabled.tpl",
            $data,
            {
                destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf"
            }
        );
        $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf"
        )->delFile();
        return $rs if $rs;
    }

    # Ensure that custom httpd conffile exists (cover case where file has been removed for any reasons)
    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf" ) {
        $data->{'SKIP_TEMPLATE_CLEANER'} = 1;
        $rs = $self->buildConfFile(
            "$self->{'apacheTplDir'}/custom.conf.tpl",
            $data,
            {
                destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf"
            }
        );
        return $rs if $rs;
    }

    # Transitional - Remove deprecated `domain_disable_page' directory if any
    if ( $data->{'DOMAIN_TYPE'} eq 'dmn' && -d $data->{'WEB_DIR'} ) {
        clearImmutable( $data->{'WEB_DIR'} );
        iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/domain_disable_page" )->remove();
        setImmutable( $data->{'WEB_DIR'} ) if $data->{'WEB_FOLDER_PROTECTION'} eq 'yes';
    }

    $self->flushData();
    $self->{'eventManager'}->trigger( 'afterHttpdDisableDmn', $data );
}

=item deleteDmn( \%data )

 Process deleteDmn tasks

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub deleteDmn
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelDmn', $data );
    $rs ||= $self->disableSites( "$data->{'DOMAIN_NAME'}.conf", "$data->{'DOMAIN_NAME'}_ssl.conf" );
    return $rs if $rs;

    for ( "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf",
        "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf",
        "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf"
    ) {
        next unless -f $_;
        $rs = iMSCP::File->new( filename => $_ )->delFile();
        return $rs if $rs;
    }

    $rs = $self->umountLogsFolder( $data );
    return $rs if $rs;

    unless ( $data->{'SHARED_MOUNT_POINT'} || !-d $data->{'WEB_DIR'} ) {
        ( my $userWebDir = $main::imscpConfig{'USER_WEB_DIR'} ) =~ s%/+$%%;
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
            do {
                setImmutable( $parentDir ) if -d $parentDir;
            } while ( $parentDir = dirname( $parentDir ) ) ne $userWebDir;
        }
    }

    iMSCP::Dir->new( dirname => "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}" )->remove();
    iMSCP::Dir->new( dirname => "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}" )->remove();
    iMSCP::Dir->new(
        dirname => "$self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'}/$data->{'DOMAIN_NAME'}"
    )->remove();

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelDmn', $data );
    $self->{'restart'} = 1 unless $rs;
    $rs;
}

=item addSub( \%data )

 Process addSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub addSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddSub', $data );
    $self->setData( $data );
    $rs ||= $self->_addCfg( $data );
    $rs ||= $self->_addFiles( $data );
    $rs ||= $self->flushData();
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddSub', $data );
    $self->{'restart'} = 1 unless $rs;
    $rs;
}

=item restoreSub( \%data )

 Process restoreSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub restoreSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdRestoreSub', $data );
    $self->setData( $data );
    $rs ||= $rs = $self->_addFiles( $data );
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
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableSub', $data );
    $rs ||= $self->disableDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDisableSub', $data );
}

=item deleteSub(\%data)

 Process deleteSub tasks

 Param hash \%data Subdomain data
 Return int 0 on success, other on failure

=cut

sub deleteSub
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelSub', $data );
    $rs ||= $rs = $self->deleteDmn( $data );
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdDelSub', $data );
}

=item addHtpasswd( \%data )

 Process AddHtpasswd tasks

 Param hash \%data Htpasswd entry data
 Return int 0 on success, other on failure

=cut

sub addHtpasswd
{
    my ($self, $data) = @_;

    my $fileName = $self->{'config'}->{'HTACCESS_USERS_FILENAME'};
    my $filePath = "$data->{'WEB_DIR'}/$fileName";

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = -f $filePath ? $file->get() : '';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddHtpasswd', \$fileContent, $data );
    return $rs if $rs;

    $fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;
    $fileContent .= "$data->{'HTUSER_NAME'}:$data->{'HTUSER_PASS'}\n";

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdAddHtpasswd', \$fileContent, $data );
    return $rs if $rs;

    $file->set( $fileContent );

    local $UMASK = 027;

    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
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
    my ($self, $data) = @_;

    my $fileName = $self->{'config'}->{'HTACCESS_USERS_FILENAME'};
    my $filePath = "$data->{'WEB_DIR'}/$fileName";

    return 0 unless -f $filePath;

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = $file->get() // '';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelHtpasswd', \$fileContent, $data );
    return $rs if $rs;

    $fileContent =~ s/^$data->{'HTUSER_NAME'}:[^\n]*\n//gim;

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelHtpasswd', \$fileContent, $data );
    return $rs if $rs;

    $file->set( $fileContent );

    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
    $rs ||= $file->mode( 0640 );
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
    my ($self, $data) = @_;

    my $fileName = $self->{'config'}->{'HTACCESS_GROUPS_FILENAME'};
    my $filePath = "$data->{'WEB_DIR'}/$fileName";

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = -f $filePath ? $file->get() : '';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddHtgroup', \$fileContent, $data );
    return $rs if $rs;

    $fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;
    $fileContent .= "$data->{'HTGROUP_NAME'}:$data->{'HTGROUP_USERS'}\n";

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdAddHtgroup', \$fileContent, $data );
    return $rs if $rs;

    $file->set( $fileContent );

    local $UMASK = 027;

    $rs = $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
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
    my ($self, $data) = @_;;

    my $fileName = $self->{'config'}->{'HTACCESS_GROUPS_FILENAME'};
    my $filePath = "$data->{'WEB_DIR'}/$fileName";

    return 0 unless -f $filePath;

    clearImmutable( $data->{'WEB_DIR'} );

    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = $file->get() // '';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelHtgroup', \$fileContent, $data );
    return $rs if $rs;

    $fileContent =~ s/^$data->{'HTGROUP_NAME'}:[^\n]*\n//gim;

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelHtgroup', \$fileContent, $data );
    return $rs if $rs;

    $file->set( $fileContent );

    $rs ||= $file->save();
    $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'config'}->{'HTTPD_GROUP'} );
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
    my ($self, $data) = @_;

    # Here we process only if AUTH_PATH directory exists
    # Note: It's temporary fix for 1.1.0-rc2 (See #749)
    return 0 unless -d $data->{'AUTH_PATH'};

    my $fileUser = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
    my $fileGroup = "$data->{'HOME_PATH'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
    my $filePath = "$data->{'AUTH_PATH'}/.htaccess";

    my $isImmutable = isImmutable( $data->{'AUTH_PATH'} );
    clearImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;

    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = -f $filePath ? $file->get() : '';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddHtaccess', \$fileContent, $data );
    return $rs if $rs;

    my $bTag = "### START i-MSCP PROTECTION ###\n";
    my $eTag = "### END i-MSCP PROTECTION ###\n";
    my $tagContent = "AuthType $data->{'AUTH_TYPE'}\nAuthName \"$data->{'AUTH_NAME'}\"\nAuthUserFile $fileUser\n";

    if ( $data->{'HTUSERS'} eq '' ) {
        $tagContent .= "AuthGroupFile $fileGroup\nRequire group $data->{'HTGROUPS'}\n";
    } else {
        $tagContent .= "Require user $data->{'HTUSERS'}\n";
    }

    $fileContent = replaceBloc( $bTag, $eTag, '', $fileContent );
    $fileContent = $bTag . $tagContent . $eTag . $fileContent;

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdAddHtaccess', \$fileContent, $data );
    return $rs if $rs;

    $file->set( $fileContent );

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
    my ($self, $data) = @_;

    # We process only if AUTH_PATH directory exists
    # Note: It's temporary fix for 1.1.0-rc2 (See #749)
    return 0 unless -d $data->{'AUTH_PATH'};

    my $filePath = "$data->{'AUTH_PATH'}/.htaccess";

    return 0 unless -f $filePath;

    my $isImmutable = isImmutable( $data->{'AUTH_PATH'} );
    clearImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;

    my $file = iMSCP::File->new( filename => $filePath );
    my $fileContent = $file->get() // '';

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDelHtaccess', \$fileContent, $data );
    return $rs if $rs;

    $fileContent = replaceBloc(
        "### START i-MSCP PROTECTION ###\n", "### END i-MSCP PROTECTION ###\n", '', $fileContent
    );

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdDelHtaccess', \$fileContent, $data );
    return $rs if $rs;

    if ( $fileContent ne '' ) {
        $file->set( $fileContent );

        $rs = $file->save();
        $rs ||= $file->owner( $data->{'USER'}, $data->{'GROUP'} );
        $rs ||= $file->mode( 0640 );
        return $rs if $rs;
    } elsif ( -f $filePath ) {
        $rs = $file->delFile();
        return $rs if $rs;
    }

    setImmutable( $data->{'AUTH_PATH'} ) if $isImmutable;
    0;
}

=item buildConf( $cfgTpl, $filename [, \%data ] )

 Build the given configuration template

 Param string $cfgTpl Template content
 Param string $filename template filename
 Param hash \%data OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Return string Template content

=cut

sub buildConf
{
    my ($self, $cfgTpl, $filename, $data) = @_;

    $data ||= {};

    if ( grep( $_ eq $filename, ( 'domain.tpl', 'domain_disabled.tpl' ) ) ) {
        if ( grep( $_ eq $data->{'VHOST_TYPE'}, ( 'domain', 'domain_disabled' ) ) ) {
            # Remove ssl and forward sections
            $cfgTpl = replaceBloc( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', $cfgTpl );
            $cfgTpl = replaceBloc( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', $cfgTpl );
        } elsif ( grep( $_ eq $data->{'VHOST_TYPE'}, ( 'domain_fwd', 'domain_ssl_fwd', 'domain_disabled_fwd' ) ) ) {
            # Remove ssl if needed
            unless ( $data->{'VHOST_TYPE'} eq 'domain_ssl_fwd' ) {
                $cfgTpl = replaceBloc( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', $cfgTpl );
            }

            # Remove domain section
            $cfgTpl = replaceBloc( "# SECTION dmn BEGIN.\n", "# SECTION dmn END.\n", '', $cfgTpl );
        } elsif ( grep( $_ eq $data->{'VHOST_TYPE'}, ( 'domain_ssl', 'domain_disabled_ssl' ) ) ) {
            # Remove forward section
            $cfgTpl = replaceBloc( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', $cfgTpl );
        }
    }

    $self->{'eventManager'}->trigger( 'beforeHttpdBuildConf', \$cfgTpl, $filename, $data );
    $cfgTpl = process( $self->{'data'}, $cfgTpl );
    $self->{'eventManager'}->trigger( 'afterHttpdBuildConf', \$cfgTpl, $filename, $data );
    $cfgTpl;
}

=item buildConfFile( $file [, \%data = { } [, \%options = { } ] ] )

 Build the given configuration file

 Param string $file Absolute path to config file or config filename relative to the i-MSCP apache config directory
 Param hash \%data OPTIONAL Data as provided by Alias|Domain|Subdomain|SubAlias modules or installer
 Param hash \%options OPTIONAL Options:
  - destination: Destination file path (default to $self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/<filebasename>)
  - user: File owner
  - group: File group
  - mode:  File mode
 Return int 0 on success, other on failure

=cut

sub buildConfFile
{
    my ($self, $file, $data, $options) = @_;

    $data ||= {};
    $options ||= {};

    my ($filename, $path) = fileparse( $file );

    my $rs = $self->{'eventManager'}->trigger(
        'onLoadTemplate', 'apache_fcgid', $filename, \ my $cfgTpl, $data, $options
    );
    return $rs if $rs;

    unless ( defined $cfgTpl ) {
        $file = File::Spec->canonpath( "$self->{'apacheCfgDir'}/$filename" ) if $path eq './';
        $cfgTpl = iMSCP::File->new( filename => $file )->get();
        unless ( defined $cfgTpl ) {
            error( sprintf( "Couldn't read %s file", $file ));
            return 1;
        }
    }

    $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildConfFile', \$cfgTpl, $filename, $data, $options );
    return $rs if $rs;

    $cfgTpl = $self->buildConf( $cfgTpl, $filename, $data );

    $rs = $self->{'eventManager'}->trigger( 'afterHttpdBuildConfFile', \$cfgTpl, $filename, $data, $options );
    return $rs if $rs;

    $options->{'destination'} ||= "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$filename";

    my $fileHandler = iMSCP::File->new( filename => $options->{'destination'} );
    $rs = $fileHandler->set( $cfgTpl );
    $rs ||= $fileHandler->save();
    $rs ||= $fileHandler->owner(
        $options->{'user'} // $main::imscpConfig{'ROOT_USER'}, $options->{'group'} // $main::imscpConfig{'ROOT_GROUP'}
    );
    $rs ||= $fileHandler->mode( $options->{'mode'} // 0644 );
}

=item setData( \%data )

 Make the given data available for this server

 Param hash \%data Server data
 Return int 0

=cut

sub setData
{
    my ($self, $data) = @_;

    @{$self->{'data'}}{keys %{$data}} = values %{$data};
    0;
}

=item flushData( )

 Flush all data set via the setData( ) method

 Return int 0

=cut

sub flushData
{
    my ($self) = @_;

    delete $self->{'data'};
    0;
}

=item getTraffic( $trafficDb )

 Get httpd traffic data

 Param hashref \%trafficDb Traffic database
 Die on failure

=cut

sub getTraffic
{
    my $trafficDb = $_[1];

    my $timestamp = time();
    my $ldate = time2str( '%Y%m%d', $timestamp );
    my $dbh = iMSCP::Database->factory()->getRawDb();

    debug( sprintf( 'Collecting HTTP traffic data' ));

    local $@;
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

=item enableSites(@sites)

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
            warning( sprintf( "Site %s doesn't exists", $_ ));
            next;
        }

        $rs = execute( [ 'a2ensite', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = 1;
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
    my ($self, @sites) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableSites', \@sites );
    return $rs if $rs;

    for ( @sites ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
        $rs = execute( [ 'a2dissite', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdDisableSites', @sites );
}

=item enableModules( @modules )

 Enable the given Apache modules

 Param array @modules List of modules to enable
 Return int 0 on sucess, other on failure

=cut

sub enableModules
{
    my ($self, @modules) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableModules', \@modules );
    return $rs if $rs;

    for ( @modules ) {
        next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";
        $rs = execute( [ 'a2enmod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = 1;
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
    my ($self, @modules) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableModules', \@modules );
    return $rs if $rs;

    for ( @modules ) {
        next unless -l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/$_.load";
        $rs = execute( [ 'a2dismod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = 1;
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
    my ($self, @conffiles) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdEnableConfs', \@conffiles );
    return $rs if $rs;

    for ( @conffiles ) {
        unless ( -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$_" ) {
            warning( sprintf( "Configuration file %s doesn't exists", $_ ));
            next;
        }

        $rs = execute( [ 'a2enconf', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = 1;
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
    my ($self, @conffiles) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdDisableConfs', \@conffiles );
    return $rs if $rs;

    for ( @conffiles ) {
        next unless -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$_";
        $rs = execute( [ 'a2disconf', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
        $self->{'restart'} = 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdDisableConfs', @conffiles );
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

    local $@;
    eval { iMSCP::Service->getInstance()->start( $self->{'config'}->{'HTTPD_SNAME'} ); };
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

    local $@;
    eval { iMSCP::Service->getInstance()->stop( $self->{'config'}->{'HTTPD_SNAME'} ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdStop' );
}

=item forceRestart( )

 Force httpd service to be restarted

 Return int 0

=cut

sub forceRestart
{
    my ($self) = @_;

    $self->{'forceRestart'} = 1;
    0;
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

    local $@;
    eval {
        if ( $self->{'forceRestart'} ) {
            iMSCP::Service->getInstance()->restart( $self->{'config'}->{'HTTPD_SNAME'} );
        } else {
            iMSCP::Service->getInstance()->reload( $self->{'config'}->{'HTTPD_SNAME'} );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->{'eventManager'}->trigger( 'afterHttpdRestart' );
}

=item mountLogsFolder( \%data )

 Mount logs folder which belong to the given domain into customer's logs folder

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub mountLogsFolder
{
    my ($self, $data) = @_;

    my $fsSpec = File::Spec->canonpath( "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}" );
    my $fsFile = File::Spec->canonpath( "$data->{'HOME_DIR'}/logs/$data->{'DOMAIN_NAME'}" );
    my $fields = { fs_spec => $fsSpec, fs_file => $fsFile, fs_vfstype => 'none', fs_mntops => 'bind' };
    my $rs = $self->{'eventManager'}->trigger( 'beforeMountLogsFolder', $data, $fields );
    return $rs if $rs;

    iMSCP::Dir->new( dirname => $fsFile )->make();

    $rs = addMountEntry( "$fields->{'fs_spec'} $fields->{'fs_file'} $fields->{'fs_vfstype'} $fields->{'fs_mntops'}" );
    $rs ||= mount( $fields ) unless isMountpoint( $fields->{'fs_file'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterMountLogsFolder', $data, $fields );
}

=item umountLogsFolder( \%data )

 Umount logs folder which belong to the given domain from customer's logs folder

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub umountLogsFolder
{
    my ($self, $data) = @_;

    my $recursive = 1;
    my $fsFile = "$data->{'HOME_DIR'}/logs";

    # We operate recursively only if domain type is 'dmn' (full account)
    if ( $data->{'DOMAIN_TYPE'} ne 'dmn' ) {
        $recursive = 0;
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

 Return Servers::httpd::apache_fcgid

=cut

sub _init
{
    my ($self) = @_;

    $self->{'start'} = 0;
    $self->{'restart'} = 0;
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'apacheCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
    $self->{'apacheTplDir'} = "$self->{'apacheCfgDir'}/parts";

    $self->_mergeConfig( $self->{'apacheCfgDir'}, 'apache.data' ) if -f "$self->{'apacheCfgDir'}/apache.data.dist";
    tie %{$self->{'config'}},
        'iMSCP::Config',
        fileName    => "$self->{'apacheCfgDir'}/apache.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => ( defined $main::execmode && $main::execmode eq 'setup' );

    $self->{'phpCfgDir'} = "$main::imscpConfig{'CONF_DIR'}/php";

    $self->_mergeConfig( $self->{'phpCfgDir'}, 'php.data' ) if -f "$self->{'phpCfgDir'}/php.data.dist";
    tie %{$self->{'phpConfig'}},
        'iMSCP::Config',
        fileName    => "$self->{'phpCfgDir'}/php.data",
        readonly    => !( defined $main::execmode && $main::execmode eq 'setup' ),
        nodeferring => ( defined $main::execmode && $main::execmode eq 'setup' );

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
    my (undef, $confDir, $confName) = @_;

    if ( -f "$confDir/$confName" ) {
        tie my %newConfig, 'iMSCP::Config', fileName => "$confDir/$confName.dist";
        tie my %oldConfig, 'iMSCP::Config', fileName => "$confDir/$confName", readonly => 1;

        debug( 'Merging old configuration with new configuration...' );

        while ( my ($key, $value) = each( %oldConfig ) ) {
            next unless exists $newConfig{$key};
            $newConfig{$key} = $value;
        }

        untie( %newConfig );
        untie( %oldConfig );
    }

    iMSCP::File->new( filename => "$confDir/$confName.dist" )->moveFile( "$confDir/$confName" ) == 0 or die(
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
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddCfg', $data );
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
    my @domainIPs = (
        $data->{'DOMAIN_IP'},
        ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes' ? $data->{'BASE_SERVER_IP'} : () )
    );

    $rs = $self->{'eventManager'}->trigger( 'onAddHttpdVhostIps', $data, \@domainIPs );
    return $rs if $rs;

    # Remove duplicate IP if any and map the INADDR_ANY IP to *
    @domainIPs = uniq( map { $net->normalizeAddr( $_ ) =~ s/^\Q0.0.0.0\E$/*/r } @domainIPs );

    $self->setData(
        {
            DOMAIN_IPS             => join(
                ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':80' } @domainIPs
            ),
            FCGID_NAME             => $confLevel,
            HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
            HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
            PHP_FCGI_STARTER_DIR   => $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'},
            SERVER_ALIASES         => "www.$data->{'DOMAIN_NAME'}" . ( $main::imscpConfig{'CLIENT_WEBSITES_ALT_URLS'} eq 'yes'
                ? " $data->{'ALIAS'}.$main::imscpConfig{'BASE_SERVER_VHOST'}" : ''
            )
        }
    );

    # Create http vhost

    if ( $data->{'HSTS_SUPPORT'} ) {
        $self->setData(
            {
                FORWARD      => "https://$data->{'DOMAIN_NAME'}/",
                FORWARD_TYPE => '301'
            }
        );
        $data->{'VHOST_TYPE'} = 'domain_fwd';
    } elsif ( $data->{'FORWARD'} ne 'no' ) {
        if ( $data->{'FORWARD_TYPE'} eq 'proxy' ) {
            $self->setData( {
                X_FORWARDED_PROTOCOL => 'https',
                X_FORWARDED_PORT     => 80
            } );
        }

        $data->{'VHOST_TYPE'} = 'domain_fwd';
    } else {
        $data->{'VHOST_TYPE'} = 'domain';
    }

    $rs = $self->buildConfFile(
        "$self->{'apacheTplDir'}/domain.tpl",
        $data,
        {
            destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}.conf"
        }
    );
    $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $data->{'SSL_SUPPORT'} ) {
        $self->setData(
            {
                CERTIFICATE => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$data->{'DOMAIN_NAME'}.pem",
                DOMAIN_IPS  => join(
                    ' ',
                    map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':443' } @domainIPs
                )
            }
        );

        if ( $data->{'FORWARD'} ne 'no' ) {
            $self->setData(
                {
                    FORWARD      => $data->{'FORWARD'},
                    FORWARD_TYPE => $data->{'FORWARD_TYPE'}
                }
            );

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

        $rs = $self->buildConfFile(
            "$self->{'apacheTplDir'}/domain.tpl",
            $data,
            {
                destination => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf"
            }
        );
        $rs ||= $self->enableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$data->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new(
            filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$data->{'DOMAIN_NAME'}_ssl.conf"
        )->delFile();
        return $rs if $rs;
    }

    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf" ) {
        $data->{'SKIP_TEMPLATE_CLEANER'} = 1;
        $rs = $self->buildConfFile(
            "$self->{'apacheTplDir'}/custom.conf.tpl",
            $data,
            {
                destination => "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$data->{'DOMAIN_NAME'}.conf"
            }
        );
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
    my ($self, $data) = @_;

    my @folders = ();

    $self->{'eventManager'}->trigger( 'beforeHttpdDmnFolders', \@folders );
    push(
        @folders,
        [
            "$self->{'config'}->{'HTTPD_LOG_DIR'}/$data->{'DOMAIN_NAME'}",
            $main::imscpConfig{'ROOT_USER'},
            $main::imscpConfig{'ADM_GROUP'},
            0755
        ]
    );
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
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdAddFiles', $data );
    return $rs if $rs;

    for ( $self->_dmnFolders( $data ) ) {
        iMSCP::Dir->new( dirname => $_->[0] )->make(
            {
                user  => $_->[1],
                group => $_->[2],
                mode  => $_->[3]
            }
        );
    }

    # Whether or not permissions must be fixed recursively
    my $fixPermissions = iMSCP::Getopt->fixPermissions || $data->{'ACTION'} =~ /^restore(?:Dmn|Sub)$/;

    # Prepare Web folder
    my $skelDir;
    if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
        $skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/domain";
    } elsif ( $data->{'DOMAIN_TYPE'} eq 'als' ) {
        $skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/alias";
    } else {
        $skelDir = "$main::imscpConfig{'CONF_DIR'}/skel/subdomain";
    }

    # Copy skeleton in tmp dir
    my $tmpDir = File::Temp->newdir();
    iMSCP::Dir->new( dirname => $skelDir )->rcopy( $tmpDir, { preserve => 'no' } );

    # Build default page if needed (if htdocs doesn't exists or is empty)
    if ( !-d "$data->{'WEB_DIR'}/htdocs"
        || iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/htdocs" )->isEmpty()
    ) {
        if ( -d "$tmpDir/htdocs" ) {
            # Test needed in case admin removed the index.html file from the skeleton
            if ( -f "$tmpDir/htdocs/index.html" ) {
                $data->{'SKIP_TEMPLATE_CLEANER'} = 1;
                my $fileSource = "$tmpDir/htdocs/index.html";
                $rs = $self->buildConfFile( $fileSource, $data, { destination => $fileSource } );
                return $rs if $rs;
            }
        } else {
            error( "Web folder skeleton must provides the `htdocs' directory." );
            return 1;
        }

        # Force recursive permissions for newly created Web folders
        $fixPermissions = 1;
    } else {
        iMSCP::Dir->new( dirname => "$tmpDir/htdocs" )->remove();
    }

    if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
        if ( -d "$data->{'WEB_DIR'}/errors" && !iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/errors" )->isEmpty() ) {
            iMSCP::Dir->new( dirname => "$tmpDir/errors" )->remove();
        } elsif ( !-d "$tmpDir/errors" ) {
            error( "The `domain' Web folder skeleton must provides the `errors' directory." );
            return 1;
        } else {
            $fixPermissions = 1;
        }
    }

    my $parentDir = dirname( $data->{'WEB_DIR'} );

    # Fix #IP-1327 - Ensure that parent Web folder exists
    unless ( -d $parentDir ) {
        clearImmutable( dirname( $parentDir ));
        iMSCP::Dir->new( dirname => $parentDir )->make(
            {
                user  => $data->{'USER'},
                group => $data->{'GROUP'},
                mode  => 0750
            }
        );
    } else {
        clearImmutable( $parentDir );
    }

    clearImmutable( $data->{'WEB_DIR'} ) if -d $data->{'WEB_DIR'};

    if ($data->{'DOMAIN_TYPE'} eq 'dmn' && $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} ne 'yes') {
        $rs = $self->umountLogsFolder($data);
        return $rs if $rs;

        iMSCP::Dir->new(dirname => "$data->{'WEB_DIR'}/logs")->remove();
        iMSCP::Dir->new(dirname => "$tmpDir/logs")->remove();
    } elsif ( $data->{'DOMAIN_TYPE'} eq 'dmn' && !-d "$tmpDir/logs" ) {
        error("Web folder skeleton must provides the `logs' directory.");
        return 1;
    }

    # Copy Web folder
    iMSCP::Dir->new( dirname => $tmpDir )->rcopy( $data->{'WEB_DIR'}, { preserve => 'no' } );

    # Cleanup (Transitional)
    if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
        # Remove deprecated `domain_disable_page' directory if any
        iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/domain_disable_page" )->remove();
    } elsif ( !$data->{'SHARED_MOUNT_POINT'} ) {
        # Remove deprecated phptmp directory if any
        iMSCP::Dir->new( dirname => "$data->{'WEB_DIR'}/phptmp" )->remove();
        iMSCP::Dir->new( dirname => "$tmpDir/phptmp" )->remove();
    }

    # Set ownership and permissions

    # Set ownership and permissions for Web folder root
    # Web folder root vuxxx:vuxxx 0750 (no recursive)
    $rs = setRights(
        $data->{'WEB_DIR'},
        {
            user  => $data->{'USER'},
            group => $data->{'GROUP'},
            mode  => '0750'
        }
    );
    return $rs if $rs;

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
        next unless -e "$data->{'WEB_DIR'}/$file";

        $rs = setRights(
            "$data->{'WEB_DIR'}/$file",
            {
                user      => $data->{'USER'},
                group     => $data->{'GROUP'},
                recursive => $fixPermissions
            }
        );
        return $rs if $rs;
    }

    if ( $data->{'DOMAIN_TYPE'} eq 'dmn' ) {
        # Set ownership and permission for .htgroup and .htpasswd files if any
        # .htgroup  root:www-data
        # .htpasswd root:www-data
        for my $file( qw/ .htgroup .htpasswd / ) {
            next unless -f "$data->{'WEB_DIR'}/$file";
            $rs = setRights(
                "$data->{'WEB_DIR'}/$file",
                {
                    user  => $main::imscpConfig{'ROOT_USER'},
                    group => $self->{'config'}->{'HTTPD_GROUP'},
                    mode  => '0640'
                }
            );
            return $rs if $rs;
        }

        # Set ownership and permissions for logs directory if any
        # logs root:vuxxx (no recursive)
        if ( -d "$data->{'WEB_DIR'}/logs" ) {
            $rs = setRights(
                "$data->{'WEB_DIR'}/logs",
                {
                    user  => $main::imscpConfig{'ROOT_USER'},
                    group => $data->{'GROUP'}
                }
            );
            return $rs if $rs;
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
        next unless -e "$data->{'WEB_DIR'}/$file";

        $rs = setRights(
            "$data->{'WEB_DIR'}/$file",
            {
                dirmode   => '0750',
                filemode  => '0640',
                recursive => $file =~ /^(?:00_private|cgi-bin|logs|htdocs)$/ ? 0 : $fixPermissions
            }
        );
        return $rs if $rs;
    }

    if ( $data->{'WEB_FOLDER_PROTECTION'} eq 'yes' ) {
        my $dir = $data->{'WEB_DIR'};
        my $userWebDir = File::Spec->canonpath( $main::imscpConfig{'USER_WEB_DIR'} );
        do {
            setImmutable( $dir );
        } while ( $dir = dirname( $dir ) ) ne $userWebDir;
    }

    $rs = $self->mountLogsFolder( $data ) if $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} eq 'yes';
    $rs ||= $self->{'eventManager'}->trigger( 'afterHttpdAddFiles', $data );
}

=item _buildPHPConfig( \%data )

 Build PHP related configuration files

 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _buildPHPConfig
{
    my ($self, $data) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeHttpdBuildPhpConf', $data );
    return $rs if $rs;

    my $phpStarterDir = $self->{'phpConfig'}->{'PHP_FCGI_STARTER_DIR'};
    my $phpVersion = $self->{'phpConfig'}->{'PHP_VERSION'};
    my $confLevel = $self->{'phpConfig'}->{'PHP_CONFIG_LEVEL'};
    my $domainType = $data->{'DOMAIN_TYPE'};

    my ($fcgidName, $emailDomain);
    if ( $confLevel eq 'per_user' ) {
        # One php.ini file for all domains
        $fcgidName = $data->{'ROOT_DOMAIN_NAME'};
        $emailDomain = $data->{'ROOT_DOMAIN_NAME'};
    } elsif ( $confLevel eq 'per_domain' ) {
        # One php.ini file for each domains (including subdomains)
        $fcgidName = $data->{'PARENT_DOMAIN_NAME'};
        $emailDomain = $data->{'PARENT_DOMAIN_NAME'};
    } else {
        # One php.ini file for each domain
        $fcgidName = $data->{'DOMAIN_NAME'};
        $emailDomain = $data->{'DOMAIN_NAME'};
    }

    if ( $data->{'FORWARD'} eq 'no' && $data->{'PHP_SUPPORT'} eq 'yes' ) {
        iMSCP::Dir->new( dirname => $phpStarterDir )->make(
            {
                user  => $main::imscpConfig{'ROOT_USER'},
                group => $main::imscpConfig{'ROOT_GROUP'},
                mode  => 0555
            }
        );
        iMSCP::Dir->new( dirname => "$phpStarterDir/$fcgidName" )->remove();

        for ( "$phpStarterDir/$fcgidName", "$phpStarterDir/$fcgidName/php$phpVersion" ) {
            iMSCP::Dir->new( dirname => $_ )->make(
                {
                    user  => $data->{'USER'},
                    group => $data->{'GROUP'},
                    mode  => 0550
                }
            );
        }

        $self->setData(
            {
                EMAIL_DOMAIN          => $emailDomain,
                FCGID_NAME            => $fcgidName,
                PHP_VERSION           => $phpVersion,
                PHP_FCGI_BIN_PATH     => $self->{'phpConfig'}->{'PHP_FCGI_BIN_PATH'},
                PHP_FCGI_CHILDREN     => $self->{'phpConfig'}->{'PHP_FCGI_CHILDREN'},
                PHP_FCGI_MAX_REQUESTS => $self->{'phpConfig'}->{'PHP_FCGI_MAX_REQUESTS'},
                TMPDIR                => $data->{'HOME_DIR'} . '/phptmp'
            }
        );

        $data->{'SKIP_TEMPLATE_CLEANER'} = 1;

        $rs = $self->buildConfFile(
            "$self->{'phpCfgDir'}/fcgi/php-fcgi-starter",
            $data,
            {
                destination => "$phpStarterDir/$fcgidName/php-fcgi-starter",
                user        => $data->{'USER'},
                group       => $data->{'GROUP'},
                mode        => 0550
            }
        );
        $rs ||= $self->buildConfFile(
            "$self->{'phpCfgDir'}/fcgi/php.ini",
            $data,
            {
                destination => "$phpStarterDir/$fcgidName/php$phpVersion/php.ini",
                user        => $data->{'USER'},
                group       => $data->{'GROUP'},
                mode        => 0440
            }
        );
        return $rs if $rs;
    } elsif ( $data->{'PHP_SUPPORT'} ne 'yes'
        || $confLevel eq 'per_user' && $domainType ne 'dmn'
        || $confLevel eq 'per_domain' && $domainType !~ /^(?:dmn|als)$/
        || $confLevel eq 'per_site'
    ) {
        iMSCP::Dir->new( dirname => "$phpStarterDir/$data->{'DOMAIN_NAME'}" )->remove();
    }

    $self->{'eventManager'}->trigger( 'afterHttpdBuildPhpConf', $data );
}

=item _cleanTemplate( \$tpl, $name, \%data )

 Event listener which is responsible to cleanup production configuration files

 Param string \$tpl Template content
 Param string $name Template name
 Param hash \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0

=cut

sub _cleanTemplate
{
    my (undef, $tpl, $name, $data) = @_;

    if ( $data->{'SKIP_TEMPLATE_CLEANER'} ) {
        delete $data->{'SKIP_TEMPLATE_CLEANER'};
        return 0;
    }

    if ( $name eq 'domain.tpl' ) {
        if ( $data->{'VHOST_TYPE'} !~ /fwd/ ) {
            unless ( $data->{'CGI_SUPPORT'} eq 'yes' ) {
                ${$tpl} = replaceBloc( "# SECTION cgi BEGIN.\n", "# SECTION cgi END.\n", '', ${$tpl} );
            }

            if ( $data->{'PHP_SUPPORT'} eq 'yes' ) {
                ${$tpl} = replaceBloc( "# SECTION php_off BEGIN.\n", "# SECTION php_off END.\n", '', ${$tpl} );
            } else {
                ${$tpl} = replaceBloc( "# SECTION php_on BEGIN.\n", "# SECTION php_on END.\n", '', ${$tpl} );
            }

            ${$tpl} = replaceBloc( "# SECTION itk BEGIN.\n", "# SECTION itk END.\n", '', ${$tpl} );
            ${$tpl} = replaceBloc( "# SECTION php_fpm BEGIN.\n", "# SECTION php_fpm END.\n", '', ${$tpl} );
        } elsif ( $data->{'FORWARD'} ne 'no' ) {
            if ( $data->{'FORWARD_TYPE'} eq 'proxy' && ( !$data->{'HSTS_SUPPORT'} || $data->{'VHOST_TYPE'} =~ /ssl/ ) ) {
                ${$tpl} = replaceBloc( "# SECTION std_fwd BEGIN.\n", "# SECTION std_fwd END.\n", '', ${$tpl} );

                if ( index( $data->{'FORWARD'}, 'https' ) != 0 ) {
                    ${$tpl} = replaceBloc( "# SECTION ssl_proxy BEGIN.\n", "# SECTION ssl_proxy END.\n", '', ${$tpl} );
                }
            } else {
                ${$tpl} = replaceBloc( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', ${$tpl} );
            }
        } else {
            ${$tpl} = replaceBloc( "# SECTION proxy_fwd BEGIN.\n", "# SECTION proxy_fwd END.\n", '', ${$tpl} );
        }
    }

    ${$tpl} =~ s/^\s*(?:[#;].*)?\n//gmi;
    0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
