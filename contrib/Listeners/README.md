# i-MSCP listeners

## Introduction

Set of listener files for i-MSCP. These listener files are only compatible with i-MSCP >= **1.2.0**.

## Installation

To install a listener file, you must upload it the **/etc/imscp/listeners.d** directory, and edit the configuration
parameters inside it if any. Once done, you should rerun the i-MSCP installer.

## Apache2 listeners

### 10_apache2_dualstack.pl

Listener file that provides dual stack support for Apache2.

### 20_apache2_serveralias_override.pl

Listener file that allows to override Apache2 ServerAlias directive value.

### 30_apache2_redirects_permanently.pl

Listener file that allows to change the domain redirect type in customer's vhost files from 302 to 301.

### 40_apache2_tools_proxy.pl

Listener file for redirect/proxy in customers vhost files for the i-MSCP tools

### 50_apache2_hsts.pl

Listener file for HTTP Strict Transport Security (HSTS) with Apache2

## Dovecot listeners

### 10_dovecot_compress.pl

Listener file for activating the dovecot compress plugin, to reduce the bandwidth usage of IMAP and to also compress
the stored mails. For more information please check: 
http://wiki2.dovecot.org/Plugins/Compress
http://wiki2.dovecot.org/Plugins/Zlib

### 20_dovecot_connections.pl

Listener file to increase the mail_max_userip_connections

### 30_dovecot_namespace.pl

Listener file that creates the INBOX. as a compatibility name, so old clients can continue using it while new clients 
will use the empty prefix namespace.

### 40_dovecot_pfs.pl

Listener file to activate the Perfect Forward Secrecy logging

### 50_dovecot_plaintext.pl

Listener file to disable plaintext logins and force tls.
Also remove the authentication mechanisms cram-md5 and digest-md5 which won't be supported anymore in i-MSCP 1.3

## Named listeners

### 10_bind9_localnets.pl

Listener file that allows to setup Bind9 for local network.

### 10_named_tuning.pl

Listener file that allows to replace defaults **@ IN <IP>** DNS record with a custom DNS record.

### 20_bind9_dualstack.pl

Listener file that provides dual stack support for bind9.

### 10_named_zonetransfer.pl

Listener file that provides zone output for zone transfer to secondary nameserver (zone provisioning).

### 10_named_tuning2.pl

Listener file that modifies the zone files, removes default nameservers and adds custom out-of-zone nameservers.

## Nginx listeners

### 10_nginx_hsts.pl

Listener file for HTTP Strict Transport Security (HSTS) with Nginx

## Postfix listeners

### 10_postfix_smarthost.pl

Listener file that allows to configure the Postfix as smarthost with SASL authentication.

### 10_postfix_tuning.pl

Listener file that allows to tune Postfix configuration files (main.cf and master.cf).

### 20_postfix_policyd_whitelist.pl

Listener file that allows to setup policyd-weight whilelist maps.

### 30_postfix_bcc_maps.pl

Listener file that allows to setup recipient and sender bbc map.

### 40_postfix_sender_canonical.pl

Listener file that allows to setup sender canonical maps.

### 50_postfix_sender_generic.pl

Listener file that allows to setup sender generic map.

### 60_postfix_pfs.pl

Listener file to add the self generated EDH parameter files for Perfect 
Forward Secrecy (PFS). First create the files before activating this listener:

```
cd /etc/postfix
umask 022
openssl dhparam -out dh512.tmp 512 && mv dh512.tmp dh512.pem
openssl dhparam -out dh2048.tmp 2048 && mv dh2048.tmp dh2048.pem
chmod 644 dh512.pem dh2048.pem
```

### 70_postfix_submission_tls.pl

Listener file to force TLS connection on postfix submission.

## Proftpd listeners

### 10_proftpd_tuning.pl

Listener file that removes the ServerIdent information, and forces a TLS 
connection for non local networks.

## Roundcube listeners

### 10_roundcube_tls.pl

Listener file to change the Roundcube config to connect via TLS.

## System listeners

## 10_system_hosts.pl

Listener file that allows to add host entries in the system hosts file (eg. /etc/hosts).
