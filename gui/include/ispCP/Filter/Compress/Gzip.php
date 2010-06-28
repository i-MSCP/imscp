<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * @category	ispCP
 * @package		ispCP_Filter
 * @subpackage	Compress
 * @copyright	2006-2010 by ispCP | http://isp-control.net
 * @author		Laurent Declercq <laurent.declercq@ispcp.net>
 * @version		SVN: $Id$
 * @link		http://isp-control.net ispCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 * @filesource
 */

/**
 * Gzip Filter class
 *
 * This class provides filter that allow to compress a string in GZIP file
 * format.
 *
 * This filter can be used both for create a standard gz file, and as filter for
 * the PHP ob_start() function.
 *
 * This filter compresses the data by using the GZIP format specifications
 * according the rfc 1952.
 *
 * @category	ispCP
 * @package		ispCP_Filter
 * @subpackage	Compress
 * @author		Laurent declercq <laurent.declercq@ispcp.net>
 * @since		1.0.6
 * @version		1.0.0
 * @replace		spOutput class
 */
class ispCP_Filter_Compress_Gzip {

	/**
	 * @var string
	 */
	const FILTER_CALLBACK = 'filter';

	/**
	 * @var string
	 */
	const FILTER_BUFFER = 0;

	/**
	 * @var string
	 */
	const FILTER_FILE = 1;

	/**
	 * Tells whether information about compression should be added as HTML
	 * comments
	 *
	 * It's not recommended to use it in production to avoid multiple
	 * compression work.
	 *
	 * @var boolean
	 */
	public $_compressionInformation = true;

	/**
	 * Min compression level
	 *
	 * @var int
	 */
	protected $_minCompressionLevel = 0;

	/**
	 * Max compression level
	 *
	 * @var int
	 */
	protected $_maxCompressionLevel = 9;

	/**
	 * Compression level
	 *
	 * @var int
	 */
	protected $_compressionLevel = 7;

	/**
	 * Accepted browser content-coding
	 *
	 * @var string
	 */
	protected $_browserAcceptedEncoding = '';

	/**
	 * Data to be compressed
	 *
	 * @var string
	 */
	protected $_data = '';

	/**
	 * Data size
	 *
	 * @var int
	 */
	protected $_dataSize = 0;

	/**
	 * Gzip Data size
	 *
	 * @var int
	 */
	protected $_gzipDataSize = 0;

	/**
	 * Tells if the filter should act as callback function for the PHP ob_start
	 * function or as function for create a standard gz file.
	 *
	 * @var int
	 */
	protected $_mode;

	/**
	 * Constructor
	 *
	 * @param int $mode Tells if the filter should act as callback function for
	 *	the PHP ob_start function or as function for create a standard gz file.
	 *	Possible values are bufferFilter for the callback function, or gzFile
	 *	for creation of a standard gzip file
	 * @param int $compressionLevel Compression level
	 * @return void
	 */
	public function __construct($mode = self::FILTER_FILE, $compressionLevel = 7) {

		if($mode === self::FILTER_BUFFER or $mode === self::FILTER_FILE) {
			$this->_mode = $mode;
		} else {
			throw new ispCP_Exception(
				'ispCP_GzipFilter error: Unknown mode!'
			);
		}

		if(in_array($compressionLevel, 
			range($this->_minCompressionLevel, $this->_maxCompressionLevel))) {

			$this->_compressionLevel = $compressionLevel;
		} else {
			throw new ispCP_Exception(
				'ispCP_GzipFilter error: Wrong value for compression level!'
			);
		}
	}

	/**
	 * Gzip Filter method
	 *
	 * This method can be used both for create standard gz files, and as filter
	 * for the ob_start() function to help facilitate sending gzip encoded data
	 * to the clients browsers that support the gzip content-coding.
	 *
	 * @param string $data Data to be compressed
	 * @param string $destination File path to be used for gz file creation
	 * @return string|false Encoded string in gzip file format, FALSE on failure
	 */
	public function filter($data, $destination = '') {

		$this->_data = $data;

		// Act as filter for the PHP ob_start function
		if($this->_mode === self::FILTER_BUFFER) {
			if($this->_getEncoding()) {

				if($this->_compressionInformation) {

					$statTime = microtime(true);

					$gzipData = $this->_getEncodedData();

					$time = round((microtime(true) - $statTime) * 1000, 2);

					$this->_gzipDataSize = strlen($gzipData);

					$gzipData = $this->_addCompressionInformation($time);
				} else {
					$gzipData = $this->_getEncodedData();
					$this->_gzipDataSize = strlen($gzipData);
				}

				// Send required headers
				$this->_sendHeaders();

			}

		// Create standard gz file
		} else {

			$gzipData = $this->_getEncodedData();
			$this->_writeFile($gzipData, $destination);
		}

		return $gzipData;
	}

	/**
	 * Write gzip files
	 *
	 * @throws ispCP_Exception
	 * @param string $gzipData Data in GZIP file format
	 * @param string $destination Destination file path for Gzip file
	 * @return void
	 */
	protected function _writeFile($gzipData, $destination) {

		$directory = dirname($destination);

		if(is_dir($directory) && is_writable($directory) &&
			 $gzipData !== false) {

			$fileHandle = fopen($destination, 'w');
			fwrite($fileHandle, $gzipData);
			fclose($fileHandle);
		} else {
			throw new ispCP_Exception(
				"ispCP_GzipFilter error: `$destination` is not a valid " .
					"directory or is not writable!"
			);
		}
	}

	/**
	 * Encode data in Gzip file format
	 *
	 * @return string|false Encoded string in gzip file format, FALSE on failure
	 */
	protected function _getEncodedData() {

		return gzencode($this->_data, $this->_compressionLevel);
	}

	/**
	 * Check and sets the acceptable content-coding for compression
	 *
	 * @return boolean TRUE if the client browser accepte gzip content-coding as
	 *	response, FALSE otherwise
	 */
	protected function _getEncoding() {

		if(isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			if(strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
				$this->_browserAcceptedEncoding = 'x-gzip';
			} elseif(strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
				$this->_browserAcceptedEncoding = 'gzip';
			} else {
				return false;
			}

		} else {
			return false;
		}

		return true;
	}

	/**
	 * Send headers
	 *
	 * Note: Only called when the filter is used in 'bufferFilter' mode.
	 *
	 * @return void
	 */
	protected function _sendHeaders() {

		header("Content-Encoding: {$this->_browserAcceptedEncoding}");
		header("Content-Length: {$this->_gzipDataSize}");
	}

	/**
	 * Adds compression information as HTML comment
	 *
	 * Note: Only used  when the filter is used to create GZIP standard file.
	 *
	 * @param string $gzipData Encoded data in gzip file format
	 * @param string $time Time for data compression
	 * @return string|false Encoded data in gzip file format, FALSE on failure
	 */
	protected function _addCompressionInformation($time) {

		$dataSize = round(strlen($this->_data) / 1024, 2);
		$gzipDataSize = round($this->_gzipDataSize / 1024, 2);
		$savingkb = $dataSize - $gzipDataSize;
		$saving = ($dataSize > 0) ? round($savingkb / $dataSize * 100, 0) : 0;

		// Prepare compression Information
		$compressionInformation =
			"\n<!--\n" .
			"\tCompression level: {$this->_compressionLevel}\n" .
			"\tOriginal size: $dataSize kb\n" .
			"\tNew size: $gzipDataSize kb\n" .
			"\tSaving: $savingkb kb ($saving %)\n" .
			"\tTime: $time ms\n" .
			"-->\n";

		$this->_data .= $compressionInformation;

		$gzipData = $this->_getEncodedData();

		$this->_gzipDataSize = strlen($gzipData);

		return $gzipData;
	}

}
