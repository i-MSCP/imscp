<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_ADD_SQL_DATABASE_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
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
				<h1 class="database">{TR_MENU_MANAGE_SQL}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="sql_database_add.php">{TR_MENU_ADD_SQL_DATABASE}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="sql"><span>{TR_ADD_DATABASE}</span></h2>

			<form name="sql_add_database_frm" method="post" action="sql_database_add.php">
				<table>
					<tr>
						<td><label for="db_name">{TR_DB_NAME}</label></td>
						<td><input type="text" id="db_name" name="db_name" value="{DB_NAME}" /></td>
					</tr>
					<tr>
						<td>
							<!-- BDP: mysql_prefix_yes -->
								<input type="checkbox" name="use_dmn_id" {USE_DMN_ID} />
							<!-- EDP: mysql_prefix_yes -->
							<!-- BDP: mysql_prefix_no -->
								<input type="hidden" name="use_dmn_id" value="on" />
							<!-- EDP: mysql_prefix_no -->
							{TR_USE_DMN_ID}
						</td>
						<td>
							<!-- BDP: mysql_prefix_all -->
								<input type="radio" name="id_pos" value="start" {START_ID_POS_CHECKED} />{TR_START_ID_POS}<br />
							   	<input type="radio" name="id_pos" value="end" {END_ID_POS_CHECKED} />{TR_END_ID_POS}
							<!-- EDP: mysql_prefix_all -->
							<!-- BDP: mysql_prefix_infront -->
								<input type="hidden" name="id_pos" value="start" checked="checked" />{TR_START_ID_POS}
							<!-- EDP: mysql_prefix_infront -->
							<!-- BDP: mysql_prefix_behind -->
								<input type="hidden" name="id_pos" value="end" checked="checked" />{TR_END_ID_POS}
							<!-- EDP: mysql_prefix_behind -->
						</td>
					</tr>
				</table>

				<div class="buttons">
					<input name="Add_New" type="submit" class="button" id="Add_New" value="{TR_ADD}" />
				</div>
				<input type="hidden" name="uaction" value="add_db" />
			</form>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
