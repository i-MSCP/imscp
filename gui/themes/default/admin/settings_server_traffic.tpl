<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_CHANGE_SERVER_TRAFFIC_SETTINGS_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
    </head>
    <body>
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area icons-left">
                <h1 class="settings">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
		<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
                <li><a href="ip_manage.php">{TR_SERVER_TRAFFIC_SETTINGS}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

	    <h2 class="settings"><span>{TR_SERVER_TRAFFIC_SETTINGS}</span></h2>
	    <form action="settings_server_traffic.php" method="post" name="admin_modify_server_traffic_settings" id="admin_modify_server_traffic_settings">
		<fieldset>
		    <legend>{TR_SET_SERVER_TRAFFIC_SETTINGS}</legend>

		    <table>
			<tr>
			    <td><label for="max_traffic">{TR_MAX_TRAFFIC}</label></td>
			    <td>
				<input name="max_traffic" type="text" id="max_traffic" value="{MAX_TRAFFIC}" />
			    </td>
			</tr>
			<tr>
			    <td><label for="traffic_warning">{TR_WARNING}</label></td>
			    <td><input name="traffic_warning" type="text" id="traffic_warning" value="{TRAFFIC_WARNING}" />
			    </td>
			</tr>
		    </table>
		</fieldset>

		<div class="buttons">
		    <input name="Submit" type="submit" value="{TR_MODIFY}" />
		    <input type="hidden" name="uaction" value="modify" />
		</div>
            </form>

        </div>

        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
