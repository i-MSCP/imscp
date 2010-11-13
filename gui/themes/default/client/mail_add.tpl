<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_ADD_MAIL_ACC_PAGE_TITLE}</title>
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
		function changeType() {
			if (document.forms[0].elements['mail_type_normal'].checked == true) {
				document.forms[0].pass.disabled = false;
				document.forms[0].pass_rep.disabled = false;
			} else {
				document.forms[0].pass.disabled = true;
				document.forms[0].pass_rep.disabled = true;
			}
			if (document.forms[0].elements['mail_type_forward'].checked == true) {
				document.forms[0].forward_list.disabled = false;
			} else {
				document.forms[0].forward_list.disabled = true;
			}
		}

		function begin_js() {
			if (document.getElementsByName('als_id').length !== 0) {
				if (document.getElementById('dmn_type2').checked) {
					document.forms[0].als_id.disabled = false;
				} else {
					document.forms[0].als_id.disabled = true;
				}
			}
			if (document.getElementsByName('sub_id').length !== 0) {
				if (document.getElementById('dmn_type3').checked) {
					document.forms[0].sub_id.disabled = false;
				} else {
					document.forms[0].sub_id.disabled = true;
				}
			}
			if (document.getElementsByName('als_sub_id').length !== 0) {
				if (document.getElementById('dmn_type4').checked) {
					document.forms[0].als_sub_id.disabled = false;
				} else {
					document.forms[0].als_sub_id.disabled = true;
				}
			}
//			document.forms[0].pass.disabled = false;
//			document.forms[0].pass_rep.disabled = false;
//			document.forms[0].forward_list.disabled = true;
			changeType();
			document.forms[0].username.focus();
		}

		function changeDom(what) {
			if (document.getElementsByName('als_id').length !== 0) {
				if (what == "alias") {
					document.forms[0].als_id.disabled = false;
				} else {
					document.forms[0].als_id.disabled = true;
				}
			}
			if (document.getElementsByName('sub_id').length !== 0) {
				if (what == "subdom") {
					document.forms[0].sub_id.disabled = false;
				} else  {
					document.forms[0].sub_id.disabled = true;
				}
			}
			if (document.getElementsByName('als_sub_id').length !== 0) {
				if (what == "als_subdom") {
					document.forms[0].als_sub_id.disabled = false;
				} else {
					document.forms[0].als_sub_id.disabled = true;
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
				<li><a href="mail_add.php">{TR_MENU_ADD_MAIL_USER}</a></li>
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

			<h2 class="email"><span>{TR_ADD_MAIL_USER}</span></h2>

			<form name="add_mail_acc_frm" method="post" action="mail_add.php">
				<table>
					<tr>
						<td><label for="username">{TR_USERNAME}</label></td>
						<td><input type="text" name="username" id="username" value="{USERNAME}" /></td>
					</tr>
					<tr>
						<td>
							<input type="radio" name="dmn_type" id="dmn_type1" value="dmn" {MAIL_DMN_CHECKED} onclick="changeDom('real');" />
				  			<label for="dmn_type1">{TR_TO_MAIN_DOMAIN}</label>
				  		</td>
				  		<td>{DOMAIN_NAME}</td>
					</tr>
					<!-- BDP: to_alias_domain -->
						<tr>
							<td>
								<input type="radio" name="dmn_type" id="dmn_type2" value="als" {MAIL_ALS_CHECKED} onclick="changeDom('alias');" />
								<label for="dmn_type2">{TR_TO_DMN_ALIAS}</label>
							</td>
							<td>
								<select name="als_id">
									<!-- BDP: als_list -->
										<option value="{ALS_ID}" {ALS_SELECTED}>@{ALS_NAME}</option>
									<!-- EDP: als_list -->
								</select>
							</td>
						</tr>
					<!-- EDP: to_alias_domain -->
					<!-- BDP: to_subdomain -->
						<tr>
							<td>
								<input type="radio" name="dmn_type" id="dmn_type3" value="sub" {MAIL_SUB_CHECKED} onclick="changeDom('subdom');" />
								<label for="dmn_type3">{TR_TO_SUBDOMAIN}</label>
							</td>
							<td>
								<select name="sub_id">
									<!-- BDP: sub_list -->
										<option value="{SUB_ID}" {SUB_SELECTED}>@{SUB_NAME}</option>
									<!-- EDP: sub_list -->
								</select>
							</td>
						</tr>
					<!-- EDP: to_subdomain -->
					<!-- BDP: to_alias_subdomain -->
						<tr>
							<td>
								<input type="radio" name="dmn_type" id="dmn_type4" value="als_sub" {MAIL_ALS_SUB_CHECKED} onclick="changeDom('als_subdom');" />
								<label for="dmn_type4">{TR_TO_ALS_SUBDOMAIN}</label>
							</td>
							<td>
								<select name="als_sub_id">
									<!-- BDP: als_sub_list -->
										<option value="{ALS_SUB_ID}" {ALS_SUB_SELECTED}>@{ALS_SUB_NAME}</option>
									<!-- EDP: als_sub_list -->
								</select>
							</td>
						</tr>
					<!-- EDP: to_alias_subdomain -->
					<tr>
						<td colspan="2"><input type="checkbox" name="mail_type_normal" value="1" onclick="changeType();" {NORMAL_MAIL_CHECKED} />{TR_NORMAL_MAIL}</td>
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
						<td colspan="2"><input type="checkbox" name="mail_type_forward" value="1" {FORWARD_MAIL_CHECKED} onclick="changeType();" />{TR_FORWARD_MAIL}</td>
					</tr>
					<tr>
						<td>
							<label for="forward_list">{TR_FORWARD_TO}</label>
			   				<span class="icon i_help" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" >Help</span>
						</td>
						<td><textarea id="forward_list" name="forward_list" cols="35" rows="5">{FORWARD_LIST}</textarea></td>
					</tr>
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_ADD}" />
				</div>
				<input type="hidden" name="uaction" value="add_user" />
			</form>
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
