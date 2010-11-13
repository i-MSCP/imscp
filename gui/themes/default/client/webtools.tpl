<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_WEBTOOLS_PAGE_TITLE}</title>
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
				<li><a href="webtools.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="webtools"><span>{TR_MENU_WEBTOOLS}</span></h2>

		   	<a href="protected_areas.php">{TR_HTACCESS}</a>
			<p>{TR_HTACCESS_TEXT}</p>

			<a href="protected_user_manage.php">{TR_HTACCESS_USER}</a>
			<p>{TR_HTACCESS_USER}</p>

			<a href="error_pages.php">{TR_ERROR_PAGES}</a>
			<p>{TR_ERROR_PAGES_TEXT}</p>

			<a href="backup.php">{TR_BACKUP}</a>
			<p>{TR_BACKUP_TEXT}</p>

 			<!-- BDP: active_email -->
 				<a href="{WEBMAIL_PATH}">{TR_WEBMAIL}</a>
				<p>{TR_WEBMAIL_TEXT}</p>
 			<!-- EDP: active_email -->

			<a href="{FILEMANAGER_PATH}">{TR_FILEMANAGER}</a>
			<p>{TR_FILEMANAGER_TEXT}</p>

			<!-- BDP: active_awstats -->
				<a href="{AWSTATS_PATH}">{TR_AWSTATS}</a>
				<p>{TR_AWSTATS_TEXT}</p>
			<!-- EDP: active_awstats -->
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
