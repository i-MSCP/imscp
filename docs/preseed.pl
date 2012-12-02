#!/usr/bin/perl

# i-MSCP preseed.pl template file for installer preseeding and hooking features
# See documentation at LINK TO DOC

#
## Preseeding
#

## Autoinstall questions

$main::preseed{'SERVERS'} = {
	'HTTPD' => 'apache_itk',	# Server to use for the Httpd service (apache_itk|apache_fgcid)
	'PO' => 'courier'			# Server to use for the po service (courier|dovecot)
};

## Setup questions

# Server hostname
$main::preseed{'SERVER_HOSTNAME'} = ''; # Fully qualified hostname name such as imscp.i-mscp.net

# Domain name from which the i-MSCP frontEnd should be reachable
$main::preseed{'BASE_SERVER_VHOST'} = ''; # Fully qualified domain name such as panel. imscp.i-mscp.net

# Local DNS resolver
$main::preseed{'LOCAL_DNS_RESOLVER'} = 'yes';

# Base server Ip (primary external ip) - Accept both ipV4 and ipV6 ips
# Ip must be already configured (see ifconfig)
$main::preseed{'BASE_SERVER_IP'} = ''; # 192.168.5.110

# Additional ips to make available for i-MSCP - Accept both IPv4 and IPv6
# Any ips listed must be already configured (see ifconfig)
$main::preseed{'SERVER_IPS'} = []; # ['192.168.5.115', '192.168.5.115']

# SQL DSN and i-MSCP SQL user
$main::preseed{'DATABASE_TYPE'} = 'mysql'; # database type (for now, only 'mysql' is allowed)
$main::preseed{'DATABASE_HOST'} = 'localhost'; # Accept both hostname and ip
$main::preseed{'DATABASE_PORT'} = '3306'; # Only relevant for TCP (eg: when DATABASE_HOST is not equal to localhost)
$main::preseed{'DATABASE_USER'} = 'root'; # SQL user (user must exist and have full privileges on SQL server)
$main::preseed{'DATABASE_PASSWORD'} = 'password'; # Password shouldn't be empty

# i-MSCP database name
$main::preseed{'DATABASE_NAME'} = 'imscp';

# MySQL prefix/sufix
$main::preseed{'MYSQL_PREFIX'} = 'no'; # (yes|no)
$main::preseed{'MYSQL_PREFIX_TYPE'} = 'none'; # (none if MYSQL_PREFIX equal to 'no' or infront or behind)

# Default admin
$main::preseed{'ADMIN_LOGIN_NAME'} = 'admin'; #
$main::preseed{'ADMIN_PASSWORD'} = 'password'; # Password shouldn't be empty
$main::preseed{'DEFAULT_ADMIN_ADDRESS'} = ''; # Valid email address

# PHP Timzone
$main::preseed{'PHP_TIMEZONE'} = 'Europe/London'; # A valid PHP timezone (see http://php.net/manual/en/timezones.php)

# PhpMyAdmin restricted SQL user
$main::preseed{'PMA_USER'} = 'pma';
$main::preseed{'PMA_PASSWORD'} = 'password'; # Password shouldn't be empty

#
## SSL questions
#

# SSL for i-MSCP services
$main::preseed{'SSL_ENABLED'} = 'no'; # (yes|no)

# Only releavant if the SSL_ENABLED parameter is set to 'yes'
$main::preseed{'CERTIFICATE_PATH'} = ''; # Leave blank if you do not have certificate (selfsigned certificate will be used instead)

# Only releavant if the SSL_ENABLED parameter is set to 'yes'
$main::preseed{'CERTIFICATE_PASSPHRASE'} = ''; # Leave blank if your certificate is not protected by a passphrase

# Only releavant if the SSL_ENABLED parameter is set to 'yes'
$main::preseed{'INTERMEDIATE_CERTIFICATE_PATH'} = ''; # Leave blank if you do not have intermediate certificate


# iMSCP backup feature
$main::preseed{'BACKUP_IMSCP'} = 'yes'; # (yes|no) - It's recommended to set this question to 'yes'

# Customers backup feature
$main::preseed{'BACKUP_DOMAINS'} = 'no'; # (yes|no)

## Server questions

# Proftpd sql user
$main::preseed['FTPD_SQL_USER'] = 'vftp'
$main::preseed['FTPD_SQL_PASSWORD'] = '' # Password shouldn't be empty

# apache_fcgi - Only relevant if server for httpd service is set to apache_fcgi
$main::preseed{'PHP_FASTCGI'}  = 'fcgid'; # (fcgid|fastcgi)

# bind
$main::preseed{'BIND_MODE'} = 'master'; # (master|slave)

# Only relevant if you set the BIND_MODE parameter to 'slave'
# Allow to indicate IP addresses of your primary DNS server(s)
$main::preseed{'PRIMARY_DNS'} = '' # (empty value or list of ips, each separated by semicolon)

# Only relevant if you set the BIND_MODE parameter to master and if you have slave DNS server(s)
$main::preseed{'SECONDARY_DNS'} = 'no' # (no|list of ips, each separated by semicolon)

# dovecot SQL USER (only relevant if you choice dovecot for the PO service
$main::preseed{'DOVECOT_SQL_USER'} = 'dovecot_user';
$main::preseed{'DOVECOT_SQL_PASSWORD'} = 'password'; # Password shouldn't be empty

## Addons questions

# Aswtats addon
$main::preseed{'AWSTATS_ACTIVE'} = 'yes' # (yes|no)
$main::preseed{'AWSTATS_MODE'} = '0'; # (0 for dynamic ; 1 for static) - Only relevant if the question above is set to 'yes'

# Policyd Weight configurator addon
$main::preseed['DNSBL_CHECKS_ONLY'] = 'no' # (yes|no)

# Roundcube addon
$main::preseed['ROUNDCUBE_SQL_USER'] = 'roundcube_user'
$main::preseed['ROUNDCUBE_SQL_PASSWORD'] = 'password'; # Password shouldn't be empty


#
## Hooking - See the documentation at LINK TO DOC
#

1;
