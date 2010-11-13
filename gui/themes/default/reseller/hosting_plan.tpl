<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_RESELLER_MAIN_INDEX_PAGE_TITLE}</title>
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
				<h1 class="hosting_plans">{TR_MENU_HOSTING_PLANS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="hosting_plan.php">{TR_MENU_HOSTING_PLANS}</a></li>
				<li><a href="hosting_plan.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
				<h2 class="serverstatus"><span>{TR_HOSTING_PLANS}</span></h2>
				<!-- BDP: page_message -->
					<div class="warning">{MESSAGE}</div>
				<!-- EDP: page_message -->

				<!-- BDP: hp_table -->
					<table>
						<thead>
							<tr>
								<th>{TR_NOM}</th>
								<th>{TR_PLAN_NAME}</th>
								<th>{TR_PURCHASING}</th>
								<th>{TR_ACTION}</th>
							</tr>
						</thead>
						<tbody>
							<!-- BDP: hp_entry -->
								<tr>
									<td>{PLAN_NOM}</td>
									<td><a href="../orderpanel/package_info.php?coid={CUSTOM_ORDERPANEL_ID}&amp;user_id={RESELLER_ID}&amp;id={HP_ID}" target="_blank" title="{PLAN_SHOW}">{PLAN_NAME}</a></td>
									<td>{PURCHASING}</td>
									<td>
										<a href="hosting_plan_edit.php?hpid={HP_ID}" class="icon i_edit">{TR_EDIT}</a>
										<!-- BDP: hp_delete -->
											<a href="#" onclick="return action_delete('hosting_plan_delete.php?hpid={HP_ID}', '{PLAN_NAME2}')" class="icon i_delete">{PLAN_ACTION}</a>
										<!-- EDP: hp_delete -->
									</td>
								</tr>
							<!-- EDP: hp_entry -->
						</tbody>
					</table>
				<!-- EDP: hp_table -->

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
