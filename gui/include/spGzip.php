<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright	2001-2006 by moleSoftware GmbH
 * @copyright	2006-2009 by ispCP | http://isp-control.net
 * @version		SVN: $Id$
 * @link		http://isp-control.net
 * @author		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

/**
 * This class checks the output buffer
 * zips the input if necessary
 *
 * @param: int $MaxServerload the maximum serverload
 * @param: int $MinCompression the minimum compression level
 * @param: int $MaxCompression the maximum compression level
 * @input: mixed $level the compression level
 * @input: bool $debug use debug mode (no compression)
 * @input: bool $showSize show the compression in html
 * @return: mixed the output
 *
 * @todo prevent compression, if contents/buffer is empty (cause otherwise
 * 		 0.00 KB files grow up to 0.01 KB gzipped files, this means more load
 * 		 and more traffic)
 * @todo check if-clause about "There might be some problems"
 */
class spOutput {
	/**
	 * public vars
	 */
	var $MaxServerload = '2';
	var $MinCompression = '2';
	var $MaxCompression = '8';
	/**
	 * private vars
	 */
	var $contents;
	var $gzdata;
	var $encoding;
	var $crc;
	var $size;
	var $gzsize;
	var $serverload;
	var $level;
	var $debug;
	var $showSize;

	/**
	 * constructor
	 */
	function spOutput($level = '3', $debug = false, $showSize = true) {
		$this->level = $level;
		$this->debug = $debug;
		$this->showSize = $showSize;
	}

	/**
	 * Let's work a bit with the buffer
	 *
	 * @param string $buffer
	 * @return string
	 */
	function output($buffer) {
		$this->contents = $buffer;

		/* Find out which encoding to use */
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

		/* Find out more about the file which should be compressed */
		$filetype = substr($this->contents, 0, 4);

		if (substr($filetype, 0, 2) === '^_') {
			/* gzip data */
			$this->encoding = false;
		} elseif (substr($filetype, 0, 3) === 'GIF') {
			/* gif images */
			$this->encoding = false;
		} elseif (substr($filetype, 0, 2) === "\xFF\xD8") {
			/* jpeg images */
			$this->encoding = false;
		} elseif (substr($filetype, 0, 4) === "\x89PNG") {
			/* png images */
			$this->encoding = false;
		} elseif (substr($filetype, 0, 3) === 'FWS') {
			/* Shockwave Flash */
			$this->encoding = false;
		} elseif (substr($filetype, 0, 2) === 'PK') {
			/* pk zip file */
			$this->encoding = false;
		} elseif ($filetype == '%PDF') {
			/* PDF File */
			$this->encoding = false;
		}

		/* There might be some problems */
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

		/* We need a level to compress the output */
		if ($this->level == 'auto') {
			/* Let's find out, which compression level is OK for the system */
			if (@file_exists('/proc/loadavg')) {
				$fh = @fopen('/proc/loadavg', 'r');
				$load_averages = @fread($fh, 64);
				@fclose($fh);

				$load_averages = @explode(' ', $load_averages);
				$this->serverload = ((float)$load_averages[0]+(float)$load_averages[1]+(float)$load_averages[2])/3;

			} elseif (preg_match('/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/i', @exec('uptime'), $load_averages)) {

				$this->serverload = ((float)$load_averages[1]+(float)$load_averages[2]+(float)$load_averages[3])/3;
			} else {
				$this->serverload = '1';
			}

			$this->level = (1-($this->serverload/$this->MaxServerload))*10;

			/* OK, that looks terrible, but it's faster than a min/max construction */
			$this->level = ($this->level > $this->MinCompression)
							? (($this->level < $this->MaxCompression)
								? intval($this->level)
								: $this->MaxCompression)
							: $this->MinCompression;
		}

		/* The introduction for the compressed data */
		$this->gzdata = "\x1f\x8b\x08\x00\x00\x00\x00\x00";

		/*
		 * show some extra information
		 * this means compress the content two times
		 */
		if ($this->showSize) {
			/* We need some vars for the information */
			$uncompressed	= round(strlen($this->contents)/1024, 2);
			$start			= $this->getMicrotime();
			$compressed		= round(strlen(gzcompress($this->contents, $this->level))/1024, 2);
			$time			= round(($this->getMicrotime()-$start)*1000, 2);
			$savingkb		= $uncompressed-$compressed;
			$saving			= $uncompressed > '0' ? @round($savingkb/$uncompressed*100, 0) : '0';
			$showlevel		= ($this->level == 'auto') ? ' (auto)' : '';

			/* Shows some informations */
			$this->contents .= "\n".'<!--'."\n\t".'Compression level: '.$this->level.$showlevel."\n\t".'Original size: '.$uncompressed.' kb'."\n\t".'New size: '.$compressed.' kb'."\n\t".'Saving: '.$savingkb.' kb ('.$saving.' %)'."\n\t".'Time: '.$time.' ms'."\n\t".'Serverload: '.round($this->serverload, 2)."\n".'-->';
		}

		/* create & concat the full output */
		$this->gzdata .= substr(gzcompress($this->contents, $this->level), 0, - 4);
		unset($compressed_contents);
		$this->gzdata .= pack('V', crc32($this->contents));
		$this->gzdata .= pack('V', strlen($this->contents));
		$this->gzsize = strlen($this->gzdata);

		/* This prevents stupid IEs from displaying blank pages */
		if (isset($_SERVER['HTTP_USER_AGENT'])
			&& preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])
			&& $this->gzsize < 4096) {
			/* Returns the uncompressed content */
			return $this->contents;
		}

		/* Maybe you just want to see the result of all this */
		if ($this->debug) {
			return $this->contents . "\n".'<!--'."\n\t".'spGzip is in debug mode. The shown output is uncompressed'."\n".'-->';
		}

		/* Send the special header */
		header('Content-Encoding: '.$this->encoding);
		header('Content-Length: '.$this->gzsize);

		/* Exit the class and send all data to the browser */
		return $this->gzdata;
	}

	/**
	 * Returns the actual microtime
	 *
	 * @return: int the actual microtime
	 */
	function getMicrotime() {
		return array_sum(explode(' ', microtime()));
	}

} // end of class


/**
 * construct the object
 */
$GLOBALS['class']['output'] = new spOutput('auto', false);


// look in config if enable/disable extra (server load) informations
if (Config::get('SHOW_SERVERLOAD')) {
	$GLOBALS['class']['output']->showSize = true;
} else {
	$GLOBALS['class']['output']->showSize = false;
}


/**
 * Start the output buffering
 */
ob_start(array(&$GLOBALS['class']['output'], 'output'));

?>