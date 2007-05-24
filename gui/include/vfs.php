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
 *
 **/

/*
 * This should be class constants, but we're php4 compatible
 */

/*
 * File types definition
 */
define('VFS_TYPE_DIR',  'd');
define('VFS_TYPE_LINK', 'l');
define('VFS_TYPE_FILE', '-');

/*
 * Temporary directory
 */
define('VFS_TMP_DIR', '/var/www/ispcp/gui/phptmp');

/*
 * Possible VFS Transfer modes
 */
define('VFS_ASCII',  FTP_ASCII);
define('VFS_BINARY', FTP_BINARY);

/**
 * Virtual File System main class
 *
 * This class allows the ISPCP Control panel to browse and
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
	 * specified domain.
	 *
	 * Warning! $domain parameter is not sanitized, so this is
	 * left as work for the caller.
	 *
	 * @param  string   $domain  Domain name of the new VFS.
	 * @param  resource $db      Adodb database resource.
	 * @return vfs
	 */
	function vfs($domain, &$db) {
		// Sort of php4 destructor
		register_shutdown_function(array(&$this, "__destruct"));
		return $this->__construct($domain, $db);
	}

	/**
	 * PHP5 constructor
	 *
	 * @param  string   $domain Domain name of the new VFS.
	 * @param  resource $db     Adodb database resource.
	 * @return vfs
	 */
	function __construct($domain, &$db) {
		$this->_domain =  $domain;
		$this->_db     =& $db;
		putenv("TMPDIR=" . VFS_TMP_DIR);
	}

	/**
	 * Destructor, ensure that we logout and remove the
	 * temporary user
	 */
	function __destruct() {
		$this->close();
	}

	/**
	 * Set ISPCP DB handler
	 *
	 * The system uses a "global" $sql variable to store the DB
	 * handler, but we're a "black box" ;).
	 *
	 * @param  resource $db Adodb database resource.
	 */
	function setDb(&$db) {
		$this->_db =& $db;
	}

	/**
	 * Create a temporary FTP user
	 *
	 * @return boolean Returns TRUE on succes or FALSE on failure.
	 */
	function _createTmpUser() {

		// Get domain data
		$query = 'select domain_uid, domain_gid
				  from   domain
				  where  domain_name = ?';
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
			$user, $passwd, $rs->fields['domain_uid'], $rs->fields['domain_gid'],
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
	 * @return Returns TRUE on succes or FALSE on failure.
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
	 * @return boolean	Returns TRUE on succes or FALSE on failure.
	 */
	function open() {
		// Check if we're already open
		if ( is_resource($this->_handle) ) {
			return true;
		}

		// Check if we have a valid ispcp database
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
		if ( !is_resource($this->_handle) ) {
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

	/**
	 * Get directory listing
	 *
	 * Get the directory listing of a specified dir,
	 * either in short (default) or long mode.
	 *
	 * @param  string $dirname VFS directory path.
	 * @return array  Returns an array of directory entries or FALSE on error.
	 */
	function ls($dirname) {
		// Ensure that we're open
		if ( !$this->open() ) {
			return false;
		}

		// Path is always relative to the root vfs
		if (substr($dirname,0,1) != '/') {
			$dirname = '/' . $dirname;
		}

		// No security implications, the FTP server handles
		// this for us
		$list = ftp_rawlist( $this->_handle, '-a '.$dirname, false );
		if ( !$list ) {
			return false;
		}
		$len = count($list);
		for( $i=0; $i<$len; $i++ ) {
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
				'type'		=> substr($parts[0],0,1),
			);
		}

		return $list;
	}

	/**
	 * Checks for file existance
	 *
	 * @param  string $file VFS file path.
	 * @param  int $type Type of the file to match. Must be either VFS_TYPE_DIR,
	 * 					VFS_TYPE_LINK or VFS_TYPE_FILE.
	 * @return boolean Returns TRUE if file exists or FALSE if it doesn't exist.
	 */
	function exists($file, $type=null) {
		// Ensure that we're open
		if ( false === $this->open() ) {
			return false;
		}

		// Actually get the listing
		$dirname = dirname($file);
		$list = $this->ls($dirname);
		if ( !$list )
			return false;

		// We get filenames only from the listing
		$file = basename($file);

		// Try to match it
		foreach($list as $entry) {
			// Skip non-matching files
			if ( $entry['file'] != $file ) {
				continue;
			}
			// Check type
			if ( $type !== null && $entry['type'] != $type )
				return false;

			// Matched and same type (or no type specified)
			return true;
		}
		return false;
	}

	/**
	 * Retrieves a file from the virtual file system
	 *
	 * @param  string $file VFS file path.
	 * @param  int VFS transfer mode. Must be either VFS_ASCII or VFS_BINARY.
	 * @return boolean Returns TRUE on succes or FALSE on failure.
	 */
	function get($file, $mode=VFS_ASCII) {
		// Ensure that we're open
		if ( !$this->open() ) {
			return false;
		}

		// Get a temporary file name
		$tmp = tempnam(VFS_TMP_DIR, 'vfs_');
		// Get the actual file
		$res = ftp_get( $this->_handle, $tmp, $file, $mode);
		if ( false === $res ) {
			return false;
		}

		// Retrieve file contents
		$res = file_get_contents($tmp);

		// Delete temporary file
		unlink($tmp);

		return $res;
	}

	/**
	 * Stores a file inside the virtual file system
	 *
	 * @param string $file VFS file path.
	 * @param string $content File contents.
	 * @param int VFS transfer mode. Must be either VFS_ASCII or VFS_BINARY.
	 * @return boolean Returns TRUE on success or FALSE on failure.
	 */
	function put($file, $content, $mode=VFS_ASCII) {

		// Ensure that we're open
		if ( !$this->open() ) {
			return false;
		}

		// Get a temporary file name
		$tmp = tempnam(VFS_TMP_DIR, 'vfs_');

		// Save temporary file
		$res = file_put_contents($tmp, $content);
		if ( false === $res ) {
			return false;
		}

		// Upload it
		$res = ftp_put( $this->_handle, $file, $tmp, $mode);
		if ( !$res ) {
			return false;
		}

		// Remove temp file
		unlink($tmp);

		return true;
	}

}

/**
 * Make sure we have needed file_put_contents() functionality
 */
if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $content)
    {
        // Make sure that we have a string to write
        if (!is_scalar($content)) {
            user_error('file_put_contents() The 2nd parameter should be a string',
                E_USER_WARNING);
            return false;
        }

        // Get the data size
        $length = strlen($content);

        // Open the file for writing
        if (($fh = @fopen($filename, 'wb')) === false) {
            user_error('file_put_contents() failed to open stream: Permission denied',
                E_USER_WARNING);
            return false;
        }

        // Write to the file
        $bytes = 0;
        if (($bytes = @fwrite($fh, $content)) === false) {
            $errormsg = sprintf('file_put_contents() Failed to write %d bytes to %s',
                            $length,
                            $filename);
            user_error($errormsg, E_USER_WARNING);
            return false;
        }

        // Close the handle
        @fclose($fh);

        // Check all the data was written
        if ($bytes != $length) {
            $errormsg = sprintf('file_put_contents() Only %d of %d bytes written, possibly out of free disk space.',
                            $bytes,
                            $length);
            user_error($errormsg, E_USER_WARNING);
            return false;
        }

        // Return length
        return $bytes;
    }
}


?>