<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_SUBDOMAIN_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltips.js"></script>
		<!--[if IE 6]>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">
			DD_belatedPNG.fix('*');
		</script>
		<![endif]-->
		<script type="text/javascript">
			/* <![CDATA[ */
			function setForwardReadonly(obj){
				if(obj.value == 1) {
					document.getElementById('editFrm').elements['schemeSpecific'].readOnly = false;
					document.getElementById('editFrm').elements['scheme'].disabled = false;
				} else {
					document.getElementById('editFrm').elements['schemeSpecific'].readOnly = true;
					document.getElementById('editFrm').elements['schemeSpecific'].value = '';
					document.getElementById('editFrm').elements['scheme'].disabled = true;
				}
			}
			/* ]]> */
		</script>
	</head>
	<body>

		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area icons-left">
				<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li>
					<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
				</li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
				</li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li><a href="subdomain_add.php">{TR_EDIT_SUBDOMAIN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="domains"><span>{TR_EDIT_SUBDOMAIN}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form action="subdomain_edit.php?id={SUBDOMAIN_ID}&type={SUBDOMAIN_TYPE}" method="post" id="editFrm">
				<table>
					<tr>
						<td style="width:200px;">{TR_SUBDOMAIN_NAME}</td>
						<td>{SUBDOMAIN_NAME}</td>
					</tr>
					<tr>
						<td>{TR_URL_FORWARDING}</td>
						<td>
							<input type="radio" name="urlForwarding" id="urlForwardingEnabled"{RADIO_ENABLED} value="1" onchange="setForwardReadonly(this);" />
							<label for="urlForwardingEnabled">{TR_ENABLE}</label>
							<input type="radio" name="urlForwarding" id="urlForwardingDisabled"{RADIO_DISABLED} value="0" onchange="setForwardReadonly(this);" />
							<label for="urlForwardingDisabled">{TR_DISABLE}</label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="scheme">{TR_FORWARD}</label>
						</td>
						<td>
							<select name="scheme" id="scheme" style="vertical-align:middle"{SELECT_DISABLED}>
								<!-- BDP: scheme_options -->
								<option value="{SCHEME}"{SELECTED}>{SCHEME}</option>
								<!-- EDP: scheme_options -->
							</select>
							<input name="schemeSpecific" type="text" id="schemeSpecific" value="{SCHEME_SPECIFIC}"{INPUT_READONLY} style="vertical-align:middle;width:300px;" />
						</td>
					</tr>
				</table>
				<input name="subdomainName" type="hidden" value="{SUBDOMAIN_NAME}" />
				<div class="buttons">
					<input name="update" type="submit" value="{TR_UPDATE}" />
					<input name="cancel" type="submit" value="{TR_CANCEL}" />
				</div>
			</form>

			<div class="footer">
				i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
			</div>
		</div>
	</body>
</html>
