<?php
//   -------------------------------------------------------------------------------
//  |             VHCS(tm) - Virtual Hosting Control System                         |
//  |              Copyright (c) 2001-2005 by moleSoftware		            		|
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

$tpl -> define_dynamic('page', $cfg['ADMIN_TEMPLATE_PATH'].'/add_user.tpl');

$tpl -> define_dynamic('page_message', 'page');
$tpl -> define_dynamic('hosting_plans', 'page');

global $cfg;
$theme_color = $cfg['USER_INITIAL_THEME'];

$tpl -> assign(
                array(
                        'TR_ADMIN_ADD_USER_PAGE_TITLE' => tr('VHCS - Admin/Manage users/Add User'),
                        'THEME_COLOR_PATH' => "../themes/$theme_color",
                        'THEME_CHARSET' => tr('encoding'),
                        'ISP_LOGO' => get_logo($_SESSION['user_id']),
                        'VHCS_LICENSE' => $cfg['VHCS_LICENSE']
                     )
              );


function add_user(&$tpl, &$sql)
{

    if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_user') {

        if(check_user_data()){

                $upass = crypt_user_pass($_POST['pass']);

                $user_id = $_SESSION['user_id'];

                $username = $_POST['username'];
                $fname =	htmlspecialchars($_POST['fname'], ENT_QUOTES, "UTF-8");
                $lname =	htmlspecialchars($_POST['lname'], ENT_QUOTES, "UTF-8");
                $firm =		htmlspecialchars($_POST['firm'], ENT_QUOTES, "UTF-8");
                $zip =		htmlspecialchars($_POST['zip'], ENT_QUOTES, "UTF-8");
                $city =		htmlspecialchars($_POST['city'], ENT_QUOTES, "UTF-8");
                $country =	htmlspecialchars($_POST['country'], ENT_QUOTES, "UTF-8");
                $email=		htmlspecialchars($_POST['email'], ENT_QUOTES, "UTF-8");
                $phone=		htmlspecialchars($_POST['phone'], ENT_QUOTES, "UTF-8");
                $fax=		htmlspecialchars($_POST['fax'], ENT_QUOTES, "UTF-8");
                $street1 =	htmlspecialchars($_POST['street1'], ENT_QUOTES, "UTF-8");
                $street2 =	htmlspecialchars($_POST['street2'], ENT_QUOTES, "UTF-8");

                $query = <<<SQL_QUERY
                    insert into
                            admin
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
                                'admin',
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

                $user_logged= $_SESSION['user_logged'];

                write_log("$user_logged: add admin: $username");

                insert_email_tpl($sql, $new_admin_id);

				$user_def_lang = $_SESSION['user_def_lang'];
				$user_theme_color = $_SESSION['user_theme_color'];
				$user_logo = 0;

                $query = <<<SQL_QUERY
                    insert into
                        user_gui_props
                            (
                                user_id,
                                lang,
                                layout,
                                logo
                            )
                        values
                            (
                              ?,?,?,?
                            )
SQL_QUERY;

                $rs = exec_query($sql, $query, array($new_admin_id,
                                                     $user_def_lang,
                                                     $user_theme_color,
                                                     $user_logo));

                send_add_user_auto_msg (
                                        $user_id,
                                        $_POST['username'],
                                        $_POST['pass'],
                                        $_POST['email'],
                                        $_POST['fname'],
                                        $_POST['lname'],
                                        tr('Administrator')
                                    );

                $_SESSION['user_added'] = 1;

                header( "Location: manage_users.php" );
                die();

        } //check user data
        else{
            $tpl -> assign(
                    array(
                            'EMAIL' => $_POST['email'],
                            'USERNAME' => $_POST['username'],
                            'FIRST_NAME' => $_POST['fname'],
                            'LAST_NAME' => $_POST['lname'],
                            'FIRM' => $_POST['firm'],
                            'ZIP' => $_POST['zip'],
                            'CITY' => $_POST['city'],
                            'COUNTRY' => $_POST['country'],
                            'STREET_1' => $_POST['street1'],
                            'STREET_2' => $_POST['street2'],
                            'PHONE' => $_POST['phone'],
                            'FAX' => $_POST['fax'],
                        )
                );
         }
    }
    else
    {
            $tpl -> assign(
                    array(
                            'EMAIL' => '',
                            'USERNAME' => '',
                            'FIRST_NAME' =>'',
                            'LAST_NAME' =>'',
                            'FIRM' => '',
                            'ZIP' =>'',
                            'CITY'  =>'',
                            'COUNTRY'  =>'',
                            'STREET_1'  =>'',
                            'STREET_2'  =>'',
                            'PHONE'  =>'',
                            'FAX'  =>'',
                        )
                );

    }// else
}

function check_user_data()
{

    global $sql;

    $username= $_POST['username'];

    $query = <<<SQL_QUERY
        select
            admin_id
        from
            admin
        where
            admin_name = ?

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

    return true;
}

/*
 *
 * static page messages.
 *
 */

gen_admin_menu($tpl, $cfg['ADMIN_TEMPLATE_PATH'].'/menu_manage_users.tpl');

add_user($tpl, $sql);

$timestamp = time();
$genpas = substr(md5($timestamp),0,6);

$tpl -> assign(
                array(
                        'TR_EMPTY_OR_WORNG_DATA' => tr('Empty data or wrong field!'),
                        'TR_PASSWORD_NOT_MATCH' => tr('Password not match!'),
                        'TR_ADD_ADMIN' => tr('Add admin'),
                        'TR_CORE_DATA' => tr('Core data'),
                        'TR_USERNAME' => tr('Username'),
                        'TR_PASSWORD' => tr('Password'),
                        'TR_PASSWORD_REPEAT' => tr('Password repeat'),
                        'TR_EMAIL' => tr('Email'),
                        'TR_ADDITIONAL_DATA' => tr('Additional data'),
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
