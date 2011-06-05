<?php
/**
 * i-MSCP a internet Multi Server Control Panel
 *
 * @copyright   2001-2006 by moleSoftware GmbH
 * @copyright   2006-2010 by ispCP | http://isp-control.net
 * @copyright   2010-2011 by i-MSCP | http://i-mscp.net
 * @version     SVN: $Id$
 * @link        http://i-mscp.net
 * @author      ispCP Team
 * @author      i-MSCP Team
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * The Original Code is "VHCS - Virtual Hosting Control System".
 *
 * The Initial Developer of the Original Code is moleSoftware GmbH.
 * Portions created by Initial Developer are Copyright (C) 2001-2006
 * by moleSoftware GmbH. All Rights Reserved.
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 * Portions created by the i-MSCP Team are Copyright (C) 2010 by
 * i-MSCP a internet Multi Server Control Panel. All Rights Reserved.
 */

require '../include/imscp-lib.php';

/************************************************************************************
 * Script functions
 */

/**
 * Save new domain data into database.
 *
 * @param  $resellerId Reseller unique identifier
 * @param  $domainId Domain unique identifier
 * @return bool TRUE if new domain data are valid, FALSE otherwise
 */
function save_domain_data($resellerId, $domainId)
{
    global $domain_expires, $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff,
        $disk, $domain_php, $domain_cgi, $allowbackup, $domain_dns,
        $domain_software_allowed;

    $domain_new_expire = clean_input($_POST['dmn_expire']);

    $sub = clean_input($_POST['dom_sub']);
    $als = clean_input($_POST['dom_alias']);
    $mail = clean_input($_POST['dom_mail_acCount']);
    $ftp = clean_input($_POST['dom_ftp_acCounts']);
    $sql_db = clean_input($_POST['dom_sqldb']);
    $sql_user = clean_input($_POST['dom_sql_users']);
    $traff = clean_input($_POST['dom_traffic']);
    $disk = clean_input($_POST['dom_disk']);
    $domain_php = clean_input($_POST['domain_php']);
    $domain_cgi = clean_input($_POST['domain_cgi']);
    $domain_dns = clean_input($_POST['domain_dns']);
    $allowbackup = clean_input($_POST['backup']);
    $domain_software_allowed = clean_input($_POST['domain_software_allowed']);

    if (!imscp_limit_check($sub, -1)) {
        set_page_message(tr('Incorrect subdomains limit!'), 'error');
    }

    if (!imscp_limit_check($als, -1)) {
        set_page_message(tr('Incorrect aliases limit!'), 'error');
    }

    if (!imscp_limit_check($mail, -1)) {
        set_page_message(tr('Incorrect mail accounts limit!'), 'error');
    }

    if (!imscp_limit_check($ftp, -1)) {
        set_page_message(tr('Incorrect FTP accounts limit!'), 'error');
    }

    if (!imscp_limit_check($sql_db, -1)) {
        set_page_message(tr('Incorrect SQL users limit!'), 'error');
    } elseif ($sql_db == -1 && $sql_user != -1) {
        set_page_message(tr('SQL databases limit is <i>disabled</i>!'));
    }

    if (!imscp_limit_check($sql_user, -1)) {
        set_page_message(tr('Incorrect SQL databases limit!'), 'error');
    } elseif ($sql_user == -1 && $sql_db != -1) {
        set_page_message(tr('SQL users limit is <i>disabled</i>!'));
    }

    if (!imscp_limit_check($traff, null)) {
        set_page_message(tr('Incorrect traffic limit!'), 'error');
    }

    if (!imscp_limit_check($disk, null)) {
        set_page_message(tr('Incorrect disk quota limit!'), 'error');
    }

    if ($domain_php == 'no' && $domain_software_allowed == 'yes') {
        set_page_message(tr('The i-MSCP application installer needs PHP to enable!'), 'error');
    }

    if (get_reseller_sw_installer($resellerId) == 'no' && $domain_software_allowed == 'yes') {
        set_page_message(tr('The i-MSCP application installer of the users reseller is not activated!'), 'error');
    }

    list($usub_current, $usub_max, $uals_current, $uals_max, $umail_current,
        $umail_max, $uftp_current, $uftp_max, $usql_db_current, $usql_db_max,
        $usql_user_current, $usql_user_max, $utraff_max, $udisk_max
    ) = generate_user_props($domainId);

    $previous_utraff_max = $utraff_max;

    list($rdmn_current, $rdmn_max, $rsub_current, $rsub_max, $rals_current,
        $rals_max, $rmail_current, $rmail_max, $rftp_current, $rftp_max,
        $rsql_db_current, $rsql_db_max, $rsql_user_current, $rsql_user_max,
        $rtraff_current, $rtraff_max, $rdisk_current, $rdisk_max
    ) = get_reseller_default_props($resellerId);

    list(,,,,,, $utraff_current, $udisk_current) = generate_user_traffic($domainId);

    if (empty($ed_error)) {
        calculate_user_dvals($sub, $usub_current, $usub_max, $rsub_current, $rsub_max, tr('Subdomain'));
        calculate_user_dvals($als, $uals_current, $uals_max, $rals_current, $rals_max, tr('Alias'));
        calculate_user_dvals($mail, $umail_current, $umail_max, $rmail_current, $rmail_max, tr('Mail'));
        calculate_user_dvals($ftp, $uftp_current, $uftp_max, $rftp_current, $rftp_max, tr('FTP'));
        calculate_user_dvals($sql_db, $usql_db_current, $usql_db_max, $rsql_db_current, $rsql_db_max, tr('SQL Database'));
    }

    //if (empty($ed_error)) {
    if(!isset($_SESSION['user_page_message'])) {
        $query = "
			SELECT
				COUNT(`su`.`sqlu_id`) AS `cnt`
			FROM
				`sql_user` `su`, `sql_database` `sd`
			WHERE
				`su`.`sqld_id` = `sd`.`sqld_id`
			AND
				`sd`.`domain_id` = ?
			;
        ";

        $rs = exec_query($query, $domainId);
        calculate_user_dvals($sql_user, $rs->fields['cnt'], $usql_user_max, $rsql_user_current, $rsql_user_max, tr('SQL User'));
    }

    if (!isset($_SESSION['user_page_message'])) {
        calculate_user_dvals($traff, $utraff_current / 1024 / 1024, $utraff_max, $rtraff_current, $rtraff_max, tr('Traffic'));
        calculate_user_dvals($disk, $udisk_current / 1024 / 1024, $udisk_max, $rdisk_current, $rdisk_max, tr('Disk'));
    }

    if (!isset($_SESSION['user_page_message'])) {
        // Set domains status to 'change' to update mod_cband's limit
        if ($previous_utraff_max != $utraff_max) {
            $query = "UPDATE `domain` SET `domain_status` = 'change' WHERE `domain_id` = ?;";
            exec_query($query, $domainId);
            $query = "UPDATE `subdomain` SET `subdomain_status` = 'change' WHERE `domain_id` = ?;";
            exec_query($query, $domainId);
            send_request();
        }

        $user_props = "$usub_current;$usub_max;";
        $user_props .= "$uals_current;$uals_max;";
        $user_props .= "$umail_current;$umail_max;";
        $user_props .= "$uftp_current;$uftp_max;";
        $user_props .= "$usql_db_current;$usql_db_max;";
        $user_props .= "$usql_user_current;$usql_user_max;";
        $user_props .= "$utraff_max;";
        $user_props .= "$udisk_max;";
        $user_props .= "$domain_php;";
        $user_props .= "$domain_cgi;";
        $user_props .= "$allowbackup;";
        $user_props .= "$domain_dns;";
        $user_props .= $domain_software_allowed;

        // Update domain properties (also known as user properties)
        update_user_props($domainId, $user_props);

        //$domain_expires = $_SESSION['domain_expires'];

        if ($domain_expires != 0 && $domain_new_expire != 0) {
            $domain_new_expire = $domain_expires + ($domain_new_expire * 2635200);
            update_expire_date($domainId, $domain_new_expire);
        } elseif ($domain_expires == 0 && $domain_new_expire != 0) {
            $domain_new_expire = time() + ($domain_new_expire * 2635200);
            update_expire_date($domainId, $domain_new_expire);
        }

        $reseller_props = "$rdmn_current;$rdmn_max;";
        $reseller_props .= "$rsub_current;$rsub_max;";
        $reseller_props .= "$rals_current;$rals_max;";
        $reseller_props .= "$rmail_current;$rmail_max;";
        $reseller_props .= "$rftp_current;$rftp_max;";
        $reseller_props .= "$rsql_db_current;$rsql_db_max;";
        $reseller_props .= "$rsql_user_current;$rsql_user_max;";
        $reseller_props .= "$rtraff_current;$rtraff_max;";
        $reseller_props .= "$rdisk_current;$rdisk_max";

        if (!update_reseller_props($resellerId, $reseller_props)) {
            set_page_message(tr('Domain properties could not be updated!'), 'error');

            return false;
        }

        // Backup Settings
        $query = "UPDATE `domain` SET `allowbackup` = ? WHERE `domain_id` = ?;";
        exec_query($query, array($allowbackup, $domainId));

        // update the sql quotas, too
        $query = "SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?;";
        $rs = exec_query($query, $domainId);
        $temp_dmn_name = $rs->fields['domain_name'];

        $query = "SELECT COUNT(`name`) AS cnt FROM `quotalimits` WHERE `name` = ?;";
        $rs = exec_query($query, $temp_dmn_name);

        if ($rs->fields['cnt'] > 0) {
            // we need to update it
            if ($disk == 0) {
                $dlim = 0;
            } else {
                $dlim = $disk * 1024 * 1024;
            }

            $query = "UPDATE `quotalimits` SET `bytes_in_avail` = ? WHERE `name` = ?;";
            exec_query($query, array($dlim, $temp_dmn_name));
        }

        set_page_message(tr('Domain properties updated successfully!'), 'success');

        return true;
    }

    return false;
}

/**
 * Calculates new user domain values.
 *
 * @throws iMSCP_Exception
 * @param  $data Domain data
 * @param  $u
 * @param  $umax
 * @param  $r
 * @param  $rmax
 * @param  $obj
 * @return
 */
function calculate_user_dvals($data, $u, &$umax, &$r, $rmax, $obj)
{
    if ($rmax == 0 && $umax == -1) {
        if ($data == -1) {

            return;
        } elseif ($data == 0) {
            $umax = $data;

            return;
        } elseif ($data > 0) {
            $umax = $data;
            $r += $umax;

            return;
        }
    } elseif ($rmax == 0 && $umax == 0) {
        if ($data == -1) {
            if ($u > 0) {
                set_page_message(tr('The <em>%s</em> service cannot be disabled!', $obj) . tr('There are <em>%s</em> records on system!', $obj), 'error');
            } else {
                $umax = $data;
            }

            return;
        } elseif ($data == 0) {
            return;
        } elseif ($data > 0) {
            if ($u > $data) {
                set_page_message(tr('The <em>%s</em> service cannot be limited! ', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj), 'error');
            } else {
                $umax = $data;
                $r += $umax;
            }

            return;
        }
    } elseif ($rmax == 0 && $umax > 0) {
        if ($data == -1) {
            if ($u > 0) {
                set_page_message(tr('The <em>%s</em> service cannot be disabled! ', $obj) . tr('There are <em>%s</em> records on the system!', $obj), 'error');
            } else {
                $r -= $umax;
                $umax = $data;
            }

            return;
        } else if ($data == 0) {
            $r -= $umax;
            $umax = $data;

            return;
        } elseif ($data > 0) {
            if ($u > $data) {
                set_page_message(tr('The <em>%s</em> service cannot be limited! ', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj), 'error');
            } else {
                if ($umax > $data) {
                    $data_dec = $umax - $data;
                    $r -= $data_dec;
                } else {
                    $data_inc = $data - $umax;
                    $r += $data_inc;
                }
                $umax = $data;
            }

            return;
        }
    } elseif ($rmax > 0 && $umax == -1) {
        if ($data == -1) {
            return;
        } else if ($data == 0) {
            set_page_message(tr('The <em>%s</em> service cannot be unlimited! ', $obj) . tr('There are reseller limits for the <em>%s</em> service!', $obj), 'error');
            return;
        } elseif ($data > 0) {
            if ($r + $data > $rmax) {
                set_page_message(tr('The <em>%s</em> service cannot be limited! ', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service!', $obj), 'error');
            } else {
                $r += $data;

                $umax = $data;
            }

            return;
        }
    } elseif ($rmax > 0 && $umax == 0) {
        throw new iMSCP_Exception('FIXME: ' . __FILE__ . ':' . __LINE__);
    } elseif ($rmax > 0 && $umax > 0) {
        if ($data == -1) {
            if ($u > 0) {
                set_page_message(tr('The <em>%s</em> service cannot be disabled! ', $obj) . tr('There are <em>%s</em> records on the system!', $obj), 'error');
            } else {
                $r -= $umax;
                $umax = $data;
            }

            return;
        } elseif ($data == 0) {
            set_page_message(tr('The <em>%s</em> service cannot be unlimited! ', $obj) . tr('There are reseller limits for the <em>%s</em> service!', $obj), 'error');
            return;
        } elseif ($data > 0) {
            if ($u > $data) {
                set_page_message(tr('The <em>%s</em> service cannot be limited! ', $obj) . tr('Specified number is smaller than <em>%s</em> records, present on the system!', $obj), 'error');
            } else {
                if ($umax > $data) {
                    $data_dec = $umax - $data;
                    $r -= $data_dec;
                } else {
                    $data_inc = $data - $umax;

                    if ($r + $data_inc > $rmax) {
                        set_page_message(tr('The <em>%s</em> service cannot be limited! ', $obj) . tr('You are exceeding reseller limits for the <em>%s</em> service!', $obj), 'error');
                        return;
                    }

                    $r += $data_inc;
                }

                $umax = $data;
            }

            return;
        }
    }
}

/**
 * Load additional domain data.
 *
 * @param  int $domainId Domain unique indentifier
 * @return void
 */
function load_additional_domain_data($domainId)
{
    global $domain_name, $domain_expires, $domain_ip, $php_sup, $cgi_supp, $username,
        $allowbackup, $dns_supp, $domain_expires_date, $software_supp;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    // Get data from database
    $query = "
		SELECT
		    `a`.`domain_name`, `a`.`domain_expires`, `a`.`domain_php`, `a`.`domain_cgi`,
		    `a`.`allowbackup`, `a`.`domain_dns`, `a`.`domain_software_allowed`, `b`.admin_name,
			`c`.`ip_number`, `c`.`ip_domain`
		FROM
		    `domain` `a`
	    LEFT JOIN
	        `admin` `b` ON (`b`.`admin_id` = `a`.`domain_admin_id`)
	    LEFT JOIN
	        `server_ips` c ON (`c`.ip_id = `a`.domain_ip_id)
		WHERE
		    `a`.`domain_id` = ?
		;
	";

    $res = exec_query($query, $domainId);
    $data = $res->fetchRow();

    iMSCP_Registry::set('domainData', $data);


    $domain_name = $data['domain_name'];
    $domain_expires = $data['domain_expires'];

    if ($domain_expires == 0) {
        $domain_expires = tr('N/A');
        $domain_expires_date = '0';
    } else {
        $date_format = $cfg->DATE_FORMAT;
        $domain_expires_date = date('m/d/Y', $domain_expires);
        $domain_expires = date($date_format, $domain_expires);
    }

    $php_sup = $data['domain_php'];
    $cgi_supp = $data['domain_cgi'];
    $allowbackup = $data['allowbackup'];
    $dns_supp = $data['domain_dns'];
    $software_supp = $data['domain_software_allowed'];
    $domain_ip = $data['ip_number'] . '&nbsp;(' . $data['ip_domain'] . ')';
    $username = $data['admin_name'];
}

/**
 * Load domain properties from database.
 *
 * @param  int $domainId Domain unique identifier
 * @return void
 */
function load_domain_properties($domainId)
{
    global $sub, $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk;

    list(
        ,$sub,, $als,, $mail,, $ftp,, $sql_db,, $sql_user, $traff, $disk
    ) = generate_user_props($domainId);

    load_additional_domain_data($domainId);
}

/**
 * Generate page.
 *
 * @param int $domainId Domain unique identifier
 * @return void
 */
function generatePage($domainId)
{
    global $domain_name, $domain_expires, $domain_expires_date, $domain_ip, $php_sup, $cgi_supp, $sub,
        $als, $mail, $ftp, $sql_db, $sql_user, $traff, $disk, $username,
        $allowbackup, $dns_supp, $software_supp;

    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

    $tpl = new iMSCP_pTemplate();
    $tpl->define_dynamic('page', $cfg->ADMIN_TEMPLATE_PATH . '/domain_edit.tpl');
    $tpl->define_dynamic('page_message', 'page');
    $tpl->define_dynamic('t_software_support', 'page');

    $tpl->assign(array(
                      'TR_EDIT_DOMAIN_PAGE_TITLE' => tr('i-MSCP - Admin/Edit Domain'),
                      'THEME_COLOR_PATH' => "../themes/{$cfg->USER_INITIAL_THEME}",
                      'THEME_CHARSET' => tr('encoding'),
                      'ISP_LOGO' => get_logo($_SESSION['user_id'])));

    $tpl->assign(array(
                      'TR_EDIT_DOMAIN' => tr('Edit Domain'),
                      'TR_DOMAIN_PROPERTIES' => tr('Domain properties'),
                      'TR_DOMAIN_NAME' => tr('Domain name'),
                      'TR_DOMAIN_IP' => tr('Domain IP'),
                      'TR_DOMAIN_EXPIRE' => tr('Domain expire'),
                      'TR_DOMAIN_NEW_EXPIRE' => tr('New expire date'),
                      'TR_PHP_SUPP' => tr('PHP support'),
                      'TR_CGI_SUPP' => tr('CGI support'),
                      'TR_DNS_SUPP' => tr('Manual DNS support (EXPERIMENTAL)'),
                      'TR_SUBDOMAINS' => tr('Max subdomains<br /><i>(-1 disabled, 0 unlimited)</i>'),
                      'TR_ALIAS' => tr('Max aliases<br /><i>(-1 disabled, 0 unlimited)</i>'),
                      'TR_MAIL_ACCOUNT' => tr('Mail accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
                      'TR_FTP_ACCOUNTS' => tr('FTP accounts limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
                      'TR_SQL_DB' => tr('SQL databases limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
                      'TR_SQL_USERS' => tr('SQL users limit <br /><i>(-1 disabled, 0 unlimited)</i>'),
                      'TR_TRAFFIC' => tr('Traffic limit [MB] <br /><i>(0 unlimited)</i>'),
                      'TR_DISK' => tr('Disk limit [MB] <br /><i>(0 unlimited)</i>'),
                      'TR_USER_NAME' => tr('Username'),
                      'TR_BACKUP' => tr('Backup'),
                      'TR_BACKUP_DOMAIN' => tr('Domain'),
                      'TR_BACKUP_SQL' => tr('SQL'),
                      'TR_BACKUP_FULL' => tr('Full'),
                      'TR_BACKUP_NO' => tr('No'),
                      'TR_UPDATE_DATA' => tr('Submit changes'),
                      'TR_CANCEL' => tr('Cancel'),
                      'TR_YES' => tr('Yes'),
                      'TR_NO' => tr('No'),
                      'TR_DMN_EXP_HELP' => tr("In case 'Domain expire' is 'N/A', the expiration date will be set from today."),
                      'TR_EXPIRE_CHECKBOX'	=> tr('or Check for <strong>never Expire</strong>'),
                      'TR_SOFTWARE_SUPP' => tr('i-MSCP application installer'),
                      'DOMAIN_ID' => $domainId,
                      'DATE_FORMAT' => strtolower($cfg->DATE_FORMAT) // todo
                 ));

    gen_admin_mainmenu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/main_menu_users_manage.tpl');
    gen_admin_menu($tpl, $cfg->ADMIN_TEMPLATE_PATH . '/menu_users_manage.tpl');

    $domain_name = decode_idna($domain_name);
    $username = decode_idna($username);

    if ($allowbackup === 'dmn') {
        $tpl->assign(array(
                          'BACKUP_DOMAIN' => $cfg->HTML_SELECTED,
                          'BACKUP_SQL' => '',
                          'BACKUP_FULL' => '',
                          'BACKUP_NO' => ''));
    } elseif ($allowbackup === 'sql') {
        $tpl->assign(array(
                          'BACKUP_DOMAIN' => '',
                          'BACKUP_SQL' => $cfg->HTML_SELECTED,
                          'BACKUP_FULL' => '',
                          'BACKUP_NO' => ''));
    } elseif ($allowbackup === 'full') {
        $tpl->assign(array(
                          'BACKUP_DOMAIN' => '',
                          'BACKUP_SQL' => '',
                          'BACKUP_FULL' => $cfg->HTML_SELECTED,
                          'BACKUP_NO' => ''));
    } elseif ($allowbackup === 'no') {
        $tpl->assign(array(
                          'BACKUP_DOMAIN' => '',
                          'BACKUP_SQL' => '',
                          'BACKUP_FULL' => '',
                          'BACKUP_NO' => $cfg->HTML_SELECTED));
    }

    // Case where domain never expire
    if($domain_expires_date === '0')    {
        $tpl->assign(array(
                          'VL_DOMAIN_EXPIRE_DATE' => '', // Empty value for datepicker field
                          'VL_DISABLED' => $cfg->HTML_DISABLED, // datepicker field is disabled
                          'VL_NEVEREXPIRE' => $cfg->HTML_CHECKED, // never expire checkbox is checked
                          'VL_DISABLED_NE' => '')); // never expire checkbox is enabled
    } else { // Case when a date is fixed for domain expiration
        $tpl->assign(array(
                          'VL_DOMAIN_EXPIRE_DATE' => $domain_expires_date, // Date value for datepicker field (in MM/DD/YYYY format)
                          'VL_DISABLED' => '', // // datepicker field is enabled
                          'VL_NEVEREXPIRE' => '', // never expire checkbox is un-checked
                          'VL_DISABLED_NE' => $cfg->HTML_DISABLED)); // // never expire checkbox is disabled
    }

    $tpl->assign(array(
                      'PHP_YES' => ($php_sup == 'yes') ? $cfg->HTML_SELECTED : '',
                      'PHP_NO' => ($php_sup != 'yes') ? $cfg->HTML_SELECTED : '',
                      'CGI_YES' => ($cgi_supp == 'yes') ? $cfg->HTML_SELECTED : '',
                      'CGI_NO' => ($cgi_supp != 'yes') ? $cfg->HTML_SELECTED : '',
                      'DNS_YES' => ($dns_supp == 'yes') ? $cfg->HTML_SELECTED : '',
                      'DNS_NO' => ($dns_supp != 'yes') ? $cfg->HTML_SELECTED : '',
                      'SOFTWARE_YES' => ($software_supp == 'yes')
                          ? $cfg->HTML_SELECTED : '',
                      'SOFTWARE_NO' => ($software_supp != 'yes')
                          ? $cfg->HTML_SELECTED : '',
                      'VL_DOMAIN_NAME' => tohtml($domain_name),
                      'VL_DOMAIN_IP' => $domain_ip,
                      //'DOMAIN_EXPIRES_DATE'	=> $domain_expires_date,
                      'VL_DOMAIN_EXPIRE' => $domain_expires,
                      'VL_DOM_SUB' => $sub,
                      'VL_DOM_ALIAS' => $als,
                      'VL_DOM_MAIL_ACCOUNT' => $mail,
                      'VL_FTP_ACCOUNTS' => $ftp,
                      'VL_SQL_DB' => $sql_db,
                      'VL_SQL_USERS' => $sql_user,
                      'VL_TRAFFIC' => $traff,
                      'VL_DOM_DISK' => $disk,
                      'VL_USER_NAME' => tohtml($username)));

    generatePageMessage($tpl);

    $tpl->parse('PAGE', 'page');
    $tpl->prnt();
}

/************************************************************************************
 * Main script
 */

// Checks for login
check_login(__FILE__);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if ($cfg->HOSTING_PLANS_LEVEL && $cfg->HOSTING_PLANS_LEVEL !== 'admin') {
    set_page_message(tr('You cannot access this page directly. You must switch to reseller level.'), 'warning');
    redirectTo('manage_users.php');
}

if(isset($_POST['uaction']) && $_POST['uaction'] === 'update') { // Update domain data into database
    if(isset($_POST['domain_id'])) {
        $domainId = intval($_POST['domain_id']);
        $resellerId = get_reseller_id($domainId);

        if(!$resellerId) {
            set_page_message(tr('Unable to update domain properties: The domain does not exist or is an orphan.'), 'error');
            redirectTo('manage_users.php');
        }
    } else {
            set_page_message(tr('Unable to update domain: Domain ID not found.'), 'error');
            redirectTo('manage_users.php');
    }

    if (save_domain_data($resellerId, $domainId)) {
        redirectTo('manage_users.php');
    }

    load_additional_domain_data($resellerId, $domainId);

} else { // Show edit page
    if(isset($_GET['edit_id'])) {
        $domainId = intval($_GET['edit_id']);
        $resellerId = get_reseller_id($domainId);

        if(!$resellerId) {
            set_page_message(tr('Unable to edit domain: The domain does not exist or is an orphan.'), 'error');
            redirectTo('manage_users.php');
        }
    } else {
            set_page_message(tr('Unable to edit domain: ID not found.'), 'error');
            redirectTo('manage_users.php');
    }

    load_domain_properties($domainId);
}

generatePage($domainId);

if ($cfg->DUMP_GUI_DEBUG) {
    dump_gui_debug();
}

unsetMessages();
