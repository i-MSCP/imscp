<?php

function disk_quota_check() {

    include_once(SM_PATH . 'plugins/disk_quota/config.php');
    include_once(SM_PATH . 'functions/i18n.php');
    bindtextdomain('disk_quota', SM_PATH . 'plugins/disk_quota/locale');
    include_once(SM_PATH . 'plugins/disk_quota/config.php');
    include_once(SM_PATH . 'functions/i18n.php');
    bindtextdomain('disk_quota', SM_PATH . 'plugins/disk_quota/locale');
    textdomain('disk_quota');

    global $username;
    global $color;
    $dq_color = $color[11];

    $dq_your_disk_usage = _("Your Disk Usage");
    $dq_details_text = _("Details");
    $dq_click_text = _("Click on graph for details");
    $dq_click_text_multi = _("Click on one of the graphs for details");
    bindtextdomain('squirrelmail', SM_PATH . 'locale');
    textdomain('squirrelmail');

    if ( $dq_check_on_remote == 0 ) {

        include_once ("../plugins/disk_quota/disk_quota.php");

    }
    elseif ( $dq_check_on_remote == 1 ) {

        $dq_quota_binary = str_replace(" ", "_", $dq_quota_binary);
        $dq_your_disk_usage = str_replace(" ", "_", $dq_your_disk_usage);
        $dq_details_text = str_replace(" ", "_", $dq_details_text);
        $dq_click_text = str_replace(" ", "_", $dq_click_text);
        $dq_click_text_multi = str_replace(" ", "_", $dq_click_text_multi);
        $dq_color = str_replace("#", "", $dq_color);

        include_once ("$dq_imap_server/disk_quota.php?username=$username&dq_quota_binary=$dq_quota_binary&dq_show_filesystem=$dq_show_filesystem&dq_sm_server=$dq_sm_server&dq_bar_width=$dq_bar_width&dq_bar_height=$dq_bar_height&dq_show_details=$dq_show_details&dq_your_disk_usage=$dq_your_disk_usage&dq_click_text=$dq_click_text&dq_click_text_multi=$dq_click_text_multi&dq_details_text=$dq_details_text&dq_color=$dq_color&dq_check_on_remote=1");

    }
}
?>
