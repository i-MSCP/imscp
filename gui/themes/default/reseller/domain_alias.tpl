<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_ALIAS_PAGE_TITLE}</title>
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
			function delete_account(url, name) {
				if (!confirm(sprintf("{TR_MESSAGE_DELETE}", name)))
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
				<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="alias.php">{TR_MENU_DOMAIN_ALIAS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="users"><span>{TR_MANAGE_ALIAS}</span></h2>

			<form name="search_alias_frm" method="post" action="alias.php?psi={PSI}">
				<input name="search_for" type="text" value="{SEARCH_FOR}" />
				<select name="search_common">
					<option value="alias_name" {M_DOMAIN_NAME_SELECTED}>{M_ALIAS_NAME}</option>
					<option value="account_name" {M_ACCOUN_NAME_SELECTED}>{M_ACCOUNT_NAME}</option>
				</select>
				<input name="Submit" type="submit" value="{TR_SEARCH}" />
				<input type="hidden" name="uaction" value="go_search" />
			</form>

			<!-- BDP: table_list -->
				<table>
					<thead>
						<tr>
							<th>{TR_NAME}</th>
							<th>{TR_REAL_DOMAIN}</th>
							<th>{TR_FORWARD}</th>
							<th>{TR_STATUS}</th>
							<th>{TR_ACTION}</th>
						</tr>
					</thead>
					<tbody>
						<!-- BDP: table_item -->
							<tr>
								<td><a href="http://www.{NAME}/" target="_blank" class="icon i_domain_icon">{NAME}</a><br />{ALIAS_IP}</td>
								<td>{REAL_DOMAIN}<br />{REAL_DOMAIN_MOUNT}</td>
								<td>{FORWARD}</td>
								<td>{STATUS}</td>
								<td>
									<a href="{EDIT_LINK}" class="icon i_edit" title="{EDIT}">{EDIT}</a>
									<a href="#" onclick="delete_account('{DELETE_LINK}', '{NAME}')" class="icon i_delete" title="{DELETE}">{DELETE}</a>
								</td>
							</tr>
						<!-- EDP: table_item -->
					</tbody>
				</table>
			<!-- EDP: table_list -->
			<div class="buttons">
				<input name="Submit" type="submit" onclick="MM_goToURL('parent','alias_add.php');return document.MM_returnValue" value="{TR_ADD_ALIAS}" />
			</div>

			<div class="paginator">
				<!-- BDP: scroll_next_gray -->
				<a class="icon i_next_gray" href="#">&nbsp;</a>
				<!-- EDP: scroll_next_gray -->
				<!-- BDP: scroll_next -->
				<a class="icon i_next" href="manage_users.php?psi={NEXT_PSI}" title="next">next</a>
				<!-- EDP: scroll_next -->
				<!-- BDP: scroll_prev -->
				<a class="icon i_prev" href="manage_users.php?psi={PREV_PSI}" title="previous">previous</a>
				<!-- EDP: scroll_prev -->
				<!-- BDP: scroll_prev_gray -->
				<a class="icon i_prev_gray" href="#">&nbsp;</a>
				<!-- EDP: scroll_prev_gray -->
			</div>

		</div>

	</body>
</html>
