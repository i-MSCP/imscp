lock_method flock
required_score		4.3
rewrite_header Subject *****SPAM*****
report_safe 0
clear_internal_networks
clear_trusted_networks
internal_networks {BASE_SERVER_IP} 10.0.0/24
trusted_networks {BASE_SERVER_IP} 10.0.0/24
use_bayes 1
bayes_auto_expire 0
bayes_store_module		   Mail::SpamAssassin::BayesStore::MySQL
bayes_sql_dsn			   DBI:mysql:spam:localhost
bayes_sql_username	spam
bayes_sql_password	******
bayes_sql_override_username		amavis
bayes_auto_learn 1
bayes_auto_learn_threshold_nonspam    0.1
bayes_auto_learn_threshold_spam       7.0
#use_auto_whitelist	0
auto_whitelist_factory          Mail::SpamAssassin::SQLBasedAddrList
user_awl_dsn                    DBI:mysql:spam:localhost
user_awl_sql_username           spam
user_awl_sql_password           ******
skip_rbl_checks 0
dns_available yes

auto_whitelist_distinguish_signed 1
