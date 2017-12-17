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
use autouse 'iMSCP::Crypt' => qw/ ALNUM decryptRijndaelCBC randomStr /;
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
use iMSCP::TemplateParser qw/ processByRef replaceBlocByRef /;
use iMSCP::Umask;
use Scalar::Defer;
use parent qw/ Servers::httpd::Interface Common::SingletonClass /;

my $HAS_TMPFS;
my $TMPFS = lazy
    {
        mount(
            {
                    fs_spec => 'tmpfs',
                    fs_file => my $tmpfs = File::Temp->newdir( CLEANUP => 0 ),
                fs_vfstype      => 'tmpfs',
                fs_mntops       => 'noexec,nosuid,size=32m',
                ignore_failures => 1 # Ignore failures in case tmpfs isn't supported/allowed
            }
        );
        $HAS_TMPFS = 1;
        $tmpfs;
    };

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

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2PreInstall' );
    $rs ||= $self->stop();
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2PreInstall' );
}

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Install' );
    $rs ||= $self->_setVersion();
    $rs ||= $self->_makeDirs();
    $rs ||= $self->_copyDomainDisablePages();
    $rs ||= $self->_setupModules();
    $rs ||= $self->_configure();
    $rs ||= $self->_installLogrotate();
    $rs ||= $self->_setupVlogger();
    $rs ||= $self->_cleanup();
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2Install' );
}

=item postinstall( )

 Process postinstall tasks

 Return int 0 on success, other on failure

=cut

sub postinstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2PostInstall' );
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
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2PostInstall' );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2Uninstall' );
    $rs ||= $self->_removeVloggerSqlUser();
    $rs ||= $self->_removeDirs();
    $rs ||= $self->_restoreDefaultConfig();
    $rs ||= $self->restart() if iMSCP::Service->getInstance()->hasService( 'apache2' );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2Uninstall' );
}

=item setEnginePermissions( )

 Set engine permissions

 Return int 0 on success, other on failure

=cut

sub setEnginePermissions
{
    my ($self) = @_;

    my $rs ||= setRights( '/usr/local/sbin/vlogger',
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => '0750'
        }
    );
    $rs ||= setRights( $self->{'config'}->{'HTTPD_LOG_DIR'},
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $main::imscpConfig{'ADM_GROUP'},
            dirmode   => '0755',
            filemode  => '0644',
            recursive => iMSCP::Getopt->fixPermissions
        }
    );
    $rs ||= setRights( "$main::imscpConfig{'USER_WEB_DIR'}/domain_disabled_pages",
        {
            user      => $main::imscpConfig{'ROOT_USER'},
            group     => $self->{'config'}->{'HTTPD_GROUP'},
            dirmode   => '0550',
            filemode  => '0440',
            recursive => iMSCP::Getopt->fixPermissions
        }
    );
}

=item addUser( \%moduleData )

 See Servers::httpd::Interface::addUser()

=cut

sub addUser
{
    my ($self, $moduleData) = @_;

    return 0 if $moduleData->{'STATUS'} eq 'tochangepwd';

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2AddUser', $moduleData );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->addToGroup( $moduleData->{'GROUP'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2AddUser', $moduleData );
}

=item deleteUser( \%moduleData )

 See Servers::httpd::Interface::deleteUser()

=cut

sub deleteUser
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DeleteUser', $moduleData );
    $rs ||= iMSCP::SystemUser->new( username => $self->{'config'}->{'HTTPD_USER'} )->removeFromGroup( $moduleData->{'GROUP'} );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DeleteUser', $moduleData );
}

=item addDomain( \%moduleData )

 See Servers::httpd::Interface::addDomain()

=cut

sub addDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2AddDomain', $moduleData );
    $rs ||= $self->_addCfg( $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2AddDomain', $moduleData );
}

=item restoreDmn( \%moduleData )

 See Servers::httpd::Interface::restoreDmn()

=cut

sub restoreDmn
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2RestoreDomain', $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2RestoreDomain', $moduleData );
}

=item disableDomain( \%moduleData )

 See Servers::httpd::Interface::disableDomain()

=cut

sub disableDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DisableDomain', $moduleData );
    $rs ||= $self->_disableDomain( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DisableDomain', $moduleData );
}

=item deleteDomain( \%moduleData )

 See Servers::httpd::Interface::deleteDomain()

=cut

sub deleteDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DeleteDomain', $moduleData );
    $rs ||= $self->_deleteDomain( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DeleteDomain', $moduleData );
}

=item addSubbdomain( \%moduleData )

 See Servers::httpd::Interface::addSubbdomain()

=cut

sub addSubbdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2AddSubdomain', $moduleData );
    $rs ||= $self->_addCfg( $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2AddSubdomain', $moduleData );
}

=item restoreSubdomain( \%moduleData )

 See Servers::httpd::Interface::restoreSubdomain()

=cut

sub restoreSubdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2RestoreSubdomain', $moduleData );
    $rs ||= $self->_addFiles( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2RestoreSubdomain', $moduleData );
}

=item disableSub( \%moduleData )

 See Servers::httpd::Interface::disableSub()

=cut

sub disableSub
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DisableSubdomain', $moduleData );
    $rs ||= $self->_disableDomain( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DisableSubdomain', $moduleData );
}

=item deleteSubdomain( \%moduleData )

 See Servers::httpd::Interface::deleteSubdomain()

=cut

sub deleteSubdomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DeleteSubdomain', $moduleData );
    $rs ||= $self->_deleteDomain( $moduleData );
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DeleteSubdomain', $moduleData );
}

=item addHtpasswd( \%moduleData )

 See Servers::httpd::Interface::addHtpasswd()

=cut

sub addHtpasswd
{
    my ($self, $moduleData) = @_;

    eval {
        clearImmutable( $moduleData->{'WEB_DIR'} );

        my $filePath = "$moduleData->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_USERS_FILENAME'}";
        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = -f $filePath ? $file->get() : '';

        $self->{'eventManager'}->trigger( 'beforeApache2AddHtpasswd', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTUSER_NAME'}:[^\n]*\n//gim;
        $fileContent .= "$moduleData->{'HTUSER_NAME'}:$moduleData->{'HTUSER_PASS'}\n";
        $self->{'eventManager'}->trigger( 'afterApache2AddHtpasswd', \$fileContent, $moduleData ) == 0 or die(
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

 See Servers::httpd::Interface::deleteHtpasswd()

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

        $self->{'eventManager'}->trigger( 'beforeApache2DeleteHtpasswd', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTUSER_NAME'}:[^\n]*\n//gim;
        $self->{'eventManager'}->trigger( 'afterApache2DeleteHtpasswd', \$fileContent, $moduleData ) == 0 or die(
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

 See Servers::httpd::Interface::addHtgroup()

=cut

sub addHtgroup
{
    my ($self, $moduleData) = @_;

    eval {
        clearImmutable( $moduleData->{'WEB_DIR'} );

        my $filePath = "$moduleData->{'WEB_DIR'}/$self->{'config'}->{'HTACCESS_GROUPS_FILENAME'}";
        my $file = iMSCP::File->new( filename => $filePath );
        my $fileContent = -f $filePath ? $file->get() : '';

        $self->{'eventManager'}->trigger( 'beforeApache2AddHtgroup', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTGROUP_NAME'}:[^\n]*\n//gim;
        $fileContent .= "$moduleData->{'HTGROUP_NAME'}:$moduleData->{'HTGROUP_USERS'}\n";
        $self->{'eventManager'}->trigger( 'afterApache2AddHtgroup', \$fileContent, $moduleData ) == 0 or die(
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

 See Servers::httpd::Interface::deleteHtgroup()

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

        $self->{'eventManager'}->trigger( 'beforeApache2DeleteHtgroup', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        $fileContent =~ s/^$moduleData->{'HTGROUP_NAME'}:[^\n]*\n//gim;
        $self->{'eventManager'}->trigger( 'afterApache2DeleteHtgroup', \$fileContent, $moduleData ) == 0 or die(
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

 See Servers::httpd::Interface::addHtaccess()

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

        $self->{'eventManager'}->trigger( 'beforeApache2AddHtaccess', \$fileContent, $moduleData ) == 0 or die(
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

        replaceBlocByRef( $bTag, $eTag, '', \$fileContent );
        $fileContent = $bTag . $tagContent . $eTag . $fileContent;

        $self->{'eventManager'}->trigger( 'afterApache2AddHtaccess', \$fileContent, $moduleData ) == 0 or die(
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

 See Servers::httpd::Interface::deleteHtaccess()

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

        $self->{'eventManager'}->trigger( 'beforeApache2DeleteHtaccess', \$fileContent, $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        replaceBlocByRef( "### START i-MSCP PROTECTION ###\n", "### END i-MSCP PROTECTION ###\n", '', \$fileContent );

        $self->{'eventManager'}->trigger( 'afterApache2DeleteHtaccess', \$fileContent, $moduleData ) == 0 or die(
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

=item buildConfFile( $srcFile, $trgFile, [, \%moduleData = { } [, \%serverData [, \%parameters = { } ] ] ] )

 See Servers::httpd::Interface::buildConfFile()

=cut

sub buildConfFile
{
    my ($self, $srcFile, $trgFile, $moduleData, $serverData, $parameters) = @_;
    $moduleData //= {};
    $serverData //= {};
    $parameters //= {};

    my ($filename, $path) = fileparse( $srcFile );
    my $cfgTpl;

    if ( $parameters->{'cached'} && exists $self->{'_templates'}->{$filename} ) {
        $cfgTpl = $self->{'_templates'}->{$filename};
    } else {
        my $rs = $self->{'eventManager'}->trigger( 'onLoadTemplate', 'apache2', $filename, \$cfgTpl, $moduleData, $serverData, $self->{'config'} );
        return $rs if $rs;

        unless ( defined $cfgTpl ) {
            $srcFile = File::Spec->canonpath( "$self->{'cfgDir'}/$path/$filename" ) if index( $path, '/' ) != 0;
            $cfgTpl = iMSCP::File->new( filename => $srcFile )->get();
            unless ( defined $cfgTpl ) {
                error( sprintf( "Couldn't read the %s file", $srcFile ));
                return 1;
            }
        }

        $self->{'_templates'}->{$filename} = $cfgTpl if $parameters->{'cached'};
    }

    if ( grep( $_ eq $filename, ( 'domain.tpl', 'domain_disabled.tpl' ) ) ) {
        if ( grep( $_ eq $serverData->{'VHOST_TYPE'}, 'domain', 'domain_disabled' ) ) {
            replaceBlocByRef( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', \$cfgTpl );
            replaceBlocByRef( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', \$cfgTpl );
        } elsif ( grep( $_ eq $serverData->{'VHOST_TYPE'}, 'domain_fwd', 'domain_ssl_fwd', 'domain_disabled_fwd' ) ) {
            if ( $serverData->{'VHOST_TYPE'} ne 'domain_ssl_fwd' ) {
                replaceBlocByRef( "# SECTION ssl BEGIN.\n", "# SECTION ssl END.\n", '', \$cfgTpl );
            }

            replaceBlocByRef( "# SECTION dmn BEGIN.\n", "# SECTION dmn END.\n", '', \$cfgTpl );
        } elsif ( grep( $_ eq $serverData->{'VHOST_TYPE'}, 'domain_ssl', 'domain_disabled_ssl' ) ) {
            replaceBlocByRef( "# SECTION fwd BEGIN.\n", "# SECTION fwd END.\n", '', \$cfgTpl );
        }
    }

    my $rs = $self->{'eventManager'}->trigger(
        'beforeApache2BuildConfFile', \$cfgTpl, $filename, \$trgFile, $moduleData, $serverData, $self->{'config'}, $parameters
    );
    return $rs if $rs;

    processByRef( $serverData, \$cfgTpl );
    processByRef( $moduleData, \$cfgTpl );

    $rs = $self->{'eventManager'}->trigger(
        'afterApache2BuildConfFile', \$cfgTpl, $filename, \$trgFile, $moduleData, $serverData, $self->{'config'}, $parameters
    );
    return $rs if $rs;

    my $fh = iMSCP::File->new( filename => $trgFile );
    $fh->set( $cfgTpl );
    $rs = $fh->save();
    return $rs if $rs;

    if ( exists $parameters->{'user'} || exists $parameters->{'group'} ) {
        $rs = $fh->owner( $parameters->{'user'} // $main::imscpConfig{'ROOT_USER'}, $parameters->{'group'} // $main::imscpConfig{'ROOT_GROUP'} );
        return ${$rs} if $rs;
    }

    if ( exists $parameters->{'mode'} ) {
        $rs = $fh->mode( $parameters->{'mode'} );
        return $rs if $rs;
    }

    # On configuration file change, schedule server reload
    $self->{'reload'} ||= 1;
    0;
}

=item getTraffic( \%trafficDb )

 See Servers::httpd::Interface::getTraffic()

=cut

sub getTraffic
{
    my (undef, $trafficDb) = @_;

    my $ldate = time2str( '%Y%m%d', time());
    my $dbh = iMSCP::Database->getInstance()->getRawDb();

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

 See Servers::httpd::Interface::getRunningUser()

=cut

sub getRunningUser
{
    my ($self) = @_;

    $self->{'config'}->{'HTTPD_USER'};
}

=item getRunningGroup( )

 See Servers::httpd::Interface::getRunningGroup()

=cut

sub getRunningGroup
{
    my ($self) = @_;

    $self->{'config'}->{'HTTPD_GROUP'};
}

=item enableSites( @sites )

 See Servers::httpd::Interface::enableSites()

=cut

sub enableSites
{
    my ($self, @sites) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2EnableSites', \@sites );
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

    $self->{'reload'} ||= 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2EnableSites', @sites );
}

=item disableSites( @sites )

 See Servers::httpd::Interface::disableSites()

=cut

sub disableSites
{
    my ($self, @sites) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DisableSites', \@sites );
    return $rs if $rs;

    for ( @sites ) {
        next unless -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$_";
        $rs = execute( [ '/usr/sbin/a2dissite', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'reload'} ||= 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DisableSites', @sites );
}

=item enableModules( @modules )

 See Servers::httpd::Interface::enableModules()

=cut

sub enableModules
{
    my ($self, @modules) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2EnableModules', \@modules );
    return $rs if $rs;

    for ( @modules ) {
        next unless -f "$self->{'config'}->{'HTTPD_MODS_AVAILABLE_DIR'}/$_.load";
        $rs = execute( [ '/usr/sbin/a2enmod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2EnableModules', @modules );
}

=item disableModules( @modules )

 See Servers::httpd::Interface::disableModules()

=cut

sub disableModules
{
    my ($self, @modules) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DisableModules', \@modules );
    return $rs if $rs;

    for ( @modules ) {
        next unless -l "$self->{'config'}->{'HTTPD_MODS_ENABLED_DIR'}/$_.load";
        $rs = execute( [ '/usr/sbin/a2dismod', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'restart'} ||= 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DisableModules', @modules );
}

=item enableConfs( @conffiles )

 See Servers::httpd::Interface::enableConfs()

=cut

sub enableConfs
{
    my ($self, @conffiles) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2EnableConfs', \@conffiles );
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

    $self->{'reload'} ||= 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2EnableConfs', @conffiles );
}

=item disableConfs( @conffiles )

 See Servers::httpd::Interface::disableConfs()

=cut

sub disableConfs
{
    my ($self, @conffiles) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2DisableConfs', \@conffiles );
    return $rs if $rs;

    for ( @conffiles ) {
        next unless -f "$self->{'config'}->{'HTTPD_CONF_DIR'}/conf-available/$_";
        $rs = execute( [ '/usr/sbin/a2disconf', $_ ], \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        last if $rs;
    }

    $self->{'reload'} ||= 1 unless $rs;
    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2DisableConfs', @conffiles );
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

=item _init( )

 Initialize instance

 Return Servers::httpd::Apache2::Abstract

=cut

sub _init
{
    my ($self) = @_;

    @{$self}{qw/ start restart reload _templates _web_folder_skeleton /} = ( 0, 0, 0, {}, undef );
    $self->{'eventManager'} = iMSCP::EventManager->getInstance();
    $self->{'cfgDir'} = "$main::imscpConfig{'CONF_DIR'}/apache";
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

=item _deleteDomain( \%moduleData )

 Process deleteDomain tasks

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub _deleteDomain
{
    my ($self, $moduleData) = @_;

    my $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}.conf", "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
    return $rs if $rs;

    for ( "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf",
        "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf",
        "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf"
    ) {
        next unless -f $_;
        $rs = iMSCP::File->new( filename => $_ )->delFile();
        return $rs if $rs;
    }

    $rs = $self->_umountLogsFolder( $moduleData );
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
        error( $@ );
        return 1;
    }

    0;
}

=item _mountLogsFolder( \%moduleData )

 Mount logs folder which belong to the given domain into customer's logs folder

 Param hashref \%moduleData Domain data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _mountLogsFolder
{
    my ($self, $moduleData) = @_;

    my $fsSpec = File::Spec->canonpath( "$self->{'config'}->{'HTTPD_LOG_DIR'}/$moduleData->{'DOMAIN_NAME'}" );
    my $fsFile = File::Spec->canonpath( "$moduleData->{'HOME_DIR'}/logs/$moduleData->{'DOMAIN_NAME'}" );
    my $fields = { fs_spec => $fsSpec, fs_file => $fsFile, fs_vfstype => 'none', fs_mntops => 'bind' };

    unless ( -d $fsFile ) {
        eval {
            iMSCP::Dir->new( dirname => $fsFile )->make( {
                user  => $main::imscpConfig{'ROOT_USER'},
                group => $moduleData->{'GROUP'},
                mode  => 0750
            } );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    my $rs = addMountEntry( "$fields->{'fs_spec'} $fields->{'fs_file'} $fields->{'fs_vfstype'} $fields->{'fs_mntops'}" );
    $rs ||= mount( $fields ) unless isMountpoint( $fields->{'fs_file'} );
}

=item _umountLogsFolder( \%moduleData )

 Umount logs folder which belong to the given domain from customer's logs folder

 Param hashref \%moduleData Domain data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _umountLogsFolder
{
    my (undef, $moduleData) = @_;

    my $recursive = 1;
    my $fsFile = "$moduleData->{'HOME_DIR'}/logs";

    # We operate recursively only if domain type is 'dmn' (full account)
    if ( $moduleData->{'DOMAIN_TYPE'} ne 'dmn' ) {
        $recursive = 0;
        $fsFile .= "/$moduleData->{'DOMAIN_NAME'}";
    }

    $fsFile = File::Spec->canonpath( $fsFile );
    my $rs ||= removeMountEntry( qr%.*?[ \t]+\Q$fsFile\E(?:/|[ \t]+)[^\n]+% );
    $rs ||= umount( $fsFile, $recursive );
}

=item _disableDomain( \%moduleData )

 Disable a domain

 Param hashref \%moduleData Domain data as provided by Alias|Domain modules
 Return int 0 on success, other on failure

=cut

sub _disableDomain
{
    my ($self, $moduleData) = @_;

    eval {
        iMSCP::Dir->new( dirname => "$self->{'config'}->{'HTTPD_LOG_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->make( {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ADM_GROUP'},
            mode  => 0755
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my $net = iMSCP::Net->getInstance();
    my @domainIPs = ( $moduleData->{'DOMAIN_IP'}, ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' ? $moduleData->{'BASE_SERVER_IP'} : () ) );

    my $rs = $self->{'eventManager'}->trigger( 'onApache2AddVhostIps', $moduleData, \@domainIPs );
    return $rs if $rs;

    # If INADDR_ANY is found, map it to the wildcard sign and discard any other
    # IP, else, remove any duplicate IP address from the list
    @domainIPs = grep($_ eq '0.0.0.0', @domainIPs) ? ( '*' ) : unique( map { $net->normalizeAddr( $_ ) } @domainIPs );

    my $serverData = {
        DOMAIN_IPS      => join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':80' } @domainIPs ),
        HTTP_URI_SCHEME => 'http://',
        HTTPD_LOG_DIR   => $self->{'config'}->{'HTTPD_LOG_DIR'},
        USER_WEB_DIR    => $main::imscpConfig{'USER_WEB_DIR'},
        SERVER_ALIASES  => "www.$moduleData->{'DOMAIN_NAME'}" . ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes'
            ? " $moduleData->{'ALIAS'}.$main::imscpConfig{'BASE_SERVER_VHOST'}" : ''
        )
    };

    # Create http vhost

    if ( $moduleData->{'HSTS_SUPPORT'} ) {
        @{$serverData}{qw/ FORWARD FORWARD_TYPE VHOST_TYPE /} = ( "https://$moduleData->{'DOMAIN_NAME'}/", 301, 'domain_disabled_fwd' );
    } else {
        $serverData->{'VHOST_TYPE'} = 'domain_disabled';
    }

    $rs = $self->buildConfFile(
        "parts/domain_disabled.tpl",
        "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf",
        $moduleData,
        $serverData,
        { cached => 1 }
    );

    $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $moduleData->{'SSL_SUPPORT'} ) {
        @{$serverData}{qw/ CERTIFICATE DOMAIN_IPS HTTP_URI_SCHEME VHOST_TYPE /} = (
            "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$moduleData->{'DOMAIN_NAME'}.pem",
            join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':443' } @domainIPs ),
            'https://',
            'domain_disabled_ssl'
        );

        $rs = $self->buildConfFile(
            "parts/domain_disabled.tpl",
            "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf",
            $moduleData,
            $serverData,
            { cached => 1 }
        );
        $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" )->delFile();
        return $rs if $rs;
    }

    # Make sure that custom httpd conffile exists (cover case where file has been removed for any reasons)
    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
        $serverData->{'SKIP_TEMPLATE_CLEANER'} = 1;
        $rs = $self->buildConfFile(
            "parts/custom.conf.tpl",
            "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf",
            $moduleData,
            $serverData,
            { cached => 1 }
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

    0;
}

=item _addCfg( \%data )

 Add configuration files for the given domain

 Param hashref \%data Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on success, other on failure

=cut

sub _addCfg
{
    my ($self, $moduleData) = @_;

    my $rs = $self->{'eventManager'}->trigger( 'beforeApache2AddCfg', $moduleData );
    $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}.conf", "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
    return $rs if $rs;

    my $net = iMSCP::Net->getInstance();
    my @domainIPs = ( $moduleData->{'DOMAIN_IP'}, ( $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' ? $moduleData->{'BASE_SERVER_IP'} : () ) );

    $rs = $self->{'eventManager'}->trigger( 'onApache2AddVhostIps', $moduleData, \@domainIPs );
    return $rs if $rs;

    # If INADDR_ANY is found, map it to the wildcard sign and discard any other
    # IP, else, remove any duplicate IP address from the list
    @domainIPs = grep($_ eq '0.0.0.0', @domainIPs) ? ( '*' ) : unique( map { $net->normalizeAddr( $_ ) } @domainIPs );

    my $serverData = {
        DOMAIN_IPS             => join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':80' } @domainIPs ),
        HTTPD_CUSTOM_SITES_DIR => $self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'},
        HTTPD_LOG_DIR          => $self->{'config'}->{'HTTPD_LOG_DIR'},
        SERVER_ALIASES         => "www.$moduleData->{'DOMAIN_NAME'}" . (
                $main::imscpConfig{'CLIENT_DOMAIN_ALT_URLS'} eq 'yes' ? " $moduleData->{'ALIAS'}.$main::imscpConfig{'BASE_SERVER_VHOST'}" : ''
        )
    };

    # Create http vhost

    if ( $moduleData->{'HSTS_SUPPORT'} ) {
        @{$serverData}{qw/ FORWARD FORWARD_TYPE VHOST_TYPE /} = ( "https://$moduleData->{'DOMAIN_NAME'}/", 301, 'domain_fwd' );
    } elsif ( $moduleData->{'FORWARD'} ne 'no' ) {
        $serverData->{'VHOST_TYPE'} = 'domain_fwd';
        @{$serverData}{qw/ X_FORWARDED_PROTOCOL X_FORWARDED_PORT /} = ( 'http', 80 ) if $moduleData->{'FORWARD_TYPE'} eq 'proxy';
    } else {
        $serverData->{'VHOST_TYPE'} = 'domain';
    }

    $rs = $self->buildConfFile(
        "parts/domain.tpl",
        "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf",
        $moduleData,
        $serverData,
        { cached => 1 }
    );

    $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}.conf" );
    return $rs if $rs;

    # Create https vhost (or delete it if SSL is disabled)

    if ( $moduleData->{'SSL_SUPPORT'} ) {
        @{$serverData}{qw/ CERTIFICATE DOMAIN_IPS /} = (
            "$main::imscpConfig{'GUI_ROOT_DIR'}/data/certs/$moduleData->{'DOMAIN_NAME'}.pem",
            join( ' ', map { ( ( $_ eq '*' || $net->getAddrVersion( $_ ) eq 'ipv4' ) ? $_ : "[$_]" ) . ':443' } @domainIPs )
        );

        if ( $moduleData->{'FORWARD'} ne 'no' ) {
            @{$serverData}{qw/ FORWARD FORWARD_TYPE VHOST_TYPE /} = ( $moduleData->{'FORWARD'}, $moduleData->{'FORWARD_TYPE'}, 'domain_ssl_fwd' );
            @{$serverData}{qw/ X_FORWARDED_PROTOCOL X_FORWARDED_PORT /} = ( 'https', 443 ) if $moduleData->{'FORWARD_TYPE'} eq 'proxy';
        } else {
            $serverData->{'VHOST_TYPE'} = 'domain_ssl';
        }

        $rs = $self->buildConfFile(
            "parts/domain.tpl",
            "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf",
            $moduleData,
            $serverData,
            { cached => 1 }
        );
        $rs ||= $self->enableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        return $rs if $rs;
    } elsif ( -f "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" ) {
        $rs = $self->disableSites( "$moduleData->{'DOMAIN_NAME'}_ssl.conf" );
        $rs ||= iMSCP::File->new( filename => "$self->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$moduleData->{'DOMAIN_NAME'}_ssl.conf" )->delFile();
        return $rs if $rs;
    }

    unless ( -f "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf" ) {
        $serverData->{'SKIP_TEMPLATE_CLEANER'} = 1;
        $rs = $self->buildConfFile(
            "parts/custom.conf.tpl",
            "$self->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/$moduleData->{'DOMAIN_NAME'}.conf",
            $moduleData,
            $serverData,
            { cached => 1 }
        );
    }

    $rs ||= $self->{'eventManager'}->trigger( 'afterApache2AddCfg', $moduleData );
}


=item _getWebfolderSkeleton( \%moduleData )

 Get Web folder skeleton

 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return string Path to Web folder skeleton on success, die on failure

=cut

sub _getWebfolderSkeleton
{
    my (undef, $moduleData) = @_;

    my $webFolderSkeleton = $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ? 'domain' : ( $moduleData->{'DOMAIN_TYPE'} eq 'als' ? 'alias' : 'subdomain' );

    unless ( -d "$TMPFS/$webFolderSkeleton" ) {
        iMSCP::Dir->new(
            dirname => "$main::imscpConfig{'CONF_DIR'}/skel/$webFolderSkeleton" )->rcopy( "$TMPFS/$webFolderSkeleton", { preserve => 'no' }
        );

        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            for ( qw/ errors logs / ) {
                next if -d "$TMPFS/$webFolderSkeleton/$_";
                iMSCP::Dir->new( dirname => "$TMPFS/$webFolderSkeleton/$_" )->make();
            }
        }

        iMSCP::Dir->new( dirname => "$TMPFS/$webFolderSkeleton/htdocs" )->make() unless -d "$TMPFS/$webFolderSkeleton/htdocs";
    }

    "$TMPFS/$webFolderSkeleton";
}

=item _addFiles( \%moduleData )

 Add default directories and files for the given domain

 Param hashref \%moduleData Data as provided by Alias|Domain|Subdomain|SubAlias modules
 Return int 0 on sucess, other on failure

=cut

sub _addFiles
{
    my ($self, $moduleData) = @_;

    eval {
        $self->{'eventManager'}->trigger( 'beforeApache2AddFiles', $moduleData ) == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );

        iMSCP::Dir->new( dirname => "$self->{'config'}->{'HTTPD_LOG_DIR'}/$moduleData->{'DOMAIN_NAME'}" )->make( {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ADM_GROUP'},
            mode  => 0755
        } );

        # Whether or not permissions must be fixed recursively
        my $fixPermissions = iMSCP::Getopt->fixPermissions || grep( $moduleData->{'ACTION'} eq $_, 'restoreDomain', 'restoreSubdomain' );

        #
        ## Prepare Web folder
        #

        my $webFolderSkeleton = $self->_getWebfolderSkeleton( $moduleData );
        my $workingWebFolder = File::Temp->newdir( DIR => $TMPFS );

        iMSCP::Dir->new( dirname => $webFolderSkeleton )->rcopy( $workingWebFolder );

        if ( -d "$moduleData->{'WEB_DIR'}/htdocs" ) {
            iMSCP::Dir->new( dirname => "$workingWebFolder/htdocs" )->remove();
        } else {
            # Always fix permissions recursively for newly created Web folders
            $fixPermissions = 1;
        }

        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' && -d "$moduleData->{'WEB_DIR'}/errors" ) {
            iMSCP::Dir->new( dirname => "$workingWebFolder/errors" )->remove();
        }

        # Make sure that parent Web folder exists
        my $parentDir = dirname( $moduleData->{'WEB_DIR'} );
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

        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            $self->_umountLogsFolder( $moduleData ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

            if ( $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} ne 'yes' ) {
                iMSCP::Dir->new( dirname => "$moduleData->{'WEB_DIR'}/logs" )->remove();
                iMSCP::Dir->new( dirname => "$workingWebFolder/logs" )->remove();
            }
        }

        #
        ## Create Web folder
        #

        iMSCP::Dir->new( dirname => $workingWebFolder )->rcopy( $moduleData->{'WEB_DIR'}, { preserve => 'no' } );

        # Set ownership and permissions

        # Set ownership and permissions for the Web folder root
        # Web folder root vuxxx:vuxxx 0750 (no recursive)
        setRights( $moduleData->{'WEB_DIR'},
            {
                user  => $moduleData->{'USER'},
                group => $moduleData->{'GROUP'},
                mode  => '0750'
            }
        ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        # Get list of possible files inside Web folder root
        my @files = iMSCP::Dir->new( dirname => $webFolderSkeleton )->getAll();

        # Set ownership for Web folder
        for ( @files ) {
            next unless -e "$moduleData->{'WEB_DIR'}/$_";
            setRights( "$moduleData->{'WEB_DIR'}/$_",
                {
                    user      => $moduleData->{'USER'},
                    group     => $moduleData->{'GROUP'},
                    recursive => $fixPermissions
                }
            ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        if ( $moduleData->{'DOMAIN_TYPE'} eq 'dmn' ) {
            # Set ownership and permissions for .htgroup and .htpasswd files
            for ( qw/ .htgroup .htpasswd / ) {
                next unless -f "$moduleData->{'WEB_DIR'}/$_";
                setRights( "$moduleData->{'WEB_DIR'}/$_",
                    {
                        user  => $main::imscpConfig{'ROOT_USER'},
                        group => $self->getRunningGroup(),
                        mode  => '0640'
                    }
                ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }

            # Set ownership for logs directory
            if ( $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} eq 'yes' ) {
                setRights( "$moduleData->{'WEB_DIR'}/logs",
                    {
                        user      => $main::imscpConfig{'ROOT_USER'},
                        group     => $moduleData->{'GROUP'},
                        recursive => $fixPermissions
                    }
                ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }
        }

        # Set permissions for Web folder
        for my $file ( @files ) {
            next unless -e "$moduleData->{'WEB_DIR'}/$file";
            setRights( "$moduleData->{'WEB_DIR'}/$file",
                {
                    dirmode   => '0750',
                    filemode  => '0640',
                    recursive => $file =~ /^(?:00_private|cgi-bin|htdocs)$/ ? 0 : $fixPermissions
                }
            ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        if ( $self->{'config'}->{'MOUNT_CUSTOMER_LOGS'} eq 'yes' ) {
            $self->_mountLogsFolder( $moduleData ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        $self->{'eventManager'}->trigger( 'afterApache2AddFiles', $moduleData ) == 0 or die(
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

=item _setupVlogger( )

 Setup vlogger

 Return int 0 on success, other on failure

=cut

sub _setupVlogger
{
    my ($self) = @_;

    {
        my $vloggerDbSchemaFile = File::Temp->new();
        print $vloggerDbSchemaFile <<"EOF";
USE `@{ [ main::setupGetQuestion( 'DATABASE_NAME' ) ] }`;

CREATE TABLE IF NOT EXISTS httpd_vlogger (
  vhost varchar(255) NOT NULL,
  ldate int(8) UNSIGNED NOT NULL,
  bytes int(32) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY(vhost,ldate)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
EOF
        $vloggerDbSchemaFile->flush();

        my $mysqlConffile = File::Temp->new();
        print $mysqlConffile <<"EOF";
[mysql]
host = @{[ main::setupGetQuestion( 'DATABASE_HOST' ) ]}
port = @{[ main::setupGetQuestion( 'DATABASE_PORT' ) ]}
user = "@{ [ main::setupGetQuestion( 'DATABASE_USER' ) =~ s/"/\\"/gr ] }"
password = "@{ [ decryptRijndaelCBC($main::imscpKEY, $main::imscpIV, main::setupGetQuestion( 'DATABASE_PASSWORD' )) =~ s/"/\\"/gr ] }"
EOF
        $mysqlConffile->flush();

        my $rs = execute( "cat $vloggerDbSchemaFile | /usr/bin/mysql --defaults-extra-file=$mysqlConffile", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    my $host = main::setupGetQuestion( 'DATABASE_HOST' );
    $host = '127.0.0.1' if $host eq 'localhost';
    my $port = main::setupGetQuestion( 'DATABASE_PORT' );
    my $dbName = main::setupGetQuestion( 'DATABASE_NAME' );
    my $user = 'vlogger_user';
    my $userHost = main::setupGetQuestion( 'DATABASE_USER_HOST' );
    $userHost = '127.0.0.1' if $userHost eq 'localhost';
    my $oldUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'};
    my $pass = randomStr( 16, ALNUM );

    eval {
        my $sqlServer = Servers::sqld->factory();

        for ( $userHost, $oldUserHost, 'localhost' ) {
            next unless $_;
            $sqlServer->dropUser( $user, $_ );
        }

        $sqlServer->createUser( $user, $userHost, $pass );

        my $dbh = iMSCP::Database->getInstance()->getRawDb();
        local $dbh->{'RaiseError'} = 1;

        # No need to escape wildcard characters. See https://bugs.mysql.com/bug.php?id=18660
        my $qDbName = $dbh->quote_identifier( $dbName );
        $dbh->do( "GRANT SELECT, INSERT, UPDATE ON $qDbName.httpd_vlogger TO ?\@?", undef, $user, $userHost );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    $self->buildConfFile(
        "vlogger.conf.tpl",
        "$self->{'cfgDir'}/vlogger.conf",
        undef,
        {
            DATABASE_NAME         => $dbName,
            DATABASE_HOST         => $host,
            DATABASE_PORT         => $port,
            DATABASE_USER         => $user,
            DATABASE_PASSWORD     => $pass,
            SKIP_TEMPLATE_CLEANER => 1
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

=head1 SHUTDOWN TASKS

=over 4

=item END

 Umount and remove tmpfs

=cut

END {
    return unless $HAS_TMPFS;
    umount( $TMPFS );
    iMSCP::Dir->new( dirname => $TMPFS )->remove();
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
