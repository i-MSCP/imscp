<?php 
// -----------------------------------------------------------------------------
// |             VHCS(tm) - Virtual Hosting Control System                      |
// |              Copyright (c) 2001-2005 by moleSoftware		            	|
// |			http://vhcs.net | http://www.molesoftware.com		           	|
// |                                                                            |
// | This program is free software; you can redistribute it and/or              |
// | modify it under the terms of the MPL General Public License                |
// | as published by the Free Software Foundation; either version 1.1           |
// | of the License, or (at your option) any later version.                     |
// |                                                                            |
// | You should have received a copy of the MPL Mozilla Public License          |
// | along with this program; if not, write to the Open Source Initiative (OSI) |
// | http://opensource.org | osi@opensource.org								    |
// |                                                                         	|
// -----------------------------------------------------------------------------



include '../include/vhcs-lib.php';

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['PURCHASE_TEMPLATE_PATH'].'/package_info.tpl');

$tpl -> define_dynamic('purchase_list', 'page');

$tpl -> define_dynamic('purchase_message', 'page');

$tpl -> define_dynamic('purchase_header', 'page');

$tpl -> define_dynamic('purchase_footer', 'page');


/*
* Functions start
*/

function translate_sse($value)
{
	if ($value == '_yes_'){
		return tr('Yes');
	} else if ($value == '_no_'){
		return tr('No');
	} else {
		return $value;
	}

}

function translate_value($value)
{
	if ($value == -1) {
    return tr('disabled');
	} else if ($value == 0){
		return tr('unlimited');
	} else {
		return $value;
	}

}

function gen_plan_details(&$tpl, &$sql, $user_id, $plan_id)
{
global $cfg;
if (isset($cfg['HOSTING_PLANS_LEVEL']) && $cfg['HOSTING_PLANS_LEVEL'] === 'admin'){
	$query = <<<SQL_QUERY
			select
				*
			from
				hosting_plans
			where
				id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($plan_id));
} else {
	$query = <<<SQL_QUERY
			select
				*
			from
				hosting_plans
			where
				reseller_id = ?
			  and
				id = ?
SQL_QUERY;

  $rs = exec_query($sql, $query, array($user_id, $plan_id));
 }
  if ($rs -> RecordCount() == 0) {

    header("Location: index.php?user_id=$user_id");
	die();
	
  } else {
  
  $props = $rs -> fields['props'];
  list($hp_php, $hp_cgi, $hp_sub, $hp_als, $hp_mail, $hp_ftp, $hp_sql_db, $hp_sql_user, $hp_traff, $hp_disk) = explode(";", $props);
	
	$price = $rs -> fields['price'];
	$setup_fee = $rs -> fields['setup_fee'];
	
	if ($price == 0 || $price == '') {
		$price = tr('free of charge');
	} else {
		$price = $price." ".$rs -> fields['value']." ".$rs -> fields['payment'];
	}
	
	if ($setup_fee == 0 || $setup_fee == '') {
		$setup_fee = tr('free of charge');
	} else {
		$setup_fee = $setup_fee." ".$rs -> fields['value'];
	}
	$description = $rs -> fields['description'];
	if ($description == '')
		$description = '';
		
	if (is_numeric(translate_value($hp_disk))){
			$hp_disk = sizeit($hp_disk*1024*1024)."<br>";
		} else {
			$hp_disk = translate_value($hp_disk)."<br>";
		}
		
		if (is_numeric(translate_value($hp_traff))){
			$hp_traff = sizeit($hp_traff*1024*1024);
		} else {
			$hp_traff = translate_value($hp_traff*1024*1024);
		}

  	$tpl -> assign(
                            array(
                                    'PACK_NAME' => $rs -> fields['name'],
									'DESCRIPTION' => $description,
                                    'PACK_ID' => $rs -> fields['id'],
                                    'USER_ID' => $user_id,
                                    'PURCHASE' => tr('Purchase'),
									'ALIAS' => translate_value($hp_als),
									'SUBDOMAIN' => translate_value($hp_sub),
									'HDD' => $hp_disk,
									'TRAFFIC' => $hp_traff,
									'PHP' => translate_sse($hp_php),
									'CGI' => translate_sse($hp_cgi),
									'MAIL' => translate_value($hp_mail),
									'FTP' => translate_value($hp_ftp),
									'SQL_DB' => translate_value($hp_sql_db),
									'SQL_USR' => translate_value($hp_sql_user),
									'PRICE' => $price,
									'SETUP' => $setup_fee,
									
                                 )
                          );

  }

}



/*
* Functions end
*/






/*
*
* static page messages.
*
*/

if (isset($_GET['id'])){
	$plan_id = $_GET['id'];
	$_SESSION['plan_id'] = $plan_id;
	if(isset($_SESSION['user_id'])){
		$user_id = $_SESSION['user_id'];
	} else if (isset($_GET['user_id'])){ 
		$user_id = $_GET['user_id'];
		$_SESSION['user_id'] = $user_id;
	} else  {
		system_message(tr('You do not have permission to access this interface!'));
	}
} else {
	system_message(tr('You do not have permission to access this interface!'));
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_plan_details($tpl, $sql, $user_id, $plan_id);

gen_page_message($tpl);

	$tpl -> assign(
                array(
                       	'TR_DOMAINS' => tr('Domains'),
						'TR_WEBSPACE' => tr('Webspace'),
						'TR_HDD' => tr('Disk usage'),
						'TR_TRAFFIC' => tr('Traffic usage'),
						'TR_FEATURES' => tr('Domain Features'),
						'TR_STANDARD_FEATURES' => tr('Package Features'),
						'TR_WEBMAIL' => tr('Webmail'),
						'TR_FILEMANAGER' => tr('Filemanager'),
						'TR_BACKUP' => tr('Backup and Restore'),
						'TR_ERROR_PAGES' => tr('Custom Error Pages'),
						'TR_HTACCESS' => tr('Protected Areas'),
						'TR_PHP_SUPPORT' => tr('PHP support'),
                        'TR_CGI_SUPPORT' => tr('CGI support'),
                        'TR_MYSQL_SUPPORT' => tr('SQL support'),
                        'TR_SUBDOMAINS' => tr('Subdomains'),
                        'TR_DOMAIN_ALIAS' => tr('Domain aliasses'),
                        'TR_MAIL_ACCOUNTS' => tr('Mail accounts'),
                        'TR_FTP_ACCOUNTS' => tr('FTP accounts'),
                        'TR_SQL_DATABASES' => tr('SQL databases'),
                        'TR_SQL_USERS' => tr('SQL users'),
						'TR_STATISTICS' => tr('Statistics'),
						'TR_CUSTOM_LOGS' => tr('Custom Apache Logs'),
						'TR_ONLINE_SUPPORT' => tr('Web & E-Mail Support'),
						'TR_OWN_DOMAIN' => tr('Your Own Domain'),
						'TR_VHCS' => tr('VHCS Control Panel'),
						'TR_UPDATES' => tr('Automatic Updates'),
						'TR_PRICE' => tr('Price'),
						'TRR_PRICE' => tr('Package Price'),
						'TR_SETUP_FEE' => tr('Setup Fee'),
						'TR_PERFORMANCE' => tr('Performance'),
						
						'TR_PURCHASE' => tr('Purchase'),
						'TR_BACK' => tr('Back'),
						'YES' => tr('Yes'),
						

					)
			);


$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>