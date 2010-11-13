<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_MANAGE_USERS_PAGE_TITLE}</title>
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
			if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
				return false;
			location = url;
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
				<h1 class="email">{TR_MENU_EMAIL_ACCOUNTS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="mail_accounts.php">{TR_MENU_EMAIL_ACCOUNTS}</a></li>
				<li><a href="mail_accounts.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="email"><span>{TR_MAIL_USERS}</span></h2>
			<!-- BDP: mail_message -->
			<div class="info">{MAIL_MSG}</div>
			<!-- EDP: mail_message -->

			<table>
				<thead>
					<tr>
						<th>{TR_MAIL}</th>
						<th>{TR_TYPE}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTION}</th>
					</tr>
				</thead>
				<!-- BDP: mails_total -->
					<tfoot>
						<tr>
							<td colspan="4">{TR_TOTAL_MAIL_ACCOUNTS}: <strong>{TOTAL_MAIL_ACCOUNTS}</strong>/{ALLOWED_MAIL_ACCOUNTS}</td>
						</tr>
					</tfoot>
				<!-- EDP: mails_total -->
				<tbody>
					<!-- BDP: mail_item -->
						<tr>
							<td>
								<span class="icon i_mail_icon">{MAIL_ACC}</span>
								<!-- BDP: auto_respond -->
								<div style="display: {AUTO_RESPOND_VIS};">
									<br />
							  		{TR_AUTORESPOND}:
							  		[
							  			<a href="{AUTO_RESPOND_DISABLE_SCRIPT}" class="icon i_reload">{AUTO_RESPOND_DISABLE}</a>
							  			<a href="{AUTO_RESPOND_EDIT_SCRIPT}" class="icon i_edit">{AUTO_RESPOND_EDIT}</a>
							  		]
							  </div>
							  <!-- EDP: auto_respond -->
							</td>
							<td>{MAIL_TYPE}</td>
							<td>{MAIL_STATUS}</td>
							<td>
								<a href="{MAIL_EDIT_SCRIPT}" class="icon i_edit">{MAIL_EDIT}</a>
								<a href="#" class="icon i_delete" onclick="action_delete('{MAIL_DELETE_SCRIPT}', '{MAIL_ACC}')">{MAIL_DELETE}</a>
							</td>
						</tr>
					<!-- EDP: mail_item -->
				</tbody>
			</table>

			<form action="mail_accounts.php" method="post" name="showdefault" id="showdefault">
				<div class="buttons">
				  	<input name="Submit" type="submit" class="button" value="{TR_SHOW_DEFAULT_EMAILS}" />
				</div>
				<input type="hidden" name="uaction" value="show" />
			</form>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
