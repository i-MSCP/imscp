# i-MSCP Listener::Named::Slave::Provisioning listener file
# Copyright (C) 2015 UncleJ, Arthur Mayer <mayer.arthur@gmail.com>
# Copyright (C) 2016-2019 Laurent Declercq <l.declercq@nuxwin.com>
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
## Provides slave DNS server(s) provisioning service.
## This listener file requires i-MSCP 1.2.12 or newer.
## Slave provisioning service will be available at:
##   - http://<panel.domain.tld>:8080/provisioning/slave_provisioning.php
##   - https://<panel.domain.tld>:4443/provisioning/slave_provisioning.php (if you use ssl)
#

package Listener::Named::Slave::Provisioning;

use strict;
use warnings;
use iMSCP::Boolean;
use iMSCP::Debug 'error';
use iMSCP::Dir;
use iMSCP::EventManager;
use iMSCP::File;
use iMSCP::TemplateParser qw/ replaceBloc getBloc /;

#
## HTTP (Basic) authentication parameters
## Those parameters are used to restrict access to the provisioning script which
## is available through HTTP(s)
#

# Authentication username
# Leave empty to disable authentication
my $AUTH_USER = '';

# Authentication password
# Either an encrypted or plain password
# If an encrypted password, don't forget to set the $IS_HASHED_PASSWD parameter
# value to TRUE
my $AUTH_PASSWD = '';

# Tells whether or not the provided authentication password is encrypted
my $IS_HASHED_PASSWD = FALSE;

# Protected area identifier
my $REALM = 'i-MSCP provisioning service for slave DNS servers';

#
## Other parameters
#

#
## Please, don't edit anything below this line
#

# Create the .htpasswd file to restrict access to the provisioning script
sub createHtpasswdFile
{
    if ( $AUTH_USER =~ /:/ ) {
        error( "htpasswd: username contains illegal character ':'" );
        return 1;
    }

    require iMSCP::Crypt;
    my $file = iMSCP::File->new(
        filename => "$::imscpConfig{'GUI_ROOT_DIR'}/public/provisioning/.htpasswd"
    );
    $file->set(
        "$AUTH_USER:" . ( $IS_HASHED_PASSWD
            ? $AUTH_PASSWD : iMSCP::Crypt::htpasswd( $AUTH_PASSWD )
        )
    );

    my $rs = $file->save();
    $rs ||= $file->owner(
        $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
        $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
    );
    $rs ||= $file->mode( 0640 );
}

#
## Event listeners
#

# Listener that is responsible to add authentication configuration
iMSCP::EventManager->getInstance()->register(
    'afterFrontEndBuildConfFile',
    sub
    {
        my ( $tplContent, $tplName ) = @_;

        return 0 unless grep (
            $_ eq $tplName, qw/ 00_master.nginx 00_master_ssl.nginx /
        );

        my $locationSnippet = <<"EOF";
    location ^~ /provisioning/ {
        root /var/www/imscp/gui/public;

        location ~ \\.php\$ {
            include imscp_fastcgi.conf;
            satisfy any;
            deny all;
            auth_basic "$REALM";
            auth_basic_user_file $::imscpConfig{'GUI_ROOT_DIR'}/public/provisioning/.htpasswd;
        }
    }
EOF
        ${ $tplContent } = replaceBloc(
            "# SECTION custom BEGIN.\n",
            "# SECTION custom END.\n",
            "    # SECTION custom BEGIN.\n"
                . getBloc(
                "# SECTION custom BEGIN.\n",
                "# SECTION custom END.\n",
                ${ $tplContent }
            )
                . "$locationSnippet\n"
                . "    # SECTION custom END.\n",
            ${ $tplContent }
        );
        0;
    }
) if length $AUTH_USER;

# Listener that is responsible to create provisioning script
iMSCP::EventManager->getInstance()->register(
    'afterFrontEndInstall',
    sub
    {
        my $fileContent = <<'EOF';
<?php
require '../../library/imscp-lib.php';
$config = iMSCP_Registry::get('config');
if(iMSCP_Registry::isRegistered('bufferFilter')) {
    $filter = iMSCP_Registry::get('bufferFilter');
    $filter->compressionInformation = false;
}
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
        local $@;
        eval {
            iMSCP::Dir->new(
                dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/public/provisioning"
            )->make( {
                user  => $::imscpConfig{'SYSTEM_USER_PREFIX'}
                    . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
                group => $::imscpConfig{'SYSTEM_USER_PREFIX'}
                    . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
                mode  => 0550
            } );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( length $AUTH_USER ) {
            my $rs = createHtpasswdFile();
            return $rs if $rs;
        }

        my $file = iMSCP::File->new(
            filename => "$::imscpConfig{'GUI_ROOT_DIR'}/public/provisioning/slave_provisioning.php"
        );
        $file->set( $fileContent );

        my $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'SYSTEM_USER_PREFIX'}
                . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            $::imscpConfig{'SYSTEM_USER_PREFIX'}
                . $::imscpConfig{'SYSTEM_USER_MIN_UID'}
        );
        $rs ||= $file->mode( 0640 );
    }
);

1;
__END__
