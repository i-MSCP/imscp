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
	</head>

	<body>

		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
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
				<li><a href="domain_delete.php?domain_id={DOMAIN_ID}">{TR_DELETE_DOMAIN} {DOMAIN_NAME}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="user"><span>{TR_DELETE_DOMAIN} {DOMAIN_NAME}</span></h2>
			<table>
				<tr>
					<td colspan="2">{TR_DOMAIN_SUMMARY}</td>
				</tr>
				<!-- BDP: mail_list -->
				<tr>
					<th colspan="2">{TR_DOMAIN_EMAILS}</th>
				</tr>
				<!-- BDP: mail_item -->
				<tr>
					<td width="150">{MAIL_ADDR}</td>
					<td>{MAIL_TYPE}</td>
				</tr>
				<!-- EDP: mail_item -->
				<!-- EDP: mail_list -->
				<!-- BDP: ftp_list -->
				<tr>
					<th colspan="2">{TR_DOMAIN_FTPS}</th>
				</tr>
				<!-- BDP: ftp_item -->
				<tr>
					<td width="150">{FTP_USER}</td>
					<td>{FTP_HOME}</td>
				</tr>
				<!-- EDP: ftp_item -->
				<!-- EDP: ftp_list -->
				<!-- BDP: als_list -->
				<tr>
					<th colspan="2">{TR_DOMAIN_ALIASES}</th>
				</tr>
				<!-- BDP: als_item -->
				<tr>
					<td width="150">{ALS_NAME}</td>
					<td>{ALS_MNT}</td>
				</tr>
				<!-- EDP: als_item -->
				<!-- EDP: als_list -->
				<!-- BDP: sub_list -->
				<tr>
					<th colspan="2">{TR_DOMAIN_SUBS}</th>
				</tr>
				<!-- BDP: sub_item -->
				<tr>
					<td width="150">{SUB_NAME}</td>
					<td>{SUB_MNT}</td>
				</tr>
				<!-- EDP: sub_item -->
				<!-- EDP: sub_list -->
				<!-- BDP: db_list -->
				<tr>
					<th colspan="2">{TR_DOMAIN_DBS}</th>
				</tr>
				<!-- BDP: db_item -->
				<tr>
					<td width="150">{DB_NAME}</td>
					<td>{DB_USERS}</td>
				</tr>
				<!-- EDP: db_item -->
				<!-- EDP: db_list -->
				<tr>
					<td colspan="2">
						<form name="reseller_delete_domain_frm" method="post" action="domain_delete.php">
						<input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
						{TR_REALLY_WANT_TO_DELETE_DOMAIN}<br /><br/>
						<input type="checkbox" value="1" name="delete" />{TR_YES_DELETE_DOMAIN}
						<div class="buttons">
							<input type="submit" value="{TR_BUTTON_DELETE}" />
						</div>
						</form>
					</td>
				</tr>
			</table>
		</div>
		<div class="footer">
			i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>