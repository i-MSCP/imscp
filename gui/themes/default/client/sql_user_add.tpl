<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_SQL_ADD_USER_PAGE_TITLE}</title>
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
				<li><a href="sql_manage.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="sql"><span>{TR_ADD_SQL_USER}</span></h2>
			<form name="sql_add_user_frm" method="post" action="sql_user_add.php">
				<!-- BDP: show_sqluser_list -->
					<fieldset>
						<legend>{TR_ADD_EXIST}</legend>
						<table>
							<tr>
								<td><label for="sqluser_id">{TR_SQL_USER_NAME}</label></td>
								<td>
									<select name="sqluser_id" id="sqluser_id">
										<!-- BDP: sqluser_list -->
											<option value="{SQLUSER_ID}" {SQLUSER_SELECTED}>{SQLUSER_NAME}</option>
										<!-- EDP: sqluser_list -->
									</select>
								</td>
							</tr>
						</table>

						<div class="buttons">
							<input name="Add_Exist" type="submit" id="Add_Exist" value="{TR_ADD_EXIST}" tabindex="1" />
						</div>
					</fieldset>
				<!-- EDP: show_sqluser_list -->

				<!-- BDP: create_sqluser -->
					<fieldset>
						<legend>{TR_ADD_SQL_USER}</legend>

						<table>
							<tr>
								<td><label for="user_name">{TR_USER_NAME}</label></td>
								<td><input type="text" id="user_name" name="user_name" value="{USER_NAME}" /></td>
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
							<tr>
								<td><label for="pass">{TR_PASS}</label></td>
								<td><input id="pass" type="password" name="pass" value="" /></td>
							</tr>
							<tr>
								<td><label for="pass_rep">{TR_PASS_REP}</label></td>
								<td><input id="pass_rep" type="password" name="pass_rep" value="" /></td>
							</tr>
						</table>

						<div class="buttons">
							<input name="Add_New" type="submit" class="button" id="Add_New" value="{TR_ADD}" />
							<input type="button" name="Submit" value="{TR_CANCEL}" onclick="location.href = 'sql_manage.php'" class="button" />
						</div>
					</fieldset>
				<!-- EDP: create_sqluser -->
				<input type="hidden" name="uaction" value="add_user" />
				<input type="hidden" name="id" value="{ID}" />
			</form>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
