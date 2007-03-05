<?php
/**
 * VHCS Omega Virtual File System
 * 
 * This file implements the VHCS Omega Virtual File System to access
 * all user directories from within the panel. We can't access them
 * directly because we have no permissions so we will do it over an
 * FTP layer.
 * 
 * @copyright 	2006-2007 by ispCP | http://isp-control.net
 * @author Marc Pujol <marc@la3.org>
 * @version 0.1
 * @package gui
 * @license
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
 */
define('VFS_TYPE_DIR', 'd');
define('VFS_TYPE_LINK','l');
define('VFS_TYPE_FILE','-');

/**
 * Virtual File System main class
 * 
 * This class allows the VHCS Control panel to browse and
 * edit all of the user files
 */
class vfs {
	
	/**
	 * Domain name of this filesystem
	 *
	 * @var string
	 */
	var $_domain = '';
	
	/**
	 * FTP connection handle
	 *
	 * @var resource
	 */
	var $_handle = null;
	
	/**
	 * Database connection handle
	 *
	 * @var resource
	 */
	var $_db = null;
	
	/**
	 * FTP temporary user name
	 *
	 * @var string
	 */
	var $_user = '';
	
	/**
	 * FTP password
	 *
	 * @var string
	 */
	var $_passwd = '';
	
	/**
	 * Create a new Virtual File System
	 * 
	 * Creates a new Virtual File System object for the
	 * specified domain
	 * 
	 * Warning! $domain parameter is not sanitized, so this is
	 * left as work for the caller
	 *
	 * @param string  $domain
	 * @return vfs
	 */
	function vfs($domain) {
		$this->_domain = $domain;
	}
	
	/**
	 * PHP5 constructor
	 *
	 * @param string $domain
	 */
	function __construct($domain) {
		$this->vfs($domain);
	}
	
	/**
	 * Set VHCS DB handler
	 * 
	 * The system uses a "global" $sql variable to store the DB
	 * handler, but we're a "black box" ;)
	 *
	 * @param  resource $db
	 */
	function setDb(&$db) {
		$this->_db =& $db;
	}
	
	/**
	 * Create a temporary FTP user
	 *
	 * @return boolean Returns TRUE on succes or FALSE on failure
	 */
	function _createTmpUser() {
		// Get domain data
		$query = <<<SQL_QUERY
			select domain_uid AS uid,domain_gid AS gid
			from   domain 
			where  domain_name = ?
SQL_QUERY;
		$rs = exec_query($this->_db, $query, array($this->_domain));
		if ( !$rs ) {
			return false;
		}
		
		// Generate a random userid and password
		$user   = uniqid('tmp_').'@'.$this->_domain;
		$this->_passwd = uniqid('tmp_',true);
		$passwd = crypt_user_ftp_pass($this->_passwd);
		
		// Create the temporary user
		$query = <<<SQL_QUERY
	        insert into ftp_users
	            (userid, passwd, uid, gid, shell, homedir)
	        values
	            (?, ?, ?, ?, ?, ?)
SQL_QUERY;
		$rs = exec_query($this->_db, $query, array(
			$user, $passwd, $rs->fields['uid'], $rs->fields['gid'], 
			'/bin/bash', '/var/www/virtual/' . $this->_domain
		));
		if ( !$rs ) {
			return false;
		}
		
		// All ok
		$this->_user   = $user;
		return true;
	}
	
	/**
	 * Removes the temporary FTP user
	 *
	 * @return TRUE on succes or FALSE on failure
	 */
	function _removeTmpUser() {
		$query = <<<SQL_QUERY
			delete from ftp_users
			where  userid = ?
SQL_QUERY;
		$rs = exec_query($this->_db, $query, array($this->_user));
		
		return $rs ? true : false;
	}
	
	/**
	 * Open the virtual file system
	 *
	 * @return boolean	TRUE on succes or FALSE on failure
	 */
	function open() {
		// Check if we have a valid vhcs database
		if ( !$this->_db ) {
			return false;
		}
		
		// Create the temporary ftp account
		$result = $this->_createTmpUser();
		if ( !$result ) {
			return false;
		}

		// 'localhost' for testing purposes. I have to study if a better
		// $this->_domain would work on all situations
		$this->_handle = @ftp_connect('localhost');
		if ( !$this->_handle ) {
			$this->close();
		}
		
		// Perform actual login
		$response = @ftp_login( $this->_handle, $this->_user, $this->_passwd);
		if ( !$response ) {
			$this->close();
		}
		
		// All went ok! :)
		return true;
	}
	
	/**
	 * Closes the virtual file system
	 *
	 */
	function close() {
		// Close FTP connection
		if ($this->_handle) {
			ftp_close($this->_handle);
			$this->_handle = null;
		}
		// Remove temporary user
		if ($this->_user) {
			$this->_removeTmpUser();
		}
	}
	
	
	function ls($dirname, $long=false, $files_only=false) {
		if (empty($dirname)) $dirname = '/';
		// Check for connection
		if ( !$this->_handle ) {
			return false;
		}
		
		// No security implications, the FTP server handles
		// this for us
		if ( !$long ) {
			$list = ftp_nlist( $this->_handle, $dirname );
		} else {
			$list = ftp_rawlist( $this->_handle, $dirname, false );
			if ( !$list ) {
				return false;
			}
			$len = count($list);
			for( $i=0; $i<$len; $i++ ) {
				$type = substr($list[$i],0,1);
				if ( $files_only && $type != VFS_TYPE_FILE) {
					unset($list[$i]);
					continue;
				}
				$parts = preg_split("/[\s]+/",$list[$i],9);
				$list[$i] = array(
					'perms'		=> $parts[0],
					'number'	=> $parts[1],
					'owner'		=> $parts[2],
					'group'		=> $parts[3],
					'size'		=> $parts[4],
					'month'		=> $parts[5],
					'day'		=> $parts[6],
					'time'		=> $parts[7],
					'file'		=> $parts[8],
					'type'		=> $type,
				);
			}
		}
		
		return $list;
	}
}

?>