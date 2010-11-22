<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_LOSTPW_EMAL_SETUP}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
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
                <img src="{THEME_COLOR_PATH}/images/imscp_webhosting.png" alt="i-MSCP" />
            </div>
        </div>

        <div class="location">
            <div class="location-area icons-left">
                <h1 class="settings">{TR_LOSTPW_EMAIL}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
		<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
                <li><a href="settings_lostpassword.php">{TR_LOSTPW_EMAIL}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="general"><span>{TR_LOSTPW_EMAIL}</span></h2>
            <form action="settings_lostpassword.php" method="post" name="frmlostpassword" id="frmlostpassword">
                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>

		    <table>
			<tr>
			    <th colspan="2">{TR_ACTIVATION_EMAIL}</th>
			    <th colspan="2">{TR_PASSWORD_EMAIL}</th>
			</tr>
			<tr>
			    <td><strong>{TR_USER_LOGIN_NAME}</strong></td>
			    <td>{USERNAME}</td>
			    <td><strong>{TR_USER_LOGIN_NAME}</strong></td>
			    <td>{USERNAME}</td>
			</tr>
			<tr>
			    <td><strong>{TR_LOSTPW_LINK}</strong></td>
			    <td>{LINK}</td>
			    <td><strong>{TR_USER_PASSWORD}</strong></td>
			    <td>{PASSWORD}</td>
			</tr>
			<tr>
			    <td><strong>{TR_USER_REAL_NAME}</strong></td>
			    <td>{NAME}</td>
			    <td><strong>{TR_USER_REAL_NAME}</strong></td>
			    <td>{NAME}</td>
			</tr>
			<tr>
			    <td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
			    <td>{BASE_SERVER_VHOST}</td>
			    <td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
			    <td>{BASE_SERVER_VHOST}</td>
			</tr>
			<tr>
			    <td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
			    <td>{BASE_SERVER_VHOST_PREFIX}</td>
			    <td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
			    <td>{BASE_SERVER_VHOST_PREFIX}</td>
			</tr>
		    </table>
		</fieldset>
                
                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE}</legend>
                
		    <table>
			<tr>
			    <td><strong>{TR_SUBJECT}</strong></td>
			    <td><input name="subject1" type="text" id="subject1" style="width:90%" value="{SUBJECT_VALUE1}" /></td>
			    <td><input type="text" name="subject2" value="{SUBJECT_VALUE2}" style="width:90%" /></td>
			</tr>
			<tr>
			    <td><strong>{TR_MESSAGE}</strong></td>
			    <td style="width:50%"><textarea name="message1" cols="80" rows="20" id="message1" style="width:90%">{MESSAGE_VALUE1}</textarea></td>
			    <td style="width:50%"><textarea name="message2" cols="80" rows="20" id="message2" style="width:90%">{MESSAGE_VALUE2}</textarea></td>
			</tr>
			<tr>
			    <td><strong>{TR_SENDER_EMAIL}</strong></td>
			    <td>{SENDER_EMAIL_VALUE}</td>
			    <td><input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}" /></td>
			</tr>
			<tr>
			    <td><strong>{TR_SENDER_NAME}</strong></td>
			    <td>{SENDER_NAME_VALUE}</td>
			    <td><input type="hidden" name="sender_name" value="{SENDER_NAME_VALUE}" /></td>
			</tr>
		    </table>
		</fieldset>

		<div class="buttons">
		    <input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
		    <input type="hidden" name="uaction" value="apply" />
		</div>
            </form>
        </div>
        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
