<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
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
 * @category	iMSCP
 * @package	 iMSCP Roundcube password changer
 * @copyright   2010-2011 by i-MSCP team
 * @author 		Sascha Bay
 * @link		http://www.i-mscp.net i-MSCP Home Site
 * @license	 http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

function password_save($passwd){
	$rcmail = rcmail::get_instance();
	$sql = "UPDATE `mail_users` SET `mail_pass` = %p, `status` = 'change' WHERE `mail_addr` = %u LIMIT 1";

	if ($dsn = $rcmail->config->get('password_db_dsn')) {
	// #1486067: enable new_link option
	if (is_array($dsn) && empty($dsn['new_link']))
		$dsn['new_link'] = true;
	else if (!is_array($dsn) && !preg_match('/\?new_link=true/', $dsn))
	  $dsn .= '?new_link=true';

		$db = new rcube_mdb2($dsn, '', FALSE);
		$db->set_debug((bool)$rcmail->config->get('sql_debug'));
		$db->db_connect('w');
	}
	if ($err = $db->is_error())
		return PASSWORD_ERROR;

	$sql = str_replace('%u', $db->quote($_SESSION['username'],'text'), $sql);
	$sql = str_replace('%p', $db->quote($passwd,'text'), $sql);

	$res = $db->query($sql);

	if (!$db->is_error()) {
		if ($db->affected_rows($res) == 1) {
			send_request();
			return PASSWORD_SUCCESS; // This is the good case: 1 row updated
		}
	}

	return PASSWORD_ERROR;
}
function read_line(&$socket){
	$line = '';

	do {
		$ch = socket_read($socket, 1);
		$line = $line . $ch;
	} while ($ch != "\r" && $ch != "\n");

	return $line;
}

function send_request(){
	$version = "1.0.4";

	//$code = 999;

	@$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	if ($socket < 0) {
		$errno = "socket_create() failed.\n";
		return $errno;
	}

	@$result = socket_connect($socket, '127.0.0.1', 9876);
	if ($result == false) {
		$errno = "socket_connect() failed.\n";
		return $errno;
	}

	// read one line with welcome string
	$out = read_line($socket);

	list($code) = explode(' ', $out);
	if ($code == 999) {
		return $out;
	}

	// send hello query
	$query = "helo  $version\r\n";
	socket_write($socket, $query, strlen($query));

	// read one line with helo answer
	$out = read_line($socket);

	list($code) = explode(' ', $out);
	if ($code == 999) {
		return $out;
	}

	// send reg check query
	$query = "execute query\r\n";
	socket_write($socket, $query, strlen($query));
	// read one line key replay
	$execute_reply = read_line($socket);

	list($code) = explode(' ', $execute_reply);
	if ($code == 999) {
		return $out;
	}

	// send quit query
	$quit_query = "bye\r\n";
	socket_write($socket, $quit_query, strlen($quit_query));

	// read quit answer
	$quit_reply = read_line($socket);

	list($code) = explode(' ', $quit_reply);

	if ($code == 999) {
		return $out;
	}

	list($answer) = explode(' ', $execute_reply);

	socket_close($socket);

	return $answer;
}
?>
