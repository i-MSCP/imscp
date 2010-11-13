<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_ERROR_PAGE_TITLE}</title>
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
				<li><a href="error_pages.php">{TR_MENU_ERROR_PAGES}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="errors"><span>{TR_ERROR_PAGES}</span></h2>

			<table>
				<tr>
					<td><span class="icon big i_error401">{TR_ERROR_401}</span></td>
					<td><a href="error_edit.php?eid=401" class="icon i_edit">{TR_EDIT}</a></td>
					<td><a href="{DOMAIN}/errors/401.html" target="_blank" class="icon i_preview">{TR_VIEW}</a></td>
				</tr>
				<tr>
					<td><span class="icon big i_error403">{TR_ERROR_403}</span></td>
					<td><a href="error_edit.php?eid=403" class="icon i_edit">{TR_EDIT}</a></td>
					<td><a href="{DOMAIN}/errors/403.html" target="_blank" class="icon i_preview">{TR_VIEW}</a></td>
				</tr>
				<tr>
					<td><span class="icon big i_error404">{TR_ERROR_404}</span></td>
					<td><a href="error_edit.php?eid=404" class="icon i_edit">{TR_EDIT}</a></td>
					<td><a href="{DOMAIN}/errors/404.html" target="_blank" class="icon i_preview">{TR_VIEW}</a></td>
				</tr>
				<tr>
					<td><span class="icon big i_error500">{TR_ERROR_500}</span></td>
					<td><a href="error_edit.php?eid=500" class="icon i_edit">{TR_EDIT}</a></td>
					<td><a href="{DOMAIN}/errors/500.html" target="_blank" class="icon i_preview">{TR_VIEW}</a></td>
				</tr>
				<tr>
					<td><span class="icon big i_error503">{TR_ERROR_503}</span></td>
					<td><a href="error_edit.php?eid=503" class="icon i_edit">{TR_EDIT}</a></td>
					<td><a href="{DOMAIN}/errors/503.html" target="_blank" class="icon i_preview">{TR_VIEW}</a></td>
				</tr>
			</table>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
