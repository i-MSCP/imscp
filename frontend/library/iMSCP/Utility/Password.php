<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 by internet Multi Server Control Panel
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
 * @category    i-MSCP
 * @copyright   2010 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

require_once 'iMSCP/Utility/Password/Interface.php';

/**
 * Class that allow to encrypt and decrypt password in CBC mode
 *
 * This class is currently used to decrypt the i-MSCP database password and also to
 * encrypt/decrypt all user passwords. For convenience reason the final string is
 * encoded and decoded into and from the base64 encoding specified in RFC 2045.
 * 
 * @category iMSCP
 * @package iMSCP_Utility
 * @author Laurent Declercq <laurent.declercq@i-mscp.net>
 * @since 1.0.0
 * @version 1.0.0
 */
class iMSCP_Utility_Password implements iMSCP_Utility_Password_Interface {

	/**
	 * Key size length
	 */
	const KEY_LENGTH = 32;

	/**
	 * Initialization vector length
	 */
	const IV_LENGTH = 8;

	/**
	 * Password to handle
	 *
	 * Depending of the context, can be a plaintext or encrypted password
	 *
	 * @var string
	 */
	protected $password = '';

	/**
	 * Key
	 * 
	 * @var string
	 */
	protected $key = null;

	/**
	 * Initialization vector
	 *
	 * @var string
	 */
	protected $iv = null;

	/**
	 * Constructor
	 * 
	 * @throws Zend_Exception
	 * @param  $password
	 * @param Zend_Config $options
	 * @return void
	 */
	public function __construct($password, Zend_Config $options = null) {
		if (!extension_loaded('mcrypt')) {
			throw new Zend_Exception("PHP extension 'mcrypt' is not loaded!");
		} elseif($password == '') {
			throw new Zend_Exception("Error: Password can't be an empty string!");
		} elseif($options != null) {
			$this->setOptions($options);
		}

		$this->password = $password;
	}

	/**
	 * Return decrypted password
	 *
	 * @return string
	 */
	public function decrypt() {
		$this->checkOptions();
		return $this->_process('decrypt');
	}

	/**
	 * Return Encrypted password
	 *
	 * @return string Decrypted password
	 */
	public function encrypt() {
		$this->checkOptions();
		return $this->_process('encrypt');
	}

	/**
	 * Set key and initialization vector
	 *
	 * @param Zend_Config $options
	 * @return void
	 */
	public function setOptions(Zend_Config $options) {
		$this->key = $options->key;
		$this->iv = $options->iv;
		$this->checkOptions();
	}

	/**
	 * Set cypher key
	 *
	 * @param  string $key cypher key
	 * @return void
	 */
	public function setKey($key) {
		$this->key = $key;
		$this->checkOptions('key');
		return $this;
	}

	/**
	 * Set initialization vector
	 *
	 * @param string $iv Initialization vector
	 * @return void
	 */
	public function setIv($iv) {
		$this->iv = $iv;
		$this->checkOptions('iv');
		return $this;
	}

	/**
	 * Set password
	 *
	 * Note: Allow to use same object by setting another password
	 *
	 * @throws Zend_Exception
	 * @param  $password
	 * @return void
	 */
	public function setPassword($password) {
		if($password == '') {
			throw new Zend_Exception("Error: Password can't be an empty string!");
		}

		$this->password = $password;
	}

	/**
	 * Check for key and initialization vector
	 *
	 * @throws Zend_Exception
	 * @return void
	 */
	private function checkOptions($check = null) {
		switch($check) {
			case 'key':
			case 'iv':
				if (mb_strlen($this->$check) == constant('SELF::'.strtoupper($check) . '_LENGTH')) break;
				throw new Zend_Exception("KEY or IV has invalid length!");
		    default:
				$this->checkOptions('key');
				$this->checkOptions('iv');
		}
	}

	/**
	 * Encrypt / Decrypt password
	 *
	 * @param  string $mode mode in act (encrypt|decrypt)
	 * @return string Encrypted / Decrypted password
	 */
	protected function _process($mode) {
		$cipher = @mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cbc', '');
		@mcrypt_generic_init($cipher, $this->key, $this->iv);

		if($mode == 'encrypt') {
			$blockSize = @mcrypt_get_block_size(MCRYPT_BLOWFISH, 'cbc');
			$block = $this->_padding($this->password, $blockSize);
			$password = base64_encode(mcrypt_generic($cipher, $block));
		} else {
			$password = @mdecrypt_generic($cipher, base64_decode($this->password));
		}

		@mcrypt_generic_deinit($cipher);
		@mcrypt_module_close($cipher);

		return trim($password);
	}


	/**
	 * Pads block
	 * 
	 * This method mimic the Perl Crypt::CBC padding space method
	 *
	 * @param  string $block Block
	 * @param  int $blockSize Block sise
	 * @return string Padded block
	 * @todo Move to Perl Crypt::CBC standard padding
	 */
	protected function _padding($block, $blockSize) {
		if (!strlen($block)) return;
		$block = strlen($block) ? $block : '';
		return $block . pack("A".($blockSize - strlen($block) % $blockSize), chr(32));
	}
}
