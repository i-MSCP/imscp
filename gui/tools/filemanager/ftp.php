<?php
/*
  Weeble File Manager (c) Christopher Michaels & Jonathan Manna
  This software is released under the BSD License.  For a copy of
  the complete licensing agreement see the LICENSE file.
*/

  require_once ("settings.php");
  require_once ("tools/compat.php");
  require_once ("functions-ftp.php");
  require_once ("header.php");
  require_once ("gettext.php");

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>VHCS File Manager</title>
<link href="/vhcs2/tools/filemanager/themes/vhcs.css" rel="stylesheet" type="text/css">

</head>

<body>
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="/vhcs2/themes/modern_blue/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="/vhcs2/themes/modern_blue/images/top/logo_background.jpg"><img src="../../themes/user_logos/isp_logo.gif"></td>
          <td background="/vhcs2/themes/modern_blue/images/top/left_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="/vhcs2/themes/modern_blue/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="/vhcs2/themes/modern_blue/images/top/right_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="/vhcs2/themes/modern_blue/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr background="images/content_background_mainpage.gif">
    <td valign="top">

	  <form name="form_listing" method="post" action="crossover.php" enctype="multipart/form-data">
      <input type=hidden name="SID" value="<?php echo $SID ?>">
      <table border=0 cellspacing=0 cellpadding=2 width="99%">
       <tr>
        <td class="border">
		  
		  <table width="100%"  border="0" cellspacing="2" cellpadding="1">
            <tr>
              <td align="right"><span class="content">
<?php
  printf ( "<A HREF=\"crossover.php?SID=%s&submit=%s&lang=%s\">", $SID, gettext ("LOGOUT"), $iso_code );
  printf ( "<b>%s</b>", gettext ("Logout") );
?>
              </a>
              <?php 
                if ($allow_custom) {
                  echo "<a href=\"preferences.php?SID=$SID&submit=view\">";
                  printf ( "%s", gettext ("Preferences") );
                  echo "</a> | | ";
                }
              ?>
              </span></td>
            </tr>
          </table>
		  <table cellspacing=2 cellpadding=1 border=0 width="100%">
             <tr>
              <td class="content"><?php echo $sess_Data["user"]. " @ " . $sess_Data["Server Name"] ?></td>
             </tr>
             <tr>
              <td  class="content2"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
                <tr>
                  <td width="1%" nowrap>
                  	<?php printf ( "%s:", gettext ("Current Directory") ); ?>
                  	<input type="text" name="CHDIR" size="60" value="<?php echo ftp_pwd($fp) ?>">
					<input type="submit" name="submit" value="<?php printf ( "%s", gettext ("CD") ); ?>"></td>
                </tr>
              </table>
                </td>
             </tr>
          </table>
        </td>
       </tr>
       <tr>
        <td class="border">
           <table width="100%" border=0 cellspacing=0 cellpadding=1 class="manager">
          <?php          	
            // check for warnings to be displayed @ top of directory listing
            if ( $sess_Data["warn"] != "" )
            {
              echo "<TR><TH COLSPAN=13>";
              echo "<CENTER><B><FONT color=". $warn_color[$sess_Data["level"]] . ">";
              echo $sess_Data["warn"];
              echo "</FONT></B></CENTER>";
              echo "<P>";
              echo "</TH></TR>";

              $sess_Data["warn"] = "";
            }
  
            $alt_row = 0;              // used for alternating line colors
						  $dir_count = 0;						 // used to count # of dirs displayed
            $file_count = 0;					 // used to count # of files displayed
            $dir_list = array();       // list of directories found
            $file_list = array();      // list of files found

            // Starts the directory listing
            // Older php4 releases require seconds parm to be non-null [4/30/02 cjm2]
            $files = ftp_rawlist ($fp, ".");

            // if you can enter the directory but not read the directory listing clear the error
            if ( substr_count($files[0], "Permission denied")) 
            {
              $files = FALSE;
              $sess_Data["warn"] = sprintf ( "%s: %s.", gettext ("Permission denied"), gettext("Directory listing unavailable") );
              $sess_Data["level"] = "major";
            }

            // check for warnings to be displayed @ top of directory listing
            if ( $sess_Data["warn"] != "" )
            {
              echo "<TR><TH COLSPAN=13>";
              echo "<CENTER><B><FONT color=". $warn_color[$sess_Data["level"]] . ">";
              echo $sess_Data["warn"];
              echo "</FONT></B></CENTER>";
              echo "<P>";
              echo "</TH></TR>";

              $sess_Data["warn"] = "";
            }

             // display link to the home directory
            $built_data = build_row( $show_col, array( '','','','','','','','', sprintf ("~ (%s)", gettext ("Home Directory") ) ), "D", $home_Dir );
            $home = display_row( $built_data, $alt_class[($alt_row % 2)], "disabled" );
            echo "$home";
            $alt_row = $alt_row + 1;
  
             // display the link to the parent directory if there is one
            if ( ftp_pwd( $fp ) != "/" )
            {  
              ftp_cdup( $fp );
              $NEWDIR = urlencode(ftp_pwd( $fp ));
              // display the .. directory
              $built_data = build_row( $show_col, array( '','','','','','','','', sprintf (".. (%s)", gettext ("Up One Directory") ) ), "D" , "..");
              $cdup = display_row( $built_data, $alt_class[($alt_row % 2)], "disabled" );
              echo "$cdup";
              $alt_row = $alt_row + 1;
            }
  
            if ( $files != FALSE )
            {
            // this is testing for whether the ftp_rawlist starts with the dir total
            // or a directory/file, $index is the index for the files[] array
            if ( count(explode(" ",$files[0])) == 2 )
              $index = 1;
            else
              $index = 0;
            
            // for each file, link, or directory listed in the current directory
            // seperate the raw data into readable variables
            for ($indexNew = 0; $index < count($files); $index++, $indexNew++)
              $data[$indexNew] = remove_ws( $files[$index] );

            // sort through directory listing 
            for ( $index = 0; $index < count( $data ); $index++ )
            {
               // put directories into a list
              if ( isDir( $data[$index][0] ) )
                $dir_list[count($dir_list)] = $data[$index];

              // if it a link
              else if ( isLink( $data[$index][0] ) )
              {
                // get the links name, and what it points at
                 list($name,$addr)= split (" -> ", $data[$index][8] );
                
                // overwrite the link name so that it just has the name to be displayed
                $data[$index][8] = $name;

                // store the link pointer as the whole path
                 if (substr($addr, 0, 1) != '/') $addr = $sess_Data["dir"]."/".$addr;
           
                // attempt to change to the link, if it fails it is a file
                // this needs work, what if you don't have permission
                 $RESULT = @ftp_chdir( $fp, $addr );
                 if ( $RESULT )
                {
                  $dir_list[count($dir_list)] = $data[$index];
                }
                // else it is a file
                else
                { 
                  $file_list[count($file_list)] = $data[$index];
                  //debug
                  foreach ($file_list as $value) 
                  {
                    error_log( "Value: $value \n");
                  }                    
                }
              }
              // else it is a file
              else
                $file_list[count($file_list)] = $data[$index];
            }

             // display the directories first
            for ( $c = 0; $c < count( $dir_list ); $c++ ) 
            {
              $built_data = build_row( $show_col, $dir_list[$c], "D" ); 
              // if displaying dir for the move operation, disable radio buttons
              if ( isset( $sess_Data["move_path"]) || isset( $sess_Data["copy_path"]) )
                $row = display_row( $built_data, $alt_class[($alt_row % 2)], "disabled" );
              // else enable radio buttons
              else
                $row = display_row( $built_data, $alt_class[($alt_row % 2)], "" ); 

              // if the user wants to show hiddens, display all directories
              if ( $personal["show_hidden"] ) 
              {
              	echo $row;
                $alt_row++;
                $dir_count++;
              }
              // else if the dir is not hidden, display it
              else if ( !isHidden ($dir_list[$c][8]) ) 
              {
                echo $row;
                $alt_row++;
                $dir_count++;
              }
            }

            // if we aren't just displaying the directories for the move operation
            if ( !isset( $sess_Data["move_path"] ) && !isset( $sess_Data["copy_path"]))
            {
               // display the files next
              for ($d = 0; $d < count( $file_list ); $d++ ) 
              {
                $built_data = build_row( $show_col, $file_list[$d], "F" ); 
                $row = display_row( $built_data, $alt_class[($alt_row % 2)], "" ); 

                // if the user wants to show hiddens, display all files
                if ( $personal["show_hidden"] ) 
                {
                	echo $row;
                  $alt_row++;
                  $file_count++;
                }
                // else if the file is not hidden, display it
                else if ( !isHidden ($file_list[$d][8]) ) 
                {
                	echo $row;
                  $alt_row++;
                  $file_count++;
                }
              }
            }
            }
          ?> 
          	<tr>
            	<?php
              	echo "<td colspan = 13 align=right class=\"". $alt_class[($alt_row % 2)]. "\">";
									printf ( "<B>%s:</B> ", gettext ("Directories") );
                echo $dir_count . " (" . count($dir_list) . ") "; 
									printf ( "<B>%s:</B> ", gettext ("Files") );
                echo $file_count . " (" . count($file_list) . ") ";
								  echo "</td>";
              ?>
            </tr>
          </table>
        </td>
      </tr>

      <?php
        if ( !isset( $sess_Data["move_path"] ) && !isset( $sess_Data["copy_path"]) )
        {
      ?>
      <tr>
        <td  class="content2">
          <?php printf ( "%s:", gettext ("Create") ); ?> 
          <?php printf ( "%s:", gettext ("Create") ); ?> 
          <input type="radio" name="CREATE" value="File" checked><?php printf ( "%s", gettext ("File") ); ?>
          <input type="radio" name="CREATE" value="Dir"><?php printf ( "%s", gettext ("Directory") ); ?>
          <input type="text" name="CREATE_NAME" value="">
          <input type="submit" name="submit" value=<?php printf ( '"%s"', gettext ("Create") ); ?>> | | 
          <input type="submit" name="submit" value=<?php printf ( '"%s"', gettext ("Edit") ); ?>> | |
          <input type="submit" name="submit" value=<?php printf ( '"%s"', gettext ("Delete") ); ?>> | | 
          <input type="submit" name="submit" value=<?php printf ( '"%s"', gettext ("Copy") ); ?>> | | 
          <input type="submit" name="submit" value=<?php printf ( '"%s"', gettext ("Move") ); ?>>
        </td>
      </tr>

      <tr>
        <td  class="content2">
          <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Rename") ); ?>">
          <input type="text" name="REN_DEST" value=""> | | 
          <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Upload") ); ?>"> 
          <input type="file" name="UPLOAD_FILE_0" size="20"> | | 
          <!-- <input type="hidden" name="MAX_FILE_SIZE" value="1048576"> -->
          <?php 
            if ($personal["show_hidden"]) 
              printf ( "<input type=\"submit\" name=\"submit\" value=\"%s\">", gettext ("Hide") );
            else
              printf ( "<input type=\"submit\" name=\"submit\" value=\"%s\">", gettext ("Show") );
          ?>
         </td>
      </tr>

        <?php
          }
          else
          {
        ?>
      <tr>
        <td  class="content2" align="center">
          <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Commit") ); ?>"> | | 
          <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Cancel") ); ?>"> | | 
          <?php 
            if ($personal["show_hidden"]) 
              printf ( "<input type=\"submit\" name=\"submit\" value=\"%s\">", gettext ("Hide") );
            else
              printf ( "<input type=\"submit\" name=\"submit\" value=\"%s\">", gettext ("Show") );
          ?>
        </td>
      </tr>
        <?php
          }
        ?>
       </table>
    </form>
	
	
</td>
  </tr>
  <tr>
    <td height="71" background="images/background_down.gif"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="/vhcs2/themes/modern_blue/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="/vhcs2/themes/modern_blue/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="/vhcs2/themes/modern_blue/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">VHCS FileManager by <br>Jon Manna & Chris Michaels</td>
          </tr>
        </table>          </td>
          <td background="/vhcs2/themes/modern_blue/images/top/down_left_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="/vhcs2/themes/modern_blue/images/top/middle_background.jpg"><img src="/vhcs2/themes/modern_blue/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="/vhcs2/themes/modern_blue/images/top/down_right_fill.jpg"><img src="/vhcs2/themes/modern_blue/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="/vhcs2/themes/modern_blue/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>