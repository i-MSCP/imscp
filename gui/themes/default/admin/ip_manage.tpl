<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_IP_MANAGE_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                if (url == "#" || !confirm(sprintf("{TR_MESSAGE_DELETE}", ip)))
		    return false;

		location = url;
            }
            /* ]]> */
        </script>
    </head>

    <body>
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
                <img src="{THEME_COLOR_PATH}/images/imscp_webhosting.png" alt="i-MSCP" />
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
                <li><a href="ip_manage.php">{MANAGE_IPS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>


        <div class="body">
            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: tickets_list -->
            <h2 class="support"><span>{MANAGE_IPS}</span></h2>
            <fieldset>
                <legend>{TR_AVAILABLE_IPS}</legend>
            
		<table>
		    <tr align="center">
			<th>{TR_IP}</th>
			<th>{TR_DOMAIN}</th>
			<th>{TR_ALIAS}</th>
			<th>{TR_NETWORK_CARD}</th>
			<th>{TR_ACTION}</th>
		    </tr>
		    <!-- BDP: ip_row -->
		    <tr>
			<td>{IP}</td>
			<td>{DOMAIN}</td>
			<td>{ALIAS}</td>
			<td>{NETWORK_CARD}</td>
			<td>
			    <!-- BDP: ip_delete_show -->
			    {IP_ACTION}
			    <!-- EDP: ip_delete_show -->
			    <!-- BDP: ip_delete_link -->
			    <a href="#" onclick="action_delete('{IP_ACTION_SCRIPT}', '{IP}')"  title="{IP_ACTION}" class="icon i_delete">{IP_ACTION}</a>
			    <!-- EDP: ip_delete_link -->
			</td>
		    </tr>
		    <!-- EDP: ip_row -->
		</table>
	    </fieldset>

            <form name="add_new_ip_frm" method="post" action="ip_manage.php">
                <fieldset>
                    <legend>{TR_ADD_NEW_IP}</legend>

		    <table>
			<tr>
			    <td><label for="ip">{TR_IP}</label></td>
			    <td>
				<input class="ip-segment" name="ip_number_1" type="text" value="{VALUE_IP1}" maxlength="3" />.
				<input class="ip-segment" name="ip_number_2" type="text" value="{VALUE_IP2}" maxlength="3" />.
				<input class="ip-segment" name="ip_number_3" type="text" value="{VALUE_IP3}" maxlength="3" />.
				<input class="ip-segment" name="ip_number_4" type="text" value="{VALUE_IP4}" maxlength="3" />
			    </td>
			</tr>
			<tr>
			    <td><label for="domain">{TR_DOMAIN}</label></td>
			    <td><input type="text" name="domain" id="domain" value="{VALUE_DOMAIN}" /></td>
			</tr>
			<tr>
			    <td><label for="alias">{TR_ALIAS}</label></td>
			    <td><input type="text" name="alias" id="alias" value="{VALUE_ALIAS}" /></td>
			</tr>
			<tr>
			    <td><label for="ip_card">{TR_NETWORK_CARD}</label></td>
			    <td>
				<select name="ip_card" id="ip_card">
				    <!-- BDP: card_list -->
				    <option>{NETWORK_CARDS}</option>
				    <!-- EDP: card_list -->
				</select>
			    </td>
			</tr>
		    </table>
		</fieldset>

		<div class="buttons">
		    <input name="Submit" type="submit" value="{TR_ADD}" />
		    <input type="hidden" name="uaction" value="add_ip" />
		</div>
            </form>
        </div>

        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
