<?php
/**
 *  ispCP (OMEGA) a Virtual Hosting Control Panel
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

function get_email_tpl_data($admin_id, $tpl_name) {

	global $sql;

	$query = <<<SQL_QUERY
         		SELECT
            	fname, lname, firm, email
            FROM
            	admin
            WHERE
             	admin_id = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id));

	if ( ($rs->fields('fname') != '') && ($rs->fields('lname') != '') ) {

  	$data['sender_name'] = $rs->fields('fname') . " " . $rs->fields('lname');

	} else {

		$data['sender_name'] = '';

  }

	if ($rs->fields('firm') != '') {

		if ($data['sender_name'] != '') {

			$data['sender_name'] = $data['sender_name'] . " " . "[" . $rs->fields('firm') . "]";

		} else {

			$data['sender_name'] = $rs->fields('firm');

		}

	}

  $data['sender_email'] = $rs->fields('email');

	$query = <<<SQL_QUERY
						SELECT
    	      	subject, message
      	    FROM
        	  	email_tpls
          	WHERE
          		owner_id = ?
          	AND
          		name = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id, $tpl_name));

	if ($rs ->RowCount() == 1 ) {

		$data['subject'] = utf8_encode(html_entity_decode($rs->fields['subject'], ENT_COMPAT));

    	$data['message'] = utf8_encode(html_entity_decode($rs->fields['message'], ENT_COMPAT));

	}
	else {

		$data['subject'] = '';

		$data['message'] = '';

	}

	return $data;

}

function set_email_tpl_data($admin_id, $tpl_name, $data) {

	global $sql;

	$query = <<<SQL_QUERY
  					SELECT
            	subject, message
            FROM
            	email_tpls
            WHERE
            	owner_id = ?
           	AND
           		name = ?
SQL_QUERY;

	$rs = exec_query($sql, $query, array($admin_id, $tpl_name));

	if ($rs ->RowCount() == 0 ) {

		$query = <<<SQL_QUERY
  						INSERT INTO email_tpls
            		(subject, message, owner_id, name)
      	  		VALUES
            		(?, ?, ?, ?)
SQL_QUERY;

	} else {

		$query = <<<SQL_QUERY
  						UPDATE
  							email_tpls
  						SET
  							subject = ?,
          	    message = ?
	            WHERE
  	          	owner_id = ?
    	        AND
      	      	name = ?
SQL_QUERY;

	}

	exec_query($sql, $query, array($data['subject'], $data['message'], $admin_id, $tpl_name));

}

function get_welcome_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'add-user-auto-msg');

	if (!$data['subject']) {

		$data['subject'] = "Welcome new ISPCP user {USERNAME}!";

	}

	if (!$data['message']) {

  	$data['message'] = <<<DATA

Hello {NAME}!

Your ispCP user type is: {USERTYPE}
Your ispCP login is: {USERNAME}
Your ispCP password is: {PASSWORD}

You can login at http://{BASE_SERVER_VHOST}

Good luck with the ispCP system!
The ispCP Team.

DATA;

	}

	return $data;

}

function set_welcome_email($admin_id, $data) {

	set_email_tpl_data($admin_id, 'add-user-auto-msg', $data);

}

function get_lostpassword_activation_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'lostpw-msg-1');

	if (!$data['subject']) {

		$data['subject'] = "Please activate your new ISPCP login!";

	}

	if (!$data['message']) {

  	$data['message'] = <<<DATA

Hello {NAME}!
Use this link to activate your new ISPCP password:

{LINK}

Good Luck with the ispCP System
The ispCP Team

DATA;

	}

	return $data;

}

function set_lostpassword_activation_email($admin_id, $data) {

	set_email_tpl_data($admin_id, 'lostpw-msg-1', $data);

}

function get_lostpassword_password_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'lostpw-msg-2');

	if (!$data['subject']) {

		$data['subject'] = "Your new ISPCP login!";

	}

	if (!$data['message']) {

  	$data['message'] = <<<DATA

Hello {NAME}!

Your ispCP login is: {USERNAME}
Your ispCP password is: {PASSWORD}

You can login at http://{BASE_SERVER_VHOST}

Good Luck with the ispCP System
The ispCP Team

DATA;

	}

	return $data;

}

function set_lostpassword_password_email($admin_id, $data) {

	set_email_tpl_data($admin_id, 'lostpw-msg-2', $data);

}

function get_order_email($admin_id) {

	$data = get_email_tpl_data($admin_id, 'after-order-msg');

	if (!$data['subject']) {

		$data['subject'] = "Confirmation for domain order {DOMAIN}!";

	}

	if (!$data['message']) {

  	$data['message'] = <<<DATA

Dear {NAME},
This is an automatic confirmation for the order of the domain:

{DOMAIN}

Thank you for using ispCP services.
The ISPCP Team

DATA;

	}

	return $data;

}

function set_order_email($admin_id, $data) {

	set_email_tpl_data($admin_id, 'after-order-msg', $data);

}

?>