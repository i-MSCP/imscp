#!/usr/bin/perl

# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
#
# Version: $Id$
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

# i-MSCP specific:
#
# If you do not want this file to be regenerated from scratch during i-MSCP
# update process, change the 'SPAMASSASSIN_REGENERATE' parameter value to 'no' in
# the imscp.conf file.

# SpamAssassin local.cf template file

# Disable it if using NFS
lock_method                            flock

# How many hits before a message is considered spam.
required_score                         4.3

# Change the subject of suspected spam
rewrite_header                         Subject *****SPAM*****

# Encapsulate spam in an attachment (0=no, 1=yes, 2=safe)
report_safe                            0

clear_internal_networks
clear_trusted_networks
internal_networks                      {BASE_SERVER_IP} 10.0.0/24
trusted_networks                       {BASE_SERVER_IP} 10.0.0/24

#
# statistical, "Bayesian" analysis system - Begin
#

# Enable the Bayes system
use_bayes                              1
bayes_auto_expire                      0

# Bayes storage module
# Default: Mail::SpamAssassin::BayesStore::DBM
#bayes_store_module                     Mail::SpamAssassin::BayesStore::MySQL
#bayes_sql_dsn                          DBI:mysql:{SPAMASSASSIN_DATABASE}:{DATABASE_HOST}
#bayes_sql_username                     {SPAMASSASSIN_SQL_USER}
#bayes_sql_password                     {SPAMASSASSIN_SQL_PASSWORD}
#bayes_sql_override_username            {AMAVIS_SQL_USERNAME}

# Enable Bayes auto-learning
bayes_auto_learn                       1
bayes_auto_learn_threshold_nonspam     0.1
bayes_auto_learn_threshold_spam        7.0

#
# statistical, "Bayesian" analysis system - End
#

#
# auto white-list (AWL) - Begin
#

#use_auto_whitelist                     0
# auto white-list sorage module
# default: Mail::SpamAssassin::DBBasedAddrList
#auto_whitelist_factory                  Mail::SpamAssassin::SQLBasedAddrList
#user_awl_dsn                            DBI:mysql:{SPAMASSASSIN_DATABASE}:{DATABASE_HOST}
#user_awl_sql_username                   {SPAMASSASSIN_SQL_USER}
#user_awl_sql_password                   {SPAMASSASSIN_SQL_PASSWORD}

#
# auto white-list (AWL) - End
#

skip_rbl_checks                      0
dns_available                        yes
auto_whitelist_distinguish_signed    1

# Enable or disable network checks (used if available)
skip_rbl_checks                      1
use_razor2                           1
#use_dcc                              1
use_pyzor                            1
