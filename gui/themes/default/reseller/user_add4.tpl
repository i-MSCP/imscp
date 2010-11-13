<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
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
		function makeUser() {
			var dname = document.forms[0].elements['ndomain_name'].value;
			dname = dname.toLowerCase();
			document.forms[0].elements['ndomain_mpoint'].value = "/" + dname;
		}
		/* ]]> */
		</script>
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
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="user_add1.php">{TR_ADD_USER}</a></li>
				<li>{TR_ADD_ALIAS}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<div id="dmn_help" class="tooltip">{TR_DMN_HELP}</div>
			<div id="fwd_help" class="tooltip">{TR_FWD_HELP}</div>

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="general"><span>{TR_ADD_USER}</span></h2>
			<!-- BDP: add_form -->
			<form name="add_alias_frm" method="post" action="user_add4.php">
				<!-- BDP: alias_list -->
					<table>
						<thead>
							<tr>
								<th>{TR_DOMAIN_ALIS}</th>
								<th>{TR_STATUS}</th>
							</tr>
						</thead>
						<tbody>
							<!-- BDP: alias_entry -->
								<tr>
									<td>{DOMAIN_ALIS}</td>
									<td>{STATUS}</td>
								</tr>
							<!-- EDP: alias_entry -->
						</tbody>
					</table>
				<!-- EDP: alias_list -->

				<fieldset>
					<legend>{TR_ADD_ALIAS}</legend>
					<table>
						<tr>
							<td>
								<label for="ndomain_name">{TR_DOMAIN_NAME}</label>
								<span class="icon i_help" onmouseover="showTip('dmn_help', event)" onmouseout="hideTip('dmn_help')" >Help</span>
							</td>
							<td><input id="ndomain_name" name="ndomain_name" type="text" value="{DOMAIN}" onblur="makeUser();" /></td>
						</tr>
						<tr>
							<td><label for="ndomain_mpoint">{TR_MOUNT_POINT}</label></td>
							<td><input id="ndomain_mpoint" name="ndomain_mpoint" type="text" value='{MP}' /></td>
						</tr>
						<tr>
							<td>
								<label for="forward">{TR_FORWARD}</label>
								<span class="icon i_help" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" >Help</span>
							</td>
							<td><input name="forward" type="text" id="forward" value="{FORWARD}" /></td>
						</tr>
					</table>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_ADD}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value="{TR_GO_USERS}" />
				</div>
				<input type="hidden" name="uaction" value="add_alias" />
			</form>
			<!-- EDP: add_form -->
		</div>
		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
