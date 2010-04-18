<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
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
 * The Original Code is "ispCP - ISP Control Panel".
 *
 * The Initial Developer of the Original Code is ispCP Team.
 * Portions created by Initial Developer are Copyright (C) 2006-2010 by
 * isp Control Panel. All Rights Reserved.
 */

/**
 * This class provides the functionallitiy needed by {@see admin/system_info.php}
 * for Linux and BSD systems.
 *
 * @author 	Benedikt Heintel <benedikt.heintel@ispcp.net>
 * @version	1.0
 * @since 	r2796
 */
class SystemInfo {

	/**
	 * @var Array() CPU info
	 */
	public $cpu;

	/**
	 * @var Array() file system info
	 */
	public $filesystem;

	/**
	 * @var String Kernel version
	 */
	public $kernel;

	/**
	 * @var Array() system load info
	 */
	public $load;

	/**
	 * @var Array() RAM info
	 */
	public $ram;

	/**
	 * @var Array() Swap info
	 */
	public $swap;

	/**
	 * @var String System uptime
	 */
	public $uptime;

	/**
	 * @var String Error message
	 */
	protected $error = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->cpu 			= $this->_getCPUInfo();
		$this->filesystem 	= $this->_getFileSystemInfo();
		$this->kernel 		= $this->_getKernelInfo();
		$this->load 		= $this->_getLoadInfo();
		$this->ram 			= $this->_getRAMInfo();
		$this->swap 		= $this->_getSwapInfo();
		$this->uptime		= $this->_getUptime();
	}

	/**
	 * Reads /proc/cpuinfo and parses its content
	 *
	 * @return Array(model, # of CPUs, CPUspeed, cache, bogomips)
	 */
	private function _getCPUInfo() {
		$cpu = array(
			'model'		=> tr('N/A'),
			'cpus'		=> tr('N/A'),
			'cpuspeed'	=> tr('N/A'),
			'cache'		=> tr('N/A'),
			'bogomips'	=> tr('N/A')
		);

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
			$tmp = array();

			$pattern = array(
				'/CPU: (.*) \((.*)-MHz (.*)\)/',	// FreeBSD
				'/^cpu(.*) (.*) MHz/', 				// OpenBSD
				'/^cpu(.*)\, (.*) MHz/'				// NetBSD
			);

			if ($cpu['model'] = $this->sysctl('hw.model')) {
				$cpu["cpus"]  = $this->sysctl('hw.ncpu');

				// Read dmesg bot log on reboot
				$dmesg = $this->read('/var/run/dmesg.boot');

				if (empty($this->error)) {
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
			if (empty($this->error)) {

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
					'SUNW,UltraSPARC@0,0',
					'SUNW,UltraSPARC-II@0,0',
					'SUNW,UltraSPARC@1c,0',
					'SUNW,UltraSPARC-IIi@1c,0',
					'SUNW,UltraSPARC-II@1c,0',
					'SUNW,UltraSPARC-IIe@0,0'
				);

				foreach ($sparclist as $sparc) {
					$raw = $this->read(
						'/proc/openprom/' . $sparc . '/ecache-size'
					);

					if(empty($this->error) && !empty($raw)) {
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
	 * @return Array[][mountPoint, fsTyp, disk, percentUsed, free, used, size]
	 */
	private function _getFileSystemInfo() {
		$filesystem = array();

		$descriptorspec = array(
			0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
			1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
			2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
		);

		/* Read output of df command from stdout
		 * Args:
		 *	T: Show File System type
		 *	l: Show only local filesystem
		 */
		// TODO: possibility to handle line breaks on long lines e.g.:
		//		10.0.100.10:/path/to/mount/point
        //		976428116 150249136 826178980  16%
        //		/var/www/virtual/<domain>/htdocs/data
        // if solved, we can savely remove the l-argument
		$proc = proc_open(
			Config::getInstance()->get('CMD_DF') . ' -Tl',
			$descriptorspec,
			$pipes
		);

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
					$filesystem[$i]['percent'] 	= $line[5];
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
	 * @return kernel information
	 */
	private function _getKernelInfo() {
		$kernel = tr('N/A');

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
			if ($kernel_raw = $this->sysctl('kern.version')) {
				$kernel_arr = explode(':', $kernel_raw);

   				$kernel = $kernel_arr[0] . $kernel_arr[1] . ':' . $kernel_arr[2];
			}
		} else {
			$kernel_raw = $this->read('/proc/version');
			if (empty($this->error)) {
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
	 * @return Array(Load1, Load5, Load15)
	 */
	private function _getLoadInfo() {
		$load = array(tr('N/A'), tr('N/A'), tr('N/A'));

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
			if ($load_raw = $this->sysctl('vm.loadavg')) {
				$load_raw = preg_replace('/{\s/', '', $load_raw);
    			$load_raw = preg_replace('/\s}/', '', $load_raw);
   				$load = explode(' ', $load_raw);
			}
		} else {
			$load_raw = $this->read('/proc/loadavg');
			if (empty($this->error)) {
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
	 * @return Array(Total, Free, Used)
	 */
	private function _getRAMInfo() {
		$ram = array('total' => 0, 'free' => 0, 'used' => 0);

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
			if ($ram_raw = $this->sysctl("hw.physmem")) {
				$descriptorspec = array(
					0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
					1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
					2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
				);

				$proc = proc_open(
					Config::getInstance()->get('CMD_VMSTAT'),
					$descriptorspec,
					$pipes
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
					array_shift($swap_info);

					$line = preg_split('/\s+/', $swap_info[0], 19);
					$ram['free'] = $line[5];
				}

				$ram['total'] = $ram_raw / 1024;
				$ram['used']  = $ram['total'] - $ram['free'];
			}
		} else {
			$ram_raw = $this->read('/proc/meminfo');
			if (empty($this->error)) {
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
	 * @return Array(Total, Free, Used)
	 */
	private function _getSwapInfo() {
		$swap = array('total' => 0, 'free' => 0, 'used' => 0);

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
			$descriptorspec = array(
				0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
				1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
				2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
			);

			if (PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
				$args = '-l -k';
			} else {
				$args = '-k';
			}

			$proc = proc_open(
				Config::getInstance()->get('CMD_SWAPCTL') . $args,
				$descriptorspec,
				$pipes
			);

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
			if (empty($this->error)) {
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
	 * Reads /proc/uptime, parses its content and makes it human readable in
	 * the format # Days # Hours # Minutes.
	 *
	 * @return Parsed System Uptime
	 */
	private function _getUptime() {
		$up = 0;
		$uptime_str = tr('N/A');

		if (PHP_OS == 'FreeBSD' || PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
			if ($uptime_raw = $this->sysctl("kern.boottime")) {
				switch (PHP_OS) {
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

			if (empty($this->error)) {
				$uptime = split(' ', $uptime_raw);

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
			$uptime_str .= $upDays . tr('Day') . ' ';
		} else if ($upDays > 1) {
			$uptime_str .= $upDays . tr('Days') . ' ';
		}
		
		if ($upHours == 1) {
			$uptime_str .= ' ' . $upHours . tr('Hour') . ' ';
		} else if ($upHours > 1) {
			$uptime_str .= ' ' . $upHours . tr('Hours') . ' ';
		}
		
		if ($upMins == 1) {
			$uptime_str .= ' ' . $upMins . tr('Minute');
		} else if ($upMins > 1) {
			$uptime_str .= ' ' . $upMins . tr('Minutes');
		}

		return $uptime_str;
	}

	/**
	 * Gets the content of a file if sucessful or and error otherwise.
	 *
	 * @param String $filename Path to file
	 * @return buf Buffer of the file of false on error
	 */
	protected function read($filename) {
		$result = @file_get_contents($filename);

		if ($result === false) {
			$this->error = sprintf(
				tr('File %s does not exists or cannot be reached!'),
				$filename
			);

			return false;
		} else {
			$this->error = '';

			return $result;
		}
	}

	/**
	 * This function emulates PHP 5.3's strstr behavior if used as
	 * strstr($haystack, $needle, true)
	 *
	 * @param string $haystack
	 * @param mixed $needle
	 */
	protected function strstrb($haystack, $needle) {
		return array_shift((explode($needle, $haystack, 2)));
	}

	/**
	 * Execute sysctl on *BDS to receive system information
	 *
	 * @param String $args Arguments to call sysctl
	 * @return String $raw unformated sysctl output
	 */
	protected function sysctl($args) {
		$descriptorspec = array(
			0 => array('pipe', 'r'), // stdin is a pipe that the child will read from
			1 => array('pipe', 'w'), // stdout is a pipe that the child will write to
			2 => array('pipe', 'a')	 // stderr is a pipe that he cild will write to
		);

		$proc = proc_open(
			Config::getInstance()->get('CMD_SYSCTL') . ' -n ' . $args,
			$descriptorspec, $pipes
		);

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
	 * @return String error
	 */
	public function getError() {
		return $this->error;
	}
}
