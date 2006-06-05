<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  // load needed files
  require_once ("settings.php");
  require_once ("tools/compat.php");
  require_once ("functions-ftp.php");
  require_once ("header.php");
  require_once ("gettext.php");

  // check to see if someone is tryingto access the preferences when allow_custom 
  // is turned off by the administrator
  if ( !($allow_custom) )
  {
    $sess_Data["warn"] = sprintf ("%s: %s.", gettext ("Permission denied"), gettext ("Administrator has \$allow_cutom off") );
    $sess_Data["level"] = "major";
    header( "Location: ftp.php?SID=$SID\n\n" );
    exit;
  }

  // determine the action to proceed with 
  switch ( $submit )
  {
    case gettext( "Save" ):
    case gettext( "Preview" ):      
      reset($pref);
      
      // if the user made personal changes to the theme
      if ( $personal["theme"] != gettext( "personal" ) && same_themes( $pref, $personal["theme"] ) == FALSE)
        $personal["theme"] = gettext( "personal" );
        
      // grab the first setting
      $item = each($pref);

      // while there are still settings to be saved
      while ( $item )
      {
      	// check to see if the string should be converted to an int
        if ( strpos( $numeric_val, strtolower(trim($item["key"]))))
        	$item["value"] = intval( $item["value"] );
         
        // if they are theme variables
        if ( substr( strtolower(trim($item["key"])), 0, 4) == "thm_" )
        {
          // if the item value isn't empty or its allowed to be empty save the setting 
          if ( trim($item["value"]) != ""  || strpos( $allow_empty, strtolower(trim($item["key"])) ))
            $theme[strtolower(trim($item["key"]))] = trim($item["value"]);
        }
        else
        {
          // if the item value isn't empty or its allowed to be empty save the setting 
          if ( trim($item["value"]) != ""  || strpos( $allow_empty, strtolower(trim($item["key"])) ))
          {
          	// if saving the radio button state for showing hidden files or not
          	if ( strtolower(trim($item["key"])) == "show_hidden" )
						{
            	if ( trim($item["value"]) == "hide" )
              	$personal[strtolower(trim($item["key"]))] = FALSE;
              else
              	$personal[strtolower(trim($item["key"]))] = TRUE;
            }
            // if the value being saved is the preview size, take the numeric 
            // and add a % if needed
          	else if ( strtolower(trim($item["key"])) == "prev_size" )
						{
							$personal[strtolower(trim($item["key"]))] = trim($item["value"]) . $PREV_TYPE;
            }
          	else
	            $personal[strtolower(trim($item["key"]))] = trim($item["value"]);
					}
        }

        // grab the next setting
        $item = each($pref);
      }

      if ( $submit == gettext( "Preview" ) )
      {
        $location = "Location: preferences.php?SID=$SID";
        $sess_Data["warn"] = sprintf ("%s: %s \"%s\". \"%s\" %s.", gettext("Info"), gettext ("Changes will not be saved until you click"), gettext ("Save"), gettext ("Cancel"), gettext ("will load last saved settings") );
        $sess_Data["level"] = "info";
      }
      else
      {
        // go back to the users home dir to save the file
      	@ftp_chdir( $fp, $home_Dir );
        // save the theme to the users .wfmrc file
        save_settings( $fp, $personal, $theme );
      }
      break;
    case gettext( "Cancel" ):
      // load users theme if allow_custom setting is on
      if ( $allow_custom )
      {
        // go back to the users home dir to save the file
      	@ftp_chdir( $fp, $home_Dir );
        // load the .wfmrc file, then load the appropriate theme
	// overwrites default settings set earlier
        $personal = array_merge( $personal, load_settings( $fp, $default_theme, $def_Display_Hidden, $numeric_val ) );

        // if the user specified to start in a different dir from his home dir
        if ( compat_array_key_exists ( "start_dir", $personal ) && $personal["start_dir"] != "" )
        {
          $location =  "Location: crossover.php?SID=" . session_id() . "&submit=CD&CHDIR=$personal[start_dir]";
        }  
      }
      $theme = find_theme( $fp, $personal, $default_theme );

      $location = "Location: ftp.php?SID=$SID";
      break;
    case gettext( "Load Theme" ):
      // if not loading the personal theme
      if ( $theme_select != gettext( "personal" ) )
      {
        $tp = fopen( "themes/" . $theme_select . ".thm", 'r' );

        // grab the theme
        $theme = load_theme( $tp );
        $personal["theme"] = $theme_select;
      
        // close the file
        fclose ($tp);
      }
      else
      {
        $theme = load_personal( $personal );
        $personal["theme"] = $theme_select;
      }
      
      $sess_Data["warn"] = sprintf ("%s: %s. %s \"%s\".", gettext ("Info"), gettext ("Theme loaded"), gettext ("Changes not saved permanently until you click"), gettext ("Save") );
      $sess_Data["level"] = "info";
      $location = "Location: preferences.php?SID=$SID";
  }

  // Redirect the browser to the appropriate page.
  if ( !isset ($location) ) $location = "Location: ftp.php?SID=$SID";
  header ( $location . "\n\n");
?>
