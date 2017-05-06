# i-MSCP listeners

## Introduction

Set of listener files for i-MSCP.

## Installation

To install a listener file, you must upload it in the **/etc/imscp/listeners.d** directory, and edit the configuration
parameters inside it if any. Once done, you should rerun the i-MSCP installer.

## Apache2 listener files

### 10_apache2_dualstack.pl

Provides dual stack support for Apache2.

### 20_apache2_serveralias_override.pl

Allows to overwrite Apache2 ServerAlias directive.

### 30_apache2_tools_proxy.pl

Allows to redirect/proxy i-MSCP tools (pma,webmail...) in customers Apache2 vhost files.

### 40_apache2_security_headers.pl

Allows to add Apache2 security headers - https://securityheaders.io

## Backup listener files

### 10_backup_storage_outsourcing.pl

Allows storage of customer backup directories elsewhere on the file system.

## Dovecot listener files

### 10_dovecot_compress.pl

Activates the Dovecot compress plugin to reduce the bandwidth usage of IMAP, and also compresses the stored mails.

For more information please consult:

 * http://wiki2.dovecot.org/Plugins/Compress
 * http://wiki2.dovecot.org/Plugins/Zlib

### 20_dovecot_connections.pl

Allows to increase the mail_max_userip_connections parameter value.

### 30_dovecot_namespace.pl

Creates the INBOX. as a compatibility name, so old clients can continue using it while new clients will use the empty
prefix namespace.

### 40_dovecot_pfs.pl

Activates the Perfect Forward Secrecy logging.

### 50_dovecot_plaintext.pl

Disables plaintext logins and enforce TLS. Also remove the cram-md5 and digest-md5 authentication mechanisms that are no
longer supported in i-MSCP 1.3.x.

### 60_dovecot_service_login.pl (requires Dovecot 2.1.0 or newer)

Allows to modify default service-login configuration options. This listener file.

## FrontEnd listener files

### 10_frontend_templates_override.pl

Listener file that allows to override frontEnd default template files

## Named listener files

### 10_named_rrl.pl

Listener file that implements RRL (Response-Rate-Limiting)

### 10_named_global_ns.pl (requires i-MSCP 1.3.8 or newer)

Listener file that allows to set identical NS entries in all zones

**Warning:**  Warning: Don't forget to declare your slave DNS servers to i-MSCP. Don't forget also to activate IPv6
support if needed. All this can be done by reconfiguring the `named` service as follow:

```
   perl /var/www/imscp/engine/setup/imscp-reconfigure -dr named
```

If you don't do so, zone transfers to your slave DNS servers won't be allowed.

### 10_named_localnets.pl

Listener file that setup Bind9 for local network.

### 10_named_override_default_rr.pl

Listener that allows overriding of default DNS records with custom DNS records

Following DNS resource records can be overriden:

 - @   IN {IP_TYPE} {DOMAIN_IP}
 - www IN CNAME     @

### 10_named_slave_provisioning.pl (requires i-MSCP 1.2.12 or newer)

Provides slave DNS server(s) provisioning service.

### 20_named_dualstack.pl

Provides dual stack support for bind9.

## Nginx listener files

### 10_nginx_hsts.pl

Activates HTTP Strict Transport Security (HSTS).

## Packages listener files

### 10_packages_override.pl

Replaces package file with custom one.

## PHP listener files

### 10_php_confoptions_override.pl

Allows to add or override PHP configuration options globally or per domain.

Be aware that only Fcgid and PHP-FPM Apache2 httpd server implementations are supported.

Note: When you want operate on a per domain basis, don't forget to set the PHP configuration level to 'per_site'. You
can do this by running:

```
# perl /var/www/imscp/engine/setup/imscp-reconfigure -dar php
```

### 10_phpfpm_settings_override.pl

Allows to override PHP-FPM settings in pool configuration files.

Note: When you want operate on a per domain basis, don't forget to set the PHP configuration level to 'per_site'. You
can do this by running:

```
# perl /var/www/imscp/engine/setup/imscp-reconfigure -dar php
```

## PhpMyAdmin listener files

### 10_phpmyadmin_conffile.pl

Allows to override default PhpMyAdmin configuration template file

## Postfix listener files

### 10_postfix_smarthost.pl

Configure Postfix to route all mails to a smarthost using SASL authentication.

### 10_postfix_transport_table.pl

Add entries in Postfix transport(5) table

### 10_postfix_tuning.pl

Tune up Postfix configuration files (main.cf and master.cf).

### 20_postfix_policy_whitelist.pl

Setup Postfix whilelist tables for policy servers.

### 30_postfix_bcc_maps.pl

Setup Postfix recipient and sender bbc maps.

### 40_postfix_sender_canonical.pl

Setup Postfix sender canonical maps.

### 50_postfix_sender_generic.pl

Setup Postfix sender generic maps.

### 60_postfix_pfs.pl

Adds self-generated EDH parameter files for Perfect Forward Secrecy (PFS).

First, you must create the files before activating this listener:

```
# cd /etc/postfix
# umask 022
# openssl dhparam -out dh512.tmp 512 && mv dh512.tmp dh512.pem
# openssl dhparam -out dh2048.tmp 2048 && mv dh2048.tmp dh2048.pem
# chmod 644 dh512.pem dh2048.pem
```

### 70_postfix_submission_tls.pl

Enforces TLS connection on Postfix submission.

## Proftpd listener files

### 10_proftpd_auth_unix.pl

Enable unix authentication.

### 10_proftpd_serverident.pl

Set custom server identification message.

Listener file compatible with i-MSCP >= 1.4.4

### 10_proftpd_tls.pl

Enforce TLS.

## Roundcube Webmail listener files

### 10_roundcube_tls.pl

Changes the Roundcube Webmail configuration to connect through TLS.

## System listener files

### 10_system_hosts.pl

Allows to add host entries in the system hosts file (eg. /etc/hosts).

### 10_system_mount_userwebdir.pl

Allows mounting of USER_WEB_DIR from another location into /var/www/virtual

Listener file compatible with i-MSCP >= 1.3.4
