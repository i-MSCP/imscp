<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ("tools/compat.php");

/*
  Variables that are needed throughout the scripts but are not supposed
  to be user 'tunables'.
*/

// int size_conv - Used for converting the byte size of a file
$size_conv = 1024;
  
// array $size_suffix - Array of sized suffixes ie. B,KB,MB,GB
$size_suffix = array ("B&nbsp;", "KB", "MB", "GB", "TB" );

// string weeblefm_Version - current version of the Weeble FM.
$weeblefm_Version = "1.2.2-CVS";

// MCrypt internal module name
$mcrypt_mod = "mcrypt";

// used to alternate the row colors
$alt_class = array("","alt_row");

// these are the only settings that are allowed to be set to ""
$allow_empty = " thm_pg_bg_img, thm_tbl_bg_img, start_dir ";
  
// these variables must be numerical
$numeric_val = "edit_col, edit_row";

// Explicitly set the session name and disable the use of transparent session ids.
ini_set ("session.name", "SID");
ini_set ("session.use_trans_sid", 0);
  
/*
  Defined funtions that are needed throughout the scripts.
*/
  
  // Name: remove_ws
  // Parameters: line_data - data to be formatted
  // Purpose: formats the directory listing, removes extra whitespace
  // Returns: formatted line
  function remove_ws ( $line_data )
  {
    // seperate the string
    $line_data = explode( " ", $line_data );
		
    // for each element in the new array
    for ( $index = 0, $count = 0; $index < count( $line_data ); $index++ )
    {
      // if its not empty and its not part of the file/dir name, store it in the new array
      if ( ! ( $line_data[$index] === "" ) && $count < 8 )
      {
        $newline[$count] = $line_data[$index];
        $count++;
      }
      // else if its part of the filename store it in $name
      else if ( $count >= 8 )
      {
      	if ( isset( $name ) )
          $name = $name . " " . $line_data[$index];
        else
          $name = $line_data[$index];
      }
    }

    // copy the completed file/dir name to the new array and return it    
    $newline[$count] = $name;
    return ($newline);
  }

  // Name: isFile
  // Parameters: perm - permission listing ( drwxr--r-- )
  // Purpose: checks to see whether the listing is a file
  // Returns: true if it is a file, false if a dir.
  function isFile( $perm )
  {
    $char = substr( $perm, 0, 1 );
    if ( $char == "d" || $char == "s" || $char == "l" )
      return FALSE;
    else
      return TRUE;
  }

  // Name: isLink
  // Parameters: perm - permission listing ( lrwxr--r-- )
  // Purpose: checks to see whether the listing is a symbolic link
  // Returns: true if yes, false if no.
  function isLink( $perm )
  {
    $char = substr( $perm, 0, 1 );
    if ( $char == "l" )
      return TRUE;
    else
      return FALSE;
  }
  
  // Name: isDir
  // Parameters: perm - permission listing ( drwxr--r-- )
  // Purpose: checks to see whether the given listing is a directory.
  // Returns: true if yes, false if no.
  function isDir( $perm )
  {
    $char = substr( $perm, 0, 1 );
    if ( $char == "d" )
      return TRUE;
    else
      return FALSE;
  }

  // Name: build_perm
  // Parameters: file - name of the file
  //          permissions - permission listing ( drwxr--r-- )
  // Purpose: builds the permissions for html output
  // Returns: html output
/*	function build_perm( $file, $perm, $SID )
  {
  	for ( $index = 1; $index < 10; $index += 3 )
    {
    	for ( $index2 = $index; $index2 < ( $index + 3 ); $index2++ )
      {
      	$bin = "";
      	if ( strpos( "ST-", substr( $perm, $index2, 1 )) != FALSE )
        	$bin = $bin . '1';
				else
        	$bin = $bin . '0';
      }
      $val[$index] = bindec( $bin );

      if ( strpos( "Ss", substr( $perm, $index2 - 1, 1 )) != FALSE )
      {
				if ( $index == 1 )
					$val[0] = $val[0] + 4000;
        else
        	$val[0] = $val[0] + 2000;
      }
      else if ( strpos( "Tt", substr( $perm, $index2 - 1, 1 )) != FALSE )
      {
        	$val[0] = $val[0] + 1000;
      }
    }
    $chmod_val = intval( implode( "", $val ) );
  }*/

  function build_perm ( $file, $perm )
  {
    global $SID;
  	global $allow_chmod;
    
    $string = substr( $perm, 0, 1 );
    $group = "u";
  
    // check user permissions
    for ( $i = 1; $i <= 9; $i++ )
    {
      $char = substr( $perm, $i, 1 );
            
			// if the admin has chmoding turned on
			if ( $allow_chmod )
			{
	      switch ( $char )
  	    {
    	    case "r":
      	    $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
        	    "&PERM=".$perm."&CHMOD=".$group."-r\">r</a>";
	          break;
  	      case "w":
    	      $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
      	      "&PERM=".$perm."&CHMOD=".$group."-w\">w</a>";
        	  break;
	        case "x":
  	        $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
    	        "&PERM=".$perm."&CHMOD=".$group."-x\">x</a>";
      	     break;
        	case "T":
          	$new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
            	"&PERM=".$perm."&CHMOD=".$group."+x\">T</a>";
	           break;
  	      case "S":
    	      $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
      	      "&PERM=".$perm."&CHMOD=".$group."+x\">S</a>";
        	   break;        
	        case "t":
  	        $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
    	        "&PERM=".$perm."&CHMOD=".$group."-x\">t</a>";
      	     break;
        	case "s":
          	$new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
            	"&PERM=".$perm."&CHMOD=".$group."-x\">s</a>";
	           break;        
  	      case "-":
    	      switch ( $i )
      	    {
        	    case 1:
          	  case 4:
            	case 7:
              	$new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
	                "&PERM=".$perm."&CHMOD=".$group."+r\">-</a>";
  	            break;
    	        case 2:
      	      case 5:
        	    case 8:
          	    $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
            	    "&PERM=".$perm."&CHMOD=".$group."+w\">-</a>";
              	break;
	            case 3:
  	          case 6:
    	        case 9:
      	        $new = "<a \nhref=\"crossover.php?SID=$SID&submit=CHMOD&FILE=".$file.
        	        "&PERM=".$perm."&CHMOD=".$group."+x\">-</a>";
          	}
      	}
      }
      // else just display the character plain, no link to chmod
      else
      	$new = $char;
        
      $string = $string.$new;
      if ( $i >= 3 && $i <= 5 )
        $group = "g";
      if ( $i >= 6 )
        $group = "a";
  }
  
  return ( $string );
  }

// Name: perm_value
// Parameters: permissions - permission of the file
// Purpose - Takes the permission string ( -rwx---r-x ) and converts it to its
//         numerical equivilance
// Return - oct number that represents the permissions
function perm_value( $perm )
{
  $result = 0;

  for ( $i = 1; $i <= 9; $i++ )
  {
    $char = substr( $perm, $i, 1 );  

    switch ( $i )
    {
      case 1:
      case 2:
      case 3:
        $mult = 100;
        break;
      case 4:
      case 5:
      case 6:
        $mult = 10;
        break;
      case 7:
      case 8:
      case 9:
        $mult = 1;
        break;
    }

    switch ( $char )
    {
      case "r":
        $result = $result + ( $mult * 4 );
        break;
      case "w":
        $result = $result + ( $mult * 2 );
        break;
      case "x":
      case "t":
      case "T":
      case "s":
      case "S":
         $result = $result + ( $mult * 1 );
        break;
    }
  }
  return ( $result );
}

function chmod_value( $chmod )
{
  $group = substr( $chmod, 0, 1 );
  $act = substr( $chmod, 1, 1 );
  $perm = substr( $chmod, 2, 1 );
  
  switch ( $group )
  {
    case "u":
      $mult = 100;
      break;
    case "g":
      $mult = 10;
      break;
    case "a":
      $mult = 1;
      break;
  }

  switch ( $perm )
  {
    case "r":
      $value = 4;
      break;
    case "w":
      $value = 2;
      break;
    case "x":
      $value = 1;
      break;
  }

  $result = $value * $mult;

  if ( $act == "-" )
    $result = $result * -1;

  return ( $result );
}

// function convert_size (int);
//  1024 = 1K
//  1048576 = 1M
//  1073741824 = 1GB
//  1099511627776 = 1TB

  function convert_size ($size) 
  {
    global $size_conv;
    global $size_suffix;
    $count = 0;
    $size_tmp = $size;
    settype ( $size_pre, "double" );

    while ( $size_tmp >= $size_conv )
    {
        $size_tmp = $size_tmp / $size_conv;
       $count = $count + 1;
       if ($count == 4) break;
    }
    
    $size_pre = round (( $size / ( pow( $size_conv, $count))),1);
    $return = $size_pre . '&nbsp;<font class="fixed">' . $size_suffix[$count] . '</font>';
    return ( $return );
  }

  // Name: get_type
  // Parameters: type - D for dir, F for file
  //             name - name of file or dir
  // Purpose: determine what icon to use
  // Returns: icon array key for proper icon
  function get_type( $type, $name )
  {
    global $icon_display;
  
    // if its a directory
    if ( $type == "D" )
    {
      // if its the cdup directory
      if ( ".." == substr( $name, 0, 2) )
        $icon = "dirup";
      // else just display generic dir icon
      else
        $icon = "dir";
    }
    // else its a file
    else
    {
      // rip appart the filename to get the extension
      $find_ext = explode( ".", strtolower($name) );
      
      // if the extension is in the list of extensions with icons, use the icon given
      if ( compat_array_key_exists ( $find_ext[count($find_ext)-1], $icon_display) )
        $icon = $find_ext[count($find_ext)-1];
      // else use the default icon
      else
         $icon = "file";         
    }
    return $icon;
  }
  
  // Name: build_row
  // Parameters: arg[0] - which columns to display
  //						 arg[1] - data grabbed from the ftp_rawlist command
  //             arg[2] - D - for directories
  //                      F - for files
  //						 arg[3] - alternate dir/file name
  // Purpose: takes the output and reorganizes it along with converting some for output
  // Returns: new array
  function build_row()
  {
    $args = func_get_args();
    
    $array[0] = $args[1][8];
    if ( $args[1][2] != "" && $args[0]["owner"] )
      $array[1] = $args[1][2] . ":" . $args[1][3];
    else
      $array[1] = "&nbsp;";

    if ( $args[1][5] != "" && $args[0]["date"] )
      $array[2] = $args[1][5] . " " . $args[1][6];
    else
      $array[2] = "&nbsp;";

    if ( $args[1][7] != "" && $args[0]["date"] )
      $array[3] = $args[1][7];
    else
      $array[3] = "&nbsp;";

    if ( $args[1][4] != "" && $args[0]["size"] )
      $array[4] = convert_size($args[1][4]);
    else
      $array[4] = "&nbsp;";

    if ( $args[1][0] != "" && $args[0]["perm"] )
      $array[5] = build_perm( $args[1][8], $args[1][0] );
    else
      $array[5] = "&nbsp;";
      
    $array[6] = $args[2];
    $array[7] = get_type( $args[2], $args[1][8] );
    
    if ( func_num_args() == "4" )
      $array[8] = $args[3];
    else
      $array[8] = $args[1][8];      
      
    return $array;
  }
  
  // Name: display_row
  // Parameters: $data - formatted data from build_row
  //             $class - the row color
  //             $enabled - enabled - enabled radio button
  //                        disabled - disabled radio button
  // Purpose: creates the html code for displaying the row
  // Returns: actually html code to be displayed
  function display_row( $data, $class, $enabled )
  {
    global $SID;
    global $icon_display;
    global $show_col;
    
    $icon = $icon_display[$data[7]];
    $display = "";
    
    $display .= "<tr class=\"$class\">\n";
    $display .= "<td align=\"right\"><input type=\"radio\" name=\"Listing\" value=\"$data[6]$data[0]\" $enabled></td>\n";
    $display .= "<td>&nbsp;<img src=\"$icon\" alt=\"\">&nbsp;</td>\n";

    if ( $data[6] == "D" )
      $display .= "<td width=\"95%\"><a href=\"crossover.php?SID=$SID&submit=". gettext ("CD") . "&CHDIR=" . urlencode($data[8]) . "\">$data[0]</a></td><td>&nbsp;</td>\n";
    else {
      $display .= "<td width=\"95%\"><a href=\"crossover.php?SID=$SID&submit=". gettext ("DOWNLOAD") ."&FILE=" . urlencode($data[0]) . "\">$data[0]</a></td>\n";
      $display .= "<td>[<a target=\"_blank\" href=\"viewer.php?SID=$SID&Filename=" . urlencode ($data[0]) . "\">";
      $display .= gettext ("View");
      $display .= "</a>]&nbsp;</td>\n";
    }

	   $display .= "<td align=\"right\" NOWRAP> $data[1] </td>\n";
   	$display .= "<td NOWRAP>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";

   	$display .= "<td align=\"right\" NOWRAP> $data[2] </td>\n";
    $display .= "<td NOWRAP>&nbsp;&nbsp;</td>\n";

   	$display .= "<td align=\"right\" NOWRAP> $data[3] </td>\n";
    $display .= "<td NOWRAP>&nbsp;&nbsp;&nbsp;&nbsp;</td>\n";

    $display .= "<td align=\"right\" NOWRAP> $data[4] </td>\n";
    $display .= "<td NOWRAP>&nbsp;&nbsp;</td>\n";

    $display .= "<td NOWRAP><font class=\"fixed\"> $data[5] </font></td>\n";
    $display .= "\n";
    
    return ( $display );
  }

// bool isHidden ( string name )
// Returns TRUE if given file/dir is a hidden, otherwise returns FALSE.
  function isHidden ( $name ) {
    if ( substr( $name, 0, 1 ) == "." ) {
      return TRUE;
    } else {
      return FALSE;
    }
  }

// string dehtmlentities ( string string )
// HTML entities are converted into characters.  This is the opposite of
//  built-in function htmlentities()
  function unhtmlentities ( $string ) {
    $tmp_ = get_html_translation_table (HTML_ENTITIES); // end functions
    $tmp_ = array_flip ($tmp_);
    return strtr ($string, $tmp_);
  }

// string encrypt_string ( string string, string key, string iv, array ciphers )
// Encrypt the given string using the given key, returns a string.
  function encrypt_string ( $string, $key, $iv, $ciphers ) {
    global $REMOTE_ADDR, $SERVER_ADDR, $HTTP_USER_AGENT;
    $td = mcrypt_module_open (get_cipher($ciphers), "", MCRYPT_MODE_CFB, "");
    $key_max = mcrypt_enc_get_key_size ($td);

    if ( strlen ($key) > $key_max ) $key = substr ( $key, 0, $key_max );
    
    $iv = compat_str_pad ("", mcrypt_enc_get_iv_size ($td), $iv);

    mcrypt_generic_init ($td, $key, $iv);
    $encrypted_data = mcrypt_generic ($td, $string);
    mcrypt_generic_deinit ($td);

    return $encrypted_data;
  }

// string decrypt_string ( string string, string key, string iv, array ciphers )
// Decrypt the given string using the given key, returns a string.
  function decrypt_string ( $string, $key, $iv, $ciphers ) {
    global $REMOTE_ADDR, $SERVER_ADDR, $HTTP_USER_AGENT;
    $td = mcrypt_module_open (get_cipher($ciphers), "", MCRYPT_MODE_CFB, "");
    $key_max = mcrypt_enc_get_key_size ($td);

    if ( strlen ($key) > $key_max ) $key = substr ( $key, 0, $key_max );
    
    $iv = compat_str_pad ("", mcrypt_enc_get_iv_size ($td), $iv);

    mcrypt_generic_init ($td, $key, $iv);
    $decrypted_data = mdecrypt_generic ($td, $string);
    mcrypt_generic_deinit ($td);

    return $decrypted_data;
  }
  
// const get_cypher ( array ciphers )
// Compare requested ciphers against supported list.  Returns the acheived cipher.
  function get_cipher ( $ciphers ) {
    $algorithms = mcrypt_list_algorithms ();
    if ( !isset ($ciphers) ) {
      $retval = $algorithms[0];
    } else {
      foreach ( $ciphers as $requested ) {
        $requested = strtolower ($requested);
        if ( in_array ($requested, $algorithms) ) {
          $retval = $requested;
          break;
        }
      }
      if ( !isset ($retval) ) $retval = $algorithms[0];
    }
    return $retval;
  }

// ftp_copy ( resource ftp_stream, string from, string to, string filename )
// Copies the 'filename' currently in 'from' to the new dir 'to'.  If the 
//   filename currently exists "Copy of" is appended to the front.
function ftp_copy ( $fp, $from, $to, $filename ) {
  $tp = tmpfile ();
  $result = @ftp_fget ( $fp, $tp, "$from/$filename", FTP_BINARY );
  if ( !$result ) return FALSE;

  $result = @ftp_size ( $fp, "$to/$filename" );
  // If the desintation file exists, rename the copy.
  while ( $result > -1 ) {
    $filename = sprintf ( "%s %s", gettext ("Copy of"), $filename );
    $result = @ftp_size ( $fp, "$to/$filename" );
  }

  rewind ( $tp );
  $result = @ftp_fput ( $fp, "$to/$filename", $tp, FTP_BINARY );

  if ( !$result ) {
    return FALSE;
  } else {
    return TRUE;
  }
}

function build_style_sheet( $theme )
{
  $style = "body { font-family: " . $theme["thm_pg_txt_fnt"] . "; ";
  $style = $style . "font-size: " . $theme["thm_pg_txt_sz"] . "; ";
  $style = $style . "color: " . $theme["thm_pg_txt_clr"] . "; ";
  if ($theme["thm_pg_bg_img"] != "")
    $style = $style . "background-image: url(" . $theme["thm_pg_bg_img"] . "); ";
  $style = $style . "background-color: " . $theme["thm_pg_bg_clr"] . "}\n";  

  $style = $style . ".manager { background-color: " . $theme["thm_tbl_bg_clr"] . "; ";
  if ($theme["thm_tbl_bg_img"] != "") 
    $style = $style . "background-image: url(" . $theme["thm_tbl_bg_img"] . "); ";
  $style = $style . "color: " . $theme["thm_tbl_txt_clr"] . "}\n";

  $style = $style . ".alt_row { background-color: " . $theme["thm_tbl_bg_alt_clr"] . "}\n"; 
  
  $style = $style . ".border { border: " . $theme["thm_tbl_bdr_clr"] . "; ";
  $style = $style . "border-style: double; border-width: auto }\n";
  
  $style = $style . ".buttonBar { background-color: " . $theme["thm_btn_bg_clr"] . "; ";
  $style = $style . "font-weight: bold; margin-bottom: 0.5em }\n";

  $style = $style . ".fixed { font-family: " . $theme["thm_pg_fxd_fnt"] . "; ";
  $style = $style . "color: " . $theme["thm_tbl_txt_clr"] . "}\n";
  
  $style = $style . "a:active { font-weight: bold; text-decoration: none; color: #000066; ";
  $style = $style . "background-color: " . $theme["thm_pg_lnk_hvr"] . "}\n";
  
  $style = $style . "a:hover { color: " . $theme["thm_pg_lnk_clr"] . "; ";
  $style = $style . "font-weight: bold; text-decoration: none; "; 
  $style = $style . "background-color: " . $theme["thm_pg_lnk_hvr"] . "}\n";
  
  $style = $style . "a:link { text-decoration: none; ";
  $style = $style . "color: " . $theme["thm_pg_lnk_clr"] . "}\n";
  
  $style = $style . "a:visited { text-decoration: none; ";
  $style = $style . "color: " . $theme["thm_pg_lnk_clr"] . "}\n";

  $style = $style . ".buttonBorder { border-style: ridge; border-width: 2px; }\n";
  
  $style = $style . "tr { font-size: " . $theme["thm_pg_txt_sz"] . "}\n";
  
  $style = $style . ".sig {  font-size: 9px; font-style: italic; font-variant: small-caps; ";
  $style = $style . "border-color: ". $theme["thm_tbl_bdr_clr"] . " " . $theme["thm_pg_bg_clr"] . " ";
  $style = $style . $theme["thm_pg_bg_clr"] . " " . $theme["thm_pg_bg_clr"] . "; border-style: solid; border-width: thin; }\n";
  
  return $style;
}

// load_settings - loads a users personal settings from their .wfmrc
// fp - ftp connection pointer
function load_settings( $fp, $default_theme, $def_Display_Hidden, $numeric_val )
{
  $personal = array();
  // grab the users settings file
  $tp = tmpfile ();
  $result = @ftp_fget ($fp, $tp, ".wfmrc", FTP_BINARY);
  // if the user has a personal settings file
  if ( $result ) 
  {
    rewind ($tp);

    // grab the first line
    $buffer = fgets($tp,4096);
    // while not at the end of the file
    while (!feof ($tp)) 
    {
      // if the line isn't a comment
      if ( substr( $buffer, 0, 1 ) != "#" )
      {
        // rip the setting apart
        $command = explode( "=", $buffer );

        // check the setting for errors
        if (trim($command[0]) != "" )
        {
        	// if the value is supposed to be an integer
        	if ( strpos( $numeric_val, trim($command[0]) ) )
          	$command[1] = intval( trim( $command[1] ) );
            
        	// if its the hidden setting check for TRUE/FALSE
        	if ( trim($command[0]) == "show_hidden" )
          {
          	if ( trim($command[1]) == "TRUE" )
							$personal[trim($command[0])] = TRUE;
						else
							$personal[trim($command[0])] = FALSE;
 					}
          else
	          $personal[trim($command[0])] = trim($command[1]);      
				}
      }
      // grab the next line
      $buffer = fgets($tp, 4096);
    }
    // close the temp file
    fclose ($tp);
  }
  else
  {
    // if the theme exists
    if ( is_readable( "themes/" . $default_theme . ".thm" ) )
    {
      // open the file
      $tp = fopen( "themes/" . $default_theme . ".thm", 'r' );

      // grab the theme
      $personal = load_theme( $tp );

      $personal["theme"] = $default_theme;
      $personal["start_dir"] = "";
      $personal["show_hidden"] = $def_Display_Hidden;
      $personal["language"] = $default_lang;
      
      // close the file
      fclose ($tp);  

      save_settings( $fp, $personal, $personal );
      
    }
  }
  // return the updated theme
  return $personal;
}

// find_theme - find a users personal theme specified in their .wfmrc
// fp - ftp connection pointer
// theme - pointer to the current theme settings stored in the session
function find_theme( $fp, $personal, $default_theme )
{
  // if a theme is specified
  if ( compat_array_key_exists ("theme", $personal ) )
  {
    // if the user is using their personal theme
    if ( $personal["theme"] == "personal" )
    {
      // point to the beginning of the array
      reset($personal);
      
      // grab the first value in the array
      $temp = each( $personal );

      while ( $temp )
      {
        // if the key is a theme variable
        if ( substr( $temp["key"], 0, 4) == "thm_" )
          $theme[$temp["key"]] = $personal[$temp["key"]];
        $temp = each( $personal );
      }
    }
    // else using a predefined theme
    else
    {
      // if the theme exists
      if ( @is_readable( "themes/" . $personal["theme"] . ".thm" ) )
      {
        // open the file
        $tp = fopen( "themes/" . $personal["theme"] . ".thm", 'r' );

        // grab the theme
        $theme = load_theme( $tp );
        
        // close the file
        fclose ($tp);  
      }
    }
  }
  // no theme was specified so use the default theme
  else
  {
    // if the theme exists
    if ( @is_readable( "themes/" . $default_theme . ".thm" ) )
    {
      // open the file
      $tp = fopen( "themes/" . $default_theme . ".thm", 'r' );

      // grab the theme
      $theme = load_theme( $tp );
        
      // close the file
      fclose ($tp);  
    }  
  }
  // return the updated theme
  return $theme;
}

// load_theme - grabs the theme from the theme file
// fp - file pointer
function load_theme( $fp )
{
  rewind( $fp );
  
  // grab the first line
  $buffer = fgets($fp,4096);

  //while not at the end of the file
  while (!feof ($fp)) 
  {
    // if the line isn't a comment
    if ( substr( $buffer, 0, 1 ) != "#" )
    {
      // rip the setting apart
      $command = explode( "=", $buffer );
      // check the setting for errors
      if (trim($command[0]) != "")
        $theme[trim($command[0])] = trim($command[1]);      
    }

    // grab the next line
    $buffer = fgets($fp, 4096);
  }
  return $theme;
}

// save_theme - saves a users personal theme to their .wfmrc file
// fp - file pointer
// theme - pointer to the current theme settings stored in the session
function save_settings( $fp, $personal, $theme )
{
  // create a temp file
  $tp = tmpfile ();

  // display the file comments at the top of the page
  fputs( $tp, "## This file contains your personal settings for Weeble File Manager     ##\n" );
  fputs( $tp, "## DO NOT edit this file, the file is saved from the preferences page.   ##\n" );
  fputs( $tp, "## This file will not be loaded if your administrator has \$allow_custom ##\n" );
  fputs( $tp, "## turned off.                                                           ##\n" );
  fputs( $tp, "##     The Weeble File Manager Staff                                     ##\n\n\n" );
  
  fputs( $tp, "## Personal Settings ##\n");

  // start at the beginning of the array
  reset($personal);
  
  // grab the first setting
  $setting = each($personal);
  
  // while there are more settings save them 
  while ( $setting )
  {
    if ( substr( $setting["key"], 0, 4) != "thm_" )
    {
     	// if its the hidden setting check for TRUE/FALSE
     	if ( $setting["key"] == "show_hidden" )
      {
       	if ( $setting["value"])
     			fputs( $tp, $setting["key"] . " = TRUE\n" ); 
				else
     			fputs( $tp, $setting["key"] . " = FALSE\n" ); 
			}
			else
	      fputs( $tp, $setting["key"] . " = " . $setting["value"] . "\n" ); 
		}
    $setting = each($personal);
  }

  fputs( $tp, "## Personal Theme ##\n" );
  // if we need to save the new personal theme or the users last personal theme
  if ( $personal["theme"] == "personal" )
    $temp = $theme;
  else
    $temp = $personal;

  reset($temp);
  
  // save the personal settings (new or old) to the .wfmrc file
  $item = each( $temp );
  while( $item )
  {  
    if ( substr( $item["key"], 0, 4) == "thm_" )
      fputs( $tp, $item["key"] . " = " . $item["value"] . "\n" ); 
  
    $item = each( $temp );
  }
  
  // write settings file to users home directory
  rewind( $tp );
  $result = @ftp_fput ($fp, ".wfmrc", $tp, FTP_ASCII);

  // close temp file
  fclose ($tp);
}

// get_theme_list - get the list of themes installed in the themes dir
// returns a string containing the themes available
function get_theme_list()
{
  // create a pointer to the themes dir
  if ( $dir = @opendir( getcwd() . "/themes" ) )
  {
    // while there is another file in the dir
    while ( $file = @readdir( $dir ) )
    {
      // if its a theme
      if ( substr( $file, strlen($file) - 4 ) == ".thm" )
      {
        // add it to the list, or start the list
        if (isset($list))
          $list = $list . ";" . substr($file, 0, strlen($file) - 4 );
        else
          $list = substr($file, 0, strlen($file) - 4 );        
       }
    }
  }
  return $list;
}

// compare_themes - compare the second theme to the preset
function same_themes( $theme, $preset )
{
  $tp = fopen( "themes/" . $preset . ".thm", 'r' );

  $ptheme = load_theme( $tp, $preset );

  // reset the array pointer
  reset( $ptheme);
  
  // grab the first array item
  $item = each( $ptheme );
  // while not at the end of the array
  while ( $item )
  {
    // if the item key is a theme setting
    if ( substr( $item["key"], 0, 4 ) == "thm_" )
    {
      // if they differ return FALSE
      if ( $item["value"] != $theme[strtoupper(trim($item["key"]))] )
        return FALSE;

    }
    // grab the next item in the array
    $item = each( $ptheme );
  }

  // they match, return TRUE
  return TRUE;
}

function load_personal( $personal )
{
  reset( $personal );
  
  $item = each( $personal );
  while ( $item )
  {
    if ( substr( $item["key"], 0, 4 ) == "thm_" )
       $theme[$item["key"]] = $item["value"];     
  
    $item = each( $personal );
  }
  
  return $theme;
}

// log_message: opens the log file and writes the given message to the end of
//			the log file
function log_message( $log, $message )
{
  // open the file
  $tp = fopen( $log["dir"]. "/" . $log["filename"], 'a' );

  fputs( $tp, "[" . date("m/d/y H:i:s") . "] " . $message ); 

  // close the file
  fclose ($tp);
}

// available_lang: list of available languages or the dropdown list on the  
//                preferences page
function available_lang( )
{
  $lang_list = array();

  $dh = opendir( "lang/" );

  while ( ($dir = readdir($dh)) !== false) 
  {
    $dir = trim($dir);

    if ( is_dir( "lang/" . $dir ) && ( $dir != "." ) && ( $dir != ".." ) && ( $dir != "CVS" ) ) 
    {
      array_push( $lang_list, $dir );
    }      
  }
  closedir($dh);

  return $lang_list;
}
?>
