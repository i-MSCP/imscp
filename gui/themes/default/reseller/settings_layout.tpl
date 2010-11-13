<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_RESELLER_LAYOUT_DATA_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
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
				<h1 class="general">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="index.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="settings_layout.php">{TR_LAYOUT_SETTINGS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->
			<h2 class="multilanguage"><span>{TR_LAYOUT_SETTINGS}</span></h2>

			<form enctype="multipart/form-data" name="set_layout" method="post" action="settings_layout.php">
				<fieldset>
					<legend>{TR_UPLOAD_LOGO}</legend>
				</fieldset>
				<table>
					<tr>
						<td></td>
						<td><img src="{OWN_LOGO}" alt="reseller logo" /></td>
					</tr>
					<tr>
						<td ><label for="logo_file">{TR_LOGO_FILE}</label></td>
						<td><input type="file" name="logo_file" /></td>
					</tr>
				</table>
				<div class="buttons">
					<input name="upload_logo" type="submit" value="{TR_UPLOAD}" />
					<input name="delete_logo" type="submit"  value="{TR_REMOVE}" />
				</div>
			</form>
		</div>
	</body>
</html>
