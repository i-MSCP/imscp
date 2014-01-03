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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2014 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_Filter
 * @subpackage	Compress
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2014 by i-MSCP | http://i-mscp.net
 * @author		Laurent Declercq <l.declercq@nuxwin.com>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * Gzip Filter class
 *
 * This class provides filter that allow to compress a string in GZIP file format.
 *
 * This filter can be used both for create a standard gz file, and as filter for the PHP ob_start() function.
 *
 * This filter compresses the data by using the GZIP format specifications
 * according the rfc 1952.
 *
 * @category	i-MSCP
 * @package		iMSCP_Filter
 * @subpackage	Compress
 * @author		Laurent declercq <l.declercq@nuxwin.com>
 * @since       1.0.7 (ispCP)
 * @version		1.0.4
 * @replace		spOutput class
 */
class iMSCP_Filter_Compress_Gzip {

	/**
	 * Contains the filter method name
	 *
	 * @var string
	 */
	const CALLBACK_NAME = 'filter';

	/**
	 * Filter mode for the PHP ob_start function
	 *
	 * @var int
	 */
	const FILTER_BUFFER = 0;

	/**
	 * Filter mode for creation of standard gzip file
	 *
	 * @var int
	 */
	const FILTER_FILE = 1;

	/**
	 * Tells whether information about compression should be added as HTML comments
	 *
	 * It's not recommended to use it in production to avoid multiple compression work.
	 *
	 * <b>Note:</b>Not usable in {@link self::FILTER_FILE} mode
	 *
	 * @var boolean
	 */
	public $compressionInformation = true;

	/**
	 * Minimum compression level
	 *
	 * @var int
	 */
	protected $_minCompressionLevel = 0;

	/**
	 * Maximum compression level
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
	 * Gzip (encoded) Data size
	 *
	 * @var int
	 */
	protected $_gzipDataSize = 0;

	/**
	 * Tells if the filter should act as callback function for the PHP ob_start function or as simple filter for
	 * standard gz file creation.
	 *
	 * @var int
	 */
	protected $_mode;

	/**
	 * Constructor.
	 *
	 * @param int $mode Tells if the filter should act as callback function for the PHP ob_start function or as function
	 * 					for create a standard gz file. The filter mode must be one of the
	 * 					iMSCP_Filter_Compress_Gzip::FILTER_* constants.
	 * @param int $compressionLevel Compression level
	 */
	public function __construct($mode = self::FILTER_FILE, $compressionLevel = 7) {

		if(extension_loaded('zlib')) {
			if($mode === self::FILTER_BUFFER or $mode === self::FILTER_FILE) {
				$this->_mode = $mode;
			} else {
				throw new iMSCP_Exception('iMSCP_Filter_Compress_Gzip error: Unknown filter mode!');
			}
		} else {
			throw new iMSCP_Exception('iMSCP_Filter_Compress_Gzip error: Zlib Compression library is not loaded.');
		}

		if(in_array(
			$compressionLevel,
			range($this->_minCompressionLevel, $this->_maxCompressionLevel))) {
			$this->_compressionLevel = $compressionLevel;
		} else {
			throw new iMSCP_Exception('iMSCP_Filter_Compress_Gzip error: Wrong value for compression level.');
		}
	}

	/**
	 * Gzip Filter
	 *
	 * This method can be used both for create standard gz files, and as filter for the ob_start() function to help
	 * facilitate sending gzip encoded data to the clients browsers that support the gzip content-coding.
	 *
	 * According the PHP documentation, when used as filter for the ob_start() function, and if any error occurs, FALSE
	 * is returned and then, content is sent to the client browser without compression. Note that FALSE is also
	 * returned when the data are already encoded.
	 *
	 * If used in {@link self::FILTER_FILE} mode and if the $filePath is not specified, the encoded string is returned
	 * instead of be written in a file.
	 *
	 * @param string $data Data to be compressed
	 * @param string $filePath File path to be used for gz file creation]
	 * @return string|bool Encoded string in gzip file format, FALSE on failure
	 */
	public function filter($data, $filePath = '') {

		$this->_data = $data;

		// Act as filter for the PHP ob_start function
		if($this->_mode === self::FILTER_BUFFER) {
			if(ini_get('output_handler') != 'ob_gzhandler' && !ini_get('zlib.output_compression') && !headers_sent()
				&& connection_status() == CONNECTION_NORMAL && $this->_getEncoding()
				&& strcmp(substr($data, 0, 2), "\x1f\x8b")) {

					if($this->compressionInformation && !is_xhr()) {
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
			} else {
				return false;
			}

		// Create standard gz file
		} else {

			$gzipData = $this->_getEncodedData();

			if($filePath != '' && $gzipData !== false) {
				$this->_writeFile($gzipData, $filePath);
			}
		}

		return $gzipData;
	}

	/**
	 * Write gzip files
	 *
	 * @throws iMSCP_Exception
	 * @param string $gzipData Data in GZIP file format
	 * @param string $filePath File path for Gzip file
	 * @return void
	 */
	protected function _writeFile($gzipData, $filePath) {

		$directory = dirname($filePath);

		if(is_dir($directory) && is_writable($directory) && $gzipData !== false) {
			$fileHandle = fopen($filePath, 'w');
			fwrite($fileHandle, $gzipData);
			fclose($fileHandle);
		} else {
			throw new iMSCP_Exception(
				"iMSCP_GzipFilter error: `$filePath` is not a valid directory or is not writable."
			);
		}
	}

	/**
	 * Encode data in Gzip file format
	 *
	 * @return string|bool Encoded string in gzip file format, FALSE on failure
	 */
	protected function _getEncodedData() {

		return gzencode($this->_data, $this->_compressionLevel);
	}

	/**
	 * Check and sets the acceptable content-coding for compression
	 *
	 * @return boolean TRUE if the client browser accept gzip content-coding as response, FALSE otherwise
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
	 * Note: Only called when the filter is used as callback function of the PHP ob_start function.
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
	 * Note: Only called when the filter is used as callback function of the PHP ob_start function.
	 *
	 * @param string $time Time for data compression
	 * @return string|bool Encoded data in gzip file format, FALSE on failure
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
