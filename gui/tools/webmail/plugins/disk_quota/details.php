<?php

if ( !defined('SM_PATH') ) {
    define('SM_PATH', '../../');
}

include_once (SM_PATH . 'plugins/disk_quota/config.php');

if ( file_exists(SM_PATH . 'include/validate.php') ) {
    require_once(SM_PATH . 'include/validate.php');
}
elseif ( file_exists(SM_PATH . 'src/validate.php') ) {
    require_once('../src/validate.php');
}
else {
    exit;
}

displayPageHeader($color, "None");

include_once(SM_PATH . 'functions/i18n.php');
bindtextdomain('disk_quota', SM_PATH . 'plugins/disk_quota/locale');
textdomain('disk_quota');

$dq_quota_usage = _("Your Disk Usage on The Server");
$dq_percent_text = _("Percent");
$dq_details_text = _("Details");
$dq_fsname_text = _("Filesystem");
if ( $dq_show_filesystem == "1" ) {
    $dq_fsname = $_POST["dq_fsname"];
}
else {
    $dq_fsname = "-";
}
$dq_blocks_text = _("Data Amount You Use on The Server");
$dq_blocks = $_POST[dq_blocks];
$dq_quota_text = _("Your Quota");
$dq_quota = $_POST[dq_quota];
$dq_limit_text = _("Your Limit");
$dq_limit = $_POST[dq_limit];
$dq_grace_text = _("Grace Time");
$dq_grace = $_POST["dq_grace"];
if ( $dq_blocks >= $dq_quota ) {
    list ($dq_grace1, $dq_grace2) = split (":", $dq_grace);
    if ( strlen($dq_grace2) >= 1 ) {
        if ( $dq_grace2 == "day" || $dq_grace2 == "days" ) {
            if ( $dq_grace1 == "1" ) {
                $dq_day = _("Day");
            }
            else {
                $dq_day = _("Days");
            }
            $dq_grace = "$dq_grace1+$dq_day";
            }
            else { 
                if ( $dq_grace1 == 1 ) {
                    $dq_hour = _("Hour");
            }
            else {
                $dq_hour = _("Hours");
            }

            if ( $dq_grace2 == 1 ) {
                $dq_min = _("Minute");
            }
            else {
                $dq_min = _("Minutes");
            }
            $dq_grace = "$dq_grace1+$dq_hour+$dq_grace2+$dq_min";
        }
    }
    else {
        if ( $dq_grace1 == 1 ) {
            $dq_min = _("Minute");
        }
        else {
            $dq_min = _("Minutes");
        }
        if ( $dq_grace1 == 0 ) {
            $dq_grace = _("Time Expired!");
        }
        else {
            $dq_grace = "$dq_grace1+$dq_min";
        }
    }
}
$dq_hover_text = _("* Hover your mouse on the texts and graph to get more information...");

/*
 * Help texts
*/

$dq_bar_info1 = _("This is the bar graph of your status. The green bar shows the amount of disk space you use.");
if ( $dq_quota < $dq_limit ) {
    $dq_bar_info2 = 
    _(" The red line shows your quota and the area after the red line shows your limit, which you cannot exceed.");
}
else {
    $dq_bar_info2 = "";
}
if ( $dq_blocks >= $dq_quota ) {
    $dq_bar_info3 = _(" YOU ARE OVER QUOTA NOW!");
}
else {
    $dq_bar_info3 = _(" At this time, you are not over quota.");
}
$dq_bar_info = "$dq_bar_info1$dq_bar_info2$dq_bar_info3";
$dq_percent_info = _("This shows your percent usage compared to your limit.");
$dq_fs_info1 = _("This is the name of the filesystem on which your data is kept.");
if ( $dq_show_filesystem == 1 ) {
    $dq_fs_info2 = "";
}
else {
    $dq_fs_info2 = _(" Your server did not show the filesystem name. So you see a hyphen for the name.");
}
$dq_fs_info = "$dq_fs_info1$dq_fs_info2";
$dq_blocks_info = _("This is the total amount of data you are using on your server.");
$dq_quota_info = _("This is the maximum amount of data you can keep on your server.");
$dq_limit_info = _("This is the maximum amount of data you can keep on the server when you are over quota in your grace time.");
$dq_grace_info1 = _("This shows the time left in which you must delete some of your data in order to get under your quota.");
if ( $dq_grace == "-" ) {
    $dq_grace_info2 = _(" You are not over quota so you see a hyphen for the grace time.");
    $dq_grace_info = "$dq_grace_info1$dq_grace_info2";
}
else {
    $dq_grace_info2 = _(" After this time,if you are still over quota, you will not be able to get any e-mails.");
    $dq_grace_info = "$dq_grace_info1$dq_grace_info2";
}

$dq_quota_usage = str_replace(" ", "+", $dq_quota_usage);
$dq_percent_text = str_replace(" ", "+", $dq_percent_text);
$dq_details_text = str_replace(" ", "+", $dq_details_text);
$dq_fsname_text = str_replace(" ", "+", $dq_fsname_text);
$dq_blocks_text = str_replace(" ", "+", $dq_blocks_text);
$dq_quota_text = str_replace(" ", "+", $dq_quota_text);
$dq_limit_text = str_replace(" ", "+", $dq_limit_text);
$dq_grace_text = str_replace(" ", "+", $dq_grace_text);
$dq_hover_text = str_replace(" ", "+", $dq_hover_text);
$dq_bar_info = str_replace(" ", "+", $dq_bar_info);
$dq_fs_info = str_replace(" ", "+", $dq_fs_info);
$dq_blocks_info = str_replace(" ", "+", $dq_blocks_info);
$dq_quota_info = str_replace(" ", "+", $dq_quota_info);
$dq_limit_info = str_replace(" ", "+", $dq_limit_info);
$dq_grace_info = str_replace(" ", "+", $dq_grace_info);

echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
 codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"
 width="600" height="400">
 <param name="movie" value="' . $dq_sm_server . '/plugins/disk_quota/swf/details.swf">
 <param name="quality" value="high">
 <param name="FlashVars" value="' .
"dq_quota_usage=$dq_quota_usage&dq_percent_text=$dq_percent_text&dq_blocks_text=$dq_blocks_text&dq_blocks=$dq_blocks&dq_quota_text=$dq_quota_text&dq_quota=$dq_quota&dq_limit_text=$dq_limit_text&dq_limit=$dq_limit&dq_grace_text=$dq_grace_text&dq_grace=$dq_grace&dq_hover_text=$dq_hover_text&dq_details_text=$dq_details_text&dq_fsname_text=$dq_fsname_text&dq_fsname=$dq_fsname&dq_bar_info=$dq_bar_info&dq_percent_info=$dq_percent_info&dq_fs_info=$dq_fs_info&dq_blocks_info=$dq_blocks_info&dq_quota_info=$dq_quota_info&dq_limit_info=$dq_limit_info&dq_grace_info=$dq_grace_info" .
'"><embed src="' . $dq_sm_server . '/plugins/disk_quota/swf/details.swf" width="600" height="400" quality="high"
 pluginspage="http://www.macromedia.com/go/getflashplayer"
 type="application/x-shockwave-flash" flashvars="' .
"dq_quota_usage=$dq_quota_usage&dq_percent_text=$dq_percent_text&dq_blocks_text=$dq_blocks_text&dq_blocks=$dq_blocks&dq_quota_text=$dq_quota_text&dq_quota=$dq_quota&dq_limit_text=$dq_limit_text&dq_limit=$dq_limit&dq_grace_text=$dq_grace_text&dq_grace=$dq_grace&dq_hover_text=$dq_hover_text&dq_details_text=$dq_details_text&dq_fsname_text=$dq_fsname_text&dq_fsname=$dq_fsname&dq_bar_info=$dq_bar_info&dq_percent_info=$dq_percent_info&dq_fs_info=$dq_fs_info&dq_blocks_info=$dq_blocks_info&dq_quota_info=$dq_quota_info&dq_limit_info=$dq_limit_info&dq_grace_info=$dq_grace_info" .
'"></embed></object>';

echo "<br><br><table width=\"600\" border=$dq_table_border_size>";
echo '  <tr bgcolor=' . "\"$color[5]\">";
echo '    <td><div align="center"><strong>' .
          _("Detailed Information") . '</strong></div></td>';
echo '  </tr>';
echo '  <tr bgcolor=' . "\"$color[0]\">";
echo '    <td><div align="center">';
if ( $dq_blocks < $dq_quota ) {
    echo _("There is no problem as you are now using ") . "<strong>$dq_blocks </strong>" .
         _("KB on the server which is under your quota. Be sure to always keep the data you use under your ") .
         "<strong>$dq_quota </strong>" . _("KB quota to ensure that you can always receive e-mails.");
}
else {
    if ( $dq_grace1 == 0 ) {
    echo '<font color=#FF0000><strong>' . _("WARNING:") . ' </strong></font>' .
         _("You are using ") . "<strong>$dq_blocks </strong>" . _("KB on the server which is over your quota. ") .
         _("You CANNOT get any e-mails now and all e-mails sent to you are bouncing back to their senders. ") .
         _("You must delete some e-mails from the server and get your usage under your ") . "<strong>$dq_quota </strong>" .
         _("KB quota in order to be able to get new e-mails.");
    }
    else {
    $dq_grace = str_replace("+", " ", $dq_grace); 
    echo '<font color=#FF0000><strong>' . _("WARNING:") . ' </strong></font>' .
         _("You are using ") . "<strong>$dq_blocks </strong>" . _("KB on the server which is over your quota. ") .
         _("If you don't delete some e-mails from the server in ") . "<strong>$dq_grace </strong>" .
         _("and get your data under your ") . "<strong>$dq_quota </strong>" .
         _("KB quota, you WILL NOT be able to get any more e-mails.");
    } 
}
echo '  </tr>';
echo '</table>';
echo '</center>';

bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');
?>
