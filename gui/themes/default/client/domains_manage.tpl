<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_MANAGE_DOMAINS_PAGE_TITLE}</title>
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
				<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li><a href="domains_manage.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
				<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="domains"><span>{TR_DOMAIN_ALIASES}</span></h2>
			<!-- BDP: als_message -->
				<div class="info">{ALS_MSG}</div>
			<!-- EDP: als_message -->
			<!-- BDP: als_list -->
				<table>
					<thead>
						<tr>
							<th>{TR_ALS_NAME}</th>
							<th>{TR_ALS_MOUNT}</th>
							<th>{TR_ALS_FORWARD}</th>
							<th>{TR_ALS_STATUS}</th>
							<th>{TR_ALS_ACTION}</th>
						</tr>
					</thead>
					<tbody>
						<!-- BDP: als_item -->
							<tr>
								<td><a href="http://{ALS_NAME}/" class="icon i_domain_icon" title="{ALS_NAME}">{ALS_NAME}</a></td>
								<td>{ALS_MOUNT}</td>
								<td>{ALS_FORWARD}</td>
								<td>{ALS_STATUS}</td>
								<td>
									<a href="#" class="icon i_delete" onclick="action_delete('{ALS_ACTION_SCRIPT}', '{ALS_NAME}')" title="{ALS_ACTION}">{ALS_ACTION}</a>
									<a href="{ALS_EDIT_LINK}" class="icon i_edit" title="{ALS_EDIT}">{ALS_EDIT}</a>
								</td>
							</tr>
						<!-- EDP: als_item -->
					</tbody>
				</table>
			<!-- EDP: als_list -->

			<h2 class="doc"><span>{TR_SUBDOMAINS}</span></h2>
			<!-- BDP: sub_message -->
				<div class="info">{SUB_MSG}</div>
			<!-- EDP: sub_message -->

			<!-- BDP: sub_list -->
				<table>
					<thead>
						<tr>
							<th>{TR_SUB_NAME}</th>
							<th>{TR_SUB_MOUNT}</th>
							<th>{TR_SUB_STATUS}</th>
							<th>{TR_SUB_ACTION}</th>
						</tr>
					</thead>
					<tbody>
						<!-- BDP: sub_item -->
							<tr>
								<td><a href="http://{SUB_NAME}.{SUB_ALIAS_NAME}/" class="icon i_domain_icon" title="{SUB_NAME}.{SUB_ALIAS_NAME}">{SUB_NAME}.{SUB_ALIAS_NAME}</a></td>
								<td>{SUB_MOUNT}</td>
								<td>{SUB_STATUS}</td>
								<td><a href="#" class="icon i_delete" onclick="action_delete('{SUB_ACTION_SCRIPT}', '{SUB_NAME}')">{SUB_ACTION}</a></td>
							</tr>
						<!-- EDP: sub_item -->
					</tbody>
				</table>
			<!-- EDP: sub_list -->

			<!-- BDP: isactive_dns -->
				<h2 class="domains"><span>{TR_DNS}</span></h2>
				<!-- BDP: dns_message -->
					<div class="info">{DNS_MSG}</div>
				<!-- EDP: dns_message -->

				<!-- BDP: dns_list -->
					<table>
						<thead>
							<tr>
								<th>{TR_DOMAIN_NAME}</th>
								<th>{TR_DNS_NAME}</th>
								<th>{TR_DNS_CLASS}</th>
								<th>{TR_DNS_TYPE}</th>
								<th>{TR_DNS_DATA}</th>
								<th>{TR_DNS_ACTION}</th>
							</tr>
						</thead>
						<tbody>
							<!-- BDP: dns_item -->
								<tr>
									<td><span class="icon i_domain_icon">{DNS_DOMAIN}</span></td>
									<td>{DNS_NAME}</td>
									<td>{DNS_CLASS}</td>
									<td>{DNS_TYPE}</td>
									<td>{DNS_DATA}</td>
									<td>
										<a class="icon i_edit" href="{DNS_ACTION_SCRIPT_EDIT}" title="{DNS_ACTION_EDIT}">{DNS_ACTION_EDIT}</a>
										<a href="#" class="icon i_delete" onclick="action_delete('{DNS_ACTION_SCRIPT_DELETE}', '{DNS_TYPE_RECORD}')" title="{DNS_ACTION_DELETE}">{DNS_ACTION_DELETE}</a>
									</td>
								</tr>
							<!-- EDP: dns_item -->
						</tbody>
					</table>
				<!-- EDP: dns_list -->
			<!-- EDP: isactive_dns -->
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
