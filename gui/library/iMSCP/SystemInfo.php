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
 * Portions created by the ispCP Team are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 *
 * Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
 * i-MSCP - internet Multi Server Control Panel. All Rights Reserved.
 *
 * @category	i-MSCP
 * @package		iMSCP_SystemInfo
 * @copyright 	2006-2010 by ispCP | http://isp-control.net
 * @copyright 	2010-2012 by i-MSCP | http://i-mscp.net
 * @author 		Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @link		http://i-mscp.net i-MSCP Home Site
 * @license		http://www.mozilla.org/MPL/ MPL 1.1
 */

/**
 * This class provides the functionality needed by {@see admin/system_info.php}
 * for Linux and BSD systems.
 *
 * @category    i-MSCP
 * @package		iMSCP_SystemInfo
 * @version     0.0.2
 * project without mention any source... 
 */
class iMSCP_SystemInfo {

	/**
	 * Operating system name where PHP is run
	 *
	 * @var string
	 */
	protected $_os;

	/**
	 * CPU info
	 *
	 * @var array
	 */
	public $cpu;

	/**
	 * File system info
	 *
	 * @var array
	 */
	public $filesystem;

	/**
	 * Kernel version
	 *
	 * @var string
	 */
	public $kernel;

	/**
	 * System load info
	 *
	 * @var array
	 */
	public $load;

	/**
	 * RAM info
	 *
	 * @var array
	 */
	public $ram;

	/**
	 *
	 * @var Array Swap info
	 */
	public $swap;

	/**
	 * System uptime
	 *
	 * @var string
	 */
	public $uptime;

	/**
	 * Error message
	 *
	 * @var string
	 */
	protected $_error = '';

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->_os = php_uname('s');
		$this->cpu = $this->_getCPUInfo();
		$this->filesystem = $this->_getFileSystemInfo();
		$this->kernel = $this->_getKernelInfo();
		$this->load = $this->_getLoadInfo();
		$this->ram = $this->_getRAMInfo();
		$this->swap = $this->_getSwapInfo();
		$this->uptime = $this->_getUptime();
	}

	/**
	 * Reads /proc/cpuinfo and parses its content
	 *
	 * @return array Cpu Information
	 */
	private function _getCPUInfo() {

		$cpu = array(
			'model' => tr('N/A'), 'cpus' => tr('N/A'), 'cpuspeed' => tr('N/A'), 'cache' => tr('N/A'),
			'bogomips' => tr('N/A')
		);

		if ($this->_os == 'FreeBSD' || $this->_os == 'OpenBSD' || $this->_os == 'NetBSD') {

			$tmp = array();

			$pattern = array(
				'/CPU: (.*) \((.*)-MHz (.*)\)/', // FreeBSD
				'/^cpu(.*) (.*) MHz/', // OpenBSD
				'/^cpu(.*)\, (.*) MHz/' // NetBSD
			);

			if ($cpu['model'] = $this->sysctl('hw.model')) {
				$cpu["cpus"]  = $this->sysctl('hw.ncpu');

				// Read dmesg bot log on reboot
				$dmesg = $this->read('/var/run/dmesg.boot');

				if (empty($this->_error)) {
					$dmesg_arr = explode('rebooting', $dmesg);
        			$dmesg_info = explode("\n", $dmesg_arr[count($dmesg_arr)-1]);

					foreach ($dmesg_info as $di) {
						if (preg_match($pattern, $di, $tmp)) {
							$cpu['cpuspeed'] = round($tmp[2]);
							break;
						}
					}
				}
			}
		} else {
			$cpu_raw = $this->read('/proc/cpuinfo');
			if (empty($this->_error)) {

				// parse line for line
				$cpu_info = explode("\n", $cpu_raw);

				// initialize Values:
				$cpu['cpus'] = 0;
				$cpu['bogomips'] = 0;

				foreach ($cpu_info as $ci) {
					$line = preg_split('/\s+:\s+/', trim($ci));

					// Every architecture has its own scheme, it's not granted
					// that this list is complete. If there are any values
					// missing, let us know about them. They will be added in a
					// upcoming release.
					switch($line[0]) {
						case 'model name':
							$cpu['model'] = $line[1];
							break;
						case 'cpu': // PPC
							$cpu['model'] = $line[1];
							break;
						case 'revision': // PPC
							$cpu['model'] .= ' ( rev: ' . $line[1] . ')';
							break;
						case 'cpu model': // Alpha 2.2.x
							$cpu['model'] .= ' (' . $line[1] . ')';
							break;
						case 'system type': // Alpha 2.2.x
							$cpu['model'] .= ', ' . $line[1] . ' ';
							break;
						case 'platform string': // Alpha 2.2.x
							$cpu['model'] .= ' (' . $line[1] . ')';
							break;
						case 'processor':
							$cpu['cpus'] += 1;
							break;
						case 'ncpus probed': // Linux sparc64 & sparc32
							$cpu["cpus"] = $line[1];
							break;
						case 'cpu MHz':
							$cpu["cpuspeed"] = sprintf("%.2f", $line[1]);
							break;
						case 'clock': // PPC
							$cpu['cpuspeed'] = sprintf('%.2f', $line[1]);
							break;
						case 'Cpu0ClkTck': // Linux sparc64
							$cpu['cpuspeed'] = sprintf(
								'%.2f',
								hexdec($line[1]) / 1000000
							);
							break;
						case 'cache size':
							$cpu['cache'] = $line[1];
							break;
						case 'L2 cache': // PPC
							$cpu['cache'] = $line[1];
							break;
						case 'bogomips':
							$cpu["bogomips"] += $line[1];
							break;
						case 'BogoMIPS': // Alpha 2.2.x
							$cpu['bogomips'] += $line[1];
							break;
						case 'BogoMips': // Sparc
							$cpu['bogomips'] += $line[1];
							break;
						case 'Cpu0Bogo': // Linux sparc64 & sparc32
							$cpu['bogomips'] += $line[1];
							break;
					}
				}

				// sparc64 specific implementation
				// Originally made by Sven Blumenstein <bazik@gentoo.org> in
				// 2004 Modified by Tom Weustink <freshy98@gmx.net> in 2004
				$sparclist = array(
					'SUNW,UltraSPARC@0,0', 'SUNW,UltraSPARC-II@0,0', 'SUNW,UltraSPARC@1c,0', 'SUNW,UltraSPARC-IIi@1c,0',
					'SUNW,UltraSPARC-II@1c,0', 'SUNW,UltraSPARC-IIe@0,0'
				);

				foreach ($sparclist as $sparc) {
					$raw = $this->read(
						'/proc/openprom/' . $sparc . '/ecache-size'
					);

					if (empty($this->_error) && !empty($raw)) {
						$cpu['cache'] = base_convert($raw, 16, 10)/1024 . ' KB';
					}
				}

				// XScale specifict implementation
				if ($cpu['cpus'] == 0) {
					foreach ($cpu_info as $ci) {
						$line = preg_split('/\s+:\s+/', trim($ci));

						switch($line[0]) {
							case 'Processor':
								$cpu['cpus'] += 1;
								$cpu['model'] = $line[1];
								break;
							// Wrong description for CPU speed; no bogoMIPS
							// available
							case 'BogoMIPS':
								$cpu['cpuspeed'] = $line[1];
								break;
							case 'I size':
								$cpu['cache'] = $line[1];
								break;
							case 'D size':
								$cpu['cache'] += $line[1];
								break;
						}
					}
					$cpu['cache'] = $cpu['cache']/1024 . ' KB';
				}
			}
		}

		return $cpu;
	}

	/**
	 * Gets and parses the information of mounted filesystem
	 *
	 * @return array File system information
	 */
	private function _getFileSystemInfo() {

		/**
		 * @var $cfg iMSCP_Config_Handler_File
		 */
		$cfg = iMSCP_Registry::get('config');

		$filesystem = array();

		$descriptorspec = array(
			0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
			1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
			2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
		);

		/* Read output of df command from stdout
		 * Args:
		 *	T: Show File System type
		 *	P: Show in POSIX format
		 */
        $pipes = array(); // satisfy warning
		$proc = proc_open($cfg->CMD_DF . ' -TP', $descriptorspec, $pipes);

		if (is_resource($proc)) {
			// Read data from stream (Pipe 1)
			$fs_raw = stream_get_contents($pipes[1]);

			// Close pipe and stream
			fclose($pipes[1]);
			proc_close($proc);

			$fs_info = explode("\n", $fs_raw);
			// First line only contains Legend
			array_shift($fs_info);

			$i = 0;
			foreach ($fs_info as $fs) {
				if (!empty($fs)) {
					$line = preg_split('/\s+/', trim($fs));

					$i++;

					$filesystem[$i]['mount'] 	= $line[0];
					$filesystem[$i]['fstype'] 	= $line[1];
					$filesystem[$i]['disk'] 	= $line[6];
					$filesystem[$i]['percent'] 	= substr($line[5],0,-1); // Remove % from the end of the string
					$filesystem[$i]['used'] 	= $line[3];
					$filesystem[$i]['size'] 	= $line[2];
					$filesystem[$i]['free'] 	= $line[4];
				}
			}
		}

		sort($filesystem);
		return $filesystem;
	}

	/**
	 * Reads /proc/version and parses its content
	 *
	 * @return string|Translated Kernel information
	 */
	private function _getKernelInfo() {

		$kernel = tr('N/A');

		if ($this->_os == 'FreeBSD' || $this->_os == 'OpenBSD' || $this->_os == 'NetBSD') {

			if ($kernel_raw = $this->sysctl('kern.version')) {
				$kernel_arr = explode(':', $kernel_raw);

   				$kernel = $kernel_arr[0] . $kernel_arr[1] . ':' . $kernel_arr[2];
			}
		} else {
			$kernel_raw = $this->read('/proc/version');
			if (empty($this->_error)) {
				if (preg_match('/version (.*?) /', $kernel_raw, $kernel_info)) {
		        	$kernel = $kernel_info[1];

		        	if (strpos($kernel_raw, 'SMP') !== false) {
		          		$kernel .= ' (SMP)';
		        	}
				}
			}
		}

		return $kernel;
	}

	/**
	 * Reads /proc/loadavg and parses its content into Load 1 min, Load 5 Min
	 * and Load 15 min
	 *
	 * @return array Load average
	 */
	private function _getLoadInfo() {

		$load = array(tr('N/A'), tr('N/A'), tr('N/A'));

		if ($this->_os == 'FreeBSD' || $this->_os == 'OpenBSD' || $this->_os == 'NetBSD') {

			if ($load_raw = $this->sysctl('vm.loadavg')) {
				$load_raw = preg_replace('/{\s/', '', $load_raw);
    			$load_raw = preg_replace('/\s}/', '', $load_raw);
   				$load = explode(' ', $load_raw);
			}
		} else {
			$load_raw = $this->read('/proc/loadavg');

			if (empty($this->_error)) {
				// $load[0] - Load 1 Min
				// $load[1] - Load 5 Min
				// $load[2] - Load 15 Min
				// $load[3] - <running processes>/<total processes> <last PID>
				$load = preg_split('/\s/', $load_raw, 4);
				// Only load values are needed
				unset($load[3]);
			}
		}

		return $load;
	}

	/**
	 * Reads /proc/meminfo and parses its content into Total, Used and Free
	 * RAM
	 *
	 * @return array Memory information
	 */
	private function _getRAMInfo() {

		/**
		 * @var $cfg iMSCP_Config_Handler_File
		 */
		$cfg = iMSCP_Registry::get('config');

		$ram = array('total' => 0, 'free' => 0, 'used' => 0);

		if ($this->_os == 'FreeBSD' || $this->_os == 'OpenBSD' || $this->_os == 'NetBSD') {
			if ($ram_raw = $this->sysctl("hw.physmem")) {
				$descriptorspec = array(
					0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
					1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
					2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
				);

				$pipes = array();

				$proc = proc_open(
					$cfg->CMD_VMSTAT, $descriptorspec, $pipes
				);

				if (is_resource($proc)) {
					// Read data from stream (Pipe 1)
					$raw = stream_get_contents($pipes[1]);
					// Close pipe and stream
					fclose($pipes[1]);
					proc_close($proc);
					// parse line for line
					$ram_info = explode("\n", $raw);
					// First line only contains Legend
					array_shift($ram_info);

					$line = preg_split('/\s+/', $ram_info[0], 19);
					$ram['free'] = $line[5];
				}

				$ram['total'] = $ram_raw / 1024;
				$ram['used']  = $ram['total'] - $ram['free'];
			}
		} else {
			$ram_raw = $this->read('/proc/meminfo');
			if (empty($this->_error)) {
				// parse line for line
				$ram_info = explode("\n", $ram_raw);

				foreach ($ram_info as $ri) {
					$line = preg_split('/:\s+/', trim($ri));

					switch ($line[0]) {
						case 'MemTotal':
							$ram['total'] = $this->strstrb($line[1], ' kB');
							break;
						case 'MemFree':
							$ram['free'] = $this->strstrb($line[1], ' kB');
							break;
					}
				}
				$ram['used'] = $ram['total'] - $ram['free'];
			}
		}

		return $ram;
	}

	/**
	 * Reads /proc/swaps and parses its content into Total, Used and Free
	 * Swaps
	 *
	 * @return array Swap information
	 */
	private function _getSwapInfo() {

		/**
		 * @var $cfg iMSCP_Config_Handler_File
		 */
		$cfg = iMSCP_Registry::get('config');

		$swap = array('total' => 0, 'free' => 0, 'used' => 0);

		if ($this->_os == 'FreeBSD' || $this->_os == 'OpenBSD' ||
			$this->_os == 'NetBSD') {

			$descriptorspec = array(
				0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
				1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
				2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
			);

			if ($this->_os == 'OpenBSD' || $this->_os == 'NetBSD') {
				$args = '-l -k';
			} else {
				$args = '-k';
			}

			$pipes = array(); // satisfy warning

			$proc = proc_open($cfg->CMD_SWAPCTL . $args, $descriptorspec, $pipes);

			if (is_resource($proc)) {
				// Read data from stream (Pipe 1)
				$raw = stream_get_contents($pipes[1]);

				// Close pipe and stream
				fclose($pipes[1]);
				proc_close($proc);

				// parse line for line
				$swap_info = explode("\n", $raw);

				foreach ($swap_info as $si) {
					if (!empty($si)) {
						$line = preg_split('/\s+/', trim($si), 6);
						if ($line[0] != 'Total') {
							$swap['total'] += $line[1];
							$swap['used']  += $line[2];
							$swap['free']  += $line[3];
						}
					}
				}

				$line = preg_split('/\s+/', $swap_info[0], 19);
				$ram['free'] = $line[5];
			}

		} else {
			$swap_raw = $this->read('/proc/swaps');
			if (empty($this->_error)) {
				// parse line for line
				$swap_info = explode("\n", $swap_raw);

				// First line only contains Legend
				array_shift($swap_info);

				foreach ($swap_info as $si) {
					if (!empty($si)) {
						$line = preg_split('/\s+/', trim($si));
						$swap['total'] += $line[2];
						$swap['used'] += $line[3];
						$swap['free'] = $swap['total'] - $swap['used'];
					}
				}
			}
		}

		return $swap;
	}

	/**

	/**
	 * Reads /proc/uptime, parses its content and makes it human readable in
	 * the format: # [[Day[s]] # Hour[s]] # Minute[s].
	 *
	 * @return string|Translated Uptime information
	 */
	private function _getUptime() {

		$up = 0;
		$uptime_str = tr('N/A');

		if ($this->_os == 'FreeBSD' || $this->_os == 'OpenBSD' || $this->_os == 'NetBSD') {
			if ($uptime_raw = $this->sysctl("kern.boottime")) {
				switch ($this->_os) {
					case 'FreeBSD':
						$up_arr = explode(' ', $uptime_raw);
						$up_tmp = preg_replace('/{\s/', '', $up_arr[3]);
						$up = time() - $up_tmp;
						break;
					case 'OpenBSD':
					case 'NetBSD':
						$up = time() - $uptime_raw;
						break;
				}
			}
		} else {
			$uptime_raw = $this->read('/proc/uptime');

			if (empty($this->_error)) {
				$uptime = explode(' ', $uptime_raw);

				// $uptime[0] - Total System Uptime
				// $uptime[1] - System Idle Time
				$up = trim($uptime[0]);
			}
		}

		$upMins  = $up / 60;
		$upHours = $upMins / 60;
		$upDays  = floor($upHours / 24);
		$upHours = floor($upHours - ($upDays * 24));
		$upMins  = floor($upMins - ($upHours * 60) - ($upDays * 24 * 60));

		$uptime_str = '';

		if ($upDays == 1) {
			$uptime_str .= $upDays . ' ' . tr('Day') . ' ';
		} else if ($upDays > 1) {
			$uptime_str .= $upDays . ' ' . tr('Days') . ' ';
		}

		if ($upHours == 1) {
			$uptime_str .= ' ' . $upHours . ' ' . tr('Hour') . ' ';
		} else if ($upHours > 1) {
			$uptime_str .= ' ' . $upHours . ' ' . tr('Hours') . ' ';
		}

		if ($upMins == 1) {
			$uptime_str .= ' ' . $upMins . ' ' . tr('Minute');
		} else if ($upMins > 1) {
			$uptime_str .= ' ' . $upMins . ' ' . tr('Minutes');
		}

		return $uptime_str;
	}

	/**
	 * Gets the content of a file if successful or and error otherwise.
	 *
	 * @param dtring $filename Path to file
	 * @return bool|string
	 */
	protected function read($filename) {

		if(is_readable($filename)) {
			$result = file_get_contents($filename);
		} else {
			$this->_error = tr(
				'File %s does not exists or cannot be reached!',
				$filename
			);

			return false;
		}

		$this->_error = '';

		return $result;
	}

	/**
	 * This function emulates PHP 5.3's strstr behavior if used as strstr($haystack, $needle, true)
	 *
	 * @param $haystack
	 * @param $needle
	 * @return mixed
	 */
	protected function strstrb($haystack, $needle) {

		return array_shift((explode($needle, $haystack, 2)));
	}

	/**
	 * Execute sysctl on *BDS to receive system information
	 *
	 * @param string $args Arguments to call sysctl
	 * @return string $raw Unformated sysctl output
	 */
	protected function sysctl($args) {

		/**
		 * @var $cfg iMSCP_Config_Handler_File
		 */
		$cfg = iMSCP_Registry::get('config');

		$descriptorspec = array(
			0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
			1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
			2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
		);

		$pipes = array(); // satisfy warning

		$proc = proc_open($cfg->CMD_SYSCTL . ' -n ' . $args, $descriptorspec, $pipes);

		if (is_resource($proc)) {
			// Read data from stream (Pipe 1)
			$raw = stream_get_contents($pipes[1]);

			// Close pipe and stream
			fclose($pipes[1]);
			proc_close($proc);
		}

		return $raw;
	}

	/**
	 * Returns the latest error
	 *
	 * @return string Error
	 */
	public function getError() {

		return $this->_error;
	}
}
