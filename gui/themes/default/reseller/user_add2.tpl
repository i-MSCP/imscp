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
				<li>{TR_HOSTING_PLAN_PROPERTIES}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<div id="dmn_help" class="tooltip">{TR_DMN_HELP}</div>

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="user"><span>{TR_ADD_USER}</span></h2>
			<!-- BDP: add_user -->
			<form name="reseller_add_users_second_frm" method="post" action="user_add2.php">
				<fieldset>
					<legend>{TR_HOSTING_PLAN_PROPERTIES}</legend>
					<table>
						<tr>
							<td>{TR_TEMPLATE_NAME}</td>
							<td><input name="template" type="hidden" id="template" value="{VL_TEMPLATE_NAME}" />{VL_TEMPLATE_NAME}</td>
						</tr>
						<tr>
							<td><label for="nreseller_max_subdomain_cnt">{TR_MAX_SUBDOMAIN}</label></td>
							<td><input id="nreseller_max_subdomain_cnt" type="text" name="nreseller_max_subdomain_cnt" value="{MAX_SUBDMN_CNT}" /></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_alias_cnt">{TR_MAX_DOMAIN_ALIAS}</label></td>
							<td><input id="nreseller_max_alias_cnt" type="text" name="nreseller_max_alias_cnt" value="{MAX_DMN_ALIAS_CNT}" /></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_mail_cnt">{TR_MAX_MAIL_COUNT}</label></td>
							<td><input id="nreseller_max_mail_cnt" type="text" name="nreseller_max_mail_cnt" value="{MAX_MAIL_CNT}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_ftp_cnt">{TR_MAX_FTP}</label></td>
							<td><input id="nreseller_max_ftp_cnt"type="text" name="nreseller_max_ftp_cnt" value="{MAX_FTP_CNT}" /></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_sql_db_cnt">{TR_MAX_SQL_DB}</label></td>
							<td><input id="nreseller_max_sql_db_cnt" type="text" name="nreseller_max_sql_db_cnt" value="{MAX_SQL_CNT}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_sql_user_cnt">{TR_MAX_SQL_USERS}</label></td>
							<td><input id="nreseller_max_sql_user_cnt" type="text" name="nreseller_max_sql_user_cnt" value="{VL_MAX_SQL_USERS}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_traffic">{TR_MAX_TRAFFIC}</label></td>
							<td><input id="nreseller_max_traffic" type="text" name="nreseller_max_traffic" value="{VL_MAX_TRAFFIC}"/></td>
						</tr>
						<tr>
							<td><label for="nreseller_max_disk">{TR_MAX_DISK_USAGE}</label></td>
							<td><input id="nreseller_max_disk" type="text" name="nreseller_max_disk" value="{VL_MAX_DISK_USAGE}"/></td>
						</tr>
						<tr>
							<td>{TR_PHP}</td>
							<td>
								<input type="radio" id="php_yes" name="php" value="_yes_" {VL_PHPY} /><label for="php_yes">{TR_YES}</label>
								<input type="radio" id="php_no" name="php" value="_no_" {VL_PHPN} /><label for="php_no">{TR_NO}</label>
							</td>
						</tr>
						<tr>
							<td>{TR_CGI}</td>
							<td>
								<input type="radio" id="cgi_yes" name="cgi" value="_yes_" {VL_CGIY} /><label for="cgi_yes">{TR_YES}</label>
								<input type="radio" id="cgi_no" name="cgi" value="_no_" {VL_CGIN} /><label for="cgi_no">{TR_NO}</label>
							</td>
						</tr>
						<tr>
							<td>{TR_DNS}</td>
							<td>
								<input type="radio" id="dns_yes" name="dns" value="_yes_" {VL_DNSY} /><label for="dns_yes">{TR_YES}</label>
								<input type="radio" id="dns_no" name="dns" value="_no_" {VL_DNSN} /><label for="dns_no">{TR_NO}</label>
							</td>
						</tr>

						<tr>
							<td>{TR_BACKUP}</td>
							<td>
								<input type="radio" id="backup_dmn" name="backup" value="_dmn_" {VL_BACKUPD} /><label for="backup_dmn">{TR_BACKUP_DOMAIN}</label>
								<input type="radio" id="backup_sql" name="backup" value="_sql_" {VL_BACKUPS} /><label for="backup_sql">{TR_BACKUP_SQL}</label>
								<input type="radio" id="backup_full" name="backup" value="_full_" {VL_BACKUPF} /><label for="backup_full">{TR_BACKUP_FULL}</label>
								<input type="radio" id="backup_no" name="backup" value="_no_" {VL_BACKUPN} /><label for="backup_no">{TR_BACKUP_NO}</label>
							</td>
						</tr>

					</table>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}" />
				</div>
				<input type="hidden" name="uaction" value="user_add2_nxt" />
			</form>
			<!-- EDP: add_user -->
		</div>
		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
