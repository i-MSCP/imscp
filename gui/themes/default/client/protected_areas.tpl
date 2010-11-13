<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_HTACCESS}</title>
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
		function action_delete(url, subject) {
			return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
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
				<h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
				<li><a href="protected_areas.php">{TR_HTACCESS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<h2 class="htaccess"><span>{TR_HTACCESS}</span></h2>

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: protected_areas -->
			<table>
				<thead>
					<tr>
						<th>{TR_HTACCESS}</th>
						<th>{TR_STATUS}</th>
						<th>{TR__ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: dir_item -->
						<tr>
							<td>{AREA_NAME}<br /><u>{AREA_PATH}</u></td>
							<td>{STATUS}</td>
							<td>
								<a href="protected_areas_add.php?id={PID}" class="icon i_edit">{TR_EDIT}</a>
								<a href="protected_areas_delete.php?id={PID}" onclick="return action_delete('protected_areas_delete.php?id={PID}', '{JS_AREA_NAME}')" class="icon i_delete">{TR_DELETE}</a>
							</td>

						</tr>
					<!-- EDP: dir_item -->
				</tbody>
			</table>
			<!-- EDP: protected_areas -->

			<div class="buttons">
				<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas_add.php');return document.MM_returnValue" value="{TR_ADD_AREA}" />
				<input name="Button2" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_MANAGE_USRES}" />
			</div>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
