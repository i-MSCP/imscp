#!/usr/bin/perl

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
use iMSCP::Stepper qw/ step endDetail startDetail /;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Umask '$UMASK';

sub setupInstallFiles
{
    local $@;
    my $rs = eval {
        my $rs = iMSCP::EventManager->getInstance()->trigger(
            'beforeSetupInstallFiles', $::{'INST_PREF'}
        );
        return $rs if $rs;

        if ( iMSCP::Service->getInstance()->hasService( 'imscp_daemon' ) ) {
            iMSCP::Service->getInstance()->stop( 'imscp_daemon' );
        }

        # Process cleanup to avoid any security risks and conflicts
        for my $service ( qw/ daemon engine gui / ) {
            iMSCP::Dir->new(
                dirname => "$::imscpConfig{'ROOT_DIR'}/$service"
            )->remove();
        }

        iMSCP::Dir->new( dirname => $::{'INST_PREF'} )->rcopy( '/' );

        iMSCP::EventManager->getInstance()->trigger(
            'afterSetupInstallFiles', $::{'INST_PREF'}
        );
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

sub setupBoot
{
    iMSCP::Bootstrapper->getInstance()->boot( {
        # Backend mode
        mode            => 'setup',
        # We do not allow writing in conffile at this time
        config_readonly => TRUE,
        # We do not establish connection to the database at this time
        nodatabase      => TRUE
    } );

    untie( %::imscpOldConfig ) if %::imscpOldConfig;

    unless ( -f "$::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) {
        local $UMASK = 027;
        my $rs = iMSCP::File->new(
            filename => "$::imscpConfig{'CONF_DIR'}/imscp.conf"
        )->copyFile(
            "$::imscpConfig{'CONF_DIR'}/imscpOld.conf", { preserve => 'no' }
        );
        return $rs if $rs;
    }

    local $@;
    eval {
        tie %::imscpOldConfig, 'iMSCP::Config',
            fileName => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf";
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

sub setupRegisterListeners
{
    my $events = iMSCP::EventManager->getInstance();

    for my $server ( iMSCP::Servers->getInstance()->getList() ) {
        ( my $sub = $server->can( 'registerSetupListeners' ) ) or next;
        my $rs = $sub->( $server->factory(), $events );
        return $rs if $rs;
    }

    for my $package ( iMSCP::Packages->getInstance()->getList() ) {
        ( my $sub = $package->can( 'registerSetupListeners' ) ) or next;
        my $rs = $sub->( $package->getInstance(), $events );
        return $rs if $rs;
    }

    0;
}

sub setupDialog
{
    my $dialogStack = [];

    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupDialog', $dialogStack
    );
    return $rs if $rs;

    # Implements a simple state machine (backup capability)
    # Any dialog subroutine *SHOULD* allow to step back by returning 30 when
    # 'back' button is pushed.  In case of a 'yesno' dialog box, there is no
    # back button. Instead, user can back up using the ESC keystroke
    # In any other context, the ESC keystroke allows user to abort.
    my ( $state, $nbDialog, $dialog ) = (
        0, scalar @{ $dialogStack }, iMSCP::Dialog->getInstance()
    );
    while ( $state < $nbDialog ) {
        $dialog->set( 'no-cancel', $state == 0 ? '' : undef );
        $rs = $dialogStack->[$state]->( $dialog );
        exit( $rs ) if $rs > 30;
        return $rs if $rs && $rs < 30;

        if ( $rs == 30 ) {
            $::reconfigure = 'forced' if $::reconfigure eq 'none';
            $state--;
            next;
        }

        $::reconfigure = 'none' if $::reconfigure eq 'forced';
        $state++;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupDialog' );
}

sub setupTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupTasks' );
    return $rs if $rs;

    my @steps = (
        [
            \&setupSaveConfig,
            'Saving configuration'
        ],
        [
            \&setupCreateMasterUser,
            'Creating system master user'
        ],
        [
            \&setupCoreServices,
            'Setup core services'
        ],
        [
            \&setupRegisterPluginListeners,
            'Registering plugin setup listeners'
        ],
        [
            \&setupServersAndPackages,
            'Processing servers/packages'
        ],
        [
            \&setupSetPermissions,
            'Setting up permissions'
        ],
        [
            \&setupDbTasks,
            'Processing DB tasks'
        ],
        [
            \&setupRestartServices,
            'Restarting services'
        ],
        [
            \&setupRemoveOldConfig,
            'Removing old configuration'
        ]
    );

    my $step = 1;
    my $nbSteps = @steps;
    for my $task ( @steps ) {
        $rs = step( @{ $task }, $nbSteps, $step );
        last if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();

    $rs ||= iMSCP::EventManager->getInstance()->trigger( 'afterSetupTasks' );
}

sub setupDeleteBuildDir
{
    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupDeleteBuildDir', $::{'INST_PREF'}
    );
    return $rs if $rs;

    eval {
        iMSCP::Dir->new( dirname => $::{'INST_PREF'} )->remove();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    iMSCP::EventManager->getInstance()->trigger(
        'afterSetupDeleteBuildDir', $::{'INST_PREF'}
    );
}

sub setupSaveConfig
{
    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupSaveConfig'
    );
    return $rs if $rs;

    local $@;
    eval {
        # Re-open main configuration file in read/write mode
        iMSCP::Bootstrapper->getInstance()->loadMainConfig( {
            nocreate        => TRUE,
            nodeferring     => TRUE,
            config_readonly => FALSE
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    while ( my ( $key, $value ) = each( %::questions ) ) {
        next unless exists $::imscpConfig{$key};
        $::imscpConfig{$key} = $value;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupSaveConfig' );
}

sub setupCreateMasterUser
{
    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupCreateMasterUser'
    );
    $rs ||= iMSCP::SystemGroup->getInstance()->addSystemGroup(
        $::imscpConfig{'IMSCP_GROUP'}
    );
    $rs ||= iMSCP::SystemUser->new(
        username => $::imscpConfig{'IMSCP_USER'},
        group    => $::imscpConfig{'IMSCP_GROUP'},
        comment  => 'i-MSCP master user',
        home     => $::imscpConfig{'IMSCP_HOMEDIR'}
    )->addSystemUser();
    return $rs if $rs;

    local $@;
    eval {
        # Ensure that correct permissions are set on i-MSCP master user homedir
        # (handle upgrade case)
        iMSCP::Dir->new( dirname => $::imscpConfig{'IMSCP_HOMEDIR'} )->make( {
            user           => $::imscpConfig{'IMSCP_USER'},
            group          => $::imscpConfig{'IMSCP_GROUP'},
            mode           => 0755,
            fixpermissions => TRUE
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    iMSCP::EventManager->getInstance()->trigger(
        'afterSetupCreateMasterUser'
    );
}

sub setupCoreServices
{
    local $@;
    eval {
        my $serviceMngr = iMSCP::Service->getInstance();
        for my $service ( qw/ imscp_daemon imscp_traffic imscp_mountall / ) {
            $serviceMngr->enable( $service );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

sub setupSetPermissions
{
    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupSetPermissions'
    );
    return $rs if $rs;

    for my $script ( qw/ set-engine-permissions.pl set-gui-permissions.pl / ) {
        startDetail();

        my @options = (
            '--setup',
            ( iMSCP::Getopt->debug ? '--debug' : '' ),
            ( $script eq 'set-engine-permissions.pl' && iMSCP::Getopt->fixPermissions
                ? '--fix-permissions' : ''
            )
        );

        my $stderr;
        $rs = executeNoWait(
            [
                '/usr/bin/perl', "$::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script",
                @options
            ],
            ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                ? undef
                : sub {
                return unless $_[0] =~ /^(.*)\t(.*)\t(.*)/;
                step( undef, "$1", "$2", "$3" );
            } ),
            sub { $stderr .= $_[0]; }
        );

        endDetail();

        if ( $rs ) {
            error( sprintf(
                'Error while setting permissions: %s',
                $stderr || 'Unknown error'
            ));
            last;
        }
    }

    $rs |= iMSCP::EventManager->getInstance()->trigger(
        'afterSetupSetPermissions'
    );
}

sub setupDbTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupDbTasks'
    );
    return $rs if $rs;

    local $@;
    eval {
        {
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
            my $additionalCondition;

            my $db = iMSCP::Database->factory();
            my $oldDbName = $db->useDatabase( setupGetQuestion( 'DATABASE_NAME' ));

            my $dbh = $db->getRawDb();
            local $dbh->{'RaiseError'} = TRUE;

            while ( my ( $table, $field ) = each %{ $tables } ) {
                if ( ref $field eq 'ARRAY' ) {
                    $additionalCondition = $field->[1];
                    $field = $field->[0];
                } else {
                    $additionalCondition = ''
                }

                ( $table, $field ) = (
                    $dbh->quote_identifier( $table ),
                    $dbh->quote_identifier( $field )
                );
                $dbh->do(
                    "
                        UPDATE $table
                        SET $field = 'tochange'
                        WHERE $field NOT IN(
                            'toadd', 'torestore', 'toenable', 'todisable',
                            'disabled', 'ordered', 'todelete'
                        )
                        $additionalCondition
                    "
                );
                $dbh->do(
                    "
                        UPDATE $table
                        SET $field = 'todisable'
                        WHERE $field = 'disabled'
                        $additionalCondition
                    "
                );
            }

            $dbh->do(
                "
                    UPDATE plugin
                    SET plugin_status = 'tochange', plugin_error = NULL
                    WHERE plugin_status IN ('tochange', 'enabled')
                    AND plugin_backend = 'yes'
                "
            );

            $db->useDatabase( $oldDbName ) if $oldDbName;
        }

        startDetail();
        iMSCP::DbTasksProcessor
            ->getInstance( mode => 'setup' )
            ->processDbTasks();
        endDetail();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupDbTasks' );
}

sub setupRegisterPluginListeners
{
    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupRegisterPluginListeners'
    );
    return $rs if $rs;

    my ( $db, $pluginNames ) = ( iMSCP::Database->factory(), undef );

    local $@;

    my $oldDbName = eval {
        $db->useDatabase( setupGetQuestion( 'DATABASE_NAME' ));
    };
    return 0 if $@; # Fresh install case

    eval {
        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = TRUE;
        $pluginNames = $dbh->selectcol_arrayref(
            "SELECT plugin_name FROM plugin WHERE plugin_status = 'enabled'"
        );
        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    if ( @{ $pluginNames } ) {
        my $events = iMSCP::EventManager->getInstance();
        my $plugins = iMSCP::Plugins->getInstance();

        for my $pluginName ( $plugins->getList() ) {
            next unless grep ( $_ eq $pluginName, @{ $pluginNames } );
            my $pluginClass = $plugins->getClass( $pluginName );
            ( my $sub = $pluginClass->can(
                'registerSetupListeners'
            ) ) or next;
            $rs = $sub->( $pluginClass, $events );
            last if $rs;
        }
    }

    $rs ||= iMSCP::EventManager->getInstance()->trigger(
        'afterSetupRegisterPluginListeners'
    );
}

sub setupServersAndPackages
{
    my $events = iMSCP::EventManager->getInstance();

    my $rs = $events->trigger( 'beforeSetupServersAndPackages' );
    return $rs if $rs;

    my @servers = iMSCP::Servers->getInstance()->getList();
    my @packages = iMSCP::Packages->getInstance()->getList();
    my $nSteps = @servers+@packages;

    for my $task ( qw/ PreInstall Install PostInstall / ) {
        my $lcTask = lc( $task );

        $rs ||= $events->trigger( 'beforeSetup' . $task . 'Servers' );
        return $rs if $rs;

        startDetail();
        my $nStep = 1;

        for my $server ( @servers ) {
            ( my $sub = $server->can( $lcTask ) ) or $nStep++ && next;
            $rs = step(
                sub { $sub->( $server->factory()) },
                sprintf( "Executing %s %s tasks...", $server, $lcTask ),
                $nSteps,
                $nStep
            );
            last if $rs;
            $nStep++;
        }

        unless ( $rs ) {
            $rs = $events->trigger( 'afterSetup' . $task . 'Servers' );
            $rs ||= $events->trigger( 'beforeSetup' . $task . 'Packages' );

            unless ( $rs ) {
                for my $package ( @packages ) {
                    ( my $sub = $package->can( $lcTask ) ) or $nStep++ && next;
                    $rs = step(
                        sub { $sub->( $package->getInstance()) },
                        sprintf( "Executing %s %s tasks...", $package, $lcTask ),
                        $nSteps,
                        $nStep
                    );
                    last if $rs;
                    $nStep++;
                }
            }
        }

        endDetail();
        $rs ||= $events->trigger( 'afterSetup' . $task . 'Packages' );
        last if $rs;
    }

    $rs ||= $events->trigger( 'afterSetupServersAndPackages' );
}

sub setupRestartServices
{
    my @services = ();
    my $events = iMSCP::EventManager->getInstance();

    # This is a bit annoying but we have not choice.
    # Not doing this would prevent propagation of upstream changes (eg: static
    # mount entries)
    my $rs = $events->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [
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

    $rs ||= $events->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [
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
    $rs ||= $events->register(
        'beforeSetupRestartServices',
        sub {
            push @{ $_[0] }, [
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
    $rs ||= $events->trigger( 'beforeSetupRestartServices', \@services );
    return $rs if $rs;

    startDetail();

    my $nbSteps = @services;
    my $step = 1;

    for my $service ( @services ) {
        $rs = step(
            $service->[0],
            sprintf( 'Restarting/Starting %s service...', $service->[1] ),
            $nbSteps,
            $step
        );
        last if $rs;
        $step++;
    }

    endDetail();

    $rs ||= $events->trigger( 'afterSetupRestartServices' );
}

sub setupRemoveOldConfig
{
    untie %::imscpOldConfig;

    iMSCP::File->new(
        filename => "$::imscpConfig{'CONF_DIR'}/imscpOld.conf"
    )->delFile();
}

sub setupGetQuestion
{
    my ( $qname, $default ) = @_;
    $default //= '';

    if ( iMSCP::Getopt->preseed ) {
        return exists $::questions{$qname} && $::questions{$qname} ne ''
            ? $::questions{$qname} : $default;
    }

    exists $::questions{$qname}
        ? $::questions{$qname}
        : ( exists $::imscpConfig{$qname} && $::imscpConfig{$qname} ne ''
        ? $::imscpConfig{$qname} : $default );
}

sub setupSetQuestion
{
    $::questions{$_[0]} = $_[1];
}

1;
__END__
