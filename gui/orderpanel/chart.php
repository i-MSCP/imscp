<?php 
/**
 *  ispCP (OMEGA) - Virtual Hosting Control System | Omega Version
 *
 *  @copyright 	2001-2006 by moleSoftware GmbH
 *  @copyright 	2006-2007 by ispCP | http://isp-control.net
 *  @link 		http://isp-control.net
 *  @author		ispCP Team (2007)
 *
 *  @license
 *  This program is free software; you can redistribute it and/or modify it under
 *  the terms of the MPL General Public License as published by the Free Software
 *  Foundation; either version 1.1 of the License, or (at your option) any later
 *  version.
 *  You should have received a copy of the MPL Mozilla Public License along with
 *  this program; if not, write to the Open Source Initiative (OSI)
 *  http://opensource.org | osi@opensource.org
 **/



include '../include/ispcp-lib.php';

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['PURCHASE_TEMPLATE_PATH'].'/chart.tpl');

$tpl -> define_dynamic('page_message', 'page');

$tpl -> define_dynamic('purchase_header', 'page');

$tpl -> define_dynamic('purchase_footer', 'page');


/*
* Functions start
*/

function gen_chart(&$tpl, &$sql, $user_id, $plan_id)
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

    header("Location: index.php");
	die();
	
  } else {
  
		$price = $rs -> fields['price'];
		$setup_fee = $rs -> fields['setup_fee'];
		$total = $price + $setup_fee;
		
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
			
			if ($total == 0 ) {
				$total = tr('free of charge');
			} else {
				$total = $total." ".$rs -> fields['value'];
			}
		
		$tpl -> assign(
                            array(
                                    'PRICE' => $price,
									'SETUP' => $setup_fee,
									'TOTAL' => $total,
									'TR_PACKAGE_NAME' => $rs -> fields['name'],

									
                                 )
                          );  
  
  }

}

function gen_personal_data(&$tpl)
{
	if (isset($_SESSION['fname'])){
		$first_name = $_SESSION['fname'];
	} else {
		$first_name = '';
	}
	
	
	
	if (isset($_SESSION['lname'])){
		$last_name = $_SESSION['lname'];
	} else {
		$last_name = '';
	}
	
	
	if (isset($_SESSION['firm'])){
		$company = $_SESSION['firm'];
	} else {
		$company = '';
	}
	
	
	if (isset($_SESSION['zip'])){
		$postal_code = $_SESSION['zip'];
	} else {
		$postal_code = '';
	}
	
	
	if (isset($_SESSION['city'])){
		$city = $_SESSION['city'];
	} else {
		$city = '';
	}
	
	
	if (isset($_SESSION['country'])){
		$country = $_SESSION['country'];
	} else {
		$country = '';
	}
	
	
	if (isset($_SESSION['street1'])){
		$street1 = $_SESSION['street1'];
	} else {
		$street1 = '';
	}
	
	
	if (isset($_SESSION['street2'])){
		$street2 = $_SESSION['street2'];
	} else {
		$street2 = '';
	}
	
	if (isset($_SESSION['phone'])){
		$phone = $_SESSION['phone'];
	} else {
		$phone = '';
	}
	
	if (isset($_SESSION['fax'])){
		$fax = $_SESSION['fax'];
	} else {
		$fax = '';
	}
	if (isset($_SESSION['email'])){
		$email = $_SESSION['email'];
	} else {
		$email = '';
	}




	$tpl -> assign(
                array(
                     'VL_USR_NAME' => $first_name,
					 'VL_LAST_USRNAME' => $last_name,
                     'VL_USR_FIRM' => $company,
                     'VL_USR_POSTCODE' => $postal_code,
                     'VL_USRCITY' => $city,
                     'VL_COUNTRY' => $country,
                     'VL_STREET1' => $street1,
                     'VL_STREET2' => $street2,
                     'VL_PHONE' => $phone,
                     'VL_FAX' => $fax,
					 'VL_EMAIL' => $email,


					)
			);


}

/*
* Functions end
*/






/*
*
* static page messages.
*
*/

if (isset($_SESSION['user_id']) && $_SESSION['plan_id']){
	$user_id = $_SESSION['user_id'];
	$plan_id = $_SESSION['plan_id'];
} else {
	system_message(tr('You do not have permission to access this interface!'));
}

gen_purchase_haf($tpl, $sql, $user_id);
gen_chart($tpl, $sql, $user_id, $plan_id);
gen_personal_data($tpl);

gen_page_message($tpl);

	$tpl -> assign(
                array(
                       	'YOUR_CHART' => tr('Your Chart'),
						'TR_COSTS' => tr('Costs'),
						'TR_PACKAGE_PRICE' => tr('Price'),
						'TR_PACKAGE_SETUPFEE' => tr('Setup fee'),
						'TR_TOTAL' => tr('Total'),
						'TR_CONTINUE' => tr('Purchase'),
						'TR_CHANGE' => tr('Change'),
						'TR_FIRSTNAME' => tr('First name'),
                  	    'TR_LASTNAME' => tr('Last name'),
                   	 	'TR_COMPANY' => tr('Company'),
               	      	'TR_POST_CODE' => tr('Zip / Postal code'),
                	    'TR_CITY' => tr('City'),
               	      	'TR_COUNTRY' => tr('Country'),
              	       	'TR_STREET1' => tr('Street 1'),
             	        'TR_STREET2' => tr('Street 2'),
              	       	'TR_EMAIL' => tr('Email'),
               	      	'TR_PHONE' => tr('Phone'),
                     	'TR_FAX' => tr('Fax'),
						'TR_EMAIL' => tr('Email'),
						'TR_PERSONAL_DATA' => tr('Personal Data'),
	

					)
			);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>