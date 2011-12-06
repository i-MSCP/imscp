<!-- INCLUDE "../shared/layout/header.tpl" -->
<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_PAGE_TITLE}</title>
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
<!-- BDP: js_to_all_domain -->
function begin_js() {
	document.forms[0].sub_id.disabled = true;
	document.forms[0].als_id.disabled = true;
	document.forms[0].username.focus();
}

function changeDom(wath) {
	if (wath == "real") {
		document.forms[0].sub_id.disabled = true;
		document.forms[0].als_id.disabled = true;
	} else if (wath == "subdom") {
		document.forms[0].sub_id.disabled = false;
		document.forms[0].als_id.disabled = true;
	} else {
		document.forms[0].sub_id.disabled = true;
		document.forms[0].als_id.disabled = false;
	}
}
<!-- EDP: js_to_all_domain -->

<!-- BDP: js_not_domain -->

function begin_js() {
	document.forms[0].username.focus();
}
<!-- EDP: js_not_domain -->

<!-- BDP: js_to_subdomain -->
function begin_js() {
	document.forms[0].sub_id.disabled = true;
	document.forms[0].username.focus();
}

function changeDom(wath) {
	if (wath == "real") {
		document.forms[0].sub_id.disabled = true;
	} else if (wath == "subdom") {
		document.forms[0].sub_id.disabled = false;
	} else {
		document.forms[0].sub_id.disabled = true;
	}
}
<!-- EDP: js_to_subdomain -->

<!-- BDP: js_to_alias_domain -->
function begin_js() {
	document.forms[0].als_id.disabled = true;
	document.forms[0].username.focus();
}

function changeDom(wath) {
	if (wath == "real") {
		document.forms[0].als_id.disabled = true;
	} else if (wath == "subdom") {
		document.forms[0].als_id.disabled = true;
	} else {
		document.forms[0].als_id.disabled = false;
	}
}
<!-- EDP: js_to_alias_domain -->
		/* ]]> */
		</script>
	</head>

	<body onload="begin_js();">
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="ftp">{TR_MENU_FTP_ACCOUNTS}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="ftp_accounts.php">{TR_MENU_FTP_ACCOUNTS}</a></li>
				<li><a href="#" onclick="return false;">{TR_TITLE_ADD_FTP_USER}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
            <h2 class="ftp"><span>{TR_TITLE_ADD_FTP_USER}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="add_ftp_acc_frm" method="post" action="ftp_add.php">
				<table>
					<tr>
						<th colspan="2">{TR_FTP_USER_DATA}</th>
					</tr>
					<tr>
						<td style="width: 300px;"><label for="username">{TR_USERNAME}</label></td>
						<td><input id="username" type="text" name="username" value="{USERNAME}" /></td>
					</tr>
					<tr>
						<td><input type="radio" name="dmn_type" value="dmn" onfocus="changeDom('real');" {DMN_TYPE_CHECKED} />{TR_TO_MAIN_DOMAIN}</td>
						<td>{FTP_SEPARATOR}{DOMAIN_NAME}</td>
					</tr>
					<!-- BDP: to_alias_domain -->
						<tr>
							<td><input type="radio" name="dmn_type" value="als" onfocus="changeDom('alias');" {ALS_TYPE_CHECKED} />{TR_TO_DOMAIN_ALIAS}</td>
							<td>
								<select name="als_id">
									<!-- BDP: als_list -->
										<option value="{ALS_ID}" {ALS_SELECTED}>{FTP_SEPARATOR}{ALS_NAME}</option>
									<!-- EDP: als_list -->
								</select>
							</td>
						</tr>
					<!-- EDP: to_alias_domain -->
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
					<input name="Submit" type="submit" class="button" value="{TR_ADD}" />
				</div>
				<input type="hidden" name="uaction" value="add_user" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
