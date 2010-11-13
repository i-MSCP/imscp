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
			if (!confirm(sprintf("{TR_MESSAGE_DELETE_ACCOUNT}", domain)))
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
				<h1 class="purchasing">{TR_MENU_ORDERS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="orders.php">{TR_MENU_ORDERS}</a></li>
				<li><a href="orders.php">{TR_MENU_OVERVIEW}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: props_list -->
				<h2 class="billing"><span>{TR_MANAGE_ORDERS}</span></h2>
				<!-- BDP: page_message -->
					<div class="warning">{MESSAGE}</div>
				<!-- EDP: page_message -->

				<!-- BDP: orders_table -->
					<table>
						<thead>
							<tr>
								<th>{TR_ID}</th>
								<th>{TR_DOMAIN}</th>
								<th>{TR_HP}</th>
								<th>{TR_USER}</th>
								<th>{TR_STATUS}</th>
								<th>{TR_ACTION}</th>
							</tr>
						</thead>
						<tbody>
							<!-- BDP: order -->
								<tr>
									<td>{ID}</td>
									<td>{DOMAIN}</td>
									<td>{HP}</td>
									<td>{USER}v</td>
									<td>{STATUS}</td>
									<td>
										<a href="{LINK}" class="icon i_add_user">{TR_ADD}</a>
										<a href="#" onclick="delete_order('orders_delete.php?order_id={ID}', '{DOMAIN}')" class="icon i_delete">{TR_DELETE}</a>
									</td>
								</tr>
							<!-- EDP: order -->
						</tbody>
					</table>
				<!-- EDP: orders_table -->

				<div class="paginator">
					<!-- BDP: scroll_next_gray -->
					<a class="icon i_next_gray" href="#" title="next">next</a>
					<!-- EDP: scroll_next_gray -->
					<!-- BDP: scroll_next -->
					<a class="icon i_next" href="orders.php?psi={NEXT_PSI}" title="next">next</a>
					<!-- EDP: scroll_next -->
					<!-- BDP: scroll_prev_gray -->
					<a class="icon i_prev_gray" href="#" title="next">next</a>
					<!-- EDP: scroll_prev_gray -->
					<!-- BDP: scroll_prev -->
					<a class="icon i_prev" href="orders.php?psi={PREV_PSI}" title="previous">previous</a>
					<!-- EDP: scroll_prev -->
				</div>
			<!-- EDP: props_list -->

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
