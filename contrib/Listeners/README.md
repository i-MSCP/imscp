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

### 20_apache2_followsymlinks.pl

Listener file that allow to replace the SymLinksIfOwnerMatch option by the FollowSymlinks option in the vhost files of
the specified domain(s)

### 30_apache2_redirects_permanently.pl

Listener file which changes the domain redirect type in customer's vhost files from 302 to 301.

### 40_apache2_followsymlinks.pl

Listener file to edit the Symlinks options in domain config files

## Dovecot listeners

### 10_dovecot_prefix.pl

Listener file to edit the prefix in dovecot.conf

## Dovecot listeners

### 10_dovecot_prefix.pl

Listener file that remove the INBOX. prefix in the dovecot configuration file

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

## System listeners

## 10_system_hosts.pl

Listener file that allows to add host entries in the system hosts file (eg. /etc/hosts).
