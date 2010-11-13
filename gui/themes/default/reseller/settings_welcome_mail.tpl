<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_ADMIN_MANAGE_EMAIL_SETUP_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
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
				<img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
				<img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
			</div>
		</div>

		<div class="location">
			<div class="location-area icons-left">
				<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="settings_welcome_mail.php">{TR_MENU_E_MAIL_SETUP}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="email"><span>{TR_EMAIL_SETUP}</span></h2>
			<form name="admin_email_setup" method="post" action="settings_welcome_mail.php">
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
					<table>
						<tr>
							<td>{TR_USER_LOGIN_NAME}</td>
							<td>{USERNAME}</td>
						</tr>
						<tr>
							<td>{TR_USER_PASSWORD}</td>
							<td>{PASSWORD}</td>
						</tr>
						<tr>
							<td>{TR_USER_REAL_NAME}</td>
							<td>{NAME}</td>
						</tr>
						<tr>
							<td>{TR_USERTYPE}</td>
							<td>{USERTYPE}</td>
						</tr>
						<tr>
							<td>{TR_BASE_SERVER_VHOST}</td>
							<td>{BASE_SERVER_VHOST}</td>
						</tr>
						<tr>
							<td>{TR_BASE_SERVER_VHOST_PREFIX}</td>
							<td>{BASE_SERVER_VHOST_PREFIX}</td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE}</legend>
					<table>
						<tr>
							<td><label for="auto_subject">{TR_SUBJECT}</label></td>
							<td><input type="text" id="auto_subject" name="auto_subject" value="{SUBJECT_VALUE}" /></td>
						</tr>
						<tr>
							<td><label for="auto_message">{TR_MESSAGE}</label></td>
							<td><textarea id="auto_message" name="auto_message" cols="80" rows="20">{MESSAGE_VALUE}</textarea></td>
						</tr>
						<tr>
							<td>{TR_SENDER_EMAIL}</td>
							<td>{SENDER_EMAIL_VALUE}</td>
						</tr>
						<tr>
							<td>{TR_SENDER_NAME}</td>
							<td>{SENDER_NAME_VALUE}</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
				</div>
				<input type="hidden" name="uaction" value="email_setup" />
			</form>

		</div>

	</body>
</html>
