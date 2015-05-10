# i-mscp-c0urier-listeners

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

Listener file that allows to change the domain redirect type in customers's vhost files from 302 to 301

## GitLab listeners

###10_gitlab.pl

Listener file that allows to start/stop GitLab when updating i-MSCP.

## Named listeners

### 10_bind9_localnets.pl

Listener file that allows to setup Bind9 for local network.

### 10_named_tuning.pl

Listener file that allows to replace defaults **@ IN <IP>** DNS record with a custom DNS record.

### 20_bind9_dualstack.pl

Listener file that provides dual stack support for bind9.

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
