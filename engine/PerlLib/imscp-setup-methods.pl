#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2019 by Laurent Declecq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Database;
use iMSCP::DbTasksProcessor;
use iMSCP::Debug 'error';
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute 'executeNoWait';
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Packages;
use iMSCP::Plugins;
use iMSCP::Servers;
use iMSCP::Service;
use iMSCP::Stepper qw/ startDetail endDetail step /;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Umask '$UMASK';
use Try::Tiny;

sub setupInstallFiles
{
    try {
        my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupInstallFiles', $::{'INST_PREF'} );
        return $rs if $rs;

        if ( iMSCP::Service->getInstance()->hasService( 'imscp_daemon' ) ) {
            # i-MSCP daemon must be stopped before changing any file on the files system
            iMSCP::Service->getInstance()->stop( 'imscp_daemon' );
        }

        # Process cleanup to avoid any security risks and conflicts
        for my $dir ( qw/ daemon engine gui / ) {
            iMSCP::Dir->new( dirname => "$::imscpConfig{'ROOT_DIR'}/$dir" )->remove();
        }

        iMSCP::Dir->new( dirname => $::{'INST_PREF'} )->rcopy( '/' );
        iMSCP::EventManager->getInstance()->trigger( 'afterSetupInstallFiles', $::{'INST_PREF'} );
    } catch {
        error( $_ );
        1;
    };
}

sub setupBoot
{
    iMSCP::Bootstrapper->getInstance()->boot( { config_readonly => TRUE } );
    untie( %::imscpOldConfig ) if %::imscpOldConfig;

    unless ( -f "$::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) {
        local $UMASK = 027;
        my $rs = iMSCP::File->new( filename => "$::imscpConfig{'CONF_DIR'}/imscp.conf" )->copyFile(
            "$::imscpConfig{'CONF_DIR'}/imscpOld.conf", { preserve => 'no' }
        );
        return $rs if $rs;
    }

    tie %::imscpOldConfig, 'iMSCP::Config', fileName => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf";
    0;
}

sub setupRegisterListeners
{
    my $em = iMSCP::EventManager->getInstance();

    for my $server ( iMSCP::Servers->getInstance()->getListWithFullNames() ) {
        next unless my $subref = $server->can( 'registerSetupListeners' );
        my $rs = $subref->( $server->factory(), $em );
        return $rs if $rs;
    }

    for my $package ( iMSCP::Packages->getInstance()->getListWithFullNames() ) {
        next unless my $subref = $package->can( 'registerSetupListeners' );
        my $rs = $subref->( $package->getInstance(), $em );
        return $rs if $rs;
    }

    0;
}

sub setupDialog
{
    my $dialogStack = [];

    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupDialog', $dialogStack );
    return $rs if $rs;

    # Implements a simple state machine (backup capability)
    # Any dialog subroutine *should* allow user to step back by returning 30 when 'back' button is pushed
    # In case of yesno dialog box, there is no back button. Instead, user can back up using the ESC keystroke
    # In any other context, the ESC keystroke allows user to abort.
    my ( $state, $nbDialog, $dialog ) = ( 0, scalar @{ $dialogStack }, iMSCP::Dialog->getInstance() );
    while ( $state < $nbDialog ) {
        $dialog->set( 'no-cancel', $state == 0 ? '' : undef );
        $rs = $dialogStack->[$state]->( $dialog );
        exit( $rs ) if $rs > 30;
        return $rs if $rs && $rs < 30;

        if ( $rs == 30 ) {
            iMSCP::Getopt->reconfigure( 'forced' ) if iMSCP::Getopt->reconfigure eq 'none';
            $state--;
            next;
        }

        iMSCP::Getopt->reconfigure( 'none' ) if iMSCP::Getopt->reconfigure eq 'forced';
        $state++;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupDialog' );
}

sub setupTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupTasks' );
    return $rs if $rs;

    my @steps = (
        [ \&setupSaveConfig, 'Saving configuration' ],
        [ \&setupCreateMasterUser, 'Creating system master user' ],
        [ \&setupCoreServices, 'Setup core services' ],
        [ \&setupRegisterPluginListeners, 'Registering plugin setup listeners' ],
        [ \&setupServersAndPackages, 'Processing servers/packages' ],
        [ \&setupSetPermissions, 'Setting up permissions' ],
        [ \&setupDbTasks, 'Processing DB tasks' ],
        [ \&setupRestartServices, 'Restarting services' ],
        [ \&setupRemoveOldConfig, 'Removing old configuration ' ]
    );

    my ( $step, $nbSteps ) = ( 1, scalar @steps );
    for ( @steps ) {
        $rs = step( @{ $_ }, $nbSteps, $step );
        last if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();
    $rs ||= iMSCP::EventManager->getInstance()->trigger( 'afterSetupTasks' );
}

sub setupDeleteBuildDir
{
    try {
        my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupDeleteBuildDir', $::{'INST_PREF'} );
        return $rs if $rs;

        iMSCP::Dir->new( dirname => $::{'INST_PREF'} )->remove();
        iMSCP::EventManager->getInstance()->trigger( 'afterSetupDeleteBuildDir', $::{'INST_PREF'} );
    } catch {
        error( $_ );
        1;
    };
}

sub setupSaveConfig
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupSaveConfig' );
    return $rs if $rs;

    # Re-open main configuration file in read/write mode
    iMSCP::Bootstrapper->getInstance()->loadMainConfig( {
        nocreate        => TRUE,
        nodeferring     => TRUE,
        config_readonly => FALSE
    } );

    while ( my ( $key, $value ) = each( %::questions ) ) {
        next unless exists $::imscpConfig{$key};
        $::imscpConfig{$key} = $value;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupSaveConfig' );
}

sub setupCreateMasterUser
{
    try {
        my $rs = iMSCP::SystemGroup->getInstance()->addSystemGroup( $::imscpConfig{'IMSCP_GROUP'} );
        $rs ||= iMSCP::SystemUser->new(
            username => $::imscpConfig{'IMSCP_USER'},
            group    => $::imscpConfig{'IMSCP_GROUP'},
            comment  => 'i-MSCP master user',
            home     => $::imscpConfig{'IMSCP_HOMEDIR'}
        )->addSystemUser();
        return $rs if $rs;

        # Ensure that correct permissions are set on i-MSCP master user homedir (handle upgrade case)
        iMSCP::Dir->new( dirname => $::imscpConfig{'IMSCP_HOMEDIR'} )->make( {
            user           => $::imscpConfig{'IMSCP_USER'},
            group          => $::imscpConfig{'IMSCP_GROUP'},
            mode           => 0755,
            fixpermissions => TRUE
        } );
    } catch {
        error( $_ );
        1;
    };
}

sub setupCoreServices
{
    my $serviceMngr = iMSCP::Service->getInstance();
    $serviceMngr->enable( $_ ) for 'imscp_daemon', 'imscp_traffic', 'imscp_mountall';
    0;
}

sub setupImportSqlSchema
{
    my ( $db, $file ) = @_;

    try {
        my $fileC = iMSCP::File->new( filename => $file )->getAsRef();
        return 1 unless defined $fileC;

        $db->getConnector()->run( fixup => sub {
            my ( $dbh ) = @_;
            $dbh->do( $_ ) for split /;\n/, ${ $fileC } =~ s/^(--[^\n]{0,})?\n//gmr;
        } );
        0;
    } catch {
        error( $_ );
        1;
    };
}

sub setupSetPermissions
{
    for my $script ( 'imscp-set-engine-permissions', 'imscp-set-gui-permissions' ) {
        startDetail();

        return 1 unless try {
            my $stderr;
            executeNoWait(
                [
                    '/usr/bin/perl', "$::imscpConfig{'ENGINE_ROOT_DIR'}/bin/$script",
                    '--setup',
                    ( iMSCP::Getopt->debug ? '--debug' : '' ),
                    ( iMSCP::Getopt->fixPermissions ? '--fix-permissions' : '' )
                ],
                ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : sub {
                    return unless $_[0] =~ /^(.*)\t(.*)\t(.*)/;
                    step( undef, $1, $2, $3 );
                } ),
                sub { $stderr .= $_[0]; }
            ) == 0 or die( $stderr );
            TRUE;
        } catch {
            error( sprintf( 'Error while setting permissions: %s', $_ || 'Unknown error' ));
            FALSE;
        } finally {
            endDetail();
        };
    }

    0;
}

sub setupDbTasks
{
    try {
        iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            my ( $dbh ) = @_;
            my $tables = {
                ssl_certs       => 'status',
                admin           => [ 'admin_status', "AND admin_type = 'user'" ],
                domain          => 'domain_status',
                subdomain       => 'subdomain_status',
                domain_aliasses => 'alias_status',
                subdomain_alias => 'subdomain_alias_status',
                domain_dns      => 'domain_dns_status',
                ftp_users       => 'status',
                mail_users      => 'status',
                htaccess        => 'status',
                htaccess_groups => 'status',
                htaccess_users  => 'status',
                server_ips      => 'ip_status'
            };

            while ( my ( $table, $field ) = each %{ $tables } ) {
                my $condition = '';
                ( $field, $condition ) = ( $field->[0], $field->[1] ) if ref $field eq 'ARRAY';
                ( $table, $field ) = ( $dbh->quote_identifier( $table ), $dbh->quote_identifier( $field ) );
                $dbh->do(
                    "
                        UPDATE $table
                        SET $field = 'tochange'
                        WHERE $field NOT IN('toadd', 'torestore', 'toenable', 'todisable', 'disabled', 'ordered', 'todelete') $condition
                    "
                );
                $dbh->do( "UPDATE $table SET $field = 'todisable' WHERE $field = 'disabled' $condition" );
            }

            $dbh->do(
                "
                    UPDATE plugin
                    SET plugin_status = 'tochange', plugin_error = NULL
                    WHERE plugin_status IN ('tochange', 'enabled') AND plugin_backend = 'yes'
                "
            );
        } );

        try {
            startDetail();
            iMSCP::DbTasksProcessor->getInstance()->processDbTasks();
            0;
        } catch {
            die $_;
        } finally {
            endDetail();
        };
    } catch {
        error( $_ );
        1;
    };
}

sub setupRegisterPluginListeners
{
    try {
        my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupRegisterPluginListeners' );
        return $rs if $rs;

        return 0 unless try {
            iMSCP::Database->factory()->useDatabase( setupGetQuestion( 'DATABASE_NAME' ));
            TRUE;
        } catch {
            # Assume a fresh install
            # FIXME: Assumptions are dangerous
            FALSE;
        };

        my $pluginNames = iMSCP::Database->factory()->getConnector()->run( fixup => sub {
            $_->selectcol_arrayref( "SELECT plugin_name FROM plugin WHERE plugin_status = 'enabled'" );
        } );

        if ( @{ $pluginNames } ) {
            my $em = iMSCP::EventManager->getInstance();
            my $plugins = iMSCP::Plugins->getInstance();
            for my $pluginName ( $plugins->getList() ) {
                next unless grep ( $_ eq $pluginName, @{ $pluginNames } );
                my $pluginClass = $plugins->getClass( $pluginName );
                ( my $subref = $pluginClass->can( 'registerSetupListeners' ) ) or next;
                $rs = $subref->( $pluginClass, $em );
                last if $rs;
            }
        }

        $rs ||= iMSCP::EventManager->getInstance()->trigger( 'afterSetupRegisterPluginListeners' );
    } catch {
        error( $_ );
        1;
    };
}

sub setupServersAndPackages
{
    my $em = iMSCP::EventManager->getInstance();
    my @servers = iMSCP::Servers->getInstance()->getListWithFullNames();
    my @packages = iMSCP::Packages->getInstance()->getListWithFullNames();
    my $countSteps = @servers+@packages;

    for my $task ( qw/ PreInstall Install PostInstall / ) {
        my $lcTask = lc( $task );

        startDetail();

        return 1 if try {
            my $rs = $em->trigger( 'beforeSetup' . $task . 'Servers' );
            return $rs if $rs;

            my $nStep = 1;
            for my $server ( @servers ) {
                ( my $subref = $server->can( $lcTask ) ) or $nStep++ && next;
                $rs = step( sub { $subref->( $server->factory()) }, sprintf( "Executing %s %s tasks...", $server, $lcTask ), $countSteps, $nStep );
                last if $rs;
                $nStep++;
            }

            return $rs if $rs;

            $rs = $em->trigger( 'afterSetup' . $task . 'Servers' );
            $rs ||= $em->trigger( 'beforeSetup' . $task . 'Packages' );
            return $rs if $rs;

            for my $package ( @packages ) {
                ( my $subref = $package->can( $lcTask ) ) or $nStep++ && next;
                $rs = step( sub { $subref->( $package->getInstance()) }, sprintf( "Executing %s %s tasks...", $package, $lcTask ), $countSteps, $nStep );
                last if $rs;
                $nStep++;
            }
            $rs ||= $em->trigger( 'afterSetup' . $task . 'Packages' );
        } catch {
            error( $_ );
            1;
        } finally {
            endDetail();
        };
    }

    0;
}

sub setupRestartServices
{
    my @services = ();
    my $em = iMSCP::EventManager->getInstance();

    # This is a bit annoying but we have not choice.
    # Not doing this would prevent propagation of upstream changes (eg: static mount entries)
    my $rs = $em->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] },
                [
                    sub {
                        iMSCP::Service->getInstance()->restart( 'imscp_mountall' );
                        0;
                    },
                    'i-MSCP mounts'
                ];
            0;
        },
        999
    );
    $rs ||= $em->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] },
                [
                    sub {
                        iMSCP::Service->getInstance()->restart( 'imscp_traffic' );
                        0;
                    },
                    'i-MSCP Traffic Logger'
                ];
            0;
        },
        99
    );
    $rs ||= $em->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] },
                [
                    sub {
                        iMSCP::Service->getInstance()->start( 'imscp_daemon' );
                        0;
                    },
                    'i-MSCP Daemon'
                ];
            0;
        },
        99
    );
    $rs ||= $em->trigger( 'beforeSetupRestartServices', \@services );
    return $rs if $rs;

    try {
        startDetail();

        my ( $nStep, $countSteps ) = ( 1, scalar @services );
        for my $service ( @services ) {
            $rs = step( $service->[0], sprintf( 'Restarting/Starting %s service...', $service->[1] ), $countSteps, $nStep );
            last if $rs;
            $nStep++;
        }

        $rs ||= $em->trigger( 'afterSetupRestartServices' );
    } catch {
        error( $_ );
        1;
    } finally {
        endDetail();
    };
}

sub setupRemoveOldConfig
{
    untie %::imscpOldConfig;
    iMSCP::File->new( filename => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf" )->delFile();
}

sub setupGetQuestion
{
    my ( $qname, $default ) = @_;
    $default //= '';

    if ( iMSCP::Getopt->preseed ) {
        return exists $::questions{$qname} && $::questions{$qname} ne '' ? $::questions{$qname} : $default;
    }

    exists $::questions{$qname}
        ? $::questions{$qname} : ( exists $::imscpConfig{$qname} && $::imscpConfig{$qname} ne '' ? $::imscpConfig{$qname} : $default );
}

sub setupSetQuestion
{
    $::questions{$_[0]} = $_[1];
}

1;
__END__
