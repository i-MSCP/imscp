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

  // check to see if someone is trying to access the preferences when allow_custom 
  // is turned off by the administrator
  if ( !($allow_custom) )
  {
    $sess_Data["warn"] = sprintf ( "%s: %s.", gettext ("Permission denied"), gettext ("Administrator has \$allow_cutom off") );
    $sess_Data["level"] = "major";
    header( "Location: ftp.php?SID=$SID\n\n" );
    exit;
  }
?><HTML>
 <HEAD>
  <TITLE>Weeble FM <?php printf ( "%s v%s", gettext ("Preferences"), $weeblefm_Version ) ?></TITLE>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style type="text/css">
      <?php 
        printf ( "%s\n", $style );
      ?>
    </style>
 </HEAD>
 <BODY>
  <table border=0 cellspacing=0 cellpadding=2 width="100%">
   <tr>
    <td>
     <table border=0 cellpadding=10 cellspacing=0 width="99%">
      <tr>
       <td align="left"><img src="<?php print ( $logo ) ?>"></td>
       <td align="right" valign="top">
        <A HREF="crossover.php?SID=<?php print ( $SID ) ?>&submit=LOGOUT"><?php printf ( "<b>%s</b>", gettext ("Logout") ) ?></A><BR>
        <span style="font-size: smaller"><?php printf ( "%s", gettext ("Help") ); ?>
        [<A href="docs/manual.html" target="_blank">html</a><b>/</b><A href="docs/PDF/manual.pdf" target="_blank">pdf</a>]</span>
       </td>
      </tr>
     </table>
    </td>
   </tr>
   <tr>
    <td class="border">
    <table cellspacing=2 cellpadding=1 border=0 width="100%" class="manager">
      <form name="form_listing" method="post" action="prefcrossover.php" enctype="multipart/form-data">
        <input type=hidden name="SID" value="<?php print ( $SID ) ?>">
        <tr>
          <td class="fixed"><?php printf ( "%s @ %s", $sess_Data["user"], $sess_Data["Server Name"] ) ?></td>
          <td align="right"><input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Cancel") ) ?>"></td>
        </tr>
      </form>
    </table>
    </td>
   </tr>
   <tr>
    <td class="border">
    <form name="form_listing" method="post" action="prefcrossover.php" enctype="multipart/form-data">
    <table cellspacing=2 cellpadding=1 border=1 width="100%" class="manager">
      <?php 
        if ( $sess_Data["warn"] != "" )
        {
          print ( "<tr><th colspan=4>" );
          printf ( "<b><font align=\"center\" color=\"%s\">%s</font></b>\n", $warn_color[$sess_Data["level"]], $sess_Data["warn"] );
          print ( "</th></tr>\n" );

          $sess_Data["warn"] = "";
        }

        $list = get_theme_list();
        $list = explode( ";", $list );
        sort ($list, SORT_STRING);
        reset ($list);

        printf ( "<tr><td>%s:</td>", gettext ("Select a preset theme") );
        print ( "<td><SELECT NAME=theme_select>" );
        print ( "<OPTION value=\"personal\"" );
        if ( "personal" == $personal["theme"] )
          print ( " SELECTED" ); 
        printf ( " >%s</OPTION>\n", gettext ("Personal") );
        for ($index = 0; $index < count($list); $index++ )
        {
          printf ( "<OPTION value=\"%s\"", $list[$index] );
          if ( $list[$index] == $personal["theme"] )
            print ( " SELECTED" ); 
          printf ( ">%s</OPTION>\n", $list[$index] );
        }          
        print ( "</SELECT></td>" );
        print ( "<td>&nbsp;</td>" );
        printf ( "<td><input type=\"submit\" name=\"submit\" value=\"%s\"></td>", gettext ("Load Theme") );
        print ( "</tr>" );
        
       ?>
      <tr>
        <td colspan=4><br><h2><?php printf ( "%s", gettext ("Display Settings") ) ?></h2></td>
      </tr>
      <tr>
        <td colspan=4 class="buttonBar"><br><h3><?php printf ( "%s", gettext ("Page") ) ?></h3></td>
      </tr>
      <tr>
        <td width="225" NOWRAP><?php printf ( "%s:", gettext ("Background Color") ) ?></td>
        <td width="200" NOWRAP><input type="text" name="pref[THM_PG_BG_CLR]" value="<?php print ( $theme["thm_pg_bg_clr"] ) ?>"></td>
        <td width="50" NOWRAP bgcolor="<?php print ( $theme["thm_pg_bg_clr"] ) ?>">&nbsp;</td>
        <td width="300"><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_bg_clr"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Background Image") ) ?></td>
        <td><input type="text" name="pref[THM_PG_BG_IMG]" value="<?php print ( $theme["thm_pg_bg_img"] ) ?>"></td>
        <td background="<?php print ( $theme["thm_pg_bg_img"] ) ?>">&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_bg_img"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Text Color") ) ?></td>
        <td><input type="text" name="pref[THM_PG_TXT_CLR]" value=<?php echo "\"$theme[thm_pg_txt_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_pg_txt_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_txt_clr"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Text Font") ) ?></td>
        <td><input type="text" name="pref[THM_PG_TXT_FNT]" value=<?php echo "\"$theme[thm_pg_txt_fnt]\""?>></td>
        <td><font face=<?php echo "\"$theme[thm_pg_txt_fnt]\""?>><?php printf ("%s", gettext("Font") )?></font></td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_txt_fnt"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Text Font Size") ) ?></td>
        <td>
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="xx-small" <?php if ("xx-small" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("xx-small") ) ?> 
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="x-small" <?php if ("x-small" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("x-small") ) ?> 
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="small" <?php if ("small" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("small") ) ?>
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="medium" <?php if ("medium" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("medium") ) ?>
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="large" <?php if ("large" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("large") ) ?>
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="x-large" <?php if ("x-large" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("x-large") ) ?>
          <input type="radio" name="pref[THM_PG_TXT_SZ]" value="xx-large" <?php if ("xx-large" == $theme["thm_pg_txt_sz"]) echo "CHECKED";?>><?php printf ( "%s", gettext ("xx-large") ) ?>
        </td>
        <?php echo "<td style=\"font-size: $theme[thm_pg_txt_sz]\">"?><?php printf ("%s", gettext("Size") )?></td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_txt_sz"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Fixed Font") ) ?></td>
        <td><input type="text" name="pref[THM_PG_FXD_FNT]" value=<?php echo "\"$theme[thm_pg_fxd_fnt]\""?>></td>
        <td class="fixed"><?php printf ("%s", gettext("Font") )?></td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_fxd_fnt"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Link Color") ) ?></td>
        <td><input type="text" name="pref[THM_PG_LNK_CLR]" value=<?php echo "\"$theme[thm_pg_lnk_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_pg_lnk_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_lnk_clr"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Link Hover Color") ) ?></td>
        <td><input type="text" name="pref[THM_PG_LNK_HVR]" value=<?php echo "\"$theme[thm_pg_lnk_hvr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_pg_lnk_hvr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_pg_lnk_hvr"] ) ?></td>
      </tr>
      <tr>
        <td colspan=4 class="buttonBar"><br><h3><?php printf ("%s", gettext ("File Manager") ) ?></h3></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Background Color") ) ?></td>
        <td><input type="text" name="pref[THM_TBL_BG_CLR]" value=<?php echo "\"$theme[thm_tbl_bg_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_tbl_bg_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_tbl_bg_clr"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Alternating Background Color") ) ?></td>
        <td><input type="text" name="pref[THM_TBL_BG_ALT_CLR]" value=<?php echo "\"$theme[thm_tbl_bg_alt_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_tbl_bg_alt_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_tbl_bg_alt_clr"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Background Image") ) ?></td>
        <td><input type="text" name="pref[THM_TBL_BG_IMG]" value=<?php echo "\"$theme[thm_tbl_bg_img]\""?>></td>
        <td background=<?php echo "\"$theme[thm_tbl_bg_img]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_tbl_bg_img"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Text Color") ) ?></td>
        <td><input type="text" name="pref[THM_TBL_TXT_CLR]" value=<?php echo "\"$theme[thm_tbl_txt_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_tbl_txt_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_tbl_txt_clr"] ) ?></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Border Color") ) ?></td>
        <td><input type="text" name="pref[THM_TBL_BDR_CLR]" value=<?php echo "\"$theme[thm_tbl_bdr_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_tbl_bdr_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_tbl_bdr_clr"] ) ?></td>
      </tr>
      <tr>
        <td colspan=4 class="buttonBar"><br><h3><?php printf ( "%s", gettext ("Miscellaneous") ) ?></h3></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Button Background Color") ) ?></td>
        <td><input type="text" name="pref[THM_BTN_BG_CLR]" value=<?php echo "\"$theme[thm_btn_bg_clr]\""?>></td>
        <td bgcolor=<?php echo "\"$theme[thm_btn_bg_clr]\""?>>&nbsp;</td>
        <td><?php printf ( "%s: \"%s\"", gettext ("Current"), $theme["thm_btn_bg_clr"] ) ?></td>
      </tr>

      <tr>
        <td colspan=4><br><h2><?php printf ( "%s", gettext ("Miscellaneous") ) ?></h2></td>
      </tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Language") ) ?></td>
        <td><SELECT NAME="pref[LANGUAGE]">
          <?php
            foreach ( available_lang() as $lang )
            {
              if ( $lang == $personal["language"] ) printf( "<OPTION SELECTED> %s </OPTION>", $lang );
              else printf( "<OPTION> %s </OPTION>", $lang );
            }?>
          </SELECT>
          </td>
        <td>&nbsp;</td>
        <td>
<?php
  printf ( "%s: ", gettext ("Current") );
  if ( isset($personal["language"])) echo "\"$personal[language]\"";
?>
</td></tr>
      <tr>
        <td><?php printf ( "%s:", gettext ("Starting Directory") ) ?></td>
        <td><input type="text" name="pref[START_DIR]" value=<?php if ( isset($personal["start_dir"])) echo "\"$personal[start_dir]\""?>></td>
        <td>&nbsp;</td>
        <td>
<?php
  printf ( "%s: ", gettext ("Current") );
  if ( isset($personal["start_dir"])) echo "\"$personal[start_dir]\"";
?>
</td></tr>      
      <tr>
        <td><?php printf ( "%s:", gettext ("Hidden Files") ) ?></td>
        <td>
        	<input type="radio" name="pref[SHOW_HIDDEN]" value="hide" <?php if ($personal["show_hidden"] == FALSE) echo "CHECKED" ?>> <?php printf ( "%s", gettext ("Hide") ) ?>
        	<input type="radio" name="pref[SHOW_HIDDEN]" value="show" <?php if ($personal["show_hidden"]) echo "CHECKED" ?>> <?php printf ( "%s", gettext ("Show") ) ?>
        </td>
        <td>&nbsp;</td>
        <td>
        	<?php 
            printf ( "%s: ", gettext ("Current") );
        		if ($personal["show_hidden"])
            	printf ( "\"%s\"", gettext ("Show") );
						  else
            	printf ( "\"%s\"", gettext ("Hide") );
          ?>
        </td>
      </tr>      
      
      <tr>
        <td><?php printf ( "%s:", gettext ("Edit Box Size") ) ?></td>
        <td>
        	<input type="text" name="pref[EDIT_COL]" value="<?php echo $personal["edit_col"]?>" size=4><?php printf ( " ( %s ) ", gettext ("Width") ) ?><br>
        	<input type="text" name="pref[EDIT_ROW]" value="<?php echo $personal["edit_row"]?>" size=4><?php printf ( " ( %s ) ", gettext ("Height") ) ?>
        </td>
        <td>&nbsp;</td>
        <td>
        	<p style="float: left"><?php printf ( "%s: ", gettext ("Current") ); ?></p>
        	<?php 
          		echo "<p style=\"float: left\">";
            	printf ( "%s: %s<br>", gettext ("Width"), $personal["edit_col"] );
            	printf ( "%s: %s", gettext ("Height"), $personal["edit_row"] );
              echo "</p>";
          ?>
        </td>
      </tr>      
      <tr>
        <td><?php printf ( "%s ( %s %s %% ):", gettext ("Preview Size"), gettext ("pixels"), gettext ("or") ); ?></td>
        <td>
        	<?php 
						$temp = $personal["prev_size"];
            $type = "pix";
            if ( substr( $temp, strlen($temp) - 1 ) == "%" )
						{
            	$temp = substr( $temp, 0, strlen($temp) - 1 );
							$type = "%";
            }            
        		echo "<P style=\"float: left\"><input type=\"text\" name=\"pref[PREV_SIZE]\" value=\"" . $temp . "\" size=4></P>";
						  echo "<p style=\"float: left\"><input type=\"radio\" name=\"PREV_TYPE\" value=\"\"";
            if ( $type == "pix" ) echo " CHECKED";
            printf ( "> %s<br>", gettext ("Pixels") );
        		echo "<input type=\"radio\" name=\"PREV_TYPE\" value=\"%\"";
            if ( $type == "%" ) echo " CHECKED";
            printf ( "> %s</p>", gettext( "Percent" ) );
          ?>
        </td>
        <td>&nbsp;</td>
        <td>
        	<?php 
            printf ( "%s: ", gettext ("Current") );
            echo "$personal[prev_size]";
          ?>
        </td>
      </tr>      
    </table>
    </td>
   </tr>
   <tr class="buttonBar">
    <td align="center" class="buttonBorder"><input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Save") ) ?>"> | | 
     <input type="submit" name="submit" value="<?php printf ( "%s", gettext ("Preview") ) ?>"> | | 
     <input type="reset" value="<?php printf ( "%s", gettext ("Reset") ) ?>"></td>
   </tr>
   <input type=hidden name="SID" value="<?php echo $SID ?>">
   </form>
  </table>
<?php
  print ( '<p class="sig"><a href="http://weeblefm.sourceforge.net/">Weeble File Manager</a> ' );
  printf ( "%s %s &amp; %s</p>\n", gettext ("by"), "Jon Manna", "Chris Michaels" );
?>
 </BODY>
</HTML>
