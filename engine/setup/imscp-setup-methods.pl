#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 by internet Multi Server Control Panel
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
use FindBin;
use DateTime;
use DateTime::TimeZone;
use Net::LibIDN qw/idn_to_ascii idn_to_unicode/;
use Data::Validate::Domain qw/is_domain/;
use Scalar::Util qw(openhandle);
use File::Basename;
use iMSCP::Debug;
use iMSCP::Net;
use iMSCP::Bootstrapper;
use iMSCP::Dialog;
use iMSCP::Stepper;
use iMSCP::Crypt qw/encryptBlowfishCBC decryptBlowfishCBC/;
use iMSCP::Database;
use iMSCP::DbTasksProcessor;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::EventManager;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::OpenSSL;
use iMSCP::Servers;
use iMSCP::Packages;
use iMSCP::Plugins;
use iMSCP::Getopt;
use iMSCP::Service;
use Servers::sqld;

# Boot
sub setupBoot
{
    # We do not try to establish connection to the database since needed data can be unavailable
    iMSCP::Bootstrapper->getInstance()->boot({ mode => 'setup', nodatabase => 'yes' });

    return 0 if %main::imscpOldConfig;

    %main::imscpOldConfig = ();
    my $oldConfig = "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf";
    tie %main::imscpOldConfig, 'iMSCP::Config', fileName => $oldConfig, readonly => 1 if -f $oldConfig;
    0;
}

# Set server capabilities
# Currently used for detecting IPv6 support
sub setServerCapabilities
{
    # FIXME: It is sufficient for check of IPv6 support?
    $main::imscpConfig{'IPV6_SUPPORT'} = -f '/proc/net/if_inet6' ? 1 : 0;
    0;
}

# Allow any server/package to register its setup event listeners before any other task
sub setupRegisterListeners
{
    my ($eventManager, $rs) = (iMSCP::EventManager->getInstance(), 0);

    for(iMSCP::Servers->getInstance()->get()) {
        next if $_ eq 'noserver';
        my $server = "Servers::$_";
        eval "require $server";
        unless($@) {
            my $instance = $server->factory();
            $rs = $instance->registerSetupListeners($eventManager) if $instance->can('registerSetupListeners');
            return $rs if $rs;
            next;
        }

        error($@);
        return 1;
    }

    for(iMSCP::Packages->getInstance()->get()) {
        my $package = "Package::$_";
        eval "require $package";
        unless($@) {
            my $instance = $package->getInstance();
            $rs = $instance->registerSetupListeners($eventManager) if $instance->can('registerSetupListeners');
            return $rs if $rs;
            next;
        }

        error($@);
        return 1;
    }

    $rs;
}

# Trigger all dialog subroutines
sub setupDialog
{
    my $dialogStack = [];

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupDialog', $dialogStack);
    return $rs if $rs;

    unshift(@{$dialogStack}, (
        \&setupAskServerHostname,
        \&setupAskServerIps,
        \&askMasterSqlUser,
        \&setupAskSqlUserHost,
        \&setupAskImscpDbName,
        \&setupAskDbPrefixSuffix,
        \&setupAskTimezone,
        \&setupAskServicesSsl,
        \&setupAskImscpBackup,
        \&setupAskDomainBackup
    ));

    my $dialog = iMSCP::Dialog->getInstance();
    $dialog->set('ok-label', 'Ok');
    $dialog->set('yes-label', 'Yes');
    $dialog->set('no-label', 'No');
    $dialog->set('cancel-label', 'Back');

    # Implements a simple state machine (backup capability)
    # Any dialog subroutine *should* allow user to step back by returning 30 when 'back' button is pushed
    # In case of yesno dialog box, there is no back button. Instead, user can back up using the ESC keystroke
    # In any other context, the ESC keystroke allows user to abort.
    my ($state, $nbDialog) = (0, scalar @{$dialogStack});

    while($state != $nbDialog) {
        $rs = $dialogStack->[$state]->($dialog);
        exit($rs) if $rs > 30;
        return $rs if $rs && $rs < 30;

        # User asked for step back?
        if($rs == 30) {
            if($state > 0) {
                $state = $state - 1;
            } else {
                $state = 0; # We don't allow to step back before first question
            }

            $main::reconfigure = 'forced' if $main::reconfigure eq 'none';
        } else {
            $main::reconfigure = 'none' if $main::reconfigure eq 'forced';
            $state++;
        }
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupDialog');
}

# Process setup tasks
sub setupTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupTasks');
    return $rs if $rs;

    my @steps = (
        [ \&setupSaveOldConfig,              'Saving old configuration file' ],
        [ \&setupWriteNewConfig,             'Writing new configuration file' ],
        [ \&setupCreateMasterUser,           'Creating system master user' ],
        [ \&setupCreateSystemDirectories,    'Creating system directories' ],
        [ \&setupServerHostname,             'Setting server hostname' ],
        [ \&setupServiceSsl,                 'Setup SSL for i-MSCP services' ],
        [ \&setupServices,                   'Setup i-MSCP services' ],
        [ \&setupRegisterDelayedTasks,       'Register delayed tasks' ],
        [ \&setupRegisterPluginListeners,    'Register plugin setup listeners' ],
        [ \&setupPreInstallServers,          'Servers pre-installation' ],
        [ \&setupPreInstallPackages,         'Packages pre-installation' ],
        [ \&setupInstallServers,             'Servers installation' ],
        [ \&setupInstallPackages,            'Packages installation' ],
        [ \&setupPostInstallServers,         'Servers post-installation' ],
        [ \&setupPostInstallPackages,        'Packages post-installation' ],
        [ \&setupSetPermissions,             'Setting permissions' ],
        [ \&setupRebuildCustomerFiles,       'Rebuilding customers files' ],
        [ \&setupRestartServices,            'Restarting services' ]
    );

    my $step = 1;
    my $nbSteps = @steps;
    for (@steps) {
        $rs = step($_->[0], $_->[1], $nbSteps, $step);
        return $rs if $rs;
        $step++;
    }
    
    iMSCP::Dialog->getInstance()->endGauge() if iMSCP::Getopt->noprompt;

    iMSCP::EventManager->getInstance()->trigger('afterSetupTasks');
}

#
## Dialog subroutines
#

# Ask for server hostname
sub setupAskServerHostname
{
    my $dialog = shift;

    my $hostname = setupGetQuestion('SERVER_HOSTNAME');
    my $options = { domain_private_tld => qr /.*/ };
    my ($rs, $msg) = (0, '');

    if($main::reconfigure =~ /^system_hostname|hostnames|all|forced$/
        || split(/\./, $hostname) < 3 || !is_domain($hostname, $options)
    ) {
        chomp($hostname) unless($hostname || execute('hostname -f', \$hostname, \my $stderr));
        $hostname = idn_to_unicode($hostname, 'utf-8');

        do {
            ($rs, $hostname) = $dialog->inputbox(<<"EOF", $hostname);

Please enter a fully-qualified hostname for the server:$msg
EOF
            $msg = "\n\n\\Z1'$hostname' is not a valid fully-qualified host name.\\Zn\n\nPlease try again:";
        } while($rs < 30 && (split(/\./, $hostname) < 3 || !is_domain(idn_to_ascii($hostname, 'utf-8'), $options)));
    }

    setupSetQuestion('SERVER_HOSTNAME', idn_to_ascii($hostname, 'utf-8')) if $rs < 30;
    $rs;
}

# Ask for server ips
sub setupAskServerIps
{
    my $dialog = shift;

    my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
    my $baseServerPublicIp = setupGetQuestion('BASE_SERVER_PUBLIC_IP');
    my $serverIps = '';
    my $serverIpsToAdd = setupGetQuestion('SERVER_IPS', []);
    my %serverIpsToDelete = ();
    my %serverIpsReplMap = ();
    my $net = iMSCP::Net->getInstance();
    my $rs = 0;

    # Retrieve list of all configured IP addresses
    my @serverIps = grep {
        $net->getAddrType($_) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/
    } $net->getAddresses();
    unless(@serverIps) {
        error('Could not retrieve server IP addresses. At least one public or private IP adddress must be configured.');
        return 1;
    }

    my $currentServerIps = { };
    my $db = '';
    my $msg = '';

    if(setupGetQuestion('DATABASE_NAME')) {
        my $db = iMSCP::Database->factory();

        local $@;
        eval { $db->useDatabase(setupGetQuestion('DATABASE_NAME')); };
        unless($@) {
            $currentServerIps = $db->doQuery('ip_number', 'SELECT ip_id, ip_number FROM server_ips');
            unless(ref $currentServerIps eq 'HASH') {
                error(sprintf('Could not retrieve server IP addresses: %s', $currentServerIps));
                return 1
            }
        }

        @{$serverIpsToAdd} = (@{$serverIpsToAdd}, keys %{$currentServerIps});
    }

    @serverIps = sort keys %{ { map { $_ => 1 } @serverIps, @{$serverIpsToAdd} } };

    if($main::reconfigure =~ /^ips|all|forced$/
        || !grep($_ eq $baseServerIp, @serverIps)
        || !$net->isValidAddr($baseServerPublicIp)
        || $net->getAddrType($baseServerPublicIp) !~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)$/
    ) {
        do {
            # Ask user for the base server IP
            ($rs, $baseServerIp) = $dialog->radiolist(
                <<"EOF", [ @serverIps ], $baseServerIp && grep($_ eq $baseServerIp, @serverIps) ? $baseServerIp : $serverIps[0]);

Please, select the primary IP address:
EOF

        } while($rs < 30 && !$baseServerIp);

        if($rs < 30) {
            # Server inside private LAN?
            if($net->getAddrType($baseServerIp) =~ /^(?:PRIVATE|UNIQUE-LOCAL-UNICAST)$/) {
                if (!$net->isValidAddr($baseServerPublicIp)
                    || $net->getAddrType($baseServerPublicIp) !~ /^(?:PUBLIC|GLOBAL-UNICAST)$/
                ) {
                    $baseServerPublicIp = '';
                }

                $msg = '';

                do {
                    ($rs, $baseServerPublicIp) = $dialog->inputbox(<<"EOF", $baseServerPublicIp);

The system has detected that your server is inside a private LAN.

Please enter your public IP address:$msg

\\ZbNote:\\Zn Leave blank to force usage of the $baseServerIp IP address.
EOF
                    if($baseServerPublicIp) {
                        unless($net->isValidAddr($baseServerPublicIp)) {
                            $msg = "\n\n\\Z1Invalid or unallowed IP address.\\Zn\n\nPlease try again:";
                        } elsif($net->getAddrType($baseServerPublicIp) !~ /^(?:PUBLIC|GLOBAL-UNICAST)$/) {
                            $msg = "\n\n\\Z1Unallowed IP address. IP address must be public.\\Zn\n\nPlease try again:";
                        } else {
                            $msg = '';
                        }
                    } else {
                        $baseServerPublicIp = $baseServerIp;
                        $msg = ''
                    }
                } while($rs < 30 && $msg);
            } else {
                $baseServerPublicIp = $baseServerIp
            }
        }

        # Handle additional IP addition / deletion
        if($rs < 30) {
            if(@serverIps > 1) {
                @serverIps = grep($_ ne $baseServerIp, @serverIps); # Remove the base server IP from the list

                # Retrieve IP to which the user is currently connected (SSH)
                my $sshConnectionIp = defined $ENV{'SSH_CONNECTION'} ? (split ' ', $ENV{'SSH_CONNECTION'})[2] : undef;

                $msg = '';

                do {
                    ($rs, $serverIps) = $dialog->checkbox(<<"EOF", [ @serverIps ], @{$serverIpsToAdd});

Please, select additional IP addresses to register into i-MSCP and deselect those to unregister:$msg
EOF
                    $msg = '';
                    if(defined $sshConnectionIp
                        && grep($_ eq $sshConnectionIp, @serverIps)
                        && !grep($_ eq $sshConnectionIp, @{$serverIps})
                    ) {
                        $msg = "\n\n\\Z1You cannot remove the $sshConnectionIp IP to which you are currently connected " .
                        "through SSH.\\Zn\n\nPlease try again:";
                    }
                } while ($rs < 30 && $msg);

                if($rs < 30) {
                    @{$serverIpsToAdd} = @{$serverIps}; # Retrieve list of IP to add into database
                    push @{$serverIpsToAdd}, $baseServerIp; # Re-add base ip

                    if($db) {
                        # Get list of IP addresses to delete
                        %serverIpsToDelete = ();

                        for my $ip(@serverIps) {
                            if(exists $currentServerIps->{$ip} && !grep($_ eq $ip, @{$serverIpsToAdd})) {
                                $serverIpsToDelete{$currentServerIps->{$ip}->{'ip_id'}} = $ip;
                            }
                        }

                        # Check for server IP addresses already in use and ask for replacement
                        my $resellerIps = $db->doQuery('reseller_ips', 'SELECT reseller_ips FROM reseller_props');

                        if(ref $resellerIps ne 'HASH') {
                            error(sprintf("Could not retrieve resellers's IP addresses: %s", $resellerIps));
                            return 1;
                        }

                        for(keys %$resellerIps) {
                            my @resellerIps = split ';';

                            for(@resellerIps) {
                                if(exists $serverIpsToDelete{$_} && !exists $serverIpsReplMap{$serverIpsToDelete{$_}}) {
                                    my $ret = '';

                                    do {
                                        ($rs, $ret) = $dialog->radiolist(<<"EOF", $serverIpsToAdd, $baseServerIp);

The IP address '$serverIpsToDelete{$_}' is already in use. Please, choose an IP to replace it:
EOF
                                    } while($rs < 30 && !$ret);

                                    $serverIpsReplMap{$serverIpsToDelete{$_}} = $ret;
                                }

                                last if $rs;
                            }

                            last if $rs;
                        }
                    }
                }
            }
        }
    }

    if($rs < 30) {
        setupSetQuestion('BASE_SERVER_IP', $baseServerIp);
        setupSetQuestion('BASE_SERVER_PUBLIC_IP', $baseServerPublicIp);
        setupSetQuestion('SERVER_IPS', $serverIpsToAdd);
        setupSetQuestion('SERVER_IPS_TO_REPLACE', {%serverIpsReplMap});
        setupSetQuestion('SERVER_IPS_TO_DELETE', [values %serverIpsToDelete]);
    }

    $rs;
}

sub askSqlRootUser
{
    my ($dialog) = @_;

    my $host = setupGetQuestion('DATABASE_HOST', $main::imscpConfig{'SQL_SERVER'} eq 'remote_server' ? '' : 'localhost');
    my $port = setupGetQuestion('DATABASE_PORT', 3306);
    my $user = setupGetQuestion('SQL_ROOT_USER', 'root');
    my $pwd = setupGetQuestion('SQL_ROOT_PASSWORD');
    my ($rs, $msg) = (0, '');

    if($host eq 'localhost') {
        # If authentication is made through unix socket, password is normally not required.
        # We try a connect without password with 'root' as user and we return on success
        for('localhost', '127.0.0.1') {
            next if tryDbConnect($_, $port, $user, $pwd);
            setupSetQuestion('DATABASE_TYPE', 'mysql');
            setupSetQuestion('DATABASE_HOST', $_);
            setupSetQuestion('DATABASE_PORT', $port);
            setupSetQuestion('SQL_ROOT_USER', $user);
            setupSetQuestion('SQL_ROOT_PASSWORD', $pwd);
            return 0;
        }
    }

    do {
        ($rs, $host) = $dialog->inputbox(<<"EOF", $host, 'utf-8');

Please enter your SQL server hostname or IP address:$msg
EOF
        $msg = $host eq '' || $host ne 'localhost' && !is_domain($host, { domain_private_tld => qr /.*/ } )
            && !iMSCP::Net->getInstance()->isValidAddr($host)
            ? "\n\n\\Z1SQL server hostname or IP address is not valid.\\Zn\n\nPlease try again:" : '';
    } while($rs < 30 && $msg ne '');

    return $rs if $rs >= 30;

    do {
        ($rs, $port) = $dialog->inputbox(<<"EOF", $port);

Please enter your SQL server port:$msg
EOF
        $msg = $port !~ /^[\d]+$/ || $port < 1025 || $port > 65535
            ? "\n\n\\Z1SQL server port is not valid.\\Zn\n\nPlease try again:"
            : '';
    } while($rs < 30 && $msg ne '');

    return $rs if $rs >= 30;

    do {
        ($rs, $user) = $dialog->inputbox(<<"EOF", $user);

Please enter your SQL root username:$msg

Note that this user must have full privileges on the SQL server.
i-MSCP only uses that user while installation or reconfiguration.
EOF
        $msg = $user eq '' ? "\n\n\\Z1A SQL root user is required.\\Zn\n\nPlease try again:" : '';
    } while($rs < 30 && $msg) ne '';

    return $rs if $rs >= 30;

    do {
        ($rs, $pwd) = $dialog->passwordbox(<<"EOF");

Please enter your SQL root user password:$msg
EOF
        $msg = $pwd eq ''? "\n\n\\Z1SQL root user password is required.\\Zn\n\nPlease try again:" : '';
    } while($rs < 30 && $msg);

    return $rs if $rs >= 30;

    if(my $connectError = tryDbConnect($host, $port, $user, $pwd)) {
        $rs = $dialog->msgbox(<<"EOF");

\\Z1Connection to SQL server failed\\Zn

i-MSCP installer could not connect to SQL server using the following data:

\\Z4Host:\\Zn $host
\\Z4Port:\\Zn $port
\\Z4Username:\\Zn $user
\\Z4Password:\\Zn $pwd

Error was: \\Z1$connectError\\Zn

Please try again.
EOF
        goto &{askSqlRootUser} unless $rs >= 30;
    }

    if($rs < 30) {
        setupSetQuestion('DATABASE_TYPE', 'mysql');
        setupSetQuestion('DATABASE_HOST', idn_to_ascii($host, 'utf-8'));
        setupSetQuestion('DATABASE_PORT', $port);
        setupSetQuestion('SQL_ROOT_USER', $user);
        setupSetQuestion('SQL_ROOT_PASSWORD', $pwd);
    }

    $rs;
}

sub askMasterSqlUser
{
    my $dialog = shift;

    my $host = setupGetQuestion('DATABASE_HOST');
    my $port = setupGetQuestion('DATABASE_PORT');
    my $user = setupGetQuestion('DATABASE_USER', 'imscp_user');
    $user = 'imscp_user' if lc($user) eq 'root'; # Handle upgrade case
    my $pwd = setupGetQuestion('DATABASE_PASSWORD');
    my ($rs, $msg) = (0, '');

    $pwd = decryptBlowfishCBC($main::imscpDBKey, $main::imscpDBiv, $pwd) unless $pwd eq '' || iMSCP::Getopt->preseed;

    if($main::reconfigure =~ /^sql|servers|all|forced$/
        || $host eq '' || $port eq '' || $user eq '' || $user eq 'root' || $pwd eq ''
        || tryDbConnect($host, $port, $user, $pwd)
    ) {
        $rs = askSqlRootUser($dialog);
        return $rs if $rs >= 30;

        do {
            ($rs, $user) = $dialog->inputbox( <<"EOF", $msg eq '' ? $user : '' );

Please enter a username for the master i-MSCP SQL user:$msg
EOF
            if (lc($user) eq 'root') {
                $msg = "\n\n\\Z1Usage of SQL root user is prohibited. \\Zn\n\nPlease try again:";
            } elsif (length $user > 16) {
                $msg = "\n\n\\Username can be up to 16 characters long.\\Zn\n\nPlease try again:";
            } elsif (length $user < 6) {
                $msg = "\n\n\\Z1Username must be at least 6 characters long.\\Zn\n\nPlease try again:";
            } elsif ($user !~ /^[\x21-\x5b\x5d-\x7e]+$/) {
                $msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease try again:";
            } else {
                $msg = '';
            }
        } while ($rs < 30 && $msg ne '');

        if ($rs < 30) {
            $pwd = '';
            do {
                ($rs, $pwd) = $dialog->passwordbox( <<"EOF", $msg eq '' ? $pwd : '');
Please enter a password for the master i-MSCP SQL user (blank for autogenerate):$msg
EOF
                if ($pwd ne '' && length $pwd < 6) {
                    $msg = "\n\n\\Z1Password must be at least 6 characters long.\\Zn\n\nPlease try again:";
                } elsif ($pwd =~ /[^\x21-\x5b\x5d-\x7e]/) {
                    $msg = "\n\n\\Z1Only printable ASCII characters (excepted space and backslash) are allowed.\\Zn\n\nPlease try again:";
                } else {
                    $msg = '';
                }
            } while ($rs < 30 && $msg ne '');

            if ($rs < 30) {
                unless ($pwd) {
                    my @allowedChr = map { chr } (0x21 .. 0x5b, 0x5d .. 0x7e);
                    $pwd = '';
                    $pwd .= $allowedChr[rand @allowedChr] for 1 .. 16;
                }

                $dialog->msgbox( <<"EOF" );

Password for master i-MSCP SQL user set to: $pwd
EOF
            }
        }
    }

    if($rs < 30) {
        setupSetQuestion('DATABASE_USER', $user);
        setupSetQuestion('DATABASE_PASSWORD', encryptBlowfishCBC($main::imscpDBKey, $main::imscpDBiv, $pwd));

        # Substitute SQL root user data with i-MSCP master user data if needed
        setupSetQuestion('SQL_ROOT_USER', setupGetQuestion('SQL_ROOT_USER', $user));
        setupSetQuestion('SQL_ROOT_PASSWORD', setupGetQuestion('SQL_ROOT_PASSWORD', $pwd));
    }

    $rs;
}

# Ask for hosts from which SQL users are allowed to connect from
sub setupAskSqlUserHost
{
    my $dialog = shift;

    my $host = idn_to_ascii(setupGetQuestion('DATABASE_USER_HOST', setupGetQuestion('BASE_SERVER_PUBLIC_IP')), 'utf-8');
    my %options = (domain_private_tld => qr /.*/);
    my $net = iMSCP::Net->getInstance();
    my $rs = 0;

    if($main::imscpConfig{'SQL_SERVER'} eq 'remote_server') { # Remote MySQL server
        if($main::reconfigure =~ /^sql|servers|all|forced$/ || $host ne '%' && !is_domain($host, \%options)
           && !$net->isValidAddr($host)
        ) {
            my $msg = '';

            do {
                ($rs, $host) = $dialog->inputbox(<<"EOF", idn_to_unicode($host, 'utf-8'));

Please, enter the host from which SQL users created by i-MSCP must be allowed to connect to your SQL server:$msg

Please refer to http://dev.mysql.com/doc/refman/5.5/en/account-names.html for allowed values.
EOF
                $msg = '';
                $host = idn_to_ascii($host, 'utf-8');

                if($host ne '%'
                    && !is_domain($host, \%options)
                    && !$net->isValidAddr($host)
                    || $net->getAddrType($host) =~ /^(?:LOOPBACK|LINK-LOCAL-UNICAST)$/
                ) {
                    $msg = sprintf("\n\n\\Z1Error: '%s' is not valid or not allowed.\\Zn\n\nPlease try again:", $host);
                }

            } while($rs < 30 && $msg ne '');
        }

        setupSetQuestion('DATABASE_USER_HOST', $host) if $rs < 30;
    } else {
        setupSetQuestion('DATABASE_USER_HOST', 'localhost');
    }

    $rs;
}

# Ask for i-MSCP database name
sub setupAskImscpDbName
{
    my $dialog = shift;

    my $dbName = setupGetQuestion('DATABASE_NAME', 'imscp');
    my $rs = 0;

    if($main::reconfigure =~ /^sql|servers|all|forced$/ || !iMSCP::Getopt->preseed && !setupIsImscpDb($dbName)) {
        my $msg = '';

        do {
            ($rs, $dbName) = $dialog->inputbox(<<"EOF", $dbName);

Please enter a database name for i-MSCP:$msg
EOF
            $msg = '';
            unless($dbName) {
                $msg = "\n\n\\Z1Database name cannot be empty.\\Zn\n\nPlease try again:";
            } elsif($dbName =~ /[:;]/) {
                $msg = "\n\n\\Z1Database name contain illegal characters ':' and/or ';'.\\Zn\n\nPlease try again:";
            } else {
                my $db = iMSCP::Database->factory();
                local $@;
                eval { $db->useDatabase($dbName); };
                if(!$@ && !setupIsImscpDb($dbName)) {
                    $msg = "\n\n\\Z1Database '$dbName' exists but do not look like an i-MSCP database.\\Zn\n\nPlease try again:";
                }
            }
        } while ($rs < 30 && $msg ne '');

        if($rs < 30) {
            my $oldDbName = setupGetQuestion('DATABASE_NAME');

            if($oldDbName && $dbName ne $oldDbName && setupIsImscpDb($oldDbName)) {
                $dbName = setupGetQuestion('DATABASE_NAME') if $dialog->yesno(<<"EOF", 1);

\\Z1An i-MSCP database has been found\\Zn

A database '$main::imscpConfig{'DATABASE_NAME'}' for i-MSCP already exists.

Are you sure you want to create a new database?

Keep in mind that the new database will be free of any reseller and customer data.

\\Z4Note:\\Zn If the database you want to create already exists, nothing will happen.
EOF
            }
        }
    }

    setupSetQuestion('DATABASE_NAME', $dbName) if $rs < 30;
    $rs;
}

# Ask for database prefix/suffix
sub setupAskDbPrefixSuffix
{
    my $dialog = shift;

    my $prefix = setupGetQuestion('MYSQL_PREFIX');
    my $rs = 0;

    if($main::reconfigure =~ /^sql|servers|all|forced$/ || $prefix !~ /^behind|infront|none$/) {
        ($rs, $prefix) = $dialog->radiolist(
            <<"EOF", [ 'infront', 'behind', 'none' ], $prefix =~ /^behind|infront$/ ? $prefix : 'none');

\\Z4\\Zb\\ZuMySQL Database Prefix/Suffix\\Zn

Do you want use a prefix or suffix for customer's SQL databases?

\\Z4Infront:\\Zn A numeric prefix such as '1_' will be added to each customer
         SQL user and database name.
 \\Z4Behind:\\Zn A numeric suffix such as '_1' will be added to each customer
         SQL user and database name.
   \\Z4None\\Zn: Choice will be let to customer.
EOF
    }

    if($rs < 30) {
        setupSetQuestion('MYSQL_PREFIX', $prefix);
    }

    $rs;
}

# Ask for timezone
sub setupAskTimezone
{
    my $dialog = shift;

    my $defaultTimezone = DateTime->new(year => 0, time_zone => 'local')->time_zone->name;
    my $timezone = setupGetQuestion('TIMEZONE');
    my $rs = 0;

    if($main::reconfigure =~ /^timezone|all|forced$/ || !($timezone && DateTime::TimeZone->is_valid_name($timezone))) {
        $timezone = $defaultTimezone unless $timezone;
        my $msg = '';

        do {
            ($rs, $timezone) = $dialog->inputbox(<<"EOF", $timezone);

Please enter your timezone:$msg
EOF
            $msg = "\n\n\\Z1'$timezone' is not a valid timezone.\\Zn\n\nPlease try again:";
        } while($rs < 30 && !DateTime::TimeZone->is_valid_name($timezone));
    }

    setupSetQuestion('TIMEZONE', $timezone) if $rs < 30;
    $rs;
}

# Ask for services SSL
sub setupAskServicesSsl
{
    my($dialog) = @_;

    my $hostname = setupGetQuestion('SERVER_HOSTNAME');
    my $hostnameUnicode = idn_to_unicode($hostname, 'utf-8');
    my $sslEnabled = setupGetQuestion('SERVICES_SSL_ENABLED');
    my $selfSignedCertificate = setupGetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'no');
    my $privateKeyPath = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH', '/root');
    my $passphrase = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE');
    my $certificatPath = setupGetQuestion('SERVICES_SSL_CERTIFICATE_PATH', '/root');
    my $caBundlePath = setupGetQuestion('SERVICES_SSL_CA_BUNDLE_PATH', '/root');
    my $openSSL = iMSCP::OpenSSL->new();
    my $rs = 0;

    if($main::reconfigure =~ /^services_ssl|ssl|all|forced$/ || $sslEnabled !~ /^yes|no$/
        || ($sslEnabled eq 'yes' && $main::reconfigure =~ /^system_hostname|hostnames$/)
    ) {
        # Ask for SSL
        $rs = $dialog->yesno(<<"EOF", $sslEnabled eq 'no' ? 1 : 0);

Do you want to activate SSL for SMTP, POP/IMAP and FTP services?
EOF
        if($rs == 0) {
            $sslEnabled = 'yes';
            # Ask for self-signed certificate
            $rs = $dialog->yesno(<<"EOF", $selfSignedCertificate eq 'no' ? 1 : 0);

Do you have an SSL certificate for the $hostnameUnicode domain?
EOF
            if($rs == 0) {
                # Ask for private key
                my $msg = '';
                do {
                    $dialog->msgbox(<<"EOF");
$msg
Please select your private key in next dialog.
EOF
                    do {
                        ($rs, $privateKeyPath) = $dialog->fselect($privateKeyPath);
                    } while($rs < 30 && !($privateKeyPath && -f $privateKeyPath));

                    if($rs < 30) {
                        ($rs, $passphrase) = $dialog->passwordbox(<<"EOF", $passphrase);

Please enter the passphrase for your private key if any:
EOF
                    }

                    if($rs < 30) {
                        $openSSL->{'private_key_container_path'} = $privateKeyPath;
                        $openSSL->{'private_key_passphrase'} = $passphrase;

                        if($openSSL->validatePrivateKey()) {
                            getMessageByType('error', { remove => 1 });
                            $msg = "\n\\Z1Wrong private key or passphrase. Please try again.\\Zn\n\n";
                        } else {
                            $msg = '';
                        }
                    }
                } while($rs < 30 && $msg);

                # Ask for CA bundle
                if($rs < 30) {
                    $rs = $dialog->yesno(<<"EOF");

Do you have an SSL CA Bundle?
EOF
                    if($rs == 0) {
                        do {
                            ($rs, $caBundlePath) = $dialog->fselect($caBundlePath);
                        } while($rs < 30 && !($caBundlePath && -f $caBundlePath));

                        $openSSL->{'ca_bundle_container_path'} = $caBundlePath if $rs < 30;
                    } else {
                        $openSSL->{'ca_bundle_container_path'} = '';
                    }
                }

                if($rs < 30) {
                    $dialog->msgbox(<<"EOF");

Please select your SSL certificate in next dialog.
EOF

                    $rs = 1;

                    do {
                        $dialog->msgbox(<<"EOF") if !$rs;

\\Z1Wrong SSL certificate. Please try again.\\Zn
EOF
                        do {
                            ($rs, $certificatPath) = $dialog->fselect($certificatPath);
                        } while($rs < 30 && !($certificatPath && -f $certificatPath));

                         getMessageByType('error', { remove => 1 });
                        $openSSL->{'certificate_container_path'} = $certificatPath if $rs < 30;
                    } while($rs < 30 && $openSSL->validateCertificate());
                }
            } else {
                $rs = 0;
                $selfSignedCertificate = 'yes';
            }
        } else {
            $rs = 0;
            $sslEnabled = 'no';
        }
    } elsif($sslEnabled eq 'yes' && !iMSCP::Getopt->preseed) {
        $openSSL->{'private_key_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'ca_bundle_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'certificate_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";

        if($openSSL->validateCertificateChain()) {
            getMessageByType('error', { remove => 1 });
            iMSCP::Dialog->getInstance()->msgbox(<<"EOF");

Your SSL certificate for the SMTP, POP/IMAP and FTP services is missing or not valid.
EOF
            setupSetQuestion('SERVICES_SSL_ENABLED', '');
            goto &{setupAskServicesSsl};
        }

        # In case the certificate is valid, we skip SSL setup process
        setupSetQuestion('SERVICES_SSL_SETUP', 'no');
    }

    if($rs < 30) {
        setupSetQuestion('SERVICES_SSL_ENABLED', $sslEnabled);
        setupSetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate);
        setupSetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH', $privateKeyPath);
        setupSetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase);
        setupSetQuestion('SERVICES_SSL_CERTIFICATE_PATH', $certificatPath);
        setupSetQuestion('SERVICES_SSL_CA_BUNDLE_PATH', $caBundlePath);
    }

    $rs;
}

# Ask for i-MSCP backup feature
sub setupAskImscpBackup
{
    my $dialog = shift;

    my $backupImscp = setupGetQuestion('BACKUP_IMSCP');
    my $rs = 0;

    if($main::reconfigure =~ /^backup|all|forced$/ || $backupImscp !~ /^yes|no$/) {
        ($rs, $backupImscp) = $dialog->radiolist(<<"EOF", [ 'yes', 'no' ], $backupImscp ne 'no' ? 'yes' : 'no');

\\Z4\\Zb\\Zui-MSCP Backup Feature\\Zn

Do you want to activate the backup feature for i-MSCP?

The backup feature for i-MSCP allows the daily save of all i-MSCP configuration files and its database. It's greatly recommended to activate this feature.
EOF
    }

    setupSetQuestion('BACKUP_IMSCP', $backupImscp) if $rs < 30;
    $rs;
}

# Ask for customer backup feature
sub setupAskDomainBackup
{
    my $dialog = shift;

    my $backupDomains = setupGetQuestion('BACKUP_DOMAINS');
    my $rs = 0;

    if($main::reconfigure =~ /^backup|all|forced$/ || $backupDomains !~ /^yes|no$/) {
        ($rs, $backupDomains) = $dialog->radiolist(<<"EOF", [ 'yes', 'no' ], $backupDomains ne 'no' ? 'yes' : 'no');

\\Z4\\Zb\\ZuDomains Backup Feature\\Zn

Do you want to activate the backup feature for customers?

This feature allows resellers to enable backup for their customers such as:

 - Full (domains and SQL databases)
 - Domains only (Web files)
 - SQL databases only
 - None (no backup)
EOF
    }

    setupSetQuestion('BACKUP_DOMAINS', $backupDomains) if $rs < 30;
    $rs;
}

#
## Setup subroutines
#

sub setupSaveOldConfig
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSaveOldConfig');
    return $rs if $rs;

    unless(-f "$main::imscpConfig{'CONF_DIR'}/imscp.conf") {
        error(sprintf('File %s not found', "$main::imscpConfig{'CONF_DIR'}/imscp.conf"));
        return 1;
    }

    $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf" )->copyFile(
        "$main::imscpConfig{'CONF_DIR'}/imscp.old.conf"
    );
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupSaveOldConfig');
}

# Write question answers into imscp.conf file
sub setupWriteNewConfig
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupWriteNewConfig');
    return $rs if $rs;

    for my $question(keys %main::questions) {
        if(exists $main::imscpConfig{$question}) {
            $main::imscpConfig{$question} = $main::questions{$question};
        }
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupWriteNewConfig');
}

sub setupCreateMasterUser
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateMasterUser');

    $rs ||= iMSCP::SystemGroup->getInstance()->addSystemGroup($main::imscpConfig{'IMSCP_GROUP'});
    $rs ||= iMSCP::SystemUser->new(
        username => $main::imscpConfig{'IMSCP_USER'},
        group => $main::imscpConfig{'IMSCP_GROUP'},
        comment => 'i-MSCP master user',
        home => $main::imscpConfig{'IMSCP_HOMEDIR'}
    )->addSystemUser();

    # Ensure that correct permissions are set on i-MSCP master user homedir (handle upgrade case)
    $rs ||= iMSCP::Dir->new( dirname => $main::imscpConfig{'IMSCP_HOMEDIR'} )->make(
        {
            user => $main::imscpConfig{'IMSCP_USER'},
            group => $main::imscpConfig{'IMSCP_GROUP'},
            mode => 0755,
            fixpermissions => 1 # We fix permissions in any case
        }
    );
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupCreateMasterUser');
}

sub setupCreateSystemDirectories
{
    my @systemDirectories  = (
        [ $main::imscpConfig{'BACKUP_FILE_DIR'}, $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'}, 0750 ]
    );

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateSystemDirectories', \@systemDirectories);
    return $rs if $rs;

    for my $dir(@systemDirectories) {
        $rs = iMSCP::Dir->new( dirname => $dir->[0] )->make(
            {
                user => $dir->[1],
                group => $dir->[2],
                mode => $dir->[3],
                fixpermissions => iMSCP::Getopt->fixPermissions
            }
        );
        return $rs if $rs;
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupCreateSystemDirectories');
}

sub setupServerHostname
{
    my $hostname = setupGetQuestion('SERVER_HOSTNAME');
    my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupServerHostname', \$hostname, \$baseServerIp);
    return $rs if $rs;

    my @labels = split /\./, $hostname;
    my $host = shift @labels;
    my $hostnameLocal = "$hostname.local";

    my $file = iMSCP::File->new( filename => '/etc/hosts' );
    $rs = $file->copyFile('/etc/hosts.bkp') unless -f '/etc/hosts.bkp';
    return $rs if $rs;

    my $net = iMSCP::Net->getInstance();
    my $content = "# 'hosts' file configuration.\n\n";
    $content .= "127.0.0.1\t$hostnameLocal\tlocalhost\n";
    $content .= "$baseServerIp\t$hostname\t$host\n";
    $content .= "::ffff:$baseServerIp\t$hostname\t$host\n" if $net->getAddrVersion($baseServerIp) eq 'ipv4';
    $content .= "::1\tip6-localhost\tip6-loopback\n" if $net->getAddrVersion($baseServerIp) eq 'ipv4';
    $content .= "::1\tip6-localhost\tip6-loopback\t$host\n" if $net->getAddrVersion($baseServerIp) eq 'ipv6';
    $content .= "fe00::0\tip6-localnet\n";
    $content .= "ff00::0\tip6-mcastprefix\n";
    $content .= "ff02::1\tip6-allnodes\n";
    $content .= "ff02::2\tip6-allrouters\n";
    $content .= "ff02::3\tip6-allhosts\n";

    $rs = $file->set($content);
    $rs ||= $file->save();
    $rs ||= $file->mode(0644);
    $rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
    return $rs if $rs;

    $file = iMSCP::File->new( filename => '/etc/hostname' );
    $rs = $file->set($host);
    $rs ||= $file->save();
    $rs ||= $file->mode(0644);
    $rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
    return $rs if $rs;

    $file = iMSCP::File->new( filename => '/etc/mailname' );
    $rs = $file->set($hostname);
    $rs ||= $file->save();
    $rs ||= $file->mode(0644);
    $rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
    return $rs if $rs;

    $rs = execute('hostname -F /etc/hostname', \my $stdout, \my $stderr);
    debug($stdout) if $stdout;
    debug($stderr) if !$rs && $stderr;
    error($stderr) if $rs && $stderr;
    error('Could not set server hostname') if $rs && !$stderr;
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupServerHostname');
}

sub setupServiceSsl
{
    my $sslEnabled = setupGetQuestion('SERVICES_SSL_ENABLED');

    if($sslEnabled eq 'no' || setupGetQuestion('SERVICES_SSL_SETUP', 'yes') eq 'no') {
        if($sslEnabled eq 'no' && -f "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem") {
            my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem" )->delFile();
            return $rs if $rs;
        }

        return 0;
    }

    if(setupGetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE') eq 'yes') {
        return iMSCP::OpenSSL->new(
            certificate_chains_storage_dir => $main::imscpConfig{'CONF_DIR'},
            certificate_chain_name => 'imscp_services'
        )->createSelfSignedCertificate(
            {
                common_name => setupGetQuestion('SERVER_HOSTNAME'),
                email => $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'}
            }
        );
    }

    iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $main::imscpConfig{'CONF_DIR'},
        certificate_chain_name => 'imscp_services',
        private_key_container_path => setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH'),
        private_key_passphrase => setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE'),
        certificate_container_path => setupGetQuestion('SERVICES_SSL_CERTIFICATE_PATH'),
        ca_bundle_container_path => setupGetQuestion('SERVICES_SSL_CA_BUNDLE_PATH')
    )->createCertificateChain();
}

sub setupServices
{
    my $serviceMngr = iMSCP::Service->getInstance();
    $serviceMngr->enable($_) for 'imscp_daemon', 'imscp_traffic', 'imscp_mountall';
    0;
}

sub setupRegisterDelayedTasks
{
    my $eventManager = iMSCP::EventManager->getInstance();
    $eventManager->register('afterSqldPreinstall', \&setupCreateMasterSqlUser);
    $eventManager->register('afterSqldPreinstall', \&setupSecureSqlInstallation);
    $eventManager->register('afterSqldPreinstall', \&setupCreateDatabase);
    $eventManager->register('afterSqldPreinstall', \&setupServerIps);
}

sub setupCreateMasterSqlUser
{
    my $user = setupGetQuestion( 'DATABASE_USER' );
    my $userHost = setupGetQuestion( 'DATABASE_USER_HOST' );
    my $pwd = decryptBlowfishCBC($main::imscpDBKey, $main::imscpDBiv, setupGetQuestion( 'DATABASE_PASSWORD' ));
    my $oldUser = $main::imscpOldConfig{'DATABASE_USER'};

    my $sqlServer = Servers::sqld->factory();

    # Remove old user if any
    for my $sqlUser ($oldUser, $user) {
        next unless $sqlUser;
        for my $host($userHost, $main::imscpOldConfig{'DATABASE_USER_HOST'}) {
            next unless $host;
            $sqlServer->dropUser( $sqlUser, $host );
        }
    }

    # Create user
    $sqlServer->createUser( $user, $userHost, $pwd );

    # Grant all privileges to that user (including GRANT otpion)
    my $qrs = iMSCP::Database->factory()->doQuery(
        'g', "GRANT ALL PRIVILEGES ON *.* TO ?@? WITH GRANT OPTION", $user, $userHost
    );
    unless (ref $qrs eq 'HASH') {
        error( sprintf( 'Could not grant privileges to master i-MSCP SQL user: %s', $qrs ) );
        return 1;
    }

    0;
}

sub setupServerIps
{
    my $baseServerIp = setupGetQuestion('BASE_SERVER_IP');
    my $serverIpsToReplace = setupGetQuestion('SERVER_IPS_TO_REPLACE', {});
    my $serverIpsToDelete = setupGetQuestion('SERVER_IPS_TO_DELETE', []);
    my $serverHostname = setupGetQuestion('SERVER_HOSTNAME');
    my $oldIptoIdMap = { };
    my @serverIps = ( $baseServerIp, $main::questions{'SERVER_IPS'} ? @{$main::questions{'SERVER_IPS'}} : () );

    my $rs = iMSCP::EventManager->getInstance()->trigger(
        'beforeSetupServerIps', \$baseServerIp, \@serverIps, $serverIpsToReplace
    );
    return $rs if $rs;

    # Ensure promoting of secondary IP addresses in case a PRIMARY addresse is being deleted
    # Note we are ignoring return value here (eg for vps)
    execute('sysctl -q -w net.ipv4.conf.all.promote_secondaries=1', \my $stdout, \my $stderr);

    my $db = iMSCP::Database->factory();
    $db->useDatabase(setupGetQuestion('DATABASE_NAME'));

   # Get IDs of IP addresses to replace
   if(%{$serverIpsToReplace}) {
        my $ipsToReplace = join q{,}, map $db->quote($_), keys %{$serverIpsToReplace};
        $oldIptoIdMap = $db->doQuery(
            'ip_number', 'SELECT ip_id, ip_number FROM server_ips WHERE ip_number IN ('. $ipsToReplace .')'
        );
        if(ref $oldIptoIdMap ne 'HASH') {
            error(sprintf('Could not get IDs of server IP addresses to replace: %s', $oldIptoIdMap));
            return 1;
        }
    }

    my $net = iMSCP::Net->getInstance();

    # Process server IPs addition
    my $defaultNetcard = (grep { $_ ne 'lo' } $net->getDevices())[0];
    for my $serverIp(@serverIps) {
        next if exists $serverIpsToReplace->{$serverIp};
        my $netCard = $net->isKnownAddr($serverIp) ? $net->getAddrDevice($serverIp) || $defaultNetcard : $defaultNetcard;

        if($netCard) {
            my $rs = $db->doQuery(
                'i', 'INSERT IGNORE INTO server_ips (ip_number, ip_card, ip_status) VALUES(?, ?, ?)',
                $serverIp, $netCard, 'toadd'
            );
            if (ref $rs ne 'HASH') {
                error(sprintf('Could not add/update %s IP address: %s', $serverIp, $rs));
                return 1;
            }
        } else {
            error(sprintf('Could not add %s IP address into database: Unknown network card', $serverIp));
            return 1;
        }
    }

    # Server IPs replacement
    for my $serverIp(keys %{$serverIpsToReplace}) {
        my $newIp = $serverIpsToReplace->{$serverIp}; # New IP
        my $oldIpId = $oldIptoIdMap->{$serverIp}->{'ip_id'}; # Old IP ID

        # Get IP IDs of resellers to which the IP to replace is currently assigned
        my $resellerIps = $db->doQuery(
            'id', 'SELECT id, reseller_ips FROM reseller_props WHERE reseller_ips REGEXP ?', "(^|[^0-9]$oldIpId;)"
        );
        unless(ref $resellerIps eq 'HASH') {
            error($resellerIps);
            return 1;
        }

        # Get new IP ID
        my $newIpId = $db->doQuery( 'ip_number', 'SELECT ip_id, ip_number FROM server_ips WHERE ip_number = ?', $newIp );
        unless(ref $newIpId eq 'HASH') {
            error($newIpId);
            return 1;
        }

        $newIpId = $newIpId->{$newIp}->{'ip_id'};

        for my $resellerIp(keys %{$resellerIps}) {
            my $ips = $resellerIps->{$resellerIp}->{'reseller_ips'};

            if($ips !~ /(?:^|[^0-9])$newIpId;/) {
                $ips =~ s/((?:^|[^0-9]))$oldIpId;?/$1$newIpId;/;
                $rs = $db->doQuery( 'u', 'UPDATE reseller_props SET reseller_ips = ? WHERE id = ?', $ips, $resellerIp );
                unless(ref $rs eq 'HASH') {
                    error($rs);
                    return 1;
                }
            }
        }

        # Update IP id of customer domains if needed
        $rs = $db->doQuery( 'u', 'UPDATE domain SET domain_ip_id = ? WHERE domain_ip_id = ?', $newIpId, $oldIpId );
        unless(ref $rs eq 'HASH') {
            error($rs);
            return 1;
        }

        # Update IP id of customer domain aliases if needed
        $rs = $db->doQuery('u', 'UPDATE domain_aliasses SET alias_ip_id = ? WHERE alias_ip_id = ?', $newIpId, $oldIpId);
        unless(ref $rs eq 'HASH') {
            error($rs);
            return 1;
        }
    }

    # Schedule IP deletion
    if(@{$serverIpsToDelete}) {
        my $serverIpsToDelete = join q{,}, map $db->quote($_), @{$serverIpsToDelete};
        my $rs = $db->doQuery(
            'u',
            'UPDATE server_ips set ip_status = ?  WHERE ip_number IN(' . $serverIpsToDelete . ') AND ip_number <> ?',
            'todelete', $baseServerIp
        );
        unless (ref $rs eq 'HASH') {
            error($rs);
            return 1;
        }
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupServerIps');
}

sub setupCreateDatabase
{
    my $dbName = setupGetQuestion('DATABASE_NAME');

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupCreateDatabase', \$dbName);
    return $rs if $rs;

    unless(setupIsImscpDb($dbName)) {
        my $db = iMSCP::Database->factory();
        my $qdbName = $db->quoteIdentifier($dbName);
        my $rs = $db->doQuery('c', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");

        if(ref $rs ne 'HASH') {
            error(sprintf("Could not create the '%s' SQL database: %s", $dbName, $rs));
            return 1;
        }

        $db->set('DATABASE_NAME', $dbName);
        !$db->connect() or die('Could not reconnect to SQL server');
        $rs = setupImportSqlSchema($db, "$main::imscpConfig{'CONF_DIR'}/database/database.sql");
        return $rs if $rs;
    }

    # In all cases, we process database update. This is important because sometime some developer forget to update the
    # database revision in the main database.sql file.
    $rs = setupUpdateDatabase();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupCreateDatabase');
}

sub setupUpdateDatabase
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupUpdateDatabase');
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php" );
    my $content = $file->get();
    unless(defined $content) {
        error(sprintf('Could not read %s file', "$main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php"));
        return 1;
    }

    if($content =~ s/\{GUI_ROOT_DIR\}/$main::imscpConfig{'GUI_ROOT_DIR'}/) {
        $rs = $file->set($content);
        $rs ||= $file->save();
        return $rs if $rs;
    }

    $rs = execute(
        "php -d date.timezone=UTC $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php", \my $stdout, \my $stderr
    );
    debug($stdout) if $stdout;
    error($stderr) if $rs && $stderr;
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupUpdateDatabase');
}

sub setupImportSqlSchema
{
    my ($db, $file) = @_;

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupImportSqlSchema', \$file);
    return $rs if $rs;

    my $content = iMSCP::File->new( filename => $file )->get();
    unless(defined $content) {
        error(sprintf('Could not read %s file', $file));
        return 1;
    }

    $content =~ s/^(--[^\n]{0,})?\n//gm;
    my @queries = split /;\n/, $content;
    my $title = "Executing " . @queries . " queries:";

    startDetail();

    my $step = 1;
    for my $query(@queries) {
        $rs = step(
            sub {
                my $qrs = $db->doQuery('dummy', $query);
                unless(ref $qrs eq 'HASH') {
                    error(sprintf('Could not execute SQL query: %s', $qrs));
                    return 1;
                }
                0;
           },
           $queries[$step] ? "$title\n$queries[$step]" : $title, scalar @queries, $step
        );
        last if $rs;
        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupImportSqlSchema');
}

# Secure any SQL account by removing those without password
#
# Basically, this method do same job as the mysql_secure_installation script
# - Remove anonymous users
# - Remove remote sql root user (only for local server)
# - Remove test database if any
# - Reload privileges tables
sub setupSecureSqlInstallation
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSecureSqlInstallation');
    return $rs if $rs;

    my $db = iMSCP::Database->factory();
    my $oldDatabase = $db->useDatabase('mysql');

    # Remove anonymous users
    my $qrs = $db->doQuery('d', "DELETE FROM user WHERE User = ''");
    unless(ref $qrs eq 'HASH') {
        error(sprintf('Could not delete anonymous users: %s', $qrs));
        return 1;
    }

    # Remove test database if any
    $qrs = $db->doQuery('d', 'DROP DATABASE IF EXISTS `test`');
    unless(ref $qrs eq 'HASH') {
        error(sprintf('Could not remove `test` database: %s', $qrs));
        return 1;
    }

    # Remove privileges on test database
    $qrs = $db->doQuery('d', "DELETE FROM db WHERE Db = 'test' OR Db = 'test\\_%'");
    unless(ref $qrs eq 'HASH') {
        error(sprintf('Could not remove privileges on `test` database: %s', $qrs));
        return 1;
    }

    # Disallow remote root login
    if($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
        $qrs = $db->doQuery(
            'd', "DELETE FROM user WHERE User = 'root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');"
        );
        unless(ref $qrs eq 'HASH'){
            error(sprintf('Could not remove remote `root` users: %s', $qrs));
            return 1;
        }
    }

    $qrs = $db->doQuery('f', 'FLUSH PRIVILEGES');
    unless(ref $qrs eq 'HASH') {
        debug(sprintf('Could not reload privileges tables: %s', $qrs));
        return 1;
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupSecureSqlInstallation');
}

sub setupSetPermissions
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSetPermissions');
    return $rs if $rs;

    my $debug = $main::imscpConfig{'DEBUG'} || 0;
    $main::imscpConfig{'DEBUG'} = iMSCP::Getopt->debug ? 1 : 0;

    for my $script ('set-engine-permissions.pl', 'set-gui-permissions.pl') {
        startDetail();

        my @options = (
            '--setup',
            $script eq 'set-engine-permissions.pl' && iMSCP::Getopt->fixPermissions ? '--fix-permissions' : ''
        );

        my $stderr;
        $rs = executeNoWait(
            "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script @options",
            sub { my $str = shift; while ($$str =~ s/^(.*)\t(.*)\t(.*)\n//) { step(undef, $1, $2, $3); } },
            sub { my $str = shift; while ($$str =~ s/^(.*\n)//) { $stderr .= $1; } }
        );

        endDetail();

        error(sprintf('Error while setting permissions: %s', $stderr)) if $stderr && $rs;
        error('Error while setting permissions: Unknown error') if $rs && !$stderr;
        return $rs if $rs;
    }

    $main::imscpConfig{'DEBUG'} = $debug;
    iMSCP::EventManager->getInstance()->trigger('afterSetupSetPermissions');
}

sub setupRebuildCustomerFiles
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupRebuildCustomersFiles');
    return $rs if $rs;

    my $tables = {
        ssl_certs => 'status',
        admin => [ 'admin_status', "AND `admin_type` = 'user'" ],
        domain => 'domain_status',
        domain_aliasses => 'alias_status',
        #subdomain => 'subdomain_status', # This is now automatically done by the domain module
        #subdomain_alias => 'subdomain_alias_status', # This is now automatically done by the alias module
        #domain_dns => 'domain_dns_status', # This is now automatically done by the domain and alias modules
        ftp_users => 'status',
        mail_users => 'status',
        htaccess => 'status',
        htaccess_groups => 'status',
        htaccess_users => 'status',
        server_ips => 'ip_status'
    };

    my $db = iMSCP::Database->factory();
    $db->useDatabase(setupGetQuestion('DATABASE_NAME'));

    my $rawDb = $db->startTransaction();

    eval {
        my $aditionalCondition;

        while (my ($table, $field) = each %{$tables}) {
            if(ref $field eq 'ARRAY') {
                $aditionalCondition = $field->[1];
                $field = $field->[0];
            } else {
                $aditionalCondition = ''
            }

            $rawDb->do(
                "
                    UPDATE $table SET $field = 'tochange'
                    WHERE $field NOT IN('toadd', 'torestore', 'todisable', 'disabled', 'ordered', 'todelete')
                    $aditionalCondition
                "
            );

            $rawDb->do("UPDATE $table SET $field = 'todisable' WHERE $field = 'disabled' $aditionalCondition");
        }

        $rawDb->do(
            "
                UPDATE plugin SET plugin_status = 'tochange', plugin_error = NULL
                WHERE plugin_status IN ('tochange', 'enabled') AND plugin_backend = 'yes'
            "
        );

        $rawDb->commit();
    };

    if($@) {
        $rawDb->rollback();
        $db->endTransaction();
        error(sprintf('Could not execute SQL query: %s', $@));
        return 1;
    }

    $db->endTransaction();

    startDetail();
    local $@;
    eval { iMSCP::DbTasksProcessor->getInstance( mode => 'setup' )->process(); };
    if($@) {
        error($@);
        $rs = 1;
    }
    endDetail();

    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupRebuildCustomersFiles');
}

sub setupRegisterPluginListeners
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupRegisterPluginListeners');
    return $rs if $rs;

    my $db = iMSCP::Database->factory();

    local $@;
    eval { $db->useDatabase(setupGetQuestion('DATABASE_NAME')); };
    return 0 if $@;
    $db = $db->getRawDb();
    $db->{'RaiseError'} = 1;

    my $pluginNames = eval {
        $db->selectcol_arrayref("SELECT plugin_name FROM plugin WHERE plugin_status = 'enabled'");
    };
    if ($@) {
        error($@);
        return 1;
    }

    $db->{'RaiseError'} = 0;

    my $eventManager = iMSCP::EventManager->getInstance();

    for my $pluginPath(iMSCP::Plugins->getInstance()->get()) {
        my $pluginName = basename($pluginPath, '.pm');
        next unless grep($_ eq $pluginName, @{$pluginNames});
        eval { require $pluginPath; };
        unless($@) {
            my $plugin = 'Plugin::' . $pluginName;
            my $rs = $plugin->registerSetupListeners($eventManager) if $plugin->can('registerSetupListeners');
            return $rs if $rs;
            next;
        }

        error($@);
        return 1
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupRegisterPluginListeners');
}

sub setupPreInstallServers
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPreInstallServers');
    return $rs if $rs;

    startDetail();

    my @servers = iMSCP::Servers->getInstance()->get();
    my $nbServers = scalar @servers;
    my $step = 1;
    for(@servers) {
        next if $_ eq 'noserver';
        my $package = "Servers::$_";
        eval "require $package";
        unless($@) {
            my $server = $package->factory();
            if($server->can('preinstall')) {
                $rs = step(
                    sub { $server->preinstall() },
                    sprintf('Running %s preinstall tasks...', ref $server),
                    $nbServers,
                    $step
                );

                last if $rs;
            }
        } else {
            error($@);
            $rs = 1;
            last;
        }

        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupPreInstallServers');
}

sub setupPreInstallPackages
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPreInstallPackages');
    return $rs if $rs;

    startDetail();

    my @packages = iMSCP::Packages->getInstance()->get();
    my $nbPackages = scalar @packages;
    my $step = 1;
    for(@packages) {
        my $package = "Package::$_";
        eval "require $package";
        unless($@) {
            my $package = $package->getInstance();
            if($package->can('preinstall')) {
                $rs = step(
                    sub { $package->preinstall() },
                    sprintf('Running %s preinstall tasks...', ref $package),
                    $nbPackages,
                    $step
                );

                last if $rs;
            }
        } else {
            error($@);
            $rs = 1;
            last;
        }

        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupPreInstallPackages');
}

sub setupInstallServers
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupInstallServers');
    return $rs if $rs;

    startDetail();

    my @servers = iMSCP::Servers->getInstance()->get();
    my $nbServers = scalar @servers;
    my $step = 1;
    for(@servers) {
        my $package = "Servers::$_";
        eval "require $package";
        unless($@) {
            next if $_ eq 'noserver';
            my $server = $package->factory();
            if($server->can('install')) {
                $rs = step(
                    sub { $server->install() },
                    sprintf('Running %s install tasks...', ref $server),
                    $nbServers,
                    $step
                );

                last if $rs;
            }
        } else {
            error($@);
            $rs = 1;
            last;
        }

        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupInstallServers');
}

sub setupInstallPackages
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupInstallPackages');
    return $rs if $rs;

    startDetail();

    my @packages = iMSCP::Packages->getInstance()->get();
    my $nbPackages = scalar @packages;
    my $step = 1;
    for(@packages) {
        my $package = "Package::$_";
        eval "require $package";
        unless($@) {
            my $package = $package->getInstance();
            if($package->can('install')) {
                $rs = step(
                    sub { $package->install() },
                    sprintf('Running %s install tasks...', ref $package),
                    $nbPackages,
                    $step
                );

                last if $rs;
            }
        } else {
            error($@);
            $rs = 1;
            last;
        }

        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupInstallPackages');
}

sub setupPostInstallServers
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPostInstallServers');
    return $rs if $rs;

    startDetail();

    my @servers = iMSCP::Servers->getInstance()->get();
    my $nbServers = scalar @servers;
    my $step = 1;
    for(@servers) {
        next if $_ eq 'noserver';
        my $package = "Servers::$_";
        eval "require $package";
        unless($@) {
            my $server = $package->factory();
            if($server->can('postinstall')) {
                $rs = step(
                    sub { $server->postinstall() },
                    sprintf('Running %s postinstall tasks...', ref $server),
                    $nbServers,
                    $step
                );

                last if $rs;
            }
        } else {
            error($@);
            $rs = 1;
            last;
        }

        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupPostInstallServers');
}

sub setupPostInstallPackages
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupPostInstallPackages');
    return $rs if $rs;

    startDetail();

    my @packages = iMSCP::Packages->getInstance()->get();
    my $nbPackages = scalar @packages;
    my $step = 1;
    for(@packages) {
        my $package = "Package::$_";
        eval "require $package";
        unless($@) {
            my $package = $package->getInstance();
            if($package->can('postinstall')) {
                $rs = step(
                    sub { $package->postinstall() },
                    sprintf('Running %s postinstall tasks...', ref $package),
                    $nbPackages,
                    $step
                );

                last if $rs;
            }
        } else {
            error($@);
            $rs = 1;
            last;
        }

        $step++;
    }

    endDetail();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupPostInstallPackages');
}

sub setupRestartServices
{
    my @services = ();

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupRestartServices', \@services);
    return $rs if $rs;

    my $serviceMngr = iMSCP::Service->getInstance();
    unshift @services, (
        [ sub { $serviceMngr->restart('imscp_mountall'); 0; }, 'Mounts i-MSCP filesystems' ],
        [ sub { $serviceMngr->restart('imscp_traffic'); 0; }, 'i-MSCP Traffic Logger' ],
        [ sub { $serviceMngr->start('imscp_daemon'); 0; }, 'i-MSCP Daemon' ]
    );

    startDetail();

    my $nbSteps = @services;
    my $step = 1;
    for (@services) {
        $rs = step($_->[0], sprintf('Restarting/Starting %s service...', $_->[1]), $nbSteps, $step);
        return $rs if $rs;
        $step++;
    }

    endDetail();
    iMSCP::EventManager->getInstance()->trigger('afterSetupRestartServices');
}

#
## Low level subroutines
#

sub setupGetQuestion
{
    my ($qname, $default) = @_;
    $default ||= '';

    return exists $main::questions{$qname} ? $main::questions{$qname} : (
        exists $main::imscpConfig{$qname} && $main::imscpConfig{$qname} ne ''
        ? $main::imscpConfig{$qname}
        : $default
    );
}

sub setupSetQuestion
{
    $main::questions{$_[0]} = $_[1];
}

sub tryDbConnect
{
    my ($host, $port, $user, $pwd) = @_;

    defined $host or die('$host parameter is not defined');
    defined $port or die('$port parameter is not defined');
    defined $user or die('$user parameter is not defined');
    defined $pwd or die('$pwd parameter is not defined');

    my $db = iMSCP::Database->factory();
    $db->set('DATABASE_HOST', idn_to_ascii($host, 'utf-8'));
    $db->set('DATABASE_PORT', $port);
    $db->set('DATABASE_USER', $user);
    $db->set('DATABASE_PASSWORD', $pwd);
    $db->connect();
}

# Return int 1 if database exists and look like an i-MSCP database, 0 otherwise
sub setupIsImscpDb
{
    my $dbName = shift;

    my $db = iMSCP::Database->factory();

    my $rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $dbName);
    ref $rs eq 'HASH' or fatal(sprintf('SQL query failed: %s', $rs));
    return 0 unless %{$rs};

    $db->useDatabase($dbName);
    $rs = $db->doQuery('1', 'SHOW TABLES');
    ref $rs eq 'HASH' or fatal(sprintf('SQL query failed: %s', $rs));

    for (qw/server_ips user_gui_props reseller_props/) {
        return 0 unless exists $rs->{$_};
    }

    1;
}

1;
__END__
