<?php

/**
  * motd_alert.tpl
  *
  * Template for building a quota alert to be shown in the MOTD
  * area for by the Check Quota plugin
  *
  * The following variables are available in this template:
  *
  * boolean $motd_pad      Whether or not MOTD already has something 
  *                        in it and should be padded before adding our output
  * string  $warning_title Warning title text
  * string  $usage_text    Text explaining current used quota level
  * string  $message       Message regarding this alert level
  * array   $color         The standard SM color array
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


if ($motd_pad)
   echo '<br /><br />';

echo '<font color="' . $color[2] . '"><b>' 
   . $warning_title . '</b></font>';

// add emphasis to usage figure 
//
echo preg_replace('/(.*?)([0-9.%]+)(.*)/', '$1<b>$2</b>$3', $usage_text);

echo ' ' . $message;

