<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_ALIAS_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
		<!--[if IE 6]>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">
			DD_belatedPNG.fix('*');
		</script>
		<![endif]-->
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			function setForwardReadonly(obj){
				if(obj.value == 1) {
					document.forms[0].elements['forward'].readOnly = false;
					document.forms[0].elements['forward_prefix'].disabled = false;
				} else {
					document.forms[0].elements['forward'].readOnly = true;
					document.forms[0].elements['forward'].value = '';
					document.forms[0].elements['forward_prefix'].disabled = true;
				}
			}
		/* ]]> */
		</script>
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
				<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li><a href="domains_manage.php">{TR_MENU_OVERVIEW}</a></li>
				<li>{TR_EDIT_ALIAS}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">

			<!-- BDP: page_message -->
				<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="domains"><span>{TR_MANAGE_DOMAIN_ALIAS}</span></h2>

			<form name="edit_alias_frm" method="post" action="alias_edit.php?edit_id={ID}">
				<fieldset>
					<legend>{TR_EDIT_ALIAS}</legend>
					<table>
						<tr>
							<td>{TR_DOMAIN_IP}</td>
							<td>{ALIAS_NAME}</td>
						</tr>
						<tr>
							<td>{TR_DOMAIN_IP}</td>
							<td>{DOMAIN_IP}</td>
						</tr>
						<tr>
							<td>{TR_ENABLE_FWD}</td>
							<td>
								<input type="radio" name="status" id="status_enable"{CHECK_EN} value="1" onChange="setForwardReadonly(this);" /><label for="status_enable">{TR_ENABLE}</label><br />
							<input type="radio" name="status" id="status_disable"{CHECK_DIS} value="0" onChange="setForwardReadonly(this);" /><label for="status_disable">{TR_DISABLE}</label>
							</td>
						</tr>
						<tr>
							<td>
								<label for="forward">{TR_FORWARD}</label>
							</td>
							<td>
								<select name="forward_prefix" style="vertical-align:middle"{DISABLE_FORWARD}>
									<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>{TR_PREFIX_HTTP}</option>
									<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>{TR_PREFIX_HTTPS}</option>
									<option value="{TR_PREFIX_FTP}"{FTP_YES}>{TR_PREFIX_FTP}</option>
								</select>
								<input name="forward" type="text" class="textinput" id="forward" value="{FORWARD}"{READONLY_FORWARD} />
							</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit"  value="{TR_MODIFY}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
				<input type="hidden" name="uaction" value="modify" />
			</form>
		</div>

		<div class="footer">
			i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
