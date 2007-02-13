<?php
/***************************************************************************
 *   Copyright (C) 2006 by phpSysInfo - A PHP System Information Script    *
 *   http://phpsysinfo.sourceforge.net/                                    *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/

// $Id: class.parseProgs.inc.php,v 1.12 2007/02/01 17:37:06 bigmichi1 Exp $

class Parser {
	var $debug	= false;
	var $df_param	= "";
	
	function parse_lspci() {
		
		$arrResults = array();
		
		if ( ( $strBuff = execute_program( "lspci", "", $this->debug ) ) != "ERROR" ) {
			$arrLines = split( "\n", $strBuff );
			foreach( $arrLines as $strLine ) {
				list( $strAddr, $strName) = explode( ' ', trim( $strLine ), 2 );
				$strName = preg_replace( '/\(.*\)/', '', $strName);
				$arrResults[] = $strName;
			}
		}
		if( empty( $arrResults ) ) {
			return false;
		} else {
			asort( $arrResults );
			return $arrResults;
		}
	}
	
	function parse_pciconf() {
		
		$arrResults = array();
		$intS = 0;
		
		if( ( $strBuff = execute_program( "pciconf", "-lv", $this->debug ) ) != "ERROR" ) {
			$arrLines = explode( "\n", $strBuff );
			foreach( $arrLines as $strLine ) {
				if( preg_match( "/(.*) = '(.*)'/", $strLine, $arrParts ) ) {
					if( trim( $arrParts[1] ) == "vendor" ) {
						$arrResults[$intS] = trim( $arrParts[2] );
					} elseif( trim( $arrParts[1]) == "device" ) {
						$arrResults[$intS] .= " - " . trim( $arrParts[2] );
						$intS++;
					}
				}
			}
		}
		if( empty( $arrResults ) ) {
			return false;
		} else {
			asort( $arrResults );
			return $arrResults;
		}
	}
	
	function parse_filesystems() {
		
		global $show_bind, $show_inodes;
		
		$results = array();
		$j = 0;
		
		$df = execute_program('df', '-k' . $this->df_param );
		$df = preg_split("/\n/", $df, -1, PREG_SPLIT_NO_EMPTY);
		sort($df);
		if( $show_inodes ) {
			$df2 = execute_program('df', '-i' . $this->df_param );
			$df2 = preg_split("/\n/", $df2, -1, PREG_SPLIT_NO_EMPTY);
			sort( $df2 );
		}
		$mount = execute_program('mount');
		$mount = preg_split("/\n/", $mount, -1, PREG_SPLIT_NO_EMPTY);
		sort($mount);
		
		foreach( $df as $df_line) {
			$df_buf1  = preg_split("/(\%\s)/", $df_line, 2);
			if( count($df_buf1) != 2) {
				continue;
			}
			preg_match("/(.*)(\s+)(([0-9]+)(\s+)([0-9]+)(\s+)([0-9]+)(\s+)([0-9]+)$)/", $df_buf1[0], $df_buf2);
			$df_buf = array($df_buf2[1], $df_buf2[4], $df_buf2[6], $df_buf2[8], $df_buf2[10], $df_buf1[1]);
			if( $show_inodes ) {
				preg_match_all("/([0-9]+)%/", $df2[$j + 1], $inode_buf, PREG_SET_ORDER);
			}
			if( count($df_buf) == 6 ) {
				$df_buf[5] = trim( $df_buf[5] );
				if( hide_mount( $df_buf[5] ) ) {
					continue;
				}
				$df_buf[0] = trim( str_replace("\$", "\\$", $df_buf[0] ) );
				$current = 0;
				foreach( $mount as $mount_line ) {
					if ( preg_match("#" . $df_buf[0] . " on " . $df_buf[5] . " type (.*) \((.*)\)#", $mount_line, $mount_buf) ) {
						$mount_buf[1] .= "," . $mount_buf[2];
					} elseif ( !preg_match("#" . $df_buf[0] . "(.*) on " . $df_buf[5] . " \((.*)\)#", $mount_line, $mount_buf) ) {
						continue;
					}
					$strFstype = substr( $mount_buf[1], 0, strpos( $mount_buf[1], "," ) );
					if( hide_fstype( $strFstype ) ) {
						continue;
					}
					$current++;
					if( $show_bind || !stristr($mount_buf[2], "bind")) {
						$results[$j] = array();
						$results[$j]['disk'] = str_replace( "\\$", "\$", $df_buf[0] );
						$results[$j]['size'] = $df_buf[1];
						$results[$j]['used'] = $df_buf[2];
						$results[$j]['free'] = $df_buf[3];
						// --> Bug 1527673
						if( $results[$j]['used'] < 0 ) {
							$results[$j]['size'] = $results[$j]['free'];
							$results[$j]['free'] = 0;
							$results[$j]['used'] = $results[$j]['size'];
						}
						// <-- Bug 1527673
						// --> Bug 1649430
						if( $results[$j]['size'] == 0 ) {
							break;
						} else {
							$results[$j]['percent'] = round(($results[$j]['used'] * 100) / $results[$j]['size']);
						}
						// <-- Bug 1649430
						$results[$j]['mount'] = $df_buf[5];
						$results[$j]['fstype'] = $strFstype;
						$results[$j]['options'] = substr( $mount_buf[1], strpos( $mount_buf[1], "," ) + 1, strlen( $mount_buf[1] ) );
						if( $show_inodes && isset($inode_buf[ count( $inode_buf ) - 1][1]) ) {
							$results[$j]['inodes'] = $inode_buf[ count( $inode_buf ) - 1][1];
						}
						$j++;
						unset( $mount[$current - 1] );
						sort( $mount );
						break;
					}
				}
			}
		}
		return $results;
	}
	
}
?>
