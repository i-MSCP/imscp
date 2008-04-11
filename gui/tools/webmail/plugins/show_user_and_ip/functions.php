<?php

include_once(SM_PATH . 'functions/i18n.php');
include_once(SM_PATH . 'plugins/show_user_and_ip/config.php');

function sui_last_load()
{
	global $username, $data_dir, $sui_last_ip, $sui_last_host, $sui_last_date,
	       $show_user_on_left_pane, $show_ip_on_left_pane, $show_host_on_left_pane;

	$sui_last_ip            = getPref($data_dir, $username, 'sui_last_ip');
	$sui_last_host          = getPref($data_dir, $username, 'sui_last_host');
	$sui_last_date          = getPref($data_dir, $username, 'sui_last_date');
	$show_user_on_left_pane = getPref($data_dir, $username, 'show_user_on_left_pane');
	$show_ip_on_left_pane   = getPref($data_dir, $username, 'show_ip_on_left_pane');
	$show_host_on_left_pane = getPref($data_dir, $username, 'show_host_on_left_pane');

	if ( strlen($show_user_on_left_pane) == 0 )
		$show_user_on_left_pane = 0;
	if ( strlen($show_ip_on_left_pane) == 0 )
		$show_ip_on_left_pane = 0;
	if ( strlen($show_host_on_left_pane) == 0 )
		$show_host_on_left_pane = 0;
}

function sui_show_ui()
{
	global $username, $show_user_on_left_pane, $show_ip_on_left_pane,
               $show_host_on_left_pane, $sui_font_size, $sui_align;

	if( $show_user_on_left_pane || $show_ip_on_left_pane ||
            ( $show_host_on_left_pane && gethostbyaddr($_SERVER['REMOTE_ADDR']) != $_SERVER['REMOTE_ADDR'] ) )
	{
		bindtextdomain('show_user_and_ip', SM_PATH . 'plugins/show_user_and_ip/locale');
		textdomain('show_user_and_ip');

		echo '<p><div align="' . $sui_align . '"><font size="' . $sui_font_size . '">' . "\n"
		   . '  <hr>' . "\n";
		if ( $show_user_on_left_pane )
		{
			echo '  <strong>' . _("Your username:") . '</strong><br>' . "\n"
	                   . "$username" . "\n";
		}
		if ( $show_user_on_left_pane && $show_ip_on_left_pane )
			echo '  <br><br>' . "\n";
		if ( $show_ip_on_left_pane )
		{
			echo '  <strong>' . _("Your IP address:") . '</strong><br>' . "\n"
	  		   . $_SERVER['REMOTE_ADDR'] . "\n";
		}
		if ( $show_host_on_left_pane )
		{
			$sui_host_left = gethostbyaddr($_SERVER['REMOTE_ADDR']);
			if ( $sui_host_left != $_SERVER['REMOTE_ADDR'] )
			{
				if ( $show_user_on_left_pane || $show_ip_on_left_pane )
					echo '  <br><br>' . "\n";
				echo '  <strong>' . _("Your Hostname:") . '</strong><br>' . "\n"
		  		   . $sui_host_left . "\n";
			}
		}
		echo '<hr>' . "\n"
		   . '</font></div></p>' . "\n";

		bindtextdomain('squirrelmail', SM_PATH . 'locale');
		textdomain('squirrelmail');
	}
}

function sui_show_last()
{
	global $color, $username, $motd, $sui_last_ip, $sui_last_host, $sui_last_date,
	       $sui_show_host_on_motd, $sui_motd_greet_with_welcome;

	if ( strlen($sui_last_date) > 0 )
	{
		$sui_display_format = _("D, F j, Y G:i");
		$sui_display_date = date(_("D, F j, Y G:i"), $sui_last_date);

		if ( !strstr($sui_display_format, "F") === FALSE )
		{    
			$sui_display_date = str_replace("January", _("January"), $sui_display_date);
			$sui_display_date = str_replace("February", _("February"), $sui_display_date);
			$sui_display_date = str_replace("March", _("March"), $sui_display_date);
			$sui_display_date = str_replace("April", _("April"), $sui_display_date);
			$sui_display_date = str_replace("May", _("May"), $sui_display_date);
			$sui_display_date = str_replace("June", _("June"), $sui_display_date);
			$sui_display_date = str_replace("July", _("July"), $sui_display_date);
			$sui_display_date = str_replace("August", _("August"), $sui_display_date);
			$sui_display_date = str_replace("September", _("September"), $sui_display_date);
			$sui_display_date = str_replace("October", _("October"), $sui_display_date);
			$sui_display_date = str_replace("November", _("November"), $sui_display_date);
			$sui_display_date = str_replace("December", _("December"), $sui_display_date);
		}
		elseif ( !strstr($sui_display_format, "M") === FALSE )
		{    
			$sui_display_date = str_replace("Jan", _("Jan"), $sui_display_date);
			$sui_display_date = str_replace("Feb", _("Feb"), $sui_display_date);
			$sui_display_date = str_replace("Mar", _("Mar"), $sui_display_date);
			$sui_display_date = str_replace("Apr", _("Apr"), $sui_display_date);
			$sui_display_date = str_replace("May", _("May"), $sui_display_date);
			$sui_display_date = str_replace("Jun", _("Jun"), $sui_display_date);
			$sui_display_date = str_replace("Jul", _("Jul"), $sui_display_date);
			$sui_display_date = str_replace("Aug", _("Aug"), $sui_display_date);
			$sui_display_date = str_replace("Sep", _("Sep"), $sui_display_date);
			$sui_display_date = str_replace("Oct", _("Oct"), $sui_display_date);
			$sui_display_date = str_replace("Nov", _("Nov"), $sui_display_date);
			$sui_display_date = str_replace("Dec", _("Dec"), $sui_display_date);
		}

		if ( !strstr($sui_display_format, "l") === FALSE )
		{
			$sui_display_date = str_replace("Monday", _("Monday"), $sui_display_date);
			$sui_display_date = str_replace("Tuesday", _("Tuesday"), $sui_display_date);
			$sui_display_date = str_replace("Wednesday", _("Wednesday"), $sui_display_date);
			$sui_display_date = str_replace("Thursday", _("Thursday"), $sui_display_date);
			$sui_display_date = str_replace("Friday", _("Friday"), $sui_display_date);
			$sui_display_date = str_replace("Saturday", _("Saturday"), $sui_display_date);
			$sui_display_date = str_replace("Sunday", _("Sunday"), $sui_display_date);
		}
		elseif ( !strstr($sui_display_format, "D") === FALSE )
		{
			$sui_display_date = str_replace("Mon", _("Mon"), $sui_display_date);
			$sui_display_date = str_replace("Tue", _("Tue"), $sui_display_date);
			$sui_display_date = str_replace("Wed", _("Wed"), $sui_display_date);
			$sui_display_date = str_replace("Thu", _("Thu"), $sui_display_date);
			$sui_display_date = str_replace("Fri", _("Fri"), $sui_display_date);
			$sui_display_date = str_replace("Sat", _("Sat"), $sui_display_date);
			$sui_display_date = str_replace("Sun", _("Sun"), $sui_display_date);
		}

		bindtextdomain('show_user_and_ip', SM_PATH . 'plugins/show_user_and_ip/locale');
		textdomain('show_user_and_ip');

		if ( $sui_last_host != "-1" && $sui_show_host_on_motd )
			$sui_last_host_formatted = " ($sui_last_host)";
		else
			$sui_last_host_formatted = ""; 

		if ( strlen($motd) > 0 )
			$sui_motd_append_before = "<br><br>";
		else
			$sui_motd_append_before = "";

		if ( $sui_motd_greet_with_welcome )
			$sui_motd_greet = _("Welcome,") . " ";
		else
			$sui_motd_greet = "<strong>" . _("INFO:") . "</strong> ";

		$sui_append_motd = sprintf(_("your last connection was from <strong>%s%s</strong> on <strong>%s</strong>."), $sui_last_ip, $sui_last_host_formatted, $sui_display_date);

		$motd .= $sui_motd_append_before . $sui_motd_greet . $sui_append_motd;

		bindtextdomain('squirrelmail', SM_PATH . 'locale');
		textdomain('squirrelmail');
	}
}

function sui_left_opt()
{
	global $show_user_on_left_pane, $show_ip_on_left_pane, $show_host_on_left_pane;

	bindtextdomain('show_user_and_ip', SM_PATH . 'plugins/show_user_and_ip/locale');
	textdomain('show_user_and_ip');

	echo '<tr><td align="right" nowrap valign="top">' . _("Show username under folders list:")
	   . '</td><td>'
	   . '<label><input type="radio" name="check_show_user_on_left_pane" value="1"';

	if ( $show_user_on_left_pane == 1 ) echo ' CHECKED';

	echo '>&nbsp;' . _("Yes") . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<label><input type="radio" name="check_show_user_on_left_pane"';

	if ( $show_user_on_left_pane == 0) echo ' CHECKED';

	echo '>&nbsp;' . _("No") . '</label></td></tr>';

	echo '<tr><td align="right" nowrap valign="top">' . _("Show IP address under folders list:")
	   . '</td><td>'
	   . '<label><input type="radio" name="check_show_ip_on_left_pane" value="1"';

	if ( $show_ip_on_left_pane == 1 ) echo ' CHECKED';

	echo '>&nbsp;' . _("Yes") . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<label><input type="radio" name="check_show_ip_on_left_pane"';

	if ( $show_ip_on_left_pane == 0) echo ' CHECKED';

	echo '>&nbsp;' . _("No") . '</label></td></tr>';

	echo '<tr><td align="right" nowrap valign="top">' . _("Show hostname under folders list:")
	   . '</td><td>'
	   . '<label><input type="radio" name="check_show_host_on_left_pane" value="1"';

	if ( $show_host_on_left_pane == 1 ) echo ' CHECKED';

	echo '>&nbsp;' . _("Yes") . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<label><input type="radio" name="check_show_host_on_left_pane"';

	if ( $show_host_on_left_pane == 0) echo ' CHECKED';

	echo '>&nbsp;' . _("No") . '</label></td></tr>';

	bindtextdomain('squirrelmail', SM_PATH . 'locale');
	textdomain('squirrelmail');
}

function sui_left_opt_save()
{
	global $username, $data_dir;

	if ( $_POST['check_show_user_on_left_pane'] == 0 )
		setPref($data_dir, $username, "show_user_on_left_pane", "0");
	elseif ( $_POST['check_show_user_on_left_pane'] == 1 )
		setPref($data_dir, $username, "show_user_on_left_pane", "1");

	if ( $_POST['check_show_ip_on_left_pane'] == 0 )
		setPref($data_dir, $username, "show_ip_on_left_pane", "0");
	elseif ( $_POST['check_show_ip_on_left_pane'] == 1 )
		setPref($data_dir, $username, "show_ip_on_left_pane", "1");

	if ( $_POST['check_show_host_on_left_pane'] == 0 )
		setPref($data_dir, $username, "show_host_on_left_pane", "0");
	elseif ( $_POST['check_show_host_on_left_pane'] == 1 )
		setPref($data_dir, $username, "show_host_on_left_pane", "1");
}

function sui_last_save()
{
	global $username, $data_dir;
	if ( !isset($_SESSION["sui_last_date_saved"]) || $_SESSION["sui_last_date_saved"] != "1" )
	{
		$sui_last_date = strtotime("now");
		setPref($data_dir, $username, "sui_last_ip", $_SERVER['REMOTE_ADDR']);
		$sui_remote_host = gethostbyaddr($_SERVER['REMOTE_ADDR']);
		if ( $sui_remote_host != $_SERVER['REMOTE_ADDR'] )
			setPref($data_dir, $username, "sui_last_host", $sui_remote_host);
		else 
			setPref($data_dir, $username, "sui_last_host", "-1");
		setPref($data_dir, $username, "sui_last_date", $sui_last_date); 
		$_SESSION["sui_last_date_saved"] = 1;
	}
}

?>
