<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
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
 * @package     iMSCP_Filter
 * @subpackage  Encrypt
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @version     SVN: $Id$
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Encryption adapter for mcrypt with space padding method and base64 encoding
 *
 * This filter is currently used to decrypt the i-MSCP database password and also to
 * encrypt/decrypt all user passwords. For storage reason, the final string is
 * encoded and decoded into and from the base64 encoding specified in RFC 2045.
 *
 * About padding method:
 *
 * When the last block of plain text is shorter than the block size, it must be padded.
 * The padding method provided by this filter mimics the Perl Crypt::CBC padding space
 * method. If $block is smaller than $blockSize, it is padded with spaces. PHP mcrypt
 * don't provides this padding method and so, it must be implemented by hand.
 *
 * @category    iMSCP
 * @package     iMSCP_Filter
 * @subpackage  Encrypt
 * @author      Laurent Declercq <laurent.declercq@i-mscp.net>
 * @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 * @since       1.0.0
 * @version     1.0.0
 */
class iMSCP_Filter_Encrypt_McryptBase64 extends Zend_Filter_Encrypt_Mcrypt {

	/**
	 * Defined by Zend_Filter_Interface
	 *
	 * Encrypts $value with the defined settings
	 *
	 * @param  string $value The content to encrypt
	 * @return string The encrypted content
	 */
	public function encrypt($value)
	{
		$blockSize = mcrypt_get_block_size($this->_encryption['algorithm'], $this->_encryption['mode']);
		$value = $this->_padding($value, $blockSize);
		$encrypted = $this->_toBase64(parent::encrypt($value));

		return $encrypted;
	}

	/**
	 * Defined by Zend_Filter_Interface
	 *
	 * Decrypts $value with the defined settings
	 *
	 * @param  string $value Content to decrypt
	 * @return string The decrypted content
	 */
	public function decrypt($value)
	{
		$value = $this->_fromBase64($value);
		$decrypted = trim(parent::decrypt($value), "\x20");

		return $decrypted;
	}

	/**
	 * Initialises the cipher with the set key
	 *
	 * @throws Zend_Filter_Exception
	 * @param  $cipher
	 * @return iMSCP_Filter_Encrypt_McryptBase64
	 */
	protected function _initCipher($cipher)
	{
		if($this->_encryption['salt'] == true) {
			parent::_initCipher($cipher);
		} else {
			$key = $this->_encryption['key'];
			$keysizes = mcrypt_enc_get_supported_key_sizes($cipher);
			$keyLength = strlen($key);
			if((empty($keysizes) && ($keyLength > mcrypt_enc_get_key_size($cipher)|| $keyLength < 1))
				&& !in_array($keyLength, $keysizes)
			) {
				require_once 'Zend/Filter/Exception.php';
				throw new Zend_Filter_Exception('The given key has a wrong size for the set algorithm');
			}

			$result = mcrypt_generic_init($cipher, $key, $this->_encryption['vector']);
			if ($result < 0) {
				require_once 'Zend/Filter/Exception.php';
				throw new Zend_Filter_Exception('Mcrypt could not be initialize with the given setting');
			}
		}

		return $this;
	}

	/**
	 * Pads block
	 *
	 * When the last block of plain text is shorter than the block size,
	 * it must be padded. This method mimic the Perl Crypt::CBC padding
	 * space method. If $block is smaller than $blockSize, it is padded
	 * with spaces.
	 *
	 * @param  string $block Block
	 * @param  int $blockSize Block size
	 * @return string Padded block
	 */
	protected function _padding($block, $blockSize)
	{
		return $block . pack("A" . ($blockSize - strlen($block) % $blockSize), "\x20");
	}

	/**
	 * Encodes value string with MIME base64
	 *
	 * @param  string $value Value to be encoded
	 * @return string Encoded value
	 */
	protected function _toBase64($value)
	{
		return base64_encode($value);
	}

	/**
	 * Decodes value with MIME base64
	 *
	 * @param  string $value Value to be decoded
	 * @return string Decoded value
	 */
	protected function _fromBase64($value)
	{
		return base64_decode($value);
	}
}
