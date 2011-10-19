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
			function action_delete(url, subject, object) {
				if(object == 'database') {
					msg = "{TR_DATABASE_MESSAGE_DELETE}"
				} else {
					msg = "{TR_USER_MESSAGE_DELETE}"
				}

				if (confirm(sprintf(msg, subject))) {
					location = url;
				}

				return false;
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
			<div class="location-area">
				<h1 class="database">{TR_MENU_MANAGE_SQL}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li>
					<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
				</li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="sql_manage.php">{TR_MENU_MANAGE_SQL}</a></li>
				<li><a href="sql_manage.php">{TR_LMENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="sql"><span>{TR_MANAGE_SQL}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: sql_databases_users_list -->
			<table>
				<tr>
					<th>{TR_DATABASE}</th>
					<th>{TR_ACTIONS}</th>
				</tr>
				<!-- BDP: sql_databases_list -->
				<tr>
					<td style="width:250px;"><strong>{DB_NAME}</strong></td>
					<td>
						<a href="sql_user_add.php?id={DB_ID}" class="icon i_add_user" title="{TR_ADD_USER}">{TR_ADD_USER}</a>
						<a href="#" class="icon i_delete" onclick="return action_delete('sql_database_delete.php?id={DB_ID}', '{DB_NAME}', 'database')" title="{TR_DELETE}">{TR_DELETE}</a>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<!-- BDP: sql_users_list -->
						<table>
							<tr style="border: none;">
								<td style="border:none;width:260px;">{DB_USER}</td>
								<td style="border:none;">
									<a href="pma_auth.php?id={USER_ID}" class="icon i_pma" target="{PMA_TARGET}" title="TR_LOGIN_PMA">{TR_PHPMYADMIN}</a>
									<a href="sql_change_password.php?id={USER_ID}" class="icon i_change_password" title="{TR_CHANGE_PASSWORD}">{TR_CHANGE_PASSWORD}</a>
									<a href="#" class="icon i_delete" onclick="return action_delete('sql_delete_user.php?id={USER_ID}', '{DB_USER}', 'user')" title="{TR_DELETE}">{TR_DELETE}</a>
								</td>
							</tr>
						</table>
						<!-- EDP: sql_users_list -->
					</td>
				</tr>
				<!-- EDP: sql_databases_list -->
			</table>
			<!-- EDP: sql_databases_users_list -->
		</div>
<!-- INCLUDE "footer.tpl" -->
