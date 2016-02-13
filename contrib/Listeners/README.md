# i-MSCP listeners

## Introduction

Set of listener files for i-MSCP. These listener files are only compatible with i-MSCP >= **1.2.0**.

## Installation

To install a listener file, you must upload it in the **/etc/imscp/listeners.d** directory, and edit the configuration
parameters inside it if any. Once done, you should rerun the i-MSCP installer.

## Apache2 listener files

### 10_apache2_dualstack.pl

Provides dual stack support for Apache2.

### 20_apache2_serveralias_override.pl

Allows to overwrite Apache2 ServerAlias directive.

### 30_apache2_redirects_permanently.pl

Changes the domain redirect type in customer's vhost files from 302 to 301.

### 40_apache2_tools_proxy.pl

Allows to redirect/proxy i-MSCP tools (pma,webmail...) in customers Apache2 vhost files.

## Dovecot listener files

### 10_dovecot_compress.pl

Activates the Dovecot compress plugin to reduce the bandwidth usage of IMAP, and also compresses the stored mails.

For more information please consult:

    http://wiki2.dovecot.org/Plugins/Compress
    http://wiki2.dovecot.org/Plugins/Zlib

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

### 60_dovecot_service_login.pl

Allows to modify default service-login configuration options. This listener file requires dovecot version 2.1.0 or newer.

## Named (Bind9) listener files

### 10_bind9_localnets.pl

Allows to setup Bind9 for local network.

### 10_named_slave_provisioning.pl

Provides slave DNS server(s) provisioning service.
This listener file requires i-MSCP 1.2.12 or newer.

### 10_named_tuning.pl

Allows to replace defaults **@ IN <IP>** DNS record with a custom DNS record (when a custom DNS is set as replacement).

### 10_named_tuning2.pl

Overwrites the default nameservers with out-of-zone nameservers.

### 20_bind9_dualstack.pl

Provides dual stack support for bind9.

## Nginx listener files

### 10_nginx_hsts.pl

Activates HTTP Strict Transport Security (HSTS).

## PHP listener files

### 10_php_confoptions_override.pl

Allows to add or override PHP configuration options globally or per domain.

Be aware that only Fcgid and PHP-FPM Apache2 httpd server implementations are supported.

Note: When you want operate on a per domain basis, don't forget to set the PHP configuration level to 'per_site'. You
can do this by running:

```
# cd <your_imscp_archive>
# perl imscp-autoinstall -dar httpd
```

## Postfix listener files

### 10_postfix_smarthost.pl

Allows to configure Postfix as smarthost with SASL authentication.

### 10_postfix_tuning.pl

Allows to tune Postfix configuration files (main.cf and master.cf).

### 20_postfix_policyd_whitelist.pl

Allows to setup policyd-weight whilelist maps.

### 30_postfix_bcc_maps.pl

Allows to setup recipient and sender bbc map.

### 40_postfix_sender_canonical.pl

Allows to setup sender canonical maps.

### 50_postfix_sender_generic.pl

Allows to setup sender generic map.

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

### 10_proftpd_tuning.pl

Removes the ServerIdent information, and enforces TLS connections for non-local networks.

## Roundcube Webmail listener files

### 10_roundcube_tls.pl

Changes the Roundcube Webmail configuration to connect through TLS.

## System listener files

## 10_system_hosts.pl

Allows to add host entries in the system hosts file (eg. /etc/hosts).
