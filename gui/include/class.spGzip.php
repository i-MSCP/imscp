<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * This class checks the output buffer and zips the input if necessary
 *
 * @todo check if-clause about "There might be some problems"
 */
class spOutput {
	/**
	 * @var $MaxServerload Maximal Load of the server
	 */
	protected $MaxServerload = '2';
	/**
	 * @var $MinCompression Minimal Compression Rate
	 */
	protected $MinCompression = '2';
	/**
	 * @var $MaxCompression Maximal Compression Rate
	 */
	protected $MaxCompression = '9';

	/**
	 * @var $contents buffered output
	 */
	private $contents;
	private $gzdata;
	/**
	 * @var $encoding encoding mechanism
	 */
	private $encoding;
	private $crc;
	private $size;
	private $gzsize;
	private $serverload;
	/**
	 * @var $level compession level
	 */
	private $level;
	/**
	 * @var $debug debug option
	 */
	private $debug;
	/**
	 * @var $showSize HTML output (as comment)
	 */
	private $showSize;

	/**
	 * constructor
	 * @param int 		$level 		the compression level
	 * @param boolean 	$debug 		use debug mode (no compression)
	 * @param boolean 	$showSize 	show the compression in HTML
	 */
	public function __construct($level = '3', $debug = false, $showSize = true) {
		if ($level < $this->MinCompression) {
			$this->level = $this->MinCompression;
		} else if ($level > $this->MaxCompression) {
			$this->level = $this->MaxCompression;
		} else {
			$this->level = $level;
		}

		$this->debug = (boolean) $debug;
		$this->showSize = (boolean) $showSize;
	}

	/**
	 * Compression output
	 *
	 * @param string $buffer input buffer
	 * @return mixed the output$buffer
	 */
	public function output($buffer) {
		/*
		 * Prevent compression, if contents/buffer is empty (cause otherwise
 		 * 0.00 KB files grow up to 0.01 kB gzipped files, this means more load
 		 * and more traffic) 
 		 */
		if (count($buffer) == 0)
			return $buffer;

		$this->contents = $buffer;

		// Find out which encoding to use
		$this->encoding = false;

		/*
		 * Check the best compress version for the browser
		 * Use the @ to prevent bots from saving an error
		 */
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
			if (@strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false) {
				$this->encoding = 'x-gzip';
			}
			if (@strpos(' ' . $_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false) {
				$this->encoding = 'gzip';
			}
		}

		// Find out more about the file which should be compressed
		$filetype = substr($this->contents, 0, 4);

		if (substr($filetype, 0, 2) === '^_') {
			// gzip data
			$this->encoding = false;
		} elseif (substr($filetype, 0, 3) === 'GIF') {
			// gif images
			$this->encoding = false;
		} elseif (substr($filetype, 0, 2) === "\xFF\xD8") {
			// jpeg images
			$this->encoding = false;
		} elseif (substr($filetype, 0, 4) === "\x89PNG") {
			// png images
			$this->encoding = false;
		} elseif (substr($filetype, 0, 3) === 'FWS') {
			// Shockwave Flash
			$this->encoding = false;
		} elseif (substr($filetype, 0, 2) === 'PK') {
			// pk zip file
			$this->encoding = false;
		} elseif ($filetype == '%PDF') {
			// PDF File
			$this->encoding = false;
		}

		// There might be some problems
		if (headers_sent()
			|| connection_status() != 0
			|| !$this->encoding
			|| $this->contents === false
			|| !extension_loaded('zlib')
			|| @ini_get('output_handler') == 'ob_gzhandler'
			|| @ini_get('zlib.output_compression')
			|| (isset($GLOBALS['data']['error'])
				&& !empty($GLOBALS['data']['error']))
			) {
			return $this->contents;
		}

		// The introduction for the compressed data
		$this->gzdata = "\x1f\x8b\x08\x00\x00\x00\x00\x00";

		/*
		 * show some extra information
		 * this means compress the content two times
		 */
		if ($this->showSize) {
			// We need some vars for the information
			$uncompressed	= round(strlen($this->contents)/1024, 2);
			$start			= $this->getMicrotime();
			$compressed		= round(
								strlen(
									gzcompress(
										$this->contents, $this->level
									)
								) /1024, 2
							);
			$time			= round(($this->getMicrotime()-$start)*1000, 2);
			$savingkb		= $uncompressed-$compressed;
			$saving			= $uncompressed > '0' ? 
								@round($savingkb/$uncompressed*100, 0) : '0';

			/* Shows some informations */
			$this->contents .= "\n<!--\n\tCompression level: " . $this->level . 
				"\n\tOriginal size: " . $uncompressed . " kb\n\tNew size: " . 
				$compressed . " kb\n\tSaving: " . $savingkb . " kb (" . $saving .
				" %)\n\tTime: " . $time . " ms\n\tServerload: " . 
				round($this->serverload, 2) . "\n-->";
		}

		// create & concat the full output
		$this->gzdata .= substr(gzcompress($this->contents, $this->level), 0, -4);
		unset($compressed_contents);
		$this->gzdata .= pack('V', crc32($this->contents));
		$this->gzdata .= pack('V', strlen($this->contents));
		$this->gzsize = strlen($this->gzdata);

		// This prevents stupid IEs from displaying blank pages
		if (isset($_SERVER['HTTP_USER_AGENT'])
			&& preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])
			&& $this->gzsize < 4096) {
			// Returns the uncompressed content
			return $this->contents;
		}

		// Maybe you just want to see the result of all this
		if ($this->debug) {
			return $this->contents . "\n<!--\n\tspGzip is in debug mode. 
				The shown output is uncompressed\n-->";
		}

		// Send the special header
		header('Content-Encoding: ' . $this->encoding);
		header('Content-Length: ' . $this->gzsize);

		// Exit the class and send all data to the browser
		return $this->gzdata;
	}

	/**
	 * Returns the actual microtime
	 *
	 * @return: int the actual microtime
	 */
	private function getMicrotime() {
		return array_sum(explode(' ', microtime()));
	}

}
