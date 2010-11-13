<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_RESELLER_CIRCULAR_PAGE_TITLE}</title>
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
				<li><a href="circular.php">{TR_MENU_CIRCULAR}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="email"><span>{TR_CIRCULAR}</span></h2>
			<form name="admin_email_setup" method="post" action="circular.php">
				<fieldset>
<!--
					<legend>{TR_ADDITIONAL_DATA}</legend>
 -->
					<table>
						<tr>
							<td><label for="sender_email">{TR_SENDER_EMAIL}</label></td>
							<td><input id="sender_email" type="text" name="sender_email" value="{SENDER_EMAIL}" /></td>
						</tr>
						<tr>
							<td><label for="sender_name">{TR_SENDER_NAME}</label></td>
							<td><input id="sender_name" type="text" name="sender_name" value="{SENDER_NAME}" /></td>
						</tr>
<!--
					</table>
				</feldset>
				<fieldset>
					<legend>{TR_CORE_DATA}</legend>
					<table>
 -->
			 			<tr>
							<td><label for="msg_subject">{TR_MESSAGE_SUBJECT}</label></td>
							<td><input id="msg_subject" type="text" name="msg_subject" value="{MESSAGE_SUBJECT}" /></td>
						</tr>
						<tr>
							<td><label for="msg_text">{TR_MESSAGE_TEXT}</label></td>
							<td><textarea id="msg_text" name="msg_text" cols="80" rows="20">{MESSAGE_TEXT}</textarea></td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_SEND_MESSAGE}" />
				</div>.
				<input type="hidden" name="uaction" value="send_circular" />
			</form>

		</div>
	</body>
</html>
