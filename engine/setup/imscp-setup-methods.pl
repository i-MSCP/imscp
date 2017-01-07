#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2017 by internet Multi Server Control Panel
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
use DateTime::TimeZone;
use Encode qw/ decode_utf8 /;
use File::Basename;
use iMSCP::Bootstrapper;
use iMSCP::Crypt qw/ randomStr encryptRijndaelCBC decryptRijndaelCBC /;
use iMSCP::Database;
use iMSCP::DbTasksProcessor;
use iMSCP::Debug;
use iMSCP::Dialog;
use iMSCP::Dialog::InputValidation;
use iMSCP::Dir;
use iMSCP::Net;
use iMSCP::EventManager;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::OpenSSL;
use iMSCP::Rights;
use iMSCP::Packages;
use iMSCP::Plugins;
use iMSCP::Servers;
use iMSCP::Service;
use iMSCP::Stepper;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::TemplateParser;
use LWP::Simple;
use Net::LibIDN qw/ idn_to_ascii idn_to_unicode /;
use Scalar::Util qw/ openhandle /;
use Servers::sqld;

sub setupSystemDirectories
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSystemDirectories');
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupSystemDirectories');
}

sub setupInstallFiles
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupInstallFiles', $main::{'INST_PREF'});
    return $rs if $rs;

    # i-MSCP daemon must be stopped before changing any file on the files system
    iMSCP::Service->getInstance()->stop( 'imscp_daemon' );

    # Process cleanup to avoid any security risks and conflicts
    for(qw/ daemon engine gui /) {
        $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'ROOT_DIR'}/$_" )->remove();
        return $rs if $rs;
    }

    $rs = iMSCP::Dir->new( dirname => $main::{'INST_PREF'} )->rcopy( '/' );
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupInstallFiles', $main::{'INST_PREF'});
}

# Boot
sub setupBoot
{
    iMSCP::Bootstrapper->getInstance()->boot(
        {
            mode            => 'setup', # Backend mode
            config_readonly => 1, # We do not allow writing in conffile at this time
            nodatabase      => 1 # We do not establish connection to the database at this time
        }
    );

    untie(%main::imscpOldConfig) if %main::imscpOldConfig;
     
    unless(-f "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf") {
        my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscp.conf" )->copyFile(
            "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf"
        );
        return $rs if $rs;
    }

    tie %main::imscpOldConfig, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf";

    0;
}

# Set server capabilities
# Currently used for detecting IPv6 support
sub setServerCapabilities
{
    # FIXME: It is sufficient for check of IPv6 support?
    main::setupSetQuestion('IPV6_SUPPORT', -f '/proc/net/if_inet6' ? 1 : 0);
    0;
}

# Allow any server/package to register its setup event listeners before any other task
sub setupRegisterListeners
{
    my $eventManager = iMSCP::EventManager->getInstance();

    for my $server(iMSCP::Servers->getInstance()->getListWithFullNames()) {
        eval "require $server";
        my $instance = $server->factory();
        if(my $subref = $instance->can( 'registerSetupListeners' )) {
            my $rs = $subref->( $instance, $eventManager );
            return $rs if $rs;
        }
    }

    for my $package(iMSCP::Packages->getInstance()->getListWithFullNames()) {
        eval "require $package";
        my $instance = $package->getInstance();
        if(my $subref = $instance->can( 'registerSetupListeners' )) {
            my $rs = $subref->( $instance, $eventManager );
            return $rs if $rs;
        }
    }

    0;
}

# Trigger all dialog subroutines
sub setupDialog
{
    my $dialogStack = [];

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupDialog', $dialogStack);
    return $rs if $rs;

    unshift(@{$dialogStack}, (
        \&setupAskServerHostname,
        \&setupAskServerPrimaryIP,
        \&askMasterSqlUser,
        \&setupAskSqlUserHost,
        \&setupAskImscpDbName,
        \&setupAskDbPrefixSuffix,
        \&setupAskTimezone,
        \&setupAskServicesSsl,
        \&setupAskImscpBackup,
        \&setupAskDomainBackup
    ));

    # Implements a simple state machine (backup capability)
    # Any dialog subroutine *should* allow user to step back by returning 30 when 'back' button is pushed
    # In case of yesno dialog box, there is no back button. Instead, user can back up using the ESC keystroke
    # In any other context, the ESC keystroke allows user to abort.
    my ($state, $nbDialog, $dialog) = (0, scalar @{$dialogStack}, iMSCP::Dialog->getInstance());
    while($state < $nbDialog) {
        $dialog->set('no-cancel', $state == 0 ? '' : undef);

        $rs = $dialogStack->[$state]->($dialog);
        exit($rs) if $rs > 30;
        return $rs if $rs && $rs < 30;

        if($rs == 30) {
            $main::reconfigure = 'forced' if $main::reconfigure eq 'none';
            $state--;
            next;
        }

        $main::reconfigure = 'none' if $main::reconfigure eq 'forced';
        $state++;
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupDialog');
}

# Process setup tasks
sub setupTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupTasks');
    return $rs if $rs;

    my @steps = (
        [ \&setupSaveConfig,              'Saving configuration' ],
        [ \&setupCreateMasterUser,        'Creating system master user' ],
        [ \&setupServerHostname,          'Setting up server hostname' ],
        [ \&setupServiceSsl,              'Configuring SSL for i-MSCP services' ],
        [ \&setupServices,                'Enabling i-MSCP services' ],
        [ \&setupRegisterDelayedTasks,    'Registering delayed tasks' ],
        [ \&setupRegisterPluginListeners, 'Registering plugin setup listeners' ],
        [ \&setupServersAndPackages,      'Processing servers/packages' ],
        [ \&setupSetPermissions,          'Setting up permissions' ],
        [ \&setupDbTasks,                 'Processing DB tasks' ],
        [ \&setupRestartServices,         'Restarting services' ],
        [ \&setupRemoveOldConfig,         'Removing old configuration ']
    );

    my $step = 1;
    my $nbSteps = @steps;
    for (@steps) {
        $rs = step(@{$_}, $nbSteps, $step);
        return $rs if $rs;
        $step++;
    }
    
    iMSCP::Dialog->getInstance()->endGauge();

    iMSCP::EventManager->getInstance()->trigger('afterSetupTasks');
}

sub setupDeleteBuildDir
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupDeleteBuildDir', $main::{'INST_PREF'});
    $rs ||= iMSCP::Dir->new( dirname => $main::{'INST_PREF'} )->remove();
    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupDeleteBuildDir', $main::{'INST_PREF'});
}

#
## Dialog subroutines
#

# Ask for server hostname
sub setupAskServerHostname
{
    my $dialog = shift;
    my $hostname = setupGetQuestion('SERVER_HOSTNAME');

    if($main::reconfigure =~ /^(?:system_hostname|hostnames|all|forced)$/
        || !isValidHostname($hostname)
    ) {
        chomp($hostname) unless($hostname || execute('hostname -f', \$hostname, \my $stderr));
        $hostname = decode_utf8( idn_to_unicode($hostname, 'utf-8') );

        my ($rs, $msg) = (0, '');
        do {
            ($rs, $hostname) = $dialog->inputbox(<<"EOF", $hostname);

Please enter your server hostname:$msg
EOF
            $msg = (isValidHostname($hostname)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while($rs < 30 && $msg);
        return $rs if $rs >= 30;
    }

    setupSetQuestion('SERVER_HOSTNAME', idn_to_ascii( $hostname, 'utf-8' ) );
    0;
}

# Ask for server's primary IP
sub setupAskServerPrimaryIP
{
    my $dialog = shift;
    my @ipList = sort grep {
        isValidIpAddr($_, qr/(?:PRIVATE|UNIQUE-LOCAL-UNICAST|PUBLIC|GLOBAL-UNICAST)/)
    } iMSCP::Net->getInstance()->getAddresses();
    unless(@ipList) {
        error('Could not get list of server IP addresses. At least one public or private IP address must be configured.');
        return 1;
    }

    my $lanIP = setupGetQuestion('BASE_SERVER_IP');
    my $wanIP = setupGetQuestion('BASE_SERVER_PUBLIC_IP');

    if($main::reconfigure =~ /^(?:primary_ip|all|forced)$/
        || !grep($_ eq $lanIP, @ipList)
        || ($wanIP ne $lanIP && !isValidIpAddr($wanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/))
    ) {
        my ($rs, $msg) = (0, '');

        do {
            ($rs, $lanIP) = $dialog->radiolist(<<"EOF", [ @ipList ], grep($_ eq $lanIP, @ipList) ? $lanIP : $ipList[0]);

Please select your primary server IP address:
EOF
        } while $rs < 30 && !isValidIpAddr($lanIP);
        return $rs if $rs >= 30;

        # IP inside private IP range?
        if(!isValidIpAddr($lanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/)) {
            unless($wanIP) { # Try to guess WAN ip using ipinfo.io Web service
                $wanIP = get('http://ipinfo.io/ip');
                $wanIP //= '';
                chomp($wanIP);
            }

            do {
                ($rs, $wanIP) = $dialog->inputbox(<<"EOF", $wanIP);

The IP address that you selected is inside private IP range.

Please enter your public IP address (WAN IP), or leave blank to force usage of the private IP address:$msg
EOF
                $msg = '';
                if($wanIP
                    && $wanIP ne $lanIP
                    && !isValidIpAddr($wanIP, qr/(?:PUBLIC|GLOBAL-UNICAST)/)
                ) {
                    $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
                } elsif(!$wanIP) {
                    $wanIP = $lanIP;
                }
            } while $rs < 30 && $msg;
            return $rs if $rs >= 30;
        } else {
            $wanIP = $lanIP
        }
    }

    setupSetQuestion('BASE_SERVER_IP', $lanIP);
    setupSetQuestion('BASE_SERVER_PUBLIC_IP', $wanIP);
    0;
}

sub askSqlRootUser
{
    my ($dialog) = @_;
    my $hostname = setupGetQuestion('DATABASE_HOST', $main::imscpConfig{'SQL_SERVER'} eq 'remote_server' ? '' : 'localhost');
    my $port = setupGetQuestion('DATABASE_PORT', 3306);
    my $user = setupGetQuestion('SQL_ROOT_USER', 'root');
    my $pwd = setupGetQuestion('SQL_ROOT_PASSWORD');

    if($hostname eq 'localhost') {
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

    my ($rs, $msg) = (0, '');

    do {
        ($rs, $hostname) = $dialog->inputbox(<<"EOF", $hostname);

Please enter your SQL server hostname or IP address:$msg
EOF
        $msg = '';
        if($hostname ne 'localhost'
            && !isValidHostname($hostname)
            && !isValidIpAddr($hostname)
        ) {
            $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
        }
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    do {
        ($rs, $port) = $dialog->inputbox(<<"EOF", $port);

Please enter your SQL server port:$msg
EOF
        $msg = '';
        if(!isNumber($port)
            || !isNumberInRange($port, 1025, 65535)
        ) {
            $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
        }
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    do {
        ($rs, $user) = $dialog->inputbox(<<"EOF", $user);

Please enter your SQL root username:$msg

Note that this user must have full privileges on the SQL server.
i-MSCP only uses that user while installation or reconfiguration.
EOF
        $msg = (isNotEmpty($user)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    do {
        ($rs, $pwd) = $dialog->passwordbox(<<"EOF");

Please enter your SQL root user password:$msg
EOF
        $msg = (isNotEmpty($pwd)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError; 
    } while $rs < 30 && $msg;
    return $rs if $rs >= 30;

    if(my $connectError = tryDbConnect(idn_to_ascii( $hostname, 'utf-8' ), $port, $user, $pwd)) {
        $dialog->msgbox(<<"EOF");

\\Z1Connection to SQL server failed\\Zn

i-MSCP installer could not connect to SQL server using the following data:

\\Z4Host:\\Zn $hostname
\\Z4Port:\\Zn $port
\\Z4Username:\\Zn $user
\\Z4Password:\\Zn $pwd

Error was: \\Z1$connectError\\Zn

Please try again.
EOF
        goto &{askSqlRootUser};
    }

    setupSetQuestion('DATABASE_TYPE', 'mysql');
    setupSetQuestion('DATABASE_HOST', idn_to_ascii($hostname, 'utf-8'));
    setupSetQuestion('DATABASE_PORT', $port);
    setupSetQuestion('SQL_ROOT_USER', $user);
    setupSetQuestion('SQL_ROOT_PASSWORD', $pwd);
    0;
}

sub askMasterSqlUser
{
    my $dialog = shift;
    my $hostname = setupGetQuestion('DATABASE_HOST');
    my $port = setupGetQuestion('DATABASE_PORT');
    my $user = setupGetQuestion('DATABASE_USER', 'imscp_user');
    $user = 'imscp_user' if lc($user) eq 'root'; # Handle upgrade case
    my $pwd = setupGetQuestion('DATABASE_PASSWORD');
    $pwd = decryptRijndaelCBC($main::imscpDBKey, $main::imscpDBiv, $pwd) unless $pwd eq '' || iMSCP::Getopt->preseed;
    my $rs = 0;

    $rs = askSqlRootUser($dialog) if iMSCP::Getopt->preseed;
    return $rs if $rs;

    if($main::reconfigure =~ /(?:sql|servers|all|forced)$/
        || !isNotEmpty($hostname)
        || !isNotEmpty($port)
        || !isNotEmpty($user)
        || !isStringNotInList($user, 'root')
        || !isNotEmpty($pwd)
        || (!iMSCP::Getopt->preseed && tryDbConnect($hostname, $port, $user, $pwd))
    ) {
        $rs = askSqlRootUser($dialog) unless iMSCP::Getopt->preseed;
        return $rs if $rs >= 30;

        my $msg = '';
        do {
            ($rs, $user) = $dialog->inputbox( <<"EOF", $user);

Please enter a username for the master i-MSCP SQL user:$msg
EOF
            $msg = '';
            if(!isValidUsername($user)
                || !isStringNotInList($user, 'root')
            ) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        $pwd = '';
        do {
            ($rs, $pwd) = $dialog->inputbox( <<"EOF", $pwd || randomStr(16, iMSCP::Crypt::ALNUM));

Please enter a password for the master i-MSCP SQL user:$msg
EOF
            $msg = (isValidPassword($pwd)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError;
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    setupSetQuestion('DATABASE_USER', $user);
    setupSetQuestion('DATABASE_PASSWORD', encryptRijndaelCBC($main::imscpDBKey, $main::imscpDBiv, $pwd));
    # Substitute SQL root user data with i-MSCP master user data if needed
    setupSetQuestion('SQL_ROOT_USER', setupGetQuestion('SQL_ROOT_USER', $user));
    setupSetQuestion('SQL_ROOT_PASSWORD', setupGetQuestion('SQL_ROOT_PASSWORD', $pwd));
    0;
}

sub setupAskSqlUserHost
{
    my $dialog = shift;

    if($main::imscpConfig{'SQL_SERVER'} ne 'remote_server') {
        setupSetQuestion('DATABASE_USER_HOST', 'localhost');
        return 0;
    }

    my $hostname = setupGetQuestion('DATABASE_USER_HOST', setupGetQuestion('BASE_SERVER_PUBLIC_IP'));
    if(grep { $hostname eq $_ } ('localhost', '127.0.0.1', '::1')) {
        $hostname = setupGetQuestion('BASE_SERVER_PUBLIC_IP');
    }

    if($main::reconfigure =~ /^(?:sql|servers|all|forced)$/
       || (
           $hostname ne '%'
           && !isValidHostname($hostname)
           && !isValidIpAddr($hostname, qr/^(?:PUBLIC|GLOBAL-UNICAST)$/)
       )
    ) {
        my ($rs, $msg) = (0, '');
        do {
            ($rs, $hostname) = $dialog->inputbox(<<"EOF", decode_utf8( idn_to_unicode($hostname, 'utf-8') ) );

Please enter the host from which SQL users created by i-MSCP must be allowed to connect:$msg
EOF
            $msg = '';
            if($hostname ne '%'
                && !isValidHostname($hostname)
                && !isValidIpAddr($hostname, qr/^(?:PUBLIC|GLOBAL-UNICAST)$/)
            ) {
               $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            }
       } while $rs < 30 && $msg;
       return $rs if $rs >= 30;
   }

   setupSetQuestion('DATABASE_USER_HOST', idn_to_ascii( $hostname, 'utf-8' ));
   0;
}

sub setupAskImscpDbName
{
    my $dialog = shift;
    my $dbName = setupGetQuestion('DATABASE_NAME', 'imscp');
    my ($rs, $msg) = (0, '');

    if($main::reconfigure =~ /^(?:sql|servers|all|forced)$/
        || (!setupIsImscpDb($dbName) && !iMSCP::Getopt->preseed)
    ) {
        my ($rs, $msg) = (0, '');
        do {
            ($rs, $dbName) = $dialog->inputbox(<<"EOF", $dbName);

Please enter a database name for i-MSCP:$msg
EOF
            $msg = '';
            if(!isValidDbName($dbName)) {
                $msg = $iMSCP::Dialog::InputValidation::lastValidationError;
            } else {
                my $db = iMSCP::Database->factory();
                local $@;
                eval { $db->useDatabase($dbName); };
                if(!$@ && !setupIsImscpDb($dbName)) {
                    $msg = "\n\n\\Z1Database '$dbName' exists but doesn't looks like an i-MSCP database.\\Zn\n\nPlease try again:";
                }
            }
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;

        my $oldDbName = setupGetQuestion('DATABASE_NAME');
        if($oldDbName
            && $dbName ne $oldDbName
            && setupIsImscpDb($oldDbName)
        ) {
            if($dialog->yesno(<<"EOF", 1)) {
A database '$main::imscpConfig{'DATABASE_NAME'}' for i-MSCP already exists.

Are you sure you want to create a new database for i-MSCP?
Keep in mind that the new database will be free of any reseller and customer data.

\\Z4Note:\\Zn If the database you want to create already exists, nothing will happen.
EOF
                goto &{setupAskImscpDbName};
            }
        }
    }

    setupSetQuestion('DATABASE_NAME', $dbName);
    0;
}

# Ask for database prefix/suffix
sub setupAskDbPrefixSuffix
{
    my $dialog = shift;
    my $prefix = setupGetQuestion('MYSQL_PREFIX');

    if($main::reconfigure =~ /^(?:sql|servers|all|forced)$/
        || $prefix !~ /^(?:behind|infront|none)$/
    ) {
        (my $rs, $prefix) = $dialog->radiolist(<<"EOF", [ 'infront', 'behind', 'none' ], $prefix =~ /^(?:behind|infront)$/ ? $prefix : 'none');

\\Z4\\Zb\\ZuMySQL Database Prefix/Suffix\\Zn

Do you want use a prefix or suffix for customer's SQL databases?

\\Z4Infront:\\Zn A numeric prefix such as '1_' will be added to each customer
         SQL user and database name.
 \\Z4Behind:\\Zn A numeric suffix such as '_1' will be added to each customer
         SQL user and database name.
   \\Z4None\\Zn: Choice will be let to customer.
EOF
        return $rs if $rs >= 30;
    }

    setupSetQuestion('MYSQL_PREFIX', $prefix);
    0;
}

# Ask for timezone
sub setupAskTimezone
{
    my $dialog = shift;
    my $timezone = setupGetQuestion('TIMEZONE');

    if($main::reconfigure =~ /^(?:timezone|all|forced)$/
        || !isValidTimezone($timezone)
    ) {
        my ($rs, $msg) = (0, '');
        do {
            ($rs, $timezone) = $dialog->inputbox(<<"EOF", $timezone || DateTime::TimeZone->new( name => 'local' )->name());

Please enter your timezone:$msg
EOF
            $msg = (isValidTimezone($timezone)) ? '' : $iMSCP::Dialog::InputValidation::lastValidationError; 
        } while $rs < 30 && $msg;
        return $rs if $rs >= 30;
    }

    setupSetQuestion('TIMEZONE', $timezone);
    0;
}

# Ask for services SSL
sub setupAskServicesSsl
{
    my ($dialog) = @_;
    my $hostname = setupGetQuestion('SERVER_HOSTNAME');
    my $hostnameUnicode = decode_utf8(idn_to_unicode($hostname, 'utf-8'));
    my $sslEnabled = setupGetQuestion('SERVICES_SSL_ENABLED');
    my $selfSignedCertificate = setupGetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE', 'no');
    my $privateKeyPath = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH', '/root');
    my $passphrase = setupGetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE');
    my $certificatePath = setupGetQuestion('SERVICES_SSL_CERTIFICATE_PATH', '/root');
    my $caBundlePath = setupGetQuestion('SERVICES_SSL_CA_BUNDLE_PATH', '/root');
    my $openSSL = iMSCP::OpenSSL->new();

    if($main::reconfigure =~ /^(?:services_ssl|ssl|all|forced)$/
        || $sslEnabled !~ /^(?:yes|no)$/
        || ($sslEnabled eq 'yes' && $main::reconfigure =~ /^(?:system_hostname|hostnames)$/)
    ) {
        my $rs = $dialog->yesno(<<"EOF", $sslEnabled eq 'no' ? 1 : 0);

Do you want to enable SSL for FTP and MAIL services?
EOF
        if($rs == 0) {
            $sslEnabled = 'yes';
            $rs = $dialog->yesno(<<"EOF", $selfSignedCertificate eq 'no' ? 1 : 0);

Do you have a SSL certificate for the $hostnameUnicode domain?
EOF
            if($rs == 0) {
                my $msg = '';

                do {
                    $dialog->msgbox(<<"EOF");
$msg
Please select your private key in next dialog.
EOF
                    do {
                        ($rs, $privateKeyPath) = $dialog->fselect($privateKeyPath);
                    } while($rs < 30 && !($privateKeyPath && -f $privateKeyPath));
                    return $rs if $rs >= 30;

                    ($rs, $passphrase) = $dialog->passwordbox(<<"EOF", $passphrase);

Please enter the passphrase for your private key if any:
EOF
                    return $rs if $rs >= 30;

                    $openSSL->{'private_key_container_path'} = $privateKeyPath;
                    $openSSL->{'private_key_passphrase'} = $passphrase;

                    $msg = '';
                    if($openSSL->validatePrivateKey()) {
                        getMessageByType('error', { remove => 1 });
                        $msg = "\n\\Z1Invalid private key or passphrase.\\Zn\n\nPlease try again.";
                    }
                } while $rs < 30 && $msg;
                return $rs if $rs >= 30;

                $rs = $dialog->yesno(<<"EOF");

Do you have a SSL CA Bundle?
EOF
                if($rs == 0) {
                    do {
                        ($rs, $caBundlePath) = $dialog->fselect($caBundlePath);
                    } while($rs < 30 && !($caBundlePath && -f $caBundlePath));
                    return $rs if $rs >= 30;

                    $openSSL->{'ca_bundle_container_path'} = $caBundlePath;
                } else {
                    $openSSL->{'ca_bundle_container_path'} = '';
                }

                $dialog->msgbox(<<"EOF");

Please select your SSL certificate in next dialog.
EOF
                $rs = 1;
                do {
                    $dialog->msgbox(<<"EOF") unless $rs;

\\Z1Invalid SSL certificate. Please try again.\\Zn
EOF
                    do {
                        ($rs, $certificatePath) = $dialog->fselect($certificatePath);
                    } while($rs < 30 && !($certificatePath && -f $certificatePath));
                    return $rs if $rs >= 30;

                    getMessageByType('error', { remove => 1 });
                    $openSSL->{'certificate_container_path'} = $certificatePath;
                } while($rs < 30 && $openSSL->validateCertificate());
                return $rs if $rs >= 30;
            } else {
                $selfSignedCertificate = 'yes';
            }
        } else {
            $sslEnabled = 'no';
        }
    } elsif($sslEnabled eq 'yes' && !iMSCP::Getopt->preseed) {
        $openSSL->{'private_key_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'ca_bundle_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";
        $openSSL->{'certificate_container_path'} = "$main::imscpConfig{'CONF_DIR'}/imscp_services.pem";

        if($openSSL->validateCertificateChain()) {
            getMessageByType('error', { remove => 1 });
            iMSCP::Dialog->getInstance()->msgbox(<<"EOF");

Your SSL certificate for the FTP and MAIL services is missing or invalid.
EOF
            setupSetQuestion('SERVICES_SSL_ENABLED', '');
            goto &{setupAskServicesSsl};
        }

        # In case the certificate is valid, we skip SSL setup process
        setupSetQuestion('SERVICES_SSL_SETUP', 'no');
    }

    setupSetQuestion('SERVICES_SSL_ENABLED', $sslEnabled);
    setupSetQuestion('SERVICES_SSL_SELFSIGNED_CERTIFICATE', $selfSignedCertificate);
    setupSetQuestion('SERVICES_SSL_PRIVATE_KEY_PATH', $privateKeyPath);
    setupSetQuestion('SERVICES_SSL_PRIVATE_KEY_PASSPHRASE', $passphrase);
    setupSetQuestion('SERVICES_SSL_CERTIFICATE_PATH', $certificatePath);
    setupSetQuestion('SERVICES_SSL_CA_BUNDLE_PATH', $caBundlePath);
    0;
}

# Ask for i-MSCP backup feature
sub setupAskImscpBackup
{
    my $dialog = shift;
    my $backupImscp = setupGetQuestion('BACKUP_IMSCP');

    if($main::reconfigure =~ /^(?:backup|all|forced)$/
        || $backupImscp !~ /^(?:yes|no)$/
    ) {
        (my $rs, $backupImscp) = $dialog->radiolist(<<"EOF", [ 'yes', 'no' ], $backupImscp ne 'no' ? 'yes' : 'no');

\\Z4\\Zb\\Zui-MSCP Backup Feature\\Zn

Do you want to activate the backup feature for i-MSCP?

The backup feature for i-MSCP allows the daily save of all i-MSCP configuration files and its database. It's greatly recommended to activate this feature.
EOF
        return $rs if $rs >= 30;
    }

    setupSetQuestion('BACKUP_IMSCP', $backupImscp);
    0;
}

# Ask for customer backup feature
sub setupAskDomainBackup
{
    my $dialog = shift;
    my $backupDomains = setupGetQuestion('BACKUP_DOMAINS');

    if($main::reconfigure =~ /^(?:backup|all|forced)$/
        || $backupDomains !~ /^(?:yes|no)$/
    ) {
        (my $rs, $backupDomains) = $dialog->radiolist(<<"EOF", [ 'yes', 'no' ], $backupDomains ne 'no' ? 'yes' : 'no');

\\Z4\\Zb\\ZuDomains Backup Feature\\Zn

Do you want to activate the backup feature for customers?

This feature allows resellers to enable backup for their customers such as:

 - Full (domains and SQL databases)
 - Domains only (Web files)
 - SQL databases only
 - None (no backup)
EOF
        return $rs if $rs >= 30;
    }

    setupSetQuestion('BACKUP_DOMAINS', $backupDomains);
    0;
}

#
## Setup subroutines
#

sub setupSaveConfig
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupSaveConfig');
    return $rs if $rs;

    # Re-open main configuration file in read/write mode
    iMSCP::Bootstrapper->getInstance()->loadMainConfig(
        {
            nocreate        => 1,
            config_readonly => 0
        }
    );

    while(my($key, $value) = each(%main::questions)) {
        next unless exists $main::imscpConfig{$key};
        $main::imscpConfig{$key} = $value;
    } 

    # Re-open main configuration file in read only mode
    iMSCP::Bootstrapper->getInstance()->loadMainConfig(
        {
            nocreate        => 1,
            config_readonly => 1
        }
    );

    iMSCP::EventManager->getInstance()->trigger('afterSetupSaveConfig');
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

sub setupServerHostname
{
    my $hostname = setupGetQuestion('SERVER_HOSTNAME');
    my $lanIP = setupGetQuestion('BASE_SERVER_IP');

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupServerHostname', \$hostname, \$lanIP);
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
    $content .= "$lanIP\t$hostname\t$host\n";
    $content .= "::ffff:$lanIP\t$hostname\t$host\n" if $net->getAddrVersion($lanIP) eq 'ipv4';
    $content .= "::1\tip6-localhost\tip6-loopback\n" if $net->getAddrVersion($lanIP) eq 'ipv4';
    $content .= "::1\tip6-localhost\tip6-loopback\t$host\n" if $net->getAddrVersion($lanIP) eq 'ipv6';
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
                email => main::setupGetQuestion( 'DEFAULT_ADMIN_ADDRESS' )
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
    
    # Make sure that the imscp_mountall service is started
    $serviceMngr->start('imscp_mountall');
    0;
}

sub setupRegisterDelayedTasks
{
    my $eventManager = iMSCP::EventManager->getInstance();
    $eventManager->register('afterSqldPreinstall', \&setupMasterSqlUser);
    $eventManager->register('afterSqldPreinstall', \&setupSecureSqlInstallation);
    $eventManager->register('afterSqldPreinstall', \&setupDatabase);
    $eventManager->register('afterSqldPreinstall', \&setupPrimaryIP);
}

sub setupMasterSqlUser
{
    my $user = setupGetQuestion( 'DATABASE_USER' );
    my $userHost = setupGetQuestion( 'DATABASE_USER_HOST' );
    my $oldUserHost = $main::imscpOldConfig{'DATABASE_USER_HOST'} || '';
    my $pwd = decryptRijndaelCBC($main::imscpDBKey, $main::imscpDBiv, setupGetQuestion( 'DATABASE_PASSWORD' ));
    my $oldUser = $main::imscpOldConfig{'DATABASE_USER'} || '';

    my $sqlServer = Servers::sqld->factory();

    # Remove old user if any
    for my $sqlUser ($oldUser, $user) {
        next unless $sqlUser;
        for my $host($userHost, $oldUserHost) {
            next unless $host;
            $sqlServer->dropUser( $sqlUser, $host );
        }
    }

    # Create user
    $sqlServer->createUser( $user, $userHost, $pwd );

    # Grant all privileges to that user (including GRANT otpion)
    my $qrs = iMSCP::Database->factory()->doQuery(
        'g', "GRANT ALL PRIVILEGES ON *.* TO ?\@? WITH GRANT OPTION", $user, $userHost
    );
    unless (ref $qrs eq 'HASH') {
        error( sprintf( 'Could not grant privileges to master i-MSCP SQL user: %s', $qrs ) );
        return 1;
    }

    0;
}

sub setupPrimaryIP
{
    my $primaryIP = setupGetQuestion('BASE_SERVER_IP');

    iMSCP::EventManager->getInstance()->trigger('beforeSetupPrimaryIP', $primaryIP);

    my $db = iMSCP::Database->factory();
    $db->useDatabase(setupGetQuestion('DATABASE_NAME'));

    my $qrs = $db->doQuery('ip_number', 'SELECT ip_number, ip_card FROM server_ips WHERE ip_number = ?', $primaryIP);
    unless(ref $qrs eq 'HASH') {
        error($qrs);
        return 1;
    }

    my $net = iMSCP::Net->getInstance();
    my $netCard = $net->getAddrDevice($primaryIP);
    unless(defined $netCard) {
        error(sprintf("Could not find network interface for the `%s' IP address", $primaryIP));
        return 1;
    }

    unless(%{$qrs}) {
        $qrs = $db->doQuery(
            'i', 'INSERT INTO server_ips (ip_number, ip_card, ip_config_mode, ip_status) VALUES(?, ?, ?, ?)',
            $primaryIP, $netCard, 'manual', 'ok'
        );
        unless (ref $qrs eq 'HASH') {
            error(sprintf("Could not add the `%s' IP address: %s", $primaryIP, $qrs));
            return 1;
        }
    } else {
        $qrs = $db->doQuery('u', 'UPDATE server_ips SET ip_card = ? WHERE ip_number = ?', $netCard, $primaryIP);
        unless (ref $qrs eq 'HASH') {
            error(sprintf("Could not update `%s' IP address: %s", $primaryIP, $qrs));
            return 1;
        }
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupPrimaryIP', $primaryIP);
}

# Create/Update i-MSCP core database
sub setupDatabase
{
    my $dbName = setupGetQuestion('DATABASE_NAME');

    unless(setupIsImscpDb($dbName)) {
        my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupDatabase', \$dbName);
        return $rs if $rs;

        my $db = iMSCP::Database->factory();
        my $qdbName = $db->quoteIdentifier($dbName);
        $rs = $db->doQuery('c', "CREATE DATABASE $qdbName CHARACTER SET utf8 COLLATE utf8_unicode_ci;");

        if(ref $rs ne 'HASH') {
            error(sprintf("Could not create the `%s' SQL database: %s", $dbName, $rs));
            return 1;
        }

        $db->set('DATABASE_NAME', $dbName);
        !$db->connect() or die('Could not reconnect to SQL server');
        $rs = setupImportSqlSchema($db, "$main::imscpConfig{'CONF_DIR'}/database/database.sql");
        $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupDatabase', \$dbName);
        return $rs if $rs;
    }

    # In all cases, we process database update. This is important because sometime some developer forget to update the
    # database revision in the main database.sql file.
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupUpdateDatabase');
    $rs ||= execute(
        "php -d date.timezone=UTC $main::imscpConfig{'ROOT_DIR'}/engine/setup/updDB.php",
        \my $stdout,
        \my $stderr
    );
    debug($stdout) if $stdout;
    error($stderr || 'Unknown error') if $rs;
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
            error(sprintf('Could not remove `root` users: %s', $qrs));
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

    (tied %main::imscpConfig)->{'temporary'} = 1;
    $main::imscpConfig{'DEBUG'} = iMSCP::Getopt->debug;

    for my $script ('set-engine-permissions.pl', 'set-gui-permissions.pl') {
        startDetail();

        my @options = (
            '--setup',
            $script eq 'set-engine-permissions.pl' && iMSCP::Getopt->fixPermissions ? '--fix-permissions' : ''
        );

        my $stderr;
        $rs = executeNoWait(
            "perl $main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/$script @options",
            (iMSCP::Getopt->noprompt && iMSCP::Getopt->verbose ? undef : sub {
                my $str = shift; while ($str =~ s/^(.*)\t(.*)\t(.*)\n//) { step(undef, $1, $2, $3); }
            }),
            sub { $stderr .= shift; }
        );

        endDetail();

        error(sprintf('Error while setting permissions: %s', $stderr || 'Unknown error')) if $rs;
        return $rs if $rs;
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupSetPermissions');
}

sub setupDbTasks
{
    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupDbTasks');
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

    $rs ||= iMSCP::EventManager->getInstance()->trigger('afterSetupDbTasks');
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

    for my $pluginPath(iMSCP::Plugins->getInstance()->getList()) {
        my $pluginName = basename($pluginPath, '.pm');
        next unless grep($_ eq $pluginName, @{$pluginNames});
        eval { require $pluginPath; };
        my $plugin = 'Plugin::' . $pluginName;

        if(my $subref = $plugin->can( 'registerSetupListeners') ) {
            $rs = $subref->( $plugin, $eventManager );
            return $rs if $rs;
        }
    }

    iMSCP::EventManager->getInstance()->trigger('afterSetupRegisterPluginListeners');
}

sub setupServersAndPackages
{
    my $eventManager = iMSCP::EventManager->getInstance();
    my @srvs = iMSCP::Servers->getInstance()->getListWithFullNames();
    my @pkgs = iMSCP::Packages->getInstance()->getListWithFullNames();
    my $nSteps = @srvs + @pkgs;
    my $rs = 0;

    for my $task(qw/ PreInstall Install PostInstall /) {
        my $lcTask = lc($task);

        $rs ||= $eventManager->trigger('beforeSetup' . $task . 'Servers');
        return $rs if $rs;

        startDetail();
        my $nStep = 1;

        for my $srv(@srvs) {
            eval "require $srv";
            my $instance = $srv->factory();
            if(my $subref = $instance->can($lcTask)) {
                $rs = step(sub { $subref->($instance) }, sprintf("Running %s %s tasks...", $srv, $lcTask), $nSteps, $nStep);
                last if $rs;
            }
            $nStep++;
        }

        $rs ||= $eventManager->trigger('afterSetup'.$task.'Servers');

        unless($rs) {            
            $rs ||= $eventManager->trigger('beforeSetup'.$task.'Packages');
            unless($rs) {
                for my $pkg(@pkgs) {
                    eval "require $pkg";
                    my $instance = $pkg->getInstance();
                    if(my $subref = $instance->can($lcTask)) {
                        $rs = step(sub { $subref->($instance) }, sprintf("Running %s %s tasks...", $pkg, $lcTask), $nSteps, $nStep);
                        last if $rs;
                    }
                    $nStep++;
                }
            }
        }    

        endDetail();
        $rs ||= $eventManager->trigger('afterSetup'.$task.'Packages');
        last if $rs;
    }

    $rs;
}

sub setupRestartServices
{
    my @services = ();

    my $rs = iMSCP::EventManager->getInstance()->trigger('beforeSetupRestartServices', \@services);
    return $rs if $rs;

    my $serviceMngr = iMSCP::Service->getInstance();
    unshift @services, (
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

sub setupRemoveOldConfig
{
    untie %main::imscpOldConfig;
    iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/imscpOld.conf")->delFile();
}

#
## Low level subroutines
#

sub setupGetQuestion
{
    my ($qname, $default) = @_;
    $default //= '';

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
