#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by Laurent Declecq <l.declercq@nuxwin.com>
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
use iMSCP::Bootstrapper;
use iMSCP::Database;
use iMSCP::DbTasksProcessor;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::Execute qw/ executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Packages;
use iMSCP::Plugins;
use iMSCP::Servers;
use iMSCP::Service;
use iMSCP::Stepper;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Umask;

sub setupInstallFiles
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupInstallFiles', $main::{'INST_PREF'} );
    return $rs if $rs;

    if ( iMSCP::Service->getInstance()->hasService( 'imscp_daemon' ) ) {
        # i-MSCP daemon must be stopped before changing any file on the files system
        iMSCP::Service->getInstance()->stop( 'imscp_daemon' );
    }

    # Process cleanup to avoid any security risks and conflicts
    for( qw/ daemon engine gui / ) {
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/$_" )->remove();
    }

    iMSCP::Dir->new( dirname => $main::{'INST_PREF'} )->rcopy( '/' );
    iMSCP::EventManager->getInstance()->trigger( 'afterSetupInstallFiles', $main::{'INST_PREF'} );
}

sub setupBoot
{
    iMSCP::Bootstrapper->getInstance()->boot(
        {
            mode            => 'setup', # Backend mode
            config_readonly => 1, # We do not allow writing in conffile at this time
            nodatabase      => 1 # We do not establish connection to the database at this time
        }
    );

    untie( %main::imscpOldConfig ) if %main::imscpOldConfig;

    unless ( -f "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf" ) {
        local $UMASK = 027;
        my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf" )->copyFile(
            "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf", { preserve => 'no' }
        );
        return $rs if $rs;
    }

    tie %main::imscpOldConfig, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf";

    0;
}

sub setupRegisterListeners
{
    my $eventManager = iMSCP::EventManager->getInstance();

    for ( iMSCP::Servers->getInstance()->getListWithFullNames() ) {
        ( my $subref = $_->can( 'registerSetupListeners' ) ) or next;
        my $rs = $subref->( $_->factory(), $eventManager );
        return $rs if $rs;
    }

    for ( iMSCP::Packages->getInstance()->getListWithFullNames() ) {
        ( my $subref = $_->can( 'registerSetupListeners' ) ) or next;
        my $rs = $subref->( $_->getInstance(), $eventManager );
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
    my ($state, $nbDialog, $dialog) = ( 0, scalar @{$dialogStack}, iMSCP::Dialog->getInstance() );
    while ( $state < $nbDialog ) {
        $dialog->set( 'no-cancel', $state == 0 ? '' : undef );

        $rs = $dialogStack->[$state]->( $dialog );
        exit( $rs ) if $rs > 30;
        return $rs if $rs && $rs < 30;

        if ( $rs == 30 ) {
            $main::reconfigure = 'forced' if $main::reconfigure eq 'none';
            $state--;
            next;
        }

        $main::reconfigure = 'none' if $main::reconfigure eq 'forced';
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

    my $step = 1;
    my $nbSteps = @steps;

    for ( @steps ) {
        $rs = step( @{$_}, $nbSteps, $step );
        last if $rs;
        $step++;
    }

    iMSCP::Dialog->getInstance()->endGauge();
    $rs ||= iMSCP::EventManager->getInstance()->trigger( 'afterSetupTasks' );
}

sub setupDeleteBuildDir
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupDeleteBuildDir', $main::{'INST_PREF'} );
    return $rs if $rs;

    iMSCP::Dir->new( dirname => $main::{'INST_PREF'} )->remove();
    iMSCP::EventManager->getInstance()->trigger( 'afterSetupDeleteBuildDir', $main::{'INST_PREF'} );
}

#
## Setup subroutines
#

sub setupSaveConfig
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupSaveConfig' );
    return $rs if $rs;

    # Re-open main configuration file in read/write mode
    iMSCP::Bootstrapper->getInstance()->loadMainConfig(
        {
            nocreate        => 1,
            nodeferring     => 1,
            config_readonly => 0
        }
    );

    while ( my ($key, $value) = each( %main::questions ) ) {
        next unless exists $main::imscpConfig{$key};
        $main::imscpConfig{$key} = $value;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupSaveConfig' );
}

sub setupCreateMasterUser
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupCreateMasterUser' );

    $rs ||= iMSCP::SystemGroup->getInstance()->addSystemGroup( $main::imscpConfig{'IMSCP_GROUP'} );
    $rs ||= iMSCP::SystemUser->new(
        username => $main::imscpConfig{'IMSCP_USER'},
        group    => $main::imscpConfig{'IMSCP_GROUP'},
        comment  => 'i-MSCP master user',
        home     => $main::imscpConfig{'IMSCP_HOMEDIR'}
    )->addSystemUser();
    return $rs if $rs;

    # Ensure that correct permissions are set on i-MSCP master user homedir (handle upgrade case)
    iMSCP::Dir->new( dirname => $main::imscpConfig{'IMSCP_HOMEDIR'} )->make(
        {
            user           => $main::imscpConfig{'IMSCP_USER'},
            group          => $main::imscpConfig{'IMSCP_GROUP'},
            mode           => 0755,
            fixpermissions => 1 # We fix permissions in any case
        }
    );
    iMSCP::EventManager->getInstance()->trigger( 'afterSetupCreateMasterUser' );
}

sub setupCoreServices
{
    my $serviceMngr = iMSCP::Service->getInstance();
    $serviceMngr->enable( $_ ) for 'imscp_daemon', 'imscp_traffic', 'imscp_mountall';
    0;
}

sub setupImportSqlSchema
{
    my ($db, $file) = @_;

    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupImportSqlSchema', \$file );
    return $rs if $rs;

    my $content = iMSCP::File->new( filename => $file )->get();
    unless ( defined $content ) {
        error( sprintf( "Couldn't read %s file", $file ));
        return 1;
    }

    local $@;
    eval {
        my $dbh = $db->getRawDb();
        local $dbh->{'RaiseError'} = 1;
        $dbh->do( $_ ) for split /;\n/, $content =~ s/^(--[^\n]{0,})?\n//gmr;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    iMSCP::EventManager->getInstance()->trigger( 'afterSetupImportSqlSchema' );
}

sub setupSetPermissions
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupSetPermissions' );
    return $rs if $rs;

    for my $script ( 'set-engine-permissions.pl', 'set-gui-permissions.pl' ) {
        startDetail();

        my @options = (
            '--setup',
            ( iMSCP::Getopt->debug ? '--debug' : '' ),
            ( $script eq 'set-engine-permissions.pl' && iMSCP::Getopt->fixPermissions ? '--fix-permissions' : '' )
        );

        my $stderr;
        $rs = executeNoWait(
            [ 'perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script", @options ],
            ( iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose
                ? undef
                : sub {
                    return unless ( shift ) =~ /^(.*)\t(.*)\t(.*)/;
                    step( undef, $1, $2, $3 );
                }
            ),
            sub { $stderr .= shift; }
        );

        endDetail();

        if ( $rs ) {
            error( sprintf( 'Error while setting permissions: %s', $stderr || 'Unknown error' ));
            last;
        }
    }

    $rs |= iMSCP::EventManager->getInstance()->trigger( 'afterSetupSetPermissions' );
}

sub setupDbTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupDbTasks' );
    return $rs if $rs;

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
            my $aditionalCondition;

            my $db = iMSCP::Database->factory();
            my $oldDbName = $db->useDatabase( setupGetQuestion( 'DATABASE_NAME' ));

            my $dbh = $db->getRawDb();
            local $dbh->{'RaiseError'};

            while ( my ($table, $field) = each %{$tables} ) {
                if ( ref $field eq 'ARRAY' ) {
                    $aditionalCondition = $field->[1];
                    $field = $field->[0];
                } else {
                    $aditionalCondition = ''
                }

                ( $table, $field ) = ( $dbh->quote_identifier( $table ), $dbh->quote_identifier( $field ) );
                $dbh->do(
                    "
                        UPDATE $table
                        SET $field = 'tochange'
                        WHERE $field NOT IN(
                            'toadd', 'torestore', 'toenable', 'todisable', 'disabled', 'ordered', 'todelete'
                        )
                        $aditionalCondition
                    "
                );
                $dbh->do( "UPDATE $table SET $field = 'todisable' WHERE $field = 'disabled' $aditionalCondition" );
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
        iMSCP::DbTasksProcessor->getInstance( mode => 'setup' )->processDbTasks();
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
    my $rs = iMSCP::EventManager->getInstance()->trigger( 'beforeSetupRegisterPluginListeners' );
    return $rs if $rs;

    my ($db, $pluginNames) = ( iMSCP::Database->factory(), undef );

    local $@;

    my $oldDbName = eval { $db->useDatabase( setupGetQuestion( 'DATABASE_NAME' )); };
    return 0 if $@; # Fresh install case

    eval {
        my $dbh = $db->getRawDb();
        $dbh->{'RaiseError'} = 1;
        $pluginNames = $dbh->selectcol_arrayref( "SELECT plugin_name FROM plugin WHERE plugin_status = 'enabled'" );
        $db->useDatabase( $oldDbName ) if $oldDbName;
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    if ( @{$pluginNames} ) {
        my $eventManager = iMSCP::EventManager->getInstance();
        my $plugins = iMSCP::Plugins->getInstance();

        for my $pluginName( $plugins->getList() ) {
            next unless grep( $_ eq $pluginName, @{$pluginNames} );
            my $pluginClass = $plugins->getClass( $pluginName );
            ( my $subref = $pluginClass->can( 'registerSetupListeners' ) ) or next;
            $rs = $subref->( $pluginClass, $eventManager );
            last if $rs;
        }
    }

    $rs ||= iMSCP::EventManager->getInstance()->trigger( 'afterSetupRegisterPluginListeners' );
}

sub setupServersAndPackages
{
    my $eventManager = iMSCP::EventManager->getInstance();
    my @servers = iMSCP::Servers->getInstance()->getListWithFullNames();
    my @packages = iMSCP::Packages->getInstance()->getListWithFullNames();
    my $nSteps = @servers+@packages;
    my $rs = 0;

    for my $task( qw/ PreInstall Install PostInstall / ) {
        my $lcTask = lc( $task );

        $rs ||= $eventManager->trigger( 'beforeSetup' . $task . 'Servers' );
        return $rs if $rs;

        startDetail();
        my $nStep = 1;

        for ( @servers ) {
            ( my $subref = $_->can( $lcTask ) ) or $nStep++ && next;
            $rs = step(
                sub { $subref->( $_->factory()) }, sprintf( "Executing %s %s tasks...", $_, $lcTask ), $nSteps, $nStep
            );
            last if $rs;
            $nStep++;
        }

        unless ( $rs ) {
            $rs = $eventManager->trigger( 'afterSetup' . $task . 'Servers' );
            $rs ||= $eventManager->trigger( 'beforeSetup' . $task . 'Packages' );

            unless ( $rs ) {
                for ( @packages ) {
                    ( my $subref = $_->can( $lcTask ) ) or $nStep++ && next;
                    $rs = step(
                        sub { $subref->( $_->getInstance()) },
                        sprintf( "Executing %s %s tasks...", $_, $lcTask ), $nSteps, $nStep
                    );
                    last if $rs;
                    $nStep++;
                }
            }
        }

        endDetail();
        $rs ||= $eventManager->trigger( 'afterSetup' . $task . 'Packages' );
        last if $rs;
    }

    $rs;
}

sub setupRestartServices
{
    my @services = ();
    my $eventManager = iMSCP::EventManager->getInstance();

    # This is a bit annoying but we have not choice.
    # Not doing this would prevent propagation of upstream changes (eg: static mount entries)
    my $rs = $eventManager->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
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

    $rs ||= $eventManager->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
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
    $rs ||= $eventManager->register(
        'beforeSetupRestartServices',
        sub {
            push @{$_[0]},
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
    $rs ||= $eventManager->trigger( 'beforeSetupRestartServices', \@services );
    return $rs if $rs;

    startDetail();

    my $nbSteps = @services;
    my $step = 1;

    for ( @services ) {
        $rs = step( $_->[0], sprintf( 'Restarting/Starting %s service...', $_->[1] ), $nbSteps, $step );
        last if $rs;
        $step++;
    }

    endDetail();

    $rs ||= $eventManager->trigger( 'afterSetupRestartServices' );
}

sub setupRemoveOldConfig
{
    untie %main::imscpOldConfig;
    iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf" )->delFile();
}

sub setupGetQuestion
{
    my ($qname, $default) = @_;
    $default //= '';

    if ( iMSCP::Getopt->preseed ) {
        return exists $main::questions{$qname} && $main::questions{$qname} ne '' ? $main::questions{$qname} : $default;
    }

    exists $main::questions{$qname}
        ? $main::questions{$qname}
        : ( exists $main::imscpConfig{$qname} && $main::imscpConfig{$qname} ne '' ? $main::imscpConfig{$qname} : $default );
}

sub setupSetQuestion
{
    $main::questions{$_[0]} = $_[1];
}

1;
__END__
