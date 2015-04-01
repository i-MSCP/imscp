<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *
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
 *
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2015 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Add catchall
 *
 * @param string $itemId
 * @return void
 */
function client_addCatchall($itemId)
{
	list($realId, $type) = explode(';', $itemId);

	// Check if user is owner of the domain
	if(!preg_match('(normal|alias|subdom|alssub)', $type) || who_owns_this($realId, $type) != $_SESSION['user_id']) {
		set_page_message(tr('User do not exist or you do not have permission to access this interface'), 'error');
		redirectTo('mail_catchall.php');
	}

	$match = array();
	$mailType = $dmnId = $subId = $mailAddr = '';

	if(isset($_POST['mail_type'])) {
		if($_POST['mail_type'] === 'normal' && isset($_POST['mail_id'])) {
			if(preg_match('/^\d+;(normal|alias|subdom|alssub)$/', $itemId, $match)) {
				$itemType = $match[1];
				$postMailId = clean_input($_POST['mail_id']);

				if(preg_match('/(\d+);([^;]+);/', $postMailId, $match)) {
					$mailId = $match[1];
					$mailAccount = $match[2];

					if($itemType === 'normal') {
						$mailType = MT_NORMAL_CATCHALL;
					} elseif($itemType === 'alias') {
						$mailType = MT_ALIAS_CATCHALL;
					} elseif($itemType === 'subdom') {
						$mailType = MT_SUBDOM_CATCHALL;
					} elseif($itemType === 'alssub') {
						$mailType = MT_ALSSUB_CATCHALL;
					} else {
						showBadRequestErrorPage();
					}

					$stmt = exec_query('SELECT domain_id, sub_id FROM mail_users WHERE mail_id = ?', $mailId);

					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

						$dmnId = $row['domain_id'];
						$subId = $row['sub_id'];

						// Find the mail_addr (catchall -> "@(sub/alias)domain.tld", should be domain part of mail_acc
						$match = explode('@', $mailAccount);
						$mailAddr = '@' . $match[1];

						iMSCP_Events_Aggregator::getInstance()->dispatch(
							iMSCP_Events::onBeforeAddMailCatchall,
							array(
								'mailCatchall' => $mailAddr,
								'mailForwardList' => array($mailAccount)
							)
						);

						exec_query(
							'
								INSERT INTO mail_users (
									mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status,
									mail_auto_respond, quota, mail_addr
								) VALUES (
									?, ?, ?, ?, ?, ?, ?, ?, ?, ?
								)
							',
							array(
								$mailAccount, '_no_', '_no_', $dmnId, $mailType, $subId, 'toadd', '_no_', NULL,
								$mailAddr
							)
						);

						iMSCP_Events_Aggregator::getInstance()->dispatch(
							iMSCP_Events::onAfterAddMailCatchall,
							array(
								'mailCatchallId' => iMSCP_Database::getInstance()->insertId(),
								'mailCatchall' => $mailAddr,
								'mailForwardList' => array($mailAccount)
							)
						);

						send_request();

						write_log("{$_SESSION['user_logged']} added new catch all", E_USER_NOTICE);
						set_page_message(tr('Catch all successfully scheduled for addition.'), 'success');
						redirectTo('mail_catchall.php');
					} else {
						showBadRequestErrorPage();
					}
				} else {
					redirectTo('mail_catchall.php');
				}
			}
		} else if($_POST['mail_type'] === 'forward' && isset($_POST['forward_list'])) {
			if(preg_match('/^(\d+);(normal|alias|subdom|alssub)$/', $itemId, $match) == 1) {
				$itemId = $match[1];
				$itemType = $match[2];

				if($itemType === 'normal') {
					$mailType = MT_NORMAL_CATCHALL;
					$subId = '0';
					$dmnId = $itemId;
					$stmt = exec_query('SELECT domain_name FROM domain WHERE domain_id = ?', $dmnId);
					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
						$mailAddr = '@' . $row['domain_name'];
					} else {
						showBadRequestErrorPage();
					}
				} elseif($itemType == 'alias') {
					$mailType = MT_ALIAS_CATCHALL;
					$subId = $itemId;
					$stmt = exec_query('SELECT domain_id, alias_name FROM domain_aliasses WHERE alias_id = ?', $itemId);

					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
						$dmnId = $row['domain_id'];
						$mailAddr = '@' . $row['alias_name'];
					} else {
						showBadRequestErrorPage();
					}
				} elseif($itemType === 'subdom') {
					$mailType = MT_SUBDOM_CATCHALL;
					$subId = $itemId;
					$stmt = exec_query(
						"
							SELECT
								domain_id, CONCAT(subdomain_name, '.', domain_name) AS subdomain_name
							FROM
								subdomain
							INNER JOIN
								domain USING(domain_id)
							WHERE
								subdomain_id = ?
						",
						$itemId
					);

					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
						$dmnId = $row['domain_id'];
						$mailAddr = '@' . $row['subdomain_name'];
					} else {
						showBadRequestErrorPage();
					}
				} elseif($itemType === 'alssub') {
					$mailType = MT_ALSSUB_CATCHALL;
					$subId = $itemId;
					$stmt = exec_query(
						"
							SELECT
								domain_id, CONCAT(subdomain_alias_name, '.', alias_name) AS subdomain_alias_name
							FROM
								subdomain_alias
							INNER JOIN
								domain_aliasses USING(alias_id)
							WHERE
								subdomain_alias_id = ?
						",
						$itemId
					);

					if($stmt->rowCount()) {
						$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
						$dmnId = $row['domain_id'];
						$mailAddr = '@' . $row['subdomain_alias_name'];
					} else {
						showBadRequestErrorPage();
					}
				} else {
					showBadRequestErrorPage();
				}

				$mailForward = clean_input($_POST['forward_list']);
				$mailAccount = array();
				$faray = preg_split("/[\n,]+/", $mailForward);

				foreach($faray as $value) {
					$value = trim($value);

					if(!chk_email($value) && $value != '') {
						set_page_message(tr('An email addresse is not valid in mail forward list.'), 'error');
						return;
					} else if($value == '') {
						set_page_message(tr('Syntax error found in mail forward list.'), 'error');
						return;
					}

					$mailAccount[] = $value;
				}

				iMSCP_Events_Aggregator::getInstance()->dispatch(
					iMSCP_Events::onBeforeAddMailCatchall,
					array(
						'mailCatchall' => $mailAddr,
						'mailForwardList' => $mailAccount
					)
				);

				exec_query(
					'
						INSERT INTO mail_users (
							mail_acc, mail_pass, mail_forward, domain_id, mail_type, sub_id, status,
							mail_auto_respond, quota, mail_addr
						) VALUES (
							?, ?, ?, ?, ?, ?, ?, ?, ?, ?
						)
					',
					array(
						implode(',', $mailAccount), '_no_', '_no_', $dmnId, $mailType, $subId, 'toadd', '_no_', NULL,
						$mailAddr
					)
				);

				iMSCP_Events_Aggregator::getInstance()->dispatch(
					iMSCP_Events::onAfterAddMailCatchall,
					array(
						'mailCatchallId' => iMSCP_Database::getInstance()->insertId(),
						'mailCatchall' => $mailAddr,
						'mailForwardList' => $mailAccount
					)
				);

				send_request();

				write_log("{$_SESSION['user_logged']} added new catch all", E_USER_NOTICE);
				set_page_message(tr('Catch all successfully scheduled for addition.'), 'success');
				redirectTo('mail_catchall.php');
			} else {
				redirectTo('mail_catchall.php');
			}
		} else {
			showBadRequestErrorPage();
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $id
 * @return void
 */
function client_generatePage($tpl, $id)
{
	$cfg = iMSCP_Registry::get('config');

	$domainProps = get_domain_default_props($_SESSION['user_id']);
	$dmnId = $domainProps['domain_id'];
	$htmlChecked = $cfg['HTML_CHECKED'];

	$okStatus = 'ok';
	$match = array();

	if(preg_match('/^(\d+);(normal|alias|subdom|alssub)$/', $id, $match)) {
		$itemId = $match[1];
		$itemType = $match[2];

		if($itemType === 'normal') {
			$stmt = exec_query(
				'
					SELECT
						mail_id, mail_type, domain_name, mail_acc
					FROM
						mail_users
					INNER JOIN
						domain USING(domain_id)
					WHERE
						domain_id = ?
					AND
						sub_id = ?
					AND
						status = ?
					ORDER BY
						mail_type DESC, mail_acc
				',
				array($dmnId, 0, $okStatus)
			);

			if(!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $htmlChecked,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? '' : $htmlChecked,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? $htmlChecked : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($row['mail_acc']);
					$showDomainName = decode_idna($row['domain_name']);
					$mailAccount = $row['mail_acc'];
					$domainName = $row['domain_name'];
					$tpl->assign(
						array(
							'MAIL_ID' => $row['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $showDomainName),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $domainName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		} elseif($itemType === 'alias') {
			$stmt = exec_query(
				"
					SELECT
						mail_id, mail_type, alias_name, mail_acc
					FROM
						mail_users
					INNER JOIN
						domain_aliasses ON(sub_id = alias_id)
					WHERE
						mail_users.domain_id = ?
					AND
						alias_id = ?
					AND
						mail_type LIKE 'alias_%'
					AND
						status = ?
					ORDER BY
						mail_type DESC, mail_acc
				",
				array($dmnId, $itemId, $okStatus)
			);

			if(!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $htmlChecked,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? '' : $htmlChecked,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? $htmlChecked : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($row['mail_acc']);
					$show_alias_name = decode_idna($row['alias_name']);
					$mailAccount = $row['mail_acc'];
					$alsName = $row['alias_name'];

					$tpl->assign(
						array(
							'MAIL_ID' => $row['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $show_alias_name),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $alsName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		} elseif($itemType === 'subdom') {
			$stmt = exec_query(
				"
					SELECT
						mail_id, mail_type, CONCAT(subdomain_name, '.', domain_name) AS subdomain_name, mail_acc
					FROM
						mail_users
					INNER JOIN
						subdomain ON(sub_id = subdomain_id)
					INNER JOIN
						domain ON(subdomain.domain_id = domain.domain_id)
					WHERE
						mail_users.domain_id = ?
					AND
						subdomain_id = ?
					AND
						mail_type LIKE 'subdom_%'
					AND
						status = ?
					ORDER BY
						mail_type DESC, mail_acc
				",
				array($dmnId, $itemId, $okStatus)
			);

			if(!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $htmlChecked,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? '' : $htmlChecked,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? $htmlChecked : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($row['mail_acc']);
					$showAliasName = decode_idna($row['subdomain_name']);
					$mailAccount = $row['mail_acc'];
					$alsName = $row['subdomain_name'];

					$tpl->assign(
						array(
							'MAIL_ID' => $row['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $showAliasName),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $alsName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		} elseif($itemType === 'alssub') {
			$stmt = exec_query(
				"
					SELECT
						mail_id, mail_type, CONCAT(subdomain_alias_name, '.', alias_name) AS subdomain_name, mail_acc
					FROM
						mail_users
					INNER JOIN
						subdomain_alias ON(sub_id = subdomain_alias_id)
					INNER JOIN
						domain_aliasses USING(alias_id)
					WHERE
						mail_users.domain_id = ?
					AND
						subdomain_alias_id = ?
					AND
						mail_type LIKE 'alssub_%'
					AND
						status = ?
					ORDER BY
						mail_type DESC, mail_acc
				",
				array($dmnId, $itemId, $okStatus)
			);

			if(!$stmt->rowCount()) {
				$tpl->assign(
					array(
						'FORWARD_MAIL_CHECKED' => $htmlChecked,
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : '',
						'MAIL_LIST' => ''
					)
				);
			} else {
				$tpl->assign(
					array(
						'NORMAL_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? '' : $htmlChecked,
						'FORWARD_MAIL_CHECKED' => (isset($_POST['mail_type']) && $_POST['mail_type'] === 'forward')
							? $htmlChecked : '',
						'FORWARD_LIST_VAL' => isset($_POST['forward_list']) ? tohtml($_POST['forward_list']) : ''
					)
				);

				while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$showMailAccount = decode_idna($row['mail_acc']);
					$showAliasName = decode_idna($row['subdomain_name']);
					$mailAccount = $row['mail_acc'];
					$alsName = $row['subdomain_name'];

					$tpl->assign(
						array(
							'MAIL_ID' => $row['mail_id'],
							'MAIL_ACCOUNT' => tohtml($showMailAccount . '@' . $showAliasName),
							'MAIL_ACCOUNT_PUNNY' => tohtml($mailAccount . '@' . $alsName)
						)
					);

					$tpl->parse('MAIL_ITEM', '.mail_item');
				}
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}

/***********************************************************************************************************************
 * Main
 */

// Include core library
require_once 'imscp-lib.php';

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if(customerHasFeature('mail') && isset($_REQUEST['id'])) {
	$itemId = clean_input($_REQUEST['id']);

	if(isset($_POST['uaction']) && $_POST['uaction'] === 'create_catchall') {
		client_addCatchall($itemId);
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => 'client/mail_catchall_add.tpl',
			'page_message' => 'layout',
			'mail_list' => 'page',
			'mail_item' => 'mail_list'
		)
	);

	$tpl->assign(
		array(
			'TR_CLIENT_CREATE_CATCHALL_PAGE_TITLE' => tr('i-MSCP - Client/Create CatchAll Mail Account'),
			'TR_PAGE_TITLE' => tr('Client / Email / Catchall / Add Catchall'),
			'TR_MAIL_LIST' => tr('Email account list'),
			'TR_CATCHALL' => tr('Catchall'),
			'TR_ADD' => tr('Add'),
			'TR_CANCEL' => tr('Cancel'),
			'TR_FORWARD_MAIL' => tr('Forward mail'),
			'TR_FORWARD_TO' => tr('Forward to'),
			'TR_FWD_HELP' => tr('Separate multiple email addresses with a line-break.'),
			'ID' => tohtml($itemId)
		)
	);

	generateNavigation($tpl);
	generatePageMessage($tpl);
	client_generatePage($tpl, $itemId);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

	unsetMessages();
} else {
	showBadRequestErrorPage();
}
