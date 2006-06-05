<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2006 by moleSoftware		            		|
//  |			http://vhcs.net | http://www.molesoftware.com		           		|
//  |                                                                               |
//  | This program is free software; you can redistribute it and/or                 |
//  | modify it under the terms of the MPL General Public License                   |
//  | as published by the Free Software Foundation; either version 1.1              |
//  | of the License, or (at your option) any later version.                        |
//  |                                                                               |
//  | You should have received a copy of the MPL Mozilla Public License             |
//  | along with this program; if not, write to the Open Source Initiative (OSI)    |
//  | http://opensource.org | osi@opensource.org								    |
//  |                                                                               |
//   -------------------------------------------------------------------------------

include '../include/vhcs-lib.php';

check_login();

$tpl = new pTemplate();

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/add_reseller.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

$tpl -> define_dynamic('rsl_ip_message', 'page');

$tpl -> define_dynamic('rsl_ip_list', 'page');

$tpl -> define_dynamic('rsl_ip_item', 'rsl_ip_list');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_ADD_RESELLER_PAGE_TITLE' => tr('VHCS - Admin/Manage users/Add reseller'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );


//
//  Get Server IPs;
//
function get_server_ip(&$tpl, &$sql)
{

    $query = <<<SQL_QUERY
        SELECT
            ip_id, ip_number, ip_domain
        FROM
            server_ips
        ORDER BY
            ip_number
SQL_QUERY;

    $rs = exec_query($sql, $query, array());

    $i = 0;

    $reseller_ips = '';

    if ($rs -> RecordCount() == 0) {

        $tpl -> assign(
                    array(
                        'RSL_IP_MESSAGE' => tr('Reseller IP list is empty!'),
                        'RSL_IP_LIST' => ''
                    )
                );

        $tpl -> parse('RSL_IP_MESSAGE', 'rsl_ip_message');

    } else {

    $tpl -> assign(
                array(
                        'TR_RSL_IP_NUMBER' => tr('No.'),
                        'TR_RSL_IP_ASSIGN' => tr('Assign'),
                        'TR_RSL_IP_LABEL' => tr('Label'),
                        'TR_RSL_IP_IP' => tr('Number'),
                    )
            );
        while (!$rs -> EOF) {

            if ($i % 2 == 0) {
                $tpl -> assign(
                        array(
                            'RSL_IP_CLASS' => 'content',
                            )
                        );
            }
            else{
                $tpl -> assign(
                        array(
                            'RSL_IP_CLASS' => 'content2',
                            )
                        );
            }

            $ip_id = $rs -> fields['ip_id'];

            $ip_var_name = "ip_$ip_id";

            if (isset($_POST[$ip_var_name]) && $_POST[$ip_var_name] == 'asgned'){

                $ip_item_assigned = 'checked';

                $reseller_ips .= "$ip_id;";

            } else {

                $ip_item_assigned = '';

            }

            $tpl -> assign(
                            array(
                                    'RSL_IP_NUMBER' => $i+1,
                                    'RSL_IP_LABEL' => $rs -> fields['ip_domain'],
                                    'RSL_IP_IP' => $rs -> fields['ip_number'],
                                    'RSL_IP_CKB_NAME' => $ip_var_name,
                                    'RSL_IP_CKB_VALUE' => 'asgned',
                                    'RSL_IP_ITEM_ASSIGNED' => $ip_item_assigned,
                                    )
                            );

            $tpl -> parse('RSL_IP_ITEM', '.rsl_ip_item');

            $rs -> MoveNext();

            $i++;

        }

        $tpl -> parse('RSL_IP_LIST', 'rsl_ip_list');

        $tpl -> assign('RSL_IP_MESSAGE', '');

    }

    return $reseller_ips;

}


function add_reseller(&$tpl, &$sql)
{

    global $reseller_ips ;

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_reseller') {

        if(check_user_data()){

            $upass = crypt_user_pass($_POST['pass']);

            $user_id = $_SESSION['user_id'];

            $username 	= $_POST['username'];
            $fname 		= htmlspecialchars($_POST['fname'], ENT_QUOTES, "UTF-8");
            $lname 		= htmlspecialchars($_POST['lname'], ENT_QUOTES, "UTF-8");
            $firm 		= htmlspecialchars($_POST['firm'], ENT_QUOTES, "UTF-8");
            $zip 		= htmlspecialchars($_POST['zip'], ENT_QUOTES, "UTF-8");
            $city 		= htmlspecialchars($_POST['city'], ENT_QUOTES, "UTF-8");
            $country 	= htmlspecialchars($_POST['country'], ENT_QUOTES, "UTF-8");
            $email 		= htmlspecialchars($_POST['email'], ENT_QUOTES, "UTF-8");
            $phone 		= htmlspecialchars($_POST['phone'], ENT_QUOTES, "UTF-8");
            $fax 		= htmlspecialchars($_POST['fax'], ENT_QUOTES, "UTF-8");
            $street1 	= htmlspecialchars($_POST['street1'], ENT_QUOTES, "UTF-8");
            $street2 	= htmlspecialchars($_POST['street2'], ENT_QUOTES, "UTF-8");

            $query = <<<SQL_QUERY
                insert into admin
                  (
                    admin_name,
                    admin_pass,
                    admin_type,
                    domain_created,
                    created_by,
                    fname,
                    lname,
                    firm,
                    zip,
                    city,
                    country,
                    email,
                    phone,
                    fax,
                    street1,
                    street2
                  )
                values
                  (
                    ?,
                    ?,
                    'reseller',
                    unix_timestamp(),
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                  )
SQL_QUERY;
            $rs = exec_query($sql, $query, array($username,
                                                 $upass,
                                                 $user_id,
                                                 $fname,
                                                 $lname,
                                                 $firm,
                                                 $zip,
                                                 $city,
                                                 $country,
                                                 $email,
                                                 $phone,
                                                 $fax,
                                                 $street1,
                                                 $street2));

            $new_admin_id = $sql -> Insert_ID();


            insert_email_tpl($sql, $new_admin_id);


            $user_logged= $_SESSION['user_logged'];

            write_log("$user_logged: add reseller: $username");

			$user_def_lang = $_SESSION['user_def_lang'];
			$user_theme_color = $_SESSION['user_theme_color'];
			$user_logo = 0;

            $query = <<<SQL_QUERY
                insert into user_gui_props
                  (
                    user_id,
                    lang,
                    layout,
                    logo
                  )
                values
                  (
                    ?, ?, ?, ?
                  )
SQL_QUERY;

            $rs = exec_query($sql, $query, array($new_admin_id,
                                                 $user_def_lang,
                                                 $user_theme_color,
                                                 $user_logo));

            /*
            * 'reseller_props' table entry;
            */

            $nreseller_max_domain_cnt 		= $_POST['nreseller_max_domain_cnt'];
            $nreseller_max_subdomain_cnt 	= $_POST['nreseller_max_subdomain_cnt'];
            $nreseller_max_alias_cnt 		= $_POST['nreseller_max_alias_cnt'];
            $nreseller_max_mail_cnt 		= $_POST['nreseller_max_mail_cnt'];
            $nreseller_max_ftp_cnt 			= $_POST['nreseller_max_ftp_cnt'];
            $nreseller_max_sql_db_cnt 		= $_POST['nreseller_max_sql_db_cnt'];
            $nreseller_max_sql_user_cnt 	= $_POST['nreseller_max_sql_user_cnt'];
            $nreseller_max_traffic 			= $_POST['nreseller_max_traffic'];
            $nreseller_max_disk 			= $_POST['nreseller_max_disk'];
            $customer_id 					= $_POST['customer_id'];

            $query = <<<SQL_QUERY
                insert into reseller_props
                  (
                    reseller_id, reseller_ips,
                    max_dmn_cnt, current_dmn_cnt,
                    max_sub_cnt, current_sub_cnt,
                    max_als_cnt, current_als_cnt,
                    max_mail_cnt, current_mail_cnt,
                    max_ftp_cnt, current_ftp_cnt,
                    max_sql_db_cnt, current_sql_db_cnt,
                    max_sql_user_cnt, current_sql_user_cnt,
                    max_traff_amnt, current_traff_amnt,
                    max_disk_amnt, current_disk_amnt,
                    customer_id
                  )
                values
                  (
                    ?, ?,
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?, '0',
                    ?
                  )

SQL_QUERY;

        $rs = exec_query($sql, $query, array($new_admin_id, $reseller_ips,
                                             $nreseller_max_domain_cnt,
                                             $nreseller_max_subdomain_cnt,
                                             $nreseller_max_alias_cnt,
                                             $nreseller_max_mail_cnt,
                                             $nreseller_max_ftp_cnt,
                                             $nreseller_max_sql_db_cnt,
                                             $nreseller_max_sql_user_cnt,
                                             $nreseller_max_traffic,
                                             $nreseller_max_disk,
                                             $customer_id));

        send_add_user_auto_msg (
                                $user_id,
                                $_POST['username'],
                                $_POST['pass'],
                                $_POST['email'],
                                htmlspecialchars($_POST['fname'], ENT_QUOTES, "UTF-8"),
                                htmlspecialchars($_POST['lname'], ENT_QUOTES, "UTF-8"),
                                tr('Reseller')
                               );

            $_SESSION['reseller_added'] = 1;

            header( "Location: manage_users.php" );
            die();

        }
        else{
            $tpl -> assign(
                    array(
                            'EMAIL' => $_POST['email'],
                            'USERNAME' => $_POST['username'],

                            'FIRST_NAME' => $_POST['fname'],
                            'CUSTOMER_ID' =>$_POST['customer_id'],
                            'LAST_NAME' => $_POST['lname'],
                            'FIRM' => $_POST['firm'],
                            'ZIP' => $_POST['zip'],
                            'CITY' => $_POST['city'],
                            'COUNTRY' => $_POST['country'],
                            'STREET_1' => $_POST['street1'],
                            'STREET_2' => $_POST['street2'],
                            'PHONE' => $_POST['phone'],
                            'FAX' => $_POST['fax'],

                            'MAX_DOMAIN_COUNT' => $_POST['nreseller_max_domain_cnt'],
                            'MAX_SUBDOMAIN_COUNT' => $_POST['nreseller_max_subdomain_cnt'],
                            'MAX_ALIASES_COUNT' => $_POST['nreseller_max_alias_cnt'],
                            'MAX_MAIL_USERS_COUNT' => $_POST['nreseller_max_mail_cnt'],
                            'MAX_FTP_USERS_COUNT' => $_POST['nreseller_max_ftp_cnt'],
                            'MAX_SQLDB_COUNT' => $_POST['nreseller_max_sql_db_cnt'],
                            'MAX_SQL_USERS_COUNT' => $_POST['nreseller_max_sql_user_cnt'],
                            'MAX_TRAFFIC_AMOUNT' => $_POST['nreseller_max_traffic'],
                            'MAX_DISK_AMOUNT' => $_POST['nreseller_max_disk'],

                        )
                );
        }
    }// not add
    else
    {
            $tpl -> assign(
                    array(
                            'EMAIL' => '',
                            'USERNAME' => '',

                            'FIRST_NAME' =>'',
                            'CUSTOMER_ID' =>'',
                            'LAST_NAME' =>'',
                            'FIRM' => '',
                            'ZIP' =>'',
                            'CITY'  =>'',
                            'COUNTRY'  =>'',
                            'STREET_1'  =>'',
                            'STREET_2'  =>'',
                            'PHONE'  =>'',
                            'FAX'  =>'',

                            'MAX_DOMAIN_COUNT' => '',
                            'MAX_SUBDOMAIN_COUNT' => '',
                            'MAX_ALIASES_COUNT' => '',
                            'MAX_MAIL_USERS_COUNT' => '',
                            'MAX_FTP_USERS_COUNT' => '',
                            'MAX_SQLDB_COUNT' => '',
                            'MAX_SQL_USERS_COUNT' => '',
                            'MAX_TRAFFIC_AMOUNT' => '',
                            'MAX_DISK_AMOUNT' => ''
                       )
                );

    }
}


function check_user_data()
{

    global $reseller_ips, $sql;

    $username= $_POST['username'];

    $query = <<<SQL_QUERY
        SELECT
            admin_id
        FROM
            admin
        WHERE
            admin_name=?
SQL_QUERY;

    $rs = exec_query($sql, $query, array($username));

    if($rs -> RecordCount() != 0){

        set_page_message(tr('This user name already exist!'));

        return false;
    }
    if (chk_username($_POST['username'])) {

        set_page_message( tr("Incorrect username range or syntax!"));

        return false;
    }
    if (chk_password($_POST['pass'])) {

        set_page_message( tr("Incorrect password range or syntax!"));

        return false;
    }
    if ($_POST['pass'] != $_POST['pass_rep']) {

        set_page_message( tr("Entered passwords does not match!"));

        return false;
    }
    if (chk_email($_POST['email'])) {

        set_page_message( tr("Incorrect email range or syntax!"));

        return false;
    }
    if (!vhcs_limit_check($_POST['nreseller_max_domain_cnt'], 999) || $_POST['nreseller_max_domain_cnt'] == -1) {

        set_page_message( tr("Incorrect max domain count or syntax!"));

        return false;
    }
    if (!vhcs_limit_check($_POST['nreseller_max_subdomain_cnt'], 999) || $_POST['nreseller_max_subdomain_cnt'] == -1) {

        set_page_message( tr("Incorrect max subdomain count or syntax!"));

        return false;

    }
    if (!vhcs_limit_check($_POST['nreseller_max_alias_cnt'], 999) || $_POST['nreseller_max_alias_cnt'] == -1) {

        set_page_message(tr('Incorrect max alias count or syntax!'));

        return false;

    }
    if (!vhcs_limit_check($_POST['nreseller_max_ftp_cnt'], 999) || $_POST['nreseller_max_ftp_cnt'] == -1) {

        set_page_message(tr('Incorrect max FTP count or syntax!'));

        return false;

    }
    if (!vhcs_limit_check($_POST['nreseller_max_mail_cnt'], 999) || $_POST['nreseller_max_mail_cnt'] == -1) {

        set_page_message(tr('Incorrect max mail count or syntax!'));

        return false;

    } else if (!vhcs_limit_check($_POST['nreseller_max_sql_db_cnt'], 999) || $_POST['nreseller_max_sql_db_cnt'] == -1) {

        set_page_message(tr('Incorrect max SQL databases count or syntax!'));

        return false;

    } else if (!vhcs_limit_check($_POST['nreseller_max_sql_user_cnt'], 999) || $_POST['nreseller_max_sql_user_cnt'] == -1) {

        set_page_message(tr('Incorrect max SQL users count or syntax!'));

        return false;

    } else if (!vhcs_limit_check($_POST['nreseller_max_traffic'] , 999999) || $_POST['nreseller_max_traffic'] == -1) {

        set_page_message(tr('Incorrect max traffic amount or syntax!'));

        return false;

    } else if (!vhcs_limit_check($_POST['nreseller_max_disk'], 999999) || $_POST['nreseller_max_disk']== -1) {

        set_page_message(tr('Incorrect max disk amount or syntax!'));

        return false;

    } else if ($reseller_ips == '') {

        set_page_message(tr('You must assign at least one IP number for a reseller!'));

        return false;

    }

    return true;

}


/*
 *
 * static page messages.
 *
 */

gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_manage_users.tpl');

$reseller_ips = get_server_ip($tpl, $sql);

add_reseller($tpl, $sql);

$timestamp = time();
$genpas = substr(md5($timestamp),0,6);

$tpl -> assign(
    array(
        'TR_ADD_RESELLER' => tr('Add reseller'),
        'TR_CORE_DATA' => tr('Core data'),
        'TR_USERNAME' => tr('Username'),
        'TR_PASSWORD' => tr('Password'),
        'TR_PASSWORD_REPEAT' => tr('Password repeat'),
        'TR_EMAIL' => tr('Email'),
        'TR_UNLIMITED' => tr('unlimited'),
        'TR_MAX_DOMAIN_COUNT' => tr('Max Domain count'),
        'TR_MAX_SUBDOMAIN_COUNT' => tr('Max subdomain count'),
        'TR_MAX_ALIASES_COUNT' => tr('Max aliases count'),
        'TR_MAX_MAIL_USERS_COUNT' => tr('Max mail users count'),
        'TR_MAX_FTP_USERS_COUNT' => tr('Max FTP users count'),
        'TR_MAX_SQLDB_COUNT' => tr('Max SQL databases count'),
        'TR_MAX_SQL_USERS_COUNT' => tr('Max SQL users count'),
        'TR_MAX_TRAFFIC_AMOUNT' => tr('Max traffic amount [MB]'),
        'TR_MAX_DISK_AMOUNT' => tr('Max disk amount [MB]'),
        'TR_PHP' => tr('PHP'),
        'TR_PERL_CGI' => tr('CGI / Perl'),
        'TR_JSP' => tr('JSP'),
        'TR_SSI' => tr('SSI'),
        'TR_FRONTPAGE_EXT' => tr('Fronpage extensions'),
        'TR_BACKUP_RESTORE' => tr('Backup and restore'),
        'TR_CUSTOM_ERROR_PAGES' => tr('Custom error pages'),
        'TR_PROTECTED_AREAS' => tr('Protected areas'),
        'TR_WEBMAIL' => tr('Webmail'),
        'TR_DIR_LIST' => tr('Directory listing'),
        'TR_APACHE_LOGFILES' => tr('Apache logfiles'),
        'TR_AWSTATS' => tr('AwStats'),
        'TR_LOGO_UPLOAD' => tr('Logo upload'),
        'TR_YES' => tr('yes'),
        'TR_NO' => tr('no'),

        'TR_RESELLER_IPS' => tr('Reseller IPs'),

        'TR_ADDITIONAL_DATA' => tr('Additional data'),
        'TR_CUSTOMER_ID' => tr('Customer ID'),
        'TR_FIRST_NAME' => tr('First name'),
        'TR_LAST_NAME' => tr('Last name'),
        'TR_COMPANY' => tr('Company'),
        'TR_ZIP_POSTAL_CODE' => tr('Zip/Postal code'),
        'TR_CITY' => tr('City'),
        'TR_COUNTRY' => tr('Country'),
        'TR_STREET_1' => tr('Street 1'),
        'TR_STREET_2' => tr('Street 2'),
        'TR_PHONE' => tr('Phone'),
        'TR_FAX' => tr('Fax'),
        'TR_PHONE' => tr('Phone'),
        'TR_ADD' => tr('Add'),
		'GENPAS' => $genpas
        )
    );

gen_page_message($tpl);

$tpl -> parse('PAGE', 'page');

$tpl -> prnt();

if (isset($cfg['DUMP_GUI_DEBUG'])) dump_gui_debug();

unset_messages();
?>
