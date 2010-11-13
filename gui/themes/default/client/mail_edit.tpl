<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_EDIT_EMAIL_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
		<!--[if IE 6]>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">
			DD_belatedPNG.fix('*');
		</script>
		<![endif]-->
		<script type="text/javascript">
		/* <![CDATA[ */
		function begin_js() {
			if (typeof(document.forms[0].elements['mail_forward']) != 'undefined') {
				if (document.forms[0].elements['mail_forward'].checked == false) {
					document.forms[0].elements['forward_list'].disabled = true;
				}
			}
		}

		function changeType(what) {
			if (what == "forward") {
				if (document.forms[0].elements['forward_list'].disabled == true) {
				 	document.forms[0].elements['forward_list'].disabled = false;
				} else {
					document.forms[0].elements['forward_list'].disabled = true;
				}
			}
		}
		/* ]]> */
		</script>
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
				<h1 class="email">{TR_MENU_EMAIL_ACCOUNTS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="mail_accounts.php">{TR_MENU_EMAIL_ACCOUNTS}</a></li>
				<li><a href="mail_accounts.php">{TR_MENU_OVERVIEW}</a></li>
				<li>{TR_EDIT_EMAIL_ACCOUNT}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<div id="fwd_help" class="tooltip">{TR_FWD_HELP}</div>

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="email"><span>{TR_EDIT_EMAIL_ACCOUNT}</span></h2>
			<form name="edit_mail_acc_frm" method="post" action="mail_edit.php?id={MAIL_ID}">
				<fieldset>
					<legend>{EMAIL_ACCOUNT}</legend>
					<!-- BDP: normal_mail -->
						<table>
							<tr>
								<td><label for="pass">{TR_PASSWORD}</label></td>
								<td><input type="password" name="pass" id="pass" value="" /></td>
							</tr>
							<tr>
								<td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label></td>
								<td><input type="password" name="pass_rep" id="pass_rep" value="" /></td>
							</tr>
							<tr>
								<td colspan="2"><input type="checkbox" name="mail_forward" id="mail_forward" value="1" {FORWARD_MAIL_CHECKED} onclick="changeType('forward');" /><label for="mail_forward">{TR_FORWARD_MAIL}</label></td>
							</tr>
							<tr>
								<td>
									<label for="forward_list">{TR_FORWARD_TO}</label>
									<span class="icon i_help" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" >Help</span>
								</td>
								<td><textarea name="forward_list" id="forward_list" cols="35" rows="5">{FORWARD_LIST}</textarea></td>
							</tr>
						</table>
					<!-- EDP: normal_mail -->
					<!-- BDP: forward_mail -->
						<table>
							<tr>
			   					<td>
			   						<label for="forward_list">{TR_FORWARD_TO}</label>
			   						<span class="icon i_help" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" >Help</span>
		   						</td>
			   					<td><textarea name="forward_list" cols="35" rows="5">{FORWARD_LIST}</textarea></td>
			   				</tr>
						</table>
					<!-- EDP: forward_mail -->
				</fieldset>

				<div class="buttons">
					<input name="Button" type="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0],'{ACTION}');" />
				</div>
				<input type="hidden" name="id" value="{MAIL_ID}" />
				<input type="hidden" name="mail_type" value="{MAIL_TYPE}" />
				<input type="hidden" name="mail_account" value="{EMAIL_ACCOUNT}" />
				<input type="hidden" name="uaction" value="{ACTION}" />
			</form>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
