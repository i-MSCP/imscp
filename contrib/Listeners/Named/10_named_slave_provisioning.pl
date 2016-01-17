# i-MSCP Listener::Named::Slave::Provisioning listener file
# Copyright (C) 2015 UncleJ, Arthur Mayer <mayer.arthur@gmail.com>
# Copyright (C) 2016 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

#
## i-MSCP listener file that provides slave DNS server(s) provisioning service
## Compatible with i-MSCP >= 1.2.12
## Slave provisioning service will be available at:
##   - http://<panel.domain.tld>:8080/provisioning/slave_provisioning.php
##   - https://<panel.domain.tld>:4443/provisioning/slave_provisioning.php (if you use ssl)
#

package Listener::Named::Slave::Provisioning;

use strict;
use warnings;
use iMSCP::Config;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser;

#
## HTTP (Basic) authentication parameters
## Those parameters are used to protect access to the provisioning script which
## is available through HTTP
#

# Authentication username
# Leave empty to disable authentication
my $authUsername = 'nuxwin';

# Authentication password
# Either an encrypted or plain password
my $authPassword = 'skittles2305';

# Tells whether or not the provided authentication password is encrypted
my $isAuthPasswordEncrypted = 0;

# Protected area identifier
my $realm = 'i-MSCP provisioning service for slave DNS servers';

#
## Other parameters
#

# Nginx configuration directory
my $nginxConfDir = '/etc/nginx';

#
## Subroutines
#

sub createHtpasswdFile
{
    if($authUsername =~ /:/) {
        error("htpasswd: username contains illegal character ':'");
        return 1;
    }

    require iMSCP::Crypt;
    my $file = iMSCP::File->new(filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/provisioning/.htpasswd");
    my $rs = $file->set(
        "$authUsername:" . ($isAuthPasswordEncrypted ? $authPassword : iMSCP::Crypt::htpasswd($authPassword))
    );
    $rs ||= $file->save();
    $rs ||= $file->owner(
        "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
        "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}"
    );
    $rs ||= $file->mode(0640);
}

#
## Event listeners
#

my $eventManager = iMSCP::EventManager->getInstance();

# Listener that is responsible to create provisioning script
$eventManager->register('afterInstall', sub {
    my $fileContent = <<'EOF';
<?php

require '../../library/imscp-lib.php';

$config = iMSCP_Registry::get('config');
$filter = iMSCP_Registry::get('bufferFilter');
$filter->compressionInformation = false;
$masterDnsServerIp = $config['BASE_SERVER_PUBLIC_IP'];

echo "// CONFIGURATION FOR MAIN DOMAIN\n";
echo "zone \"$config->BASE_SERVER_VHOST\" {\n";
echo "\ttype slave;\n";
echo "\tfile \"/var/cache/bind/$config->BASE_SERVER_VHOST.db\";\n";
echo "\tmasters { $masterDnsServerIp; };\n";
echo "\tallow-notify { $masterDnsServerIp; };\n";
echo "};\n";
echo "// END CONFIGURATION FOR MAIN DOMAIN\n\n";

$stmt = exec_query('SELECT domain_id, domain_name FROM domain');
$rowCount = $stmt->rowCount();

if ($rowCount > 0) {
    echo "// $rowCount HOSTED DOMAINS LISTED ON $config->SERVER_HOSTNAME [$masterDnsServerIp]\n";

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        echo "zone \"{$row['domain_name']}\" {\n";
        echo "\ttype slave;\n";
        echo "\tfile \"/var/cache/bind/{$row['domain_name']}.db\";\n";
        echo "\tmasters { $masterDnsServerIp; };\n";
        echo "\tallow-notify { $masterDnsServerIp; };\n";
        echo "};\n";
    }

    echo "// END DOMAINS LIST\n\n";
}

$stmt = exec_query('SELECT alias_id, alias_name FROM domain_aliasses');
$rowCount = $stmt->rowCount();

if ($rowCount > 0) {
    echo "// $rowCount HOSTED ALIASES LISTED ON $config->SERVER_HOSTNAME [$masterDnsServerIp\n";

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        echo "zone \"{$row['alias_name']}\" {\n";
        echo "\ttype slave;\n";
        echo "\tfile \"/var/cache/bind/{$row['alias_name']}.db\";\n";
        echo "\tmasters { $masterDnsServerIp; };\n";
        echo "\tallow-notify { $masterDnsServerIp; };\n";
        echo "};\n";
    }

    echo "// END ALIASES LIST\n";
}
EOF

    my $rs = iMSCP::Dir->new(dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/provisioning")->make({
        user => "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
        group => "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
        mode => 0550
    });
    return $rs if $rs;

    my $file = iMSCP::File->new(filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/provisioning/slave_provisioning.php");
    $rs = $file->set($fileContent);
    $rs ||= $file->save();
    $rs ||= $file->owner(
        "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
        "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}"
    );
    $rs ||= $file->mode(0640);
});

# Listener that is responsible to add authentication configuration
$eventManager->register('afterFrontEndBuildConfFile', sub {
    my ($tplContent, $tplName) = @_;

    if($tplName eq '00_master.conf' || $tplName eq '00_master_ssl.conf') {
        unless(-d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/provisioning") {
            my $rs = iMSCP::Dir->new(dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/provisioning")->make({
                user => "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
                group => "$main::imscpConfig{'SYSTEM_USER_PREFIX'}$main::imscpConfig{'SYSTEM_USER_MIN_UID'}",
                mode => 0550
            });

            $rs = createHtpasswdFile();
            return $rs if $rs;
        }

        my $locationSnippet = <<EOF;
    location /provisioning {
        root /var/www/imscp/gui/public;

        location ~ \\.php\$ {
            include imscp_fastcgi.conf;
            satisfy any;
            deny all;
            auth_basic "$realm";
            auth_basic_user_file $main::imscpConfig{'GUI_PUBLIC_DIR'}/provisioning/.htpasswd;
        }
    }
EOF
        $$tplContent = replaceBloc(
            "# SECTION custom BEGIN.\n",
            "# SECTION custom END.\n",
            "    # SECTION custom BEGIN.\n" .
            getBloc(
                "# SECTION custom BEGIN.\n",
                "# SECTION custom END.\n",
                $$tplContent
            ) .
            "$locationSnippet\n" .
            "    # SECTION custom END.\n",
            $$tplContent
        );
    }

    0;
}) if $authUsername;

1;
__END__
