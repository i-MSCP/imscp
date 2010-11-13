<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_HTACCESS_USER}</title>
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
			return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
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
				<li><a href="protected_user_manage.php">{TR_HTACCESS_USER}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="users"><span>{TR_USER_MANAGE}</span></h2>
			<!-- BDP: usr_msg -->
			<div class="warning">{USER_MESSAGE}</div>
			<!-- EDP: usr_msg -->
			<table>
				<thead>
					<tr>
						<th>{TR_USERNAME}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: pusres -->
						<tr>
							<td>{UNAME}</td>
							<td>{USTATUS}</td>
							<td>
								<a href="protected_user_assign.php?uname={USER_ID}" class="icon i_users">{TR_GROUP}</a>
								<a href="{USER_EDIT_SCRIPT}" class="icon i_edit">{USER_EDIT}</a>
								<a href="#" class="icon i_delete" onclick="{USER_DELETE_SCRIPT}">{USER_DELETE}</a>
							</td>
						</tr>
					<!-- EDP: pusres -->
				</tbody>
			</table>
			<div class="buttons">
				<input name="Button" type="button"  onclick="MM_goToURL('parent','protected_user_add.php');return document.MM_returnValue" value="{TR_ADD_USER}" />
			</div>

			<h2 class="groups"><span>{TR_GROUPS}</span></h2>
			<!-- BDP: grp_msg -->
			<div class="warning">{GROUP_MESSAGE}</div>
			<!-- EDP: grp_msg -->
			<table>
				<thead>
					<tr>
						<th>{TR_GROUPNAME}</th>
						<th>{TR_GROUP_MEMBERS}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: pgroups -->
						<tr>
							<td>{GNAME}</td>
							<td><!-- BDP: group_members -->{MEMBER}<!-- EDP: group_members --></td>
							<td>{GSTATUS}</td>
							<td>
								<a href="#" class="icon i_delete" onclick="{GROUP_DELETE_SCRIPT}">{GROUP_DELETE}</a>
							</td>
						</tr>
					<!-- EDP: pgroups -->
				</tbody>
			</table>
			<div class="buttons">
				<input name="Button2" type="button" value="{TR_ADD_GROUP}" onclick="MM_goToURL('parent','protected_group_add.php');return document.MM_returnValue" />
			</div>


		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
