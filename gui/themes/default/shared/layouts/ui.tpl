<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" xml:lang="en">
<head>
	<title>{TR_PAGE_TITLE}</title>
	<meta http-equiv='Content-Script-Type' content='text/javascript' />
	<meta http-equiv='Content-Style-Type' content='text/css' />
	<meta http-equiv='Content-Type' content='text/html; charset={THEME_CHARSET}' />
	<meta name='copyright' content='i-MSCP' />
	<meta name='owner' content='i-MSCP' />
	<meta name='publisher' content='i-MSCP' />
	<meta name='robots' content='nofollow, noindex' />
	<meta name='title' content='{TR_PAGE_TITLE}' />
	<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
	<link href="{THEME_COLOR_PATH}/css/{THEME_COLOR}.css" rel="stylesheet" type="text/css" />
	<link href="{THEME_COLOR_PATH}/css/jquery-ui-{THEME_COLOR}.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.ui.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltip-min.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
	<!--[if IE 6]>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.bgiframe-2.1.2.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
	<script type="text/javascript">
		DD_belatedPNG.fix(
			'.logo img, .custom_link, .database, .domains, .email, .ftp, .general, .hosting_plans, .manage_users,' +
			'.purchasing, .settings, .statistics, .support, .webtools, .custom_link,' +
			'.support, .profile, .webtools, .help, .logout, .backadmin, .adminlog, .billing, .debugger,.diskusage, .doc,' +
			'.error, .flash, .hdd, .htaccess, .ip, .layout, .maintenancemode, .multilanguage, .no, .password,' +
			'.serverstatus, .sql, .stats, .systeminfo, .tools, .traffic, .update,' +
			'.user_blue, .user_green, .user_yellow, .user, .users, .users2, .apps_installer, .custom_link,' +
			'.warning, .success2, .error, .info, .i_add_user, .i_awstatsicon, .i_backupicon, .i_bc_folder,' +
			'.i_bc_locked, .i_bc_parent, .i_change_password, .i_close_interface, .i_database_small, .i_db_comit,' +
			'.i_delete, .i_details, .i_disabled, .i_document, .i_domain_icon, .i_edit, .i_error, .i_error401,' +
			'.i_error403, .i_error404, .i_error500, .i_error503, .i_errordocsicon, .i_filemanagericon,' +
			'.i_ftp_account, .i_goto, .i_help, .i_hide_alias, .i_htaccessicon, .i_identity, .i_locale,' +
			'.i_mail_icon, .i_next, .i_next_gray, .i_ok, .i_filemanager, .i_pma, .i_prev, .i_prev_gray,' +
			'.i_reload, .i_show_alias, .i_stats, .i_user, .i_users, .i_webmailicon, .i_app_installer,' +
			'i_app_installed, .i_app_download, .i_app_asc, .i_app_desc'
		);
	</script>
	<![endif]-->
	<!--[if IE]>
	<script type="text/javascript">
	/*<![CDATA[*/
		// css adjustement for IE browsers
		$(window).load(function(){
			$('tr').each(function(index) {
				if(index % 2) {
					$(this).css('background-color', 'rgb(255, 255, 255)');
				} else {
					$(this).css('background-color', 'rgb(237, 237, 237)');
				}
			});

			$("td:last-child").css('border-right','1px solid rgb(223, 223, 223)');
			$("th:last-child, tfoot td:last-child").css('border-right','1px solid rgb(0, 0, 0)');
			$(".datatable tfoot td:last-child").css('border-right','1px solid rgb(223, 223, 223)');
		});
	/*]]>*/
	</script>
	<![endif]-->
	<script type="text/javascript">
	/*<![CDATA[*/
		$(document).ready(function() {
			$.fx.speeds._default = 500;
			setTimeout(function(){$('.timeout').fadeOut(1000);},3000);
			$('.main_menu a').imscpTooltip();
			$('.body a, .body span, .body input').imscpTooltip({extraClass:"tooltip_icon tooltip_notice"});

			// Setup buttons
			$("input:submit, input:button, button").button();
			$(".radio, .checkbox").buttonset();
			$(":radio, :checkbox").change(function(){$(this).blur();});
		});
	/*]]>*/
	</script>
</head>
<body>
	<div id="wrapper">
		<div class="header">
			<!-- INCLUDE "../partials/navigation/main_menu.tpl" -->
			<div class="logo"><img src="{ISP_LOGO}" alt="i-MSCP logo" /></div>
		</div>
		<div class="location">
			<div class="location-area"><h1 class="{SECTION_TITLE_CLASS}">{TR_SECTION_TITLE}</h1></div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<!-- INCLUDE "../partials/navigation/breadcrumbs.tpl" -->
		</div>
		<!-- INCLUDE "../partials/navigation/left_menu.tpl" -->
		<div class="body">
			<h2 class="{TITLE_CLASS}"><span>{TR_TITLE}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->
			{LAYOUT_CONTENT}
		</div>
	</div>
	<div class="footer">
		i-MSCP {VERSION}<br />Build: {BUILDDATE}<br />Codename: {CODENAME}
	</div>
</body>
</html>
