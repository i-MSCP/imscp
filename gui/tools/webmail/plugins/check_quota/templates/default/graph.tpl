<?php

/**
  * graph.tpl
  *
  * Template for a single quota graph for by the Check Quota plugin
  *
  * The following variables are available in this template:
  *
  * boolean $show_hr_before_graph Whether or not a horizontal rule 
  *                               is to be placed before this graph
  * boolean $show_hr_after_graph  Whether or not a horizontal rule 
  *                               is to be placed after this graph
  * string  $header_text          Any text to be placed above the graph
  * boolean $header_spacing       Whether or not there should be spacing
  *                               between the $header_text and the graph
  * string  $graph_alignment      The align attribute value for the 
  *                               graph placement
  * int     $font_size            The font size of the graph text
  * boolean $details_above_graph  Whether filesystem and usage details
  *                               belong before or after the graph
  * string  $protocol             "http://" or "https://" - matches the client's
  *                               current means of access to this script
  * int     $width                The total width allowed for the graph
  * int     $yellow_alert_limit   The percent at which the graph should 
  *                               turn yellow
  * int     $red_alert_limit      The percent at which the graph should 
  *                               turn red
  * string  $info                 Filesystem and textual details of
  *                               the quota (typically should change
  *                               newlines herein into line breaks)
  * string  $gd_uri               URI to the GD image generator script
  *                               to be used as image src attribute
  *                               (is only populated if using GD images)
  * string  $swf_uri              URI to the Flash image generator
  *                               (is only populated if using Flash images)
  * string  $alt                  The textual description of the quota
  * float   $percent              The percent used for this graph
  * int     $percent_rounded      Same as "percent" but rounded
  *                               to an integer
  * array   $colors               An array of the colors to be used
  *                               for the graph
  * array   $flash_colors         An array of the colors to be
  *                               used for the graph ready to be
  *                               passed to the Flash image generator
  * int     $number_columns       The number of columns/graph
  *                               segments
  *
  * @copyright &copy; 1999-2007 The SquirrelMail Project Team
  * @license http://opensource.org/licenses/gpl-license.php GNU Public License
  * @version $Id$
  * @package squirrelmail
  * @subpackage plugins
  */


// retrieve the template vars
//
extract($t);


if ($show_hr_before_graph)
   echo '<hr width="90%" />';

if ($header_spacing) 
{

?>
  <p>
    <div align="<?php echo $graph_alignment; ?>">
      <font size="<?php echo $font_size; ?>">
        <b><?php echo $header_text; ?></b>
      </font>
    </div>
  </p>
<?php

}

?>

  <p align="<?php echo $graph_alignment; ?>">

<?php

if (!$header_spacing) 
{

?>
    <div align="<?php echo $graph_alignment; ?>">
      <font size="<?php echo $font_size; ?>">
        <b><?php echo $header_text; ?></b>
      </font>
    </div>
<?php

}

?>

    <font size="<?php echo $font_size; ?>">

<?php

if ($details_above_graph)
   echo nl2br($info);

if ($swf_uri)
{

?>
      <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="<?php echo $protocol; ?>download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="<?php echo $width; ?>" height="12">
        <param name="movie" value="<?php echo $swf_uri; ?>">
        <param name="quality" value="high">
        <param name="SCALE" value="exactfit">
        <param name="menu" value="false">
        <param name=FlashVars value="p=<?php echo $percent_rounded; ?>&y=<?php echo $yellow_alert_limit; ?>&r=<?php echo $red_alert_limit; ?>&c0=0x<?php echo $flash_colors[0]; ?>&c1=0x<?php echo $flash_colors[1]; ?>&c2=0x<?php echo $flash_colors[2]; ?>&c3=0x<?php echo $flash_colors[3]; ?>">
        <embed src="<?php echo $swf_uri; ?>" FlashVars="p=<?php echo $percent_rounded; ?>&y=<?php echo $yellow_alert_limit; ?>&r=<?php echo $red_alert_limit; ?>&c0=0x<?php echo $flash_colors[0]; ?>&c1=0x<?php echo $flash_colors[1]; ?>&c2=0x<?php echo $flash_colors[2]; ?>&c3=0x<?php echo $flash_colors[3]; ?>" quality="high" menu="false" pluginspage="<?php echo $protocol; ?>www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="<?php echo $width; ?>" height="12" scale="exactfit">
        </embed>
      </object>
<?php

}

else
{

?>
      <table width="<?php echo $width; ?>" border="0" cellpadding="1" cellspacing="<?php if ($gd_uri) echo '0'; else echo '1'; ?>" bgcolor="<?php echo $colors[0]; ?>">
        <tr>
<?php

   if ($gd_uri)
   {

?>
          <td bgcolor="<?php echo $colors[0]; ?>" valign="center">
            <img src="<?php echo $gd_uri; ?>" width="100%" height="10" alt="<?php echo $alt; ?>">
          </td>
<?php

   }
   else
   {

      if ($percent_rounded) 
         echo '<td bgcolor="' . $colors[$number_columns] . '" width="' . $percent_rounded . '%" height="10">';
      if ($percent_rounded < 100)
         echo '</td><td bgcolor="' . $colors[0] . '" height="10">';
                                         
      echo '</td>';  

   }

?>
        </tr>
      </table>
<?php

}

if (!$details_above_graph)
   echo nl2br($info);

?>
    </font>
  </p>

