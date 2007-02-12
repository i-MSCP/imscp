<?php

global $left_size, $cq_show_used_instead_of_percent, $cq_use_gd_graphs,
       $cq_yellow_alert_percent, $cq_red_alert_percent,
       $cq_show_quota_intro_text, $cq_graph_align, $cq_font_size,
       $cq_show_filesystems, $cq_show_info_above_bar,
       $cq_show_above_folders_list, $cq_do_not_use_flash,
       $cq_count_quota, $cq_show_count_instead_of_percent,
       $cq_show_count_quota_intro_text, $cq_fs_alias, $cq_intro_text,
       $cq_count_intro_text, $color;

bindtextdomain('check_quota', SM_PATH . 'plugins/check_quota/locale');
textdomain('check_quota');

if ( $_SERVER["SERVER_PORT"] == 80 )
	$cq_proto = 'http://';
elseif ( $_SERVER["SERVER_PORT"] == 443 )
	$cq_proto = 'https://';

$cq_server = $_SERVER["HTTP_HOST"];

$cq_docroot = substr($_SERVER["SCRIPT_NAME"],0,strpos($_SERVER["SCRIPT_NAME"],"src/"));

$cq_image_path = "$cq_docroot" . "/plugins/check_quota/images/";
$cq_swf_path = "$cq_docroot" . "/plugins/check_quota/swf/";

$cq_width = $left_size * 0.8;
$cq_width = round("$cq_width");

if ( $cq_size_quota )
{
	if ( $cq_show_used_instead_of_percent )
		$cq_alt = sprintf(_("%s%s of %s%s"), $cq_taken, $cq_t_unit, $cq_quota, $cq_q_unit);
	else
		$cq_alt = sprintf(_("%s%% of %s%s"), $cq_percent, $cq_quota, $cq_q_unit);

	if ( $cq_use_gd_graphs )
	{
		$cq_image = "gd_bar.php?width=$cq_width&usage=$cq_taken_KB&threshold=$cq_quota_KB";
	}
	elseif ( $cq_percent < $cq_yellow_alert_percent )
		$cq_image = "green.png";
	elseif ( $cq_percent >= $cq_yellow_alert_percent && $cq_percent < $cq_red_alert_percent )
		$cq_image = "yellow.png";
	elseif ( $cq_percent >= $cq_red_alert_percent )
		$cq_image = "red.png";

	if ( $cq_show_quota_intro_text && empty($cq_intro_text) ) 
		$cq_intro_text = _("Quota Usage:");

	echo '<div align="' . $cq_graph_align . '"><font size="' . $cq_font_size . '"><br>' . "\n";

	if ( $cq_show_filesystems && !empty($cq_fsname) )
	{
		if ( !empty($cq_fs_alias["$cq_fsname"]) && $cq_show_filesystems == 2 )
			echo "  " . $cq_fs_alias["$cq_fsname"] . " - $cq_fsname" . '<br>' . "\n";
		elseif ( !empty($cq_fs_alias["$cq_fsname"]) && $cq_show_filesystems == 1 )
			echo "  " . $cq_fs_alias["$cq_fsname"] . '<br>' . "\n";
		else
			echo "  $cq_fsname" . '<br>' . "\n";
	}
	elseif ( $cq_show_quota_intro_text )
		echo "  $cq_intro_text" . '<br>' . "\n";
	elseif ( !$cq_show_filesystems && !$cq_show_quota_intro_text && $cq_show_info_above_bar )
		echo "  $cq_alt" . '<br>' . "\n";

	if ( $cq_do_not_use_flash )
	{
		echo '  <table width="' . $cq_width . '" border="0" cellpadding="1" cellspacing="0" bordercolor="' . $color[10] . '">' . "\n"
		   . '    <tr>' . "\n"
		   . '      <td bgcolor="#FFFFFF">'
		   . '<img src="' . $cq_proto . $cq_server . $cq_image_path . $cq_image
		   . '" width="';
		if ( $cq_use_gd_graphs )
			echo "100";
		elseif ( $cq_percent <= 100 )
			echo round("$cq_percent");
		else
			echo "100";
		echo '%" height="10" alt="' . $cq_alt . '">'
		   . '</td>' . "\n"
		   . '    </tr>' . "\n"
		   . '  </table>' . "\n";
	}
	else
	{
		echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'
		   . ' codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"'
		   . ' width="' . $cq_width . '" height="12">' . "\n"
		   . '        <param name="movie" value="' . $cq_proto . $cq_server . $cq_swf_path . 'bar.swf">' . "\n"
		   . '        <param name="quality" value="high"><param name="SCALE" value="exactfit">' . "\n"
		   . '        <param name="menu" value="false">' . "\n"
		   . '        <param name=FlashVars value="'
		   .		'cq_percent=' . round("$cq_percent")
		   .		'&cq_yellow=' . $cq_yellow_alert_percent 
		   .		'&cq_red=' . $cq_red_alert_percent . '">' . "\n"
		   . '        <embed src="' . $cq_proto . $cq_server . $cq_swf_path . 'bar.swf"' . "\n"
		   . '          FlashVars="'
		   .		'cq_percent=' . round("$cq_percent")
		   .		'&cq_yellow=' . $cq_yellow_alert_percent 
		   .		'&cq_red=' . $cq_red_alert_percent . '"' . "\n"
		   . '          quality="high" menu="false"' . "\n"
		   . '          pluginspage="http://www.macromedia.com/go/getflashplayer"'
		   .          ' type="application/x-shockwave-flash" width="' . $cq_width . '" height="12" scale="exactfit">' . "\n"
		   . '        </embed></object><br>';
	}
	if ( $cq_percent < 100 && ( $cq_show_filesystems || $cq_show_quota_intro_text || !$cq_show_info_above_bar ) )
		echo "  $cq_alt";
	elseif ( $cq_percent >= 100 && ( $cq_show_filesystems || $cq_show_quota_intro_text || !$cq_show_info_above_bar ) )
		echo _("OVER QUOTA!");
	echo "\n" . '</font></div>';

}

if ( $cq_count_quota )
{
	if ( $cq_show_count_instead_of_percent )
		$cq_countalt = sprintf(_("%s of %s files"), $cq_count, $cq_maxcount);
	else
		$cq_countalt = sprintf(_("%s%% of %s files"), $cq_countpercent, $cq_maxcount);

	if ( $cq_use_gd_graphs )
	{
		$cq_countimage = "gd_bar.php?width=$cq_width&usage=$cq_count&threshold=$cq_maxcount";
	}
	elseif ( $cq_countpercent < $cq_yellow_alert_percent )
		$cq_countimage = "green.png";
	elseif ( $cq_countpercent >= $cq_yellow_alert_percent && $cq_countpercent < $cq_red_alert_percent )
		$cq_countimage = "yellow.png";
	elseif ( $cq_countpercent >= $cq_red_alert_percent )
		$cq_countimage = "red.png";

	if ( $cq_show_count_quota_intro_text && empty($cq_count_intro_text) ) 
		$cq_count_intro_text = _("File Usage:");

	echo '<div align="' . $cq_graph_align . '"><font size="' . $cq_font_size . '"><br>' . "\n";

	if ( $cq_show_filesystems && !empty($cq_fsname) )
	{
		if ( !empty($cq_fs_alias["$cq_fsname"]) && $cq_show_filesystems == 2 )
			echo "  " . $cq_fs_alias["$cq_fsname"] . " - $cq_fsname" . '<br>' . "\n";
		elseif ( !empty($cq_fs_alias["$cq_fsname"]) && $cq_show_filesystems == 1 )
			echo "  " . $cq_fs_alias["$cq_fsname"] . '<br>' . "\n";
		else
			echo "  $cq_fsname" . '<br>' . "\n";
	}
	elseif ( $cq_show_count_quota_intro_text )
		echo "  $cq_count_intro_text" . '<br>' . "\n";
	elseif ( !$cq_show_filesystems && !$cq_show_count_quota_intro_text && $cq_show_info_above_bar )
		echo "  $cq_countalt" . '<br>' . "\n";

	if ( $cq_do_not_use_flash )
	{
		echo '  <table width="' . $cq_width . '" border="0" cellpadding="1" cellspacing="0" bordercolor="' . $color[10] . '">' . "\n"
		   . '    <tr>' . "\n"
		   . '      <td bgcolor="#FFFFFF">'
		   . '<img src="' . $cq_proto . $cq_server . $cq_image_path . $cq_countimage
		   . '" width="';
		if ( $cq_use_gd_graphs )
			echo "100";
		elseif ( $cq_countpercent <= 100 )
			echo round("$cq_countpercent");
		else
			echo "100";
		echo '%" height="10" alt="' . $cq_countalt . '">'
		   . '</td>' . "\n"
		   . '    </tr>' . "\n"
		   . '  </table>' . "\n";
	}
	else
	{
		echo '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"'
		   . ' codebase="' . $cq_proto . 'download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0"'
		   . ' width="' . $cq_width . '" height="12">' . "\n"
		   . '        <param name="movie" value="' . $cq_proto . $cq_server . $cq_swf_path . 'bar.swf">' . "\n"
		   . '        <param name="quality" value="high"><param name="SCALE" value="exactfit">' . "\n"
		   . '        <param name="menu" value="false">' . "\n"
		   . '        <param name=FlashVars value="'
		   .		'cq_percent=' . round("$cq_countpercent")
		   .		'&cq_yellow=' . $cq_yellow_alert_percent 
		   .		'&cq_red=' . $cq_red_alert_percent . '">' . "\n"
		   . '        <embed src="' . $cq_proto . $cq_server . $cq_swf_path . 'bar.swf"' . "\n"
		   . '          FlashVars="'
		   .		'cq_percent=' . round("$cq_countpercent")
		   .		'&cq_yellow=' . $cq_yellow_alert_percent 
		   .		'&cq_red=' . $cq_red_alert_percent . '"' . "\n"
		   . '          quality="high" menu="false"' . "\n"
		   . '          pluginspage="' . $cq_proto . 'www.macromedia.com/go/getflashplayer"'
		   .          ' type="application/x-shockwave-flash" width="' . $cq_width . '" height="12" scale="exactfit">' . "\n"
		   . '        </embed></object><br>';
	}
	if ( $cq_countpercent < 100 && ( $cq_show_filesystems || $cq_show_count_quota_intro_text || !$cq_show_info_above_bar ) )
		echo "  $cq_countalt";
	elseif ( $cq_countpercent >= 100 && ( $cq_show_filesystems || $cq_show_count_quota_intro_text || !$cq_show_info_above_bar ) )
		echo _("OVER QUOTA!");
	echo "\n" . '</font></div>';
}

if ( $cq_show_above_folders_list )
	echo '<p></p>';

bindtextdomain('squirrelmail', SM_PATH . 'locale');
textdomain('squirrelmail');

?>
