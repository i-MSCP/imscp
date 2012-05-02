<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Core
 * @subpackage  Client
 * @copyright   2010-2012 by i-MSCP team
 * @author		Sascha Bay <worst.case@gmx.de>
 * @author      iMSCP Team
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL v2
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login(__FILE__);

// If the feature is disabled, redirects in silent way
if (!customerHasFeature('external_mail') || !customerHasFeature('mail')) {
    redirectTo('index.php');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic('layout', 'shared/layouts/ui.tpl');
$tpl->define_dynamic('page', 'client/mail_external_add.tpl');
$tpl->define_dynamic('page_message', 'layout');
$tpl->define_dynamic('relay_message', 'page');
$tpl->define_dynamic('relay_server_entry_item', 'page');
$tpl->define_dynamic('mx_entry_item', 'page');
$tpl->define_dynamic('cname_entry_item', 'page');

if (isset($_GET['id']) && isset($_GET['id'])) {
	$item_id = $_GET['id'];
} else if (isset($_POST['id'])) {
	$item_id = $_POST['id'];
} else {
	redirectTo('mail_external.php');
}

/**
 * @param $data
 * @param null $value
 * @return string
 */
function create_options($data, $value = null) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$res = '';
	reset($data);

	foreach ($data as $item) {
		$res .= '<option value="' . $item . '"' .
				(($item == $value) ? $cfg->HTML_SELECTED : '') . '>' . $item .
				'</option>';
	}
	return $res;
}

/**
 * @param $dns_record
 * @param $cname_name
 * @param null $err
 * @return bool
 */
function validate_CNAME($dns_record, $cname_name, &$err = null)
{
    if(!iMSCP_Validate::getInstance()->domainName($dns_record, array('tld' => false))) {
        $err .= tr('Usage of disallowed character in CNAME');
        return false;
    }

    if (empty($cname_name)) {
        $err .= tr('Name must be filled.');
        return false;
    }
	return true;
}

/**
 * @param $domain
 * @param $err
 * @return bool
 */
function validate_NAME($domain, &$err)
{
	if(strpos($domain['name'], '.') === false) {
		$entry = $domain['name'].'.dummy';
	} else {
		$entry = $domain['name'];
	}

	if(!iMSCP_Validate::getInstance()->domainName($entry, array('tld' => false))) {
		$err .= tr('Invalid NAME');
		return false;
	}
	if (preg_match('/\.$/', $domain['name'])) {
		if (!preg_match('/'.str_replace('.', '\.', $domain['domain']).'\.$/', $domain['name'])) {
			$err .= sprintf(tr('Record "%s" is not part of domain "%s".', $domain['name'], $domain['domain']));
			return false;
		}
	}
	return true;
}

/**
 * @param $dns_record
 * @param $mx_srv_prio
 * @param $err
 * @param $text
 * @return bool
 */
function validate_MX($dns_record, $mx_srv_prio, &$err, &$text) {

	// Add a dot in the end if not
	if (substr($dns_record, -1) != '.') {
        $dns_record .= '.';
	}


	if (!preg_match('~^([\d]+)$~', $mx_srv_prio)) {
		$err .= tr('Priority must be a number!');
		return false;
	}

    if(!iMSCP_Validate::getInstance()->domainName($dns_record, array('tld' => false))) {
            $err .= tr('Usage of disallowed character in MX');
            return false;
    }

	$text = sprintf("%d\t%s", $mx_srv_prio, $dns_record);
	return true;
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $dmn_type
 * @param $post_check
 */
function gen_page_form_data($tpl, $dmn_id, $dmn_type, $post_check) {

	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if ($post_check === 'no') {
        $tpl->assign(
            array(
                'SELECT_RELAY_TYPE' => create_options(array('MX', 'CNAME'), ''),
                'SELECT_MX_PRIO' => create_options(array('10', '15', '20'), ''),
                'SELECT_MX_ALIAS' => create_options(array('empty', '*'), ''),
                'CNAME_NAME' => '',
                'SRV_DNSRECORD' => '',
                'RELAY_SERVER_ENTRY_ITEM' => ''));

	} else {
        $del_items = (isset($_POST['del_item'])) ? $_POST['del_item'] : array();
        $item_counter = 0;
        for($i=0;$i<count($_POST['relay_type']);$i++) {
        $err = '';
        $_text = '';
        $srv_dnsrecord = '';
        $cname_name = '';
        $mx_priority = '';
        $mx_alias = '';
        if($i === 0) {
            $srv_dnsrecord = (isset($_POST['srv_dnsrecord'][$i])) ? $_POST['srv_dnsrecord'][$i] : '';
            $cname_name = (isset($_POST['cname_name'][$i])) ? $_POST['cname_name'][$i] : '';
            $mx_priority = (isset($_POST['mx_priority'][$i])) ? $_POST['mx_priority'][$i] : '';
            $mx_alias = (isset($_POST['mx_alias'][$i])) ? $_POST['mx_alias'][$i] : '';
            switch ($_POST['relay_type'][$i]) {
                case 'CNAME':
                    if (!validate_CNAME($srv_dnsrecord, $cname_name, $err))
                        set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['relay_type'][$i], $err), 'error');
					if(!validate_NAME(array('name' => $cname_name, 'domain' => $srv_dnsrecord), $err))
						set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), 'CNAME', $err), 'error');
					breaK;
                case 'MX':
                    if (!validate_MX($srv_dnsrecord, $mx_priority, $err, $_text))
                        set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), $_POST['relay_type'][$i], $err), 'error');
                        break;
                default :
                    set_page_message(sprintf(tr('Unknown entry type %s.'), $_POST['relay_type'][$i]), 'error');
            }
            $tpl->assign(
                    array(
                        'SELECT_RELAY_TYPE' => create_options(array('MX', 'CNAME'), $_POST['relay_type'][$i]),
                        'SELECT_MX_PRIO' => create_options(array('10', '15', '20'), $mx_priority),
                        'SELECT_MX_ALIAS' => create_options(array('empty', '*'), $mx_alias),
                        'RELAY_SERVER_ENTRY_ITEM' => '',
                        'CNAME_NAME' => $cname_name,
                        'SRV_DNSRECORD' => $srv_dnsrecord));
            } else {
                $srv_dnsrecord = (isset($_POST['srv_dnsrecord'][$i])) ? $_POST['srv_dnsrecord'][$i] : '';
                $cname_name = (isset($_POST['cname_name'][$i])) ? $_POST['cname_name'][$i] : '';
                $mx_priority = (isset($_POST['mx_priority'][$i])) ? $_POST['mx_priority'][$i] : '';
                $mx_alias = (isset($_POST['mx_alias'][$i])) ? $_POST['mx_alias'][$i] : '';
                switch ($_POST['relay_type'][$i]) {
                    case 'CNAME':
                        if(!in_array($i, $del_items)) {
                            $item_counter++;
                            if (!validate_CNAME($srv_dnsrecord, $cname_name, $err))
                                set_page_message(sprintf(tr("\nCannot validate %s record. Reason: '%s'."), $_POST['relay_type'][$i], $err), 'error');
							if(!validate_NAME(array('name' => $cname_name, 'domain' => $srv_dnsrecord), $err))
								set_page_message(sprintf(tr("Cannot validate %s record. Reason: '%s'."), 'CNAME', $err), 'error');
                            $tpl->assign(
                                    array(
                                        'DEL_ITEM_ID' => 'del_item_'.$i,
                                        'DEL_ITEM' => $item_counter,
                                        'CNAME_NAME_ITEM' => $cname_name,
                                        'SRV_DNSRECORD_ITEM' => $srv_dnsrecord,
                                        'RELAY_TYPE_ID' => 'relay_type_'.$i,
                                        'CNAME_NAME_ID' => 'cname_name_'.$i,
                                        'MX_ALIAS_ID' => 'mx_alias_'.$i,
                                        'CNAME_PRIORITY_ID' => 'cname_priority_'.$i,
                                        'MX_PRIORITY_ID' => 'mx_priority_'.$i,
                                        'SRV_DNSRECORD_ID' => 'srv_dnsrecord_'.$i,
                                        'MX_ENTRY_ITEM' => ''));
                            $tpl->parse('CNAME_ENTRY_ITEM', 'cname_entry_item');
                            $tpl->parse('RELAY_SERVER_ENTRY_ITEM', '.relay_server_entry_item');
                        }
                        break;
                    case 'MX':
                        if(!in_array($i, $del_items)) {
                            $item_counter++;
                            if (!validate_MX($srv_dnsrecord, $mx_priority, $err, $_text))
                                set_page_message(sprintf(tr("\nCannot validate %s record. Reason: '%s'."), $_POST['relay_type'][$i], $err), 'error');
                            $tpl->assign(
                                    array(
                                        'DEL_ITEM_ID' => 'del_item_'.$i,
                                        'DEL_ITEM' => $item_counter,
                                        'SELECT_MX_PRIO_ITEM' => create_options(array('10', '15', '20'), $mx_priority),
                                        'SELECT_MX_ALIAS_ITEM' => create_options(array('empty', '*'), $mx_alias),
                                        'SRV_DNSRECORD_ITEM' => $srv_dnsrecord,
                                        'RELAY_TYPE_ID' => 'relay_type_'.$i,
                                        'MX_ALIAS_ID' => 'mx_alias_'.$i,
                                        'CNAME_NAME_ID' => 'cname_name_'.$i,
                                        'MX_PRIORITY_ID' => 'mx_priority_'.$i,
                                        'CNAME_PRIORITY_ID' => 'cname_priority_'.$i,
                                        'SRV_DNSRECORD_ID' => 'srv_dnsrecord_'.$i,
                                        'CNAME_ENTRY_ITEM' => ''));
                            $tpl->parse('MX_ENTRY_ITEM', 'mx_entry_item');
                            $tpl->parse('RELAY_SERVER_ENTRY_ITEM', '.relay_server_entry_item');
                        }
                        break;
                    default :
                        set_page_message(sprintf(tr("\nUnknown entry type %s."), $_POST['relay_type'][$i]), 'error');
                }
            }
        }
        if (!Zend_Session::namespaceIsset('pageMessages')) {
			$alias_id = ($dmn_type === "alias") ? $dmn_id : '0';
			$domain_id = ($dmn_type === "normal") ? $dmn_id : '0';
			$dns_entry_ids = '';

			for($i=0;$i<count($_POST['relay_type']);$i++) {
				if(!in_array($i, $del_items)) {
					$mx_alias = (isset($_POST['mx_alias'][$i]) && $_POST['mx_alias'][$i] === "empty") ? '' : '*';
					$_dns = ($_POST['relay_type'][$i] === "CNAME") ? $cname_name : $mx_alias;
					$srv_dnsrecord = ($_POST['relay_type'][$i] === "MX") ? $_POST['mx_priority'][$i]."	".encode_idna($_POST['srv_dnsrecord'][$i]) : encode_idna($_POST['srv_dnsrecord'][$i]);
					if (substr($srv_dnsrecord, -1) != '.') {
						$srv_dnsrecord .= '.';
					}

					$query = "
						INSERT INTO
							`domain_dns` (
								`domain_id`, `alias_id`, `domain_dns`, `domain_class`,
								`domain_type`, `domain_text`, `protected`
							) VALUES (
								?, ?, ?, ?, ?, ?, ?
							)
					";

					$rs = exec_query(
						$query,
						array($domain_id, $alias_id, $_dns, 'IN', $_POST['relay_type'][$i], $srv_dnsrecord, 'yes'),
						false
					);

					# Error because duplicate entry ? (SQLSTATE 23000)
					if($rs === false) {
						/** @var $db iMSCP_Database */
						$db = iMSCP_Registry::get('db');
						if($db->getLastErrorCode() == 23000) {
							set_page_message(tr('DNS record already exist.'), 'error');
							return false;
						} else { # Another error ? Throw exception
							throw new iMSCP_Exception_Database(
								$db->getLastErrorMessage() . " - Query: $query"
							);
						}
					} else {
						/** @var $db iMSCP_Database */
						$db = iMSCP_Registry::get('db');
						$dns_entry_ids .= ($dns_entry_ids == '') ? $db->insertId() : ",".$db->insertId();
					}

					if ($alias_id == 0) {
						$query = "
							UPDATE
								`domain`
							SET
								`domain`.`external_mail` = ?,
								`domain`.`external_mail_status` = ?,
								`domain`.`external_mail_dns_ids` = ?
							WHERE
								`domain`.`domain_id` = ?
						";

						exec_query(
							$query, array('on', $cfg->ITEM_ADD_STATUS, $dns_entry_ids, $domain_id)
						);

					} else {
						$query = "
							UPDATE
								`domain_aliasses`
							SET
								`domain_aliasses`.`external_mail` = ?,
								`domain_aliasses`.`external_mail_status` = ?,
								`domain_aliasses`.`external_mail_dns_ids` = ?
							WHERE
								`domain_aliasses`.`alias_id` = ?
						";

						exec_query(
							$query, array('on', $cfg->ITEM_ADD_STATUS, $dns_entry_ids, $alias_id)
						);
					}
				}
			}
			// Send request to i-MSCP daemon
			send_request();

			$admin_login = $_SESSION['user_logged'];
			write_log("$admin_login: add new external mail server records.", E_USER_NOTICE);

			return true;
        } else {
			return false;
		}
	}
}

/**
 * @param $tpl
 * @param $dmn_id
 * @param $item_type
 */
function get_dmn_data($tpl, $dmn_id, $item_type) {
    /** @var $cfg iMSCP_Config_Handler_File */
    $cfg = iMSCP_Registry::get('config');

	if ($item_type === 'normal') {
        $query = "
              SELECT
                `domain_name`
              FROM
                `domain`
              WHERE
                `domain_id` = '$dmn_id'
            ";
        $rs = execute_query($query);
        $dmn_name = $rs->fields['domain_name'];
	} else {
        $query = "
              SELECT
                `alias_name`
              FROM
                `domain_aliasses`
              WHERE
                `alias_id` = '$dmn_id'
            ";
        $rs = execute_query($query);
        $dmn_name = $rs->fields['alias_name'];
	}

    $tpl->assign(
        array(
            'DOMAIN_NAME' => tohtml($dmn_name),
            'ID' => $dmn_id.";".$item_type,
            'DEFAULT' => (isset($_POST['relay_type']) && $_POST['relay_type'][0] === 'CNAME') ? 'CNAME' : 'MX'));
}

/**
 * @param $tpl
 * @param $item_id
 * @param $user_id
 * @return void
 */
function gen_page_external_mail_props($tpl, $item_id, $user_id) {
    $form_data = false;
    $match = array();
    if (preg_match("/(\d+);(normal|alias)/", $item_id, $match) == 1) {
        $item_id = $match[1];
        $item_type = $match[2];
        if ($item_type === 'normal' || $item_type === 'alias') {
            get_dmn_data($tpl, $item_id, $item_type);
        } else {
            redirectTo('mail_external.php');
        }
        $post_check = isset($_POST['uaction']) ? 'yes' : 'no';
        $form_data = gen_page_form_data($tpl, $item_id, $item_type, $post_check);
        if (isset($_POST['uaction']) && $_POST['uaction'] === 'add_external_mail' && $form_data === true) {
            set_page_message(tr('External mail servers scheduled for addition.'), 'success');
            redirectTo('mail_external.php');
        }
    } else {
		set_page_message(tr('Domaintype not allowed for external mail servers.'), 'error');
        redirectTo('mail_external.php');
    }
}

// common page data.

$tpl->assign(
	array(
		 'TR_PAGE_TITLE' => tr('i-MSCP - Client / Manage mail / Create External mail server entry'),
		 'THEME_CHARSET' => tr('encoding'),
		 'ISP_LOGO' => layout_getUserLogo()));

if (isset($_SESSION['email_support']) && $_SESSION['email_support'] == "no") {
	$tpl->assign('NO_MAILS', '');
}

gen_page_external_mail_props($tpl, $item_id, $_SESSION['user_id']);
generateNavigation($tpl);

$tpl->assign(
	array(
         'TR_ACTION' => tr('Action'),
         'TR_RELAY_TYPE' => tr('Entry type'),
         'TR_MX' => tr('MX'),
         'TR_CNAME' => tr('CNAME'),
         'TR_RELAY_DNS' => tr('Alias / Canonical name'),
		 'TR_CREATE_RELAY' => tr('Activate Entries'),
         'CNAME_PRIORITY' => tr('Not needed in CNAME'),
         'TR_MX_PRIORITY' => tr('Priority'),
		 'TR_MX_ENTRY' => tr('MX entry IN'),
		 'TR_TITLE_RELAY_MAIL_USERS' => tr('Create External mail server entry'),
		 'TR_DOMAIN' => tr('Domain'),
		 'TR_RELAY' => tr('External mail servers'),
         'TR_RELAY_SERVER' => tr('IP or FQDN-Servername'),
         'TR_ADD_NEW' => tr('Add new line'),
         'TR_REMOVE_LAST' => tr('Remove last'),
         'TR_REMOVE_RELAY_ITEM' => tr('Del'),
         'TR_RESET' => tr('Reset')));

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
