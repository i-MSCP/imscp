<?xml ve<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_MANAGE_EMAIL_SETUP_PAGE_TITLE}</title>
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
                <li><a href="ip_manage.php">{TR_EMAIL_SETUP}</a></li>
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
            <h2 class="email"><span>{TR_EMAIL_SETUP}</span></h2>
	    <form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
		<fieldset>
		    <legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
            
		    <table>
			<tr>
			    <td width="200"><strong>{TR_USER_LOGIN_NAME}</strong></td>
			    <td>{USERNAME}</td>
			</tr>
			<tr>
			    <td><strong>{TR_USER_PASSWORD}</strong></td>
			    <td>{PASSWORD}</td>
			</tr>
			<tr>
			    <td><strong>{TR_USER_REAL_NAME}</strong></td>
			    <td>{NAME}</td>
			</tr>
			<tr>
			    <td><strong>{TR_USERTYPE}</strong></td>
			    <td>{USERTYPE}</td>
			</tr>
			<tr>
			    <td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
			    <td>{BASE_SERVER_VHOST}</td>
			</tr>
			<tr>
			    <td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
			    <td>{BASE_SERVER_VHOST_PREFIX}</td>
			</tr>
		    </table>
		</fieldset>

                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE}</legend>

		    <table>
			<tr>
			    <td><label for="auto_subject"><strong>{TR_SUBJECT}</strong></label></td>
			    <td><input type="text" name="auto_subject" id="auto_subject" value="{SUBJECT_VALUE}" style="width:80%" />
			    </td>
			</tr>
			<tr>
			    <td><label for="auto_message"<strong>{TR_MESSAGE}</strong></label></td>
			    <td>
				<textarea name="auto_message" id="auto_message" style="width:80%" cols="80" rows="20">{MESSAGE_VALUE}</textarea>
			    </td>
			</tr>
			<tr>
			    <td><strong>{TR_SENDER_EMAIL}</strong></td>
			    <td>{SENDER_EMAIL_VALUE}</td>
			</tr>
			<tr>
			    <td><strong>{TR_SENDER_NAME}</strong></td>
			    <td>{SENDER_NAME_VALUE}</td>
			</tr>
		    </table>
		</fieldset>
                
		<div class="buttons">
		    <input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
		    <input type="hidden" name="uaction" value="email_setup" />
		</div>
            </form>
        </div>
        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>
