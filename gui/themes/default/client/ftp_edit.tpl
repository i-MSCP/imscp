<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_EDIT_FTP_ACC_PAGE_TITLE}</title>
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

	<body onload="begin_js();">
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
				<img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
			</div>
		</div>

		<div class="location">
			<div class="location-area icons-left">
				<h1 class="ftp">{TR_MENU_FTP_ACCOUNTS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="ftp_accounts.php">{TR_MENU_FTP_ACCOUNTS}</a></li>
				<li><a href="ftp_accounts.php">{TR_MENU_OVERVIEW}</a></li>
				<li>{TR_EDIT_FTP_USER}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="ftp"><span>{TR_EDIT_FTP_USER}</span></h2>
			<form name="edit_ftp_acc_frm" method="post" action="ftp_edit.php">
				<table>
					<tr>
						<td><label for="ftp_account">{TR_FTP_ACCOUNT}</label></td>
						<td><input id="ftp_account" type="text" name="username" value="{FTP_ACCOUNT}"  readonly="readonly"/></td>
					</tr>
					<tr>
						<td><label for="pass">{TR_PASSWORD}</label></td>
						<td><input id="pass" type="password" name="pass" value="" /></td>
					</tr>
					<tr>
						<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
						<td><input id="pass_rep" type="password" name="pass_rep" value="" /></td>
					</tr>
					<tr>
						<td><input id="use_other_dir" type="checkbox" name="use_other_dir" {USE_OTHER_DIR_CHECKED} /><label for="use_other_dir">{TR_USE_OTHER_DIR}</label></td>
						<td><input type="text" name="other_dir" value="{OTHER_DIR}" /><a href="#" onclick="showFileTree();" class="icon i_bc_folder">{CHOOSE_DIR}</a></td>
					</tr>
				</table>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_EDIT}" />
				</div>
				<input type="hidden" name="uaction" value="edit_user" />
				<input type="hidden" name="id" value="{ID}" />
			</form>
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
