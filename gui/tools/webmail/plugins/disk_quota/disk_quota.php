<?php

/*
 * Some explanation before we go on.
 * The typical output from quota command is like that:
 *
 *       Disk quotas for user dq_user (uid 501):
 *            Filesystem  blocks   quota   limit   grace   files   quota   limit   grace
 *              /dev/md0    8940       0       0             854       0       0
 *              /dev/hda2      0       0       0               0       0       0
 *              /dev/md1 2504904 3000000 3500000           10125       0       0
 *
 * We will divide blocks by limit and draw a graph. The graph will show the quota of the
 * user as a red line. Also we will send the information to details.php so the user can get
 * more information about her status.
*/

$dq_usage_shown = 0;
$dq_click = 0;

putenv("LANG=C");

if ( phpversion() >= "4.2.0" && $dq_check_on_remote == 1 ) {

    extract($_POST);
    extract($_GET);

}

if ( $dq_check_on_remote == 1 ) {

    $dq_quota_binary = str_replace("_", " ", $dq_quota_binary);
    $dq_your_disk_usage = str_replace("_", " ", $dq_your_disk_usage);
    $dq_click_text = str_replace("_", " ", $dq_click_text);
    $dq_click_text_multi = str_replace("_", " ", $dq_click_text_multi);
    $dq_details_text = str_replace("_", " ", $dq_details_text);
    $dq_color = "#$dq_color";
}

exec("$dq_quota_binary -v $username", $quota_data, $return_data);

if ( sizeof($quota_data) > 2 ) {

    for ( $i=2 ; $i<sizeof($quota_data) ; $i++ ) {

        list($dq_fsname, $dq_blocks, $dq_quota, $dq_limit, $dq_grace) =
                split( "[[:blank:]]+", trim($quota_data[$i]) );
        if ( strlen($dq_fsname) != 0 && strlen($dq_blocks) == 0 && strlen($dq_quota) == 0 ) {
                list($dq_blocks, $dq_quota, $dq_limit, $dq_grace) =
                        split( "[[:blank:]]+", trim($quota_data[$i+1]) );
                $i++;
        }
        if ( $dq_quota != 0 && $dq_quota <= $dq_limit ) {

                $dq_click++;
                if ( $dq_show_filesystem == 0 ) {
                        $dq_fsname = "-";
                }
                else {
                        if ( !empty($dq_fs_alias) ) {
                                $dq_fsname = $dq_fs_alias[$dq_fsname];
                                $dq_fsname = str_replace("+", " ", $dq_fsname);
                        }
                }
                $dq_blocks = str_replace("*", "", $dq_blocks);
                $dq_grace = str_replace("day", ":day", $dq_grace);
                if ( $dq_blocks < $dq_quota ) {
                        $dq_grace = "-";
                }

if ( $dq_usage_text_shown == 0 ) {
        echo '<div align = "center">' .
             '<font color="' . $dq_color . '"><br><strong>' . $dq_your_disk_usage . '</strong><br></font>';
        $dq_usage_text_shown = 1;
}
echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
 codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"
 width="' . $dq_bar_width . '" height="' . $dq_bar_height . '">';

if ( $dq_show_details == 1 ) {
    echo '<param name="movie" value="' . $dq_sm_server . '/plugins/disk_quota/swf/quotaBar.swf">';
}
elseif ( $dq_show_details == 0 ) {
    echo '<param name="movie" value="' . $dq_sm_server . '/plugins/disk_quota/swf/quotaBar_no_det.swf">';
}

echo ' <param name=FlashVars value="' .
"dq_details_text=$dq_details_text&dq_sm_server=$dq_sm_server&dq_fsname=$dq_fsname&dq_blocks=$dq_blocks&dq_quota=$dq_quota&dq_limit=$dq_limit&dq_grace=$dq_grace" .
'"><param name="quality" value="high">';

if ( $dq_show_details == 1 ) {
   echo '<embed src="' . $dq_sm_server . '/plugins/disk_quota/swf/quotaBar.swf"';
}
elseif ( $dq_show_details == 0 ) {
   echo '<embed src="' . $dq_sm_server . '/plugins/disk_quota/swf/quotaBar_no_det.swf"';
}

echo ' FlashVars="' .
"dq_details_text=$dq_details_text&dq_sm_server=$dq_sm_server&dq_fsname=$dq_fsname&dq_blocks=$dq_blocks&dq_quota=$dq_quota&dq_limit=$dq_limit&dq_grace=$dq_grace" .
'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer"
 type="application/x-shockwave-flash"
 width="' . $dq_bar_width . '" height="' . $dq_bar_height . '"></embed>
</object>
<br>';
        }
    }
    if ( $dq_click == 1 && $dq_show_details == 1 ) {
            echo $dq_click_text;
    }
    elseif ( $dq_click > 1 && $dq_show_details == 1 ) {
            echo $dq_click_text_multi;
    }
    echo '</div>';
}

?>
