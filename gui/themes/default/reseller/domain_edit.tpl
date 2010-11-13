<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_DOMAIN_PAGE_TITLE}</title>
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
				<li><a href="manage_users.php">{TR_EDIT_DOMAIN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="domains"><span>{TR_EDIT_DOMAIN}</span></h2>
			<form name="reseller_edit_domain_frm" method="post" action="domain_edit.php">
				<table>
					<tr>
						<td>{TR_DOMAIN_NAME}</td>
						<td>{VL_DOMAIN_NAME}</td>

					</tr>
					<tr>
						<td>{TR_DOMAIN_EXPIRE}</td>
						<td>{VL_DOMAIN_EXPIRE}</td>

					</tr>

					<tr>
						<td>{TR_DOMAIN_NEW_EXPIRE}
						<!-- TODO: Implement tooltip functionality
							<span class="icon i_help" onmouseover="showTip('dmn_exp_help', event)" onmouseout="hideTip('dmn_exp_help')" />
						-->
						</td>
						<td><select id="dmn_expire" name="dmn_expire">
								<option value="0">{TR_DOMAIN_EXPIRE_UNCHANGED}</option>
								<option value="-1">{TR_DOMAIN_EXPIRE_MIN_1_MONTH}</option>
								<option value="1">{TR_DOMAIN_EXPIRE_PLUS_1_MONTH}</option>
								<option value="2">{TR_DOMAIN_EXPIRE_PLUS_2_MONTHS}</option>
								<option value="3">{TR_DOMAIN_EXPIRE_PLUS_3_MONTHS}</option>
								<option value="12">{TR_DOMAIN_EXPIRE_PLUS_6_MONTHS}</option>
								<option value="24">{TR_DOMAIN_EXPIRE_PLUS_1_YEAR}</option>
								<option value="24">{TR_DOMAIN_EXPIRE_PLUS_2_YEARS}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{TR_DOMAIN_IP}</td>
						<td>{VL_DOMAIN_IP}</td>
						<!--
				<select name="domain_ip">

				<option value="{IP_VALUE}" {IP_SELECTED}>{IP_NUM}&nbsp;({IP_NAME})</option>

			  </select>
				-->
					</tr>
					<tr>
						<td>{TR_PHP_SUPP}</td>
						<td><select id="domain_php" name="domain_php">
								<option value="_yes_" {PHP_YES}>{TR_YES}</option>
								<option value="_no_" {PHP_NO}>{TR_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{TR_CGI_SUPP}</td>
						<td><select id="domain_cgi" name="domain_cgi">
								<option value="_yes_" {CGI_YES}>{TR_YES}</option>
								<option value="_no_" {CGI_NO}>{TR_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{TR_DNS_SUPP}</td>
						<td><select id="domain_dns" name="domain_dns">
								<option value="_yes_" {DNS_YES}>{TR_YES}</option>
								<option value="_no_" {DNS_NO}>{TR_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td>{TR_BACKUP}</td>
						<td><select ide="backup" name="backup">
								<option value="_dmn_" {BACKUP_DOMAIN}>{TR_BACKUP_DOMAIN}</option>
								<option value="_sql_" {BACKUP_SQL}>{TR_BACKUP_SQL}</option>
								<option value="_full_" {BACKUP_FULL}>{TR_BACKUP_FULL}</option>
								<option value="_no_" {BACKUP_NO}>{TR_BACKUP_NO}</option>
							</select>
						</td>
					</tr>
					<tr>
						<td><label for="dom_sub">{TR_SUBDOMAINS}</label></td>
						<td><input type="text" name="dom_sub" id="dom_sub" value="{VL_DOM_SUB}"/></td>
					</tr>
					<tr>
						<td><label for="dom_alias">{TR_ALIAS}</label></td>
						<td><input type="text" name="dom_alias" id="dom_alias" value="{VL_DOM_ALIAS}"/></td>
					</tr>
					<tr>
						<td><label for="dom_mail_acCount">{TR_MAIL_ACCOUNT}</label></td>
						<td><input type="text" name="dom_mail_acCount" id="dom_mail_acCount" value="{VL_DOM_MAIL_ACCOUNT}"/></td>
					</tr>
					<tr>
						<td><label for="dom_ftp_acCounts">{TR_FTP_ACCOUNTS}</label></td>
						<td><input type="text" name="dom_ftp_acCounts" id="dom_ftp_acCounts" value="{VL_FTP_ACCOUNTS}"/></td>
					</tr>
					<tr>
						<td><label for="dom_sqldb">{TR_SQL_DB}</label></td>
						<td><input type="text" name="dom_sqldb" id="dom_sqldb" value="{VL_SQL_DB}"/></td>
					</tr>
					<tr>
						<td><label for="dom_sql_users">{TR_SQL_USERS}</label></td>
						<td><input type="text" name="dom_sql_users" id="dom_sql_users" value="{VL_SQL_USERS}"/></td>
					</tr>
					<tr>
						<td><label for="dom_traffic">{TR_TRAFFIC}</label></td>
						<td><input type="text" name="dom_traffic" id="dom_traffic" value="{VL_TRAFFIC}"/></td>
					</tr>
					<tr>
						<td><label for="dom_disk">{TR_DISK}</label></td>
						<td><input type="text" name="dom_disk" id="dom_disk" value="{VL_DOM_DISK}"/></td>
					</tr>
				</table>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_UPDATE_DATA}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','users.php');return document.MM_returnValue" value="{TR_CANCEL}" />
					<input type="hidden" name="uaction" value="sub_data" />
				</div>
			</form>
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>