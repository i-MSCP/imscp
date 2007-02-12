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

// $Id: common_functions.php,v 1.54 2007/01/21 12:39:01 bigmichi1 Exp $

// usefull during development
if( isset($showerrors) && $showerrors ) {
	error_reporting( E_ALL | E_NOTICE );
} else {
	error_reporting( E_ERROR | E_WARNING | E_PARSE );
}

// HTML/XML Comment
function created_by () {
	global $VERSION;
	
	return "<!--\n\tCreated By: phpSysInfo - " . $VERSION . "\n\thttp://phpsysinfo.sourceforge.net/\n-->\n";
} 

// print out the bar graph
// $value as full percentages
// $maximim as current maximum 
// $b as scale factor
// $type as filesystem type
function create_bargraph ($value, $maximum, $b, $type = "") {
	global $webpath;
	
	$textdir = direction();
	$imgpath = $webpath . 'templates/' . TEMPLATE_SET . '/images/';
	$maximum == 0 ? $barwidth = 0 : $barwidth = round((100  / $maximum) * $value) * $b;
	$red = 90 * $b;
	$yellow = 75 * $b;
	if (!file_exists(APP_ROOT . "/templates/" . TEMPLATE_SET . "/images/nobar_left.gif")) {
		if ($barwidth == 0) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_middle.gif" width="1">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_' . $textdir['right'] . '.gif">';
		} elseif ( file_exists( APP_ROOT . "/templates/" . TEMPLATE_SET . "/images/yellowbar_left.gif") && ( $barwidth > $yellow ) && ( $barwidth < $red ) ) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'yellowbar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'yellowbar_middle.gif" width="' . $barwidth . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'yellowbar_' . $textdir['right'] . '.gif">';
		} elseif ( ( $barwidth < $red ) || ( $type == "iso9660" ) || ( $type == "CDFS" ) ) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_middle.gif" width="' . $barwidth . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_' . $textdir['right'] . '.gif">';
		} else {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_middle.gif" width="' . $barwidth . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_' . $textdir['right'] . '.gif">';
		}
	} else {
		if ($barwidth == 0) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_middle.gif" width="' . ( 100 * $b ) . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_' . $textdir['right'] . '.gif">';
		} elseif ( file_exists( APP_ROOT . "/templates/" . TEMPLATE_SET . "/images/yellowbar_left.gif" ) && ( $barwidth > $yellow ) && ( $barwidth < $red ) ) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'yellowbar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'yellowbar_middle.gif" width="' . $barwidth . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_middle.gif" width="' . ( ( 100 * $b ) - $barwidth ) . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_' . $textdir['right'] . '.gif">';
		} elseif ( ( $barwidth < $red ) || ( $type == "iso9660" ) || ( $type == "CDFS" ) ) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'bar_middle.gif" width="' . $barwidth . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_middle.gif" width="' . ( ( 100 * $b ) - $barwidth ) . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_' . $textdir['right'] . '.gif">';
		} elseif ( $barwidth == ( 100 * $b ) ) {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_middle.gif" width="' . ( 100 * $b ) . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_' . $textdir['right'] . '.gif">';
		} else {
			return '<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_' . $textdir['left'] . '.gif">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'redbar_middle.gif" width="' . $barwidth . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_middle.gif" width="' . ( ( 100 * $b ) - $barwidth ) . '">'
				  .'<img height="' . BAR_HEIGHT . '" alt="" src="' . $imgpath . 'nobar_' . $textdir['right'] . '.gif">';
		}
	}
}

function create_bargraph_grad( $value, $maximum, $b, $type = "" ) {
	global $webpath;
	
	$maximum == 0 ? $barwidth = 0 : $barwidth = round( ( 100  / $maximum ) * $value );
	$startColor = '0ef424'; // green
	$endColor = 'ee200a'; // red
	if ( $barwidth > 100 ) {
		$barwidth = 0;
	}
	
	return '<img height="' . BAR_HEIGHT . '" width="300" src="' . $webpath . 'includes/indicator.php?height=' . BAR_HEIGHT . '&amp;percent=' . $barwidth . '&amp;color1=' . $startColor . '&amp;color2=' . $endColor . '" alt="">';
}

function direction() {
	global $text_dir;
	
	if( ! isset( $text_dir ) || ( $text_dir == "ltr" ) ) {
		$arrResult['direction'] = "ltr";
		$arrResult['left'] = "left";
		$arrResult['right'] = "right";
	} else {
		$arrResult['direction'] = "rtl";
		$arrResult['left'] = "right";
		$arrResult['right'] = "left";
	}
	
	return $arrResult;
}

// Find a system program.  Do path checking
function find_program ($strProgram) {
	global $addpaths;
	
	$arrPath = array( '/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin', '/usr/local/sbin' );
	if( isset( $addpaths ) && is_array( $addpaths ) ) {
		$arrPath = array_merge( $arrPath, $addpaths );
	}
	if ( function_exists( "is_executable" ) ) {
		foreach ( $arrPath as $strPath ) {
			$strProgrammpath = $strPath . "/" . $strProgram;
			if( is_executable( $strProgrammpath ) ) {
				return $strProgrammpath;
			}
		}
	} else {
		return strpos( $strProgram, '.exe' );
	}
}

// Execute a system program. return a trim()'d result.
// does very crude pipe checking.  you need ' | ' for it to work
// ie $program = execute_program('netstat', '-anp | grep LIST');
// NOT $program = execute_program('netstat', '-anp|grep LIST');
function execute_program ($strProgramname, $strArgs = '', $booErrorRep = true ) {
	global $error;
	$strBuffer = '';
	$strError = '';
	
	$strProgram = find_program($strProgramname);
	if ( ! $strProgram ) {
		if( $booErrorRep ) {
			$error->addError( 'find_program(' . $strProgramname . ')', 'program not found on the machine', __LINE__, __FILE__);
		}
		return "ERROR";
	}
	// see if we've gotten a |, if we have we need to do patch checking on the cmd
	if( $strArgs ) {
		$arrArgs = split( ' ', $strArgs );
		for( $i = 0; $i < count( $arrArgs ); $i++ ) {
			if ( $arrArgs[$i] == '|' ) {
				$strCmd = $arrArgs[$i + 1];
				$strNewcmd = find_program( $strCmd );
				$strArgs = ereg_replace( "\| " . $strCmd, "| " . $strNewcmd, $strArgs );
			}
		}
	}
	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("pipe", "w")   // stderr is a pipe that the child will write to
	);
	$process = proc_open( $strProgram . " " . $strArgs, $descriptorspec, $pipes );
	if( is_resource( $process ) ) {
		while( !feof( $pipes[1] ) ) {
			$strBuffer .= fgets( $pipes[1], 1024 );
		}
		fclose( $pipes[1] );
		while( !feof( $pipes[2] ) ) {
			$strError .= fgets( $pipes[2], 1024 );
		}
		fclose( $pipes[2] );
	}
	$return_value = proc_close( $process );
	if( ! empty( $strError ) || $return_value <> 0 ) {
		if( $booErrorRep ) {
			$error->addError( $strProgram, $strError . "\nReturn value: " . $return_value, __LINE__, __FILE__);
		}
	}
	return trim( $strBuffer );
}

// A helper function, when passed a number representing KB,
// and optionally the number of decimal places required,
// it returns a formated number string, with unit identifier.
function format_bytesize ($intKbytes, $intDecplaces = 2) {
	global $text;
	$strSpacer = '&nbsp;';
	
	if( $intKbytes > 1048576 ) {
		$strResult = sprintf( '%.' . $intDecplaces . 'f', $intKbytes / 1048576 );
		$strResult .= $strSpacer . $text['gb'];
	} elseif( $intKbytes > 1024 ) {
		$strResult = sprintf( '%.' . $intDecplaces . 'f', $intKbytes / 1024);
		$strResult .= $strSpacer . $text['mb'];
	} else {
		$strResult = sprintf( '%.' . $intDecplaces . 'f', $intKbytes );
		$strResult .= $strSpacer . $text['kb'];
	}
	
	return $strResult;
}

function format_speed( $intHz ) {
	$strResult = "";
	
	if( $intHz < 1000 ) {
		$strResult = $intHz . " Mhz";
	} else {
		$strResult = round( $intHz / 1000, 2 ) . " GHz";
	}
	
	return $strResult;
}

function get_gif_image_height( $image ) { 
	// gives the height of the given GIF image, by reading it's LSD (Logical Screen Discriptor)
	// by Edwin Meester aka MillenniumV3
	// Header:
	//3bytes		Discription
	// 3bytes		Version
	// LSD:
	//2bytes		Logical Screen Width
	// 2bytes		Logical Screen Height
	// 1bit		Global Color Table Flag
	// 3bits		Color Resolution
	// 1bit		Sort Flag
	// 3bits		Size of Global Color Table
	// 1byte		Background Color Index
	// 1byte		Pixel Aspect Ratio
	// Open Image
	$fp = fopen( $image, 'rb' ); 
	// read Header + LSD
	$strHeaderandlsd = fread( $fp, 13 );
	fclose( $fp ); 
	// calc Height from Logical Screen Height bytes
	$intResult = ord( $strHeaderandlsd{8} ) + ord( $strHeaderandlsd{9} ) * 255;
	
	return $intResult;
} 

// Check if a string exist in the global $hide_mounts.
// Return true if this is the case.
function hide_mount( $strMount ) {
	global $hide_mounts;
	
	if( isset( $hide_mounts ) && is_array( $hide_mounts ) && in_array( $strMount, $hide_mounts ) ) {
		return true;
	} else {
		return false;
	}
}

// Check if a string exist in the global $hide_fstypes.
// Return true if this is the case.
function hide_fstype( $strFSType ) {
	global $hide_fstypes;
	
	if( isset( $hide_fstypes ) && is_array( $hide_fstypes ) && in_array( $strFSType, $hide_fstypes ) ) {
		return true;
	} else {
		return false;
	}
}

function uptime( $intTimestamp ) {
	global $text;
	$strUptime = '';
    
	$intMin = $intTimestamp / 60;
	$intHours = $intMin / 60;
	$intDays = floor( $intHours / 24 );
	$intHours = floor( $intHours - ( $intDays * 24 ) );
	$intMin = floor( $intMin - ( $intDays * 60 * 24 ) - ( $intHours * 60 ) );
	
	if( $intDays != 0 ) {
		$strUptime .= $intDays. "&nbsp;" . $text['days'] . "&nbsp;";
	}
	if( $intHours != 0 ) {
		$strUptime .= $intHours . "&nbsp;" . $text['hours'] . "&nbsp;";
	}
	$strUptime .= $intMin . "&nbsp;" . $text['minutes'];
	
	return $strUptime;
}

//Replace some chars which are not valid in xml with iso-8859-1 encoding
function replace_specialchars( &$strXml ) {
	$arrSearch = array( chr(174), chr(169), chr(228), chr(246), chr(252), chr(214), chr(220), chr(196) );
	$arrReplace = array( "(R)", "(C)", "ae", "oe", "ue", "Oe", "Ue", "Ae" );
	$strXml = str_replace( $arrSearch, $arrReplace, $strXml );
}

// find duplicate entrys and count them, show this value befor the duplicated name
function finddups( $arrInput ) {
	$arrResult = array();
	
	if( is_array( $arrInput ) ) {
		$arrBuffer = array_count_values( $arrInput );
		foreach( $arrBuffer as $strKey => $intValue) {
			if( $intValue > 1 ) {
				$arrResult[] = "(" . $intValue . "x) " . $strKey;
			} else {
				$arrResult[] = $strKey;
			}
		}
	}
	
	return $arrResult;
}

function rfts( $strFileName, $intLines = 0, $intBytes = 4096, $booErrorRep = true ) {
	global $error;
	$strFile = "";
	$intCurLine = 1;
  
	if( file_exists( $strFileName ) ) {
		if( $fd = fopen( $strFileName, 'r' ) ) {
			while( !feof( $fd ) ) {
				$strFile .= fgets( $fd, $intBytes );
				if( $intLines <= $intCurLine && $intLines != 0 ) {
					break;
				} else {
					$intCurLine++;
				}
			}
			fclose( $fd );
		} else {
			if( $booErrorRep ) {
				$error->addError( 'fopen(' . $strFileName . ')', 'file can not read by phpsysinfo', __LINE__, __FILE__ );
			}
			return "ERROR";
		}
	} else {
		if( $booErrorRep ) {
			$error->addError( 'file_exists(' . $strFileName . ')', 'the file does not exist on your machine', __LINE__, __FILE__ );
		}
		return "ERROR";
	}
	
	return $strFile;
}

function gdc( $strPath, $booErrorRep = true ) {
	global $error;
	$arrDirectoryContent = array();
	
	if( is_dir( $strPath ) ) {
		if( $handle = opendir( $strPath ) ) {
			while( ( $strFile = readdir( $handle ) ) !== false ) {
				if( $strFile != "." && $strFile != ".." && $strFile != "CVS" ) {
					$arrDirectoryContent[] = $strFile;
				}
			}
			closedir( $handle );
		} else {
			if( $booErrorRep ) {
				$error->addError( 'opendir(' . $strPath . ')', 'directory can not be read by phpsysinfo', __LINE__, __FILE__ );
			}
		}
	} else {
		if( $booErrorRep ) {
			$error->addError( 'is_dir(' . $strPath . ')', 'directory does not exist on your machine', __LINE__, __FILE__ );
		}
	}
	
	return $arrDirectoryContent;
}

function temperature( $floatTempC ) {
    global $temperatureformat, $text, $error;
    $strResult = "&nbsp;";
    
    switch( strtoupper( $temperatureformat ) ) {
	case "F":
	    $floatFahrenheit = $floatTempC * 1.8 + 32;
	    $strResult .= round( $floatFahrenheit ) . $text['degreeF'];
	    break;
	case "C":
	    $strResult .= round( $floatTempC ) . $text['degreeC'];
	    break;
	case "F-C":
	    $floatFahrenheit = $floatTempC * 1.8 + 32;
	    $strResult .= round( $floatFahrenheit ) . $text['degreeF'];
	    $strResult .= "&nbsp;(";
	    $strResult .= round( $floatTempC ) . $text['degreeC'];
	    $strResult .= ")";
	    break;
	case "C-F":
	    $floatFahrenheit = $floatTempC * 1.8 + 32;
	    $strResult .= round( $floatTempC ) . $text['degreeC'];
	    $strResult .= "&nbsp;(";
	    $strResult .= round( $floatFahrenheit ) . $text['degreeF'];
	    $strResult .= ")";
	    break;
	default:
	    $error->addError( 'temperature(' . $floatTempC . ')', 'wrong or unspecified temperature format', __LINE__, __FILE__ );
	    break;
    }
	
    return $strResult;
}
?>
