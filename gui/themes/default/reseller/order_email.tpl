<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_RESELLER_ORDER_EMAL}</title>
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
				<h1 class="purchasing">{TR_MENU_ORDERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="orders.php">{TR_MENU_ORDERS}</a></li>
				<li><a href="order_email.php">{TR_MENU_ORDER_EMAIL}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<h2 class="email"><span>{TR_MENU_ORDER_EMAIL}</span></h2>

			<!-- BDP: page_message -->
   				<div class="warning">{MESSAGE}</div>
	   		<!-- EDP: page_message -->

			<form name="order_email" method="post" action="order_email.php">
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>
					<table>
						<tr>
							<td>{TR_USER_DOMAIN}</td>
							<td>{DOMAIN}</td>
						</tr>
						<tr>
							<td>{TR_USER_REAL_NAME}</td>
							<td>{NAME}</td>
						</tr>
						<tr>
							<td>{TR_ACTIVATION_LINK}</td>
							<td>{ACTIVATION_LINK}</td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_MESSAGE_TEMPLATE}</legend>
					<table>
						<tr>
							<td><label for="auto_subject">{TR_SUBJECT}</label></td>
							<td><input id="auto_subject" type="text" name="auto_subject" value="{SUBJECT_VALUE}" /></td>
						</tr>
						<tr>
							<td><label for="auto_message">{TR_MESSAGE}</label></td>
							<td><textarea id="auto_message" name="auto_message" cols="80" rows="30">{MESSAGE_VALUE}</textarea></td>
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
					<input name="Submit" type="submit"  value="{TR_APPLY_CHANGES}" />
				</div>

				<input type="hidden" name="uaction" value="order_email" />
			</form>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
