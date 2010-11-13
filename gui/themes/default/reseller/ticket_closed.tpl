<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_QUESTION_PAGE_TITLE}</title>
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
				<h1 class="support">{TR_MENU_QUESTIONS_AND_COMMENTS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a class="settings" href="ticket_system.php">{TR_MENU_QUESTIONS_AND_COMMENTS}</a></li>
				<li><a href="ticket_closed.php">{TR_CLOSED_TICKETS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: tickets_list -->
				<h2 class="support"><span>{TR_CLOSED_TICKETS}</span></h2>
				<table>
					<thead>
						<tr>
							<th>{TR_STATUS}</th>
							<th>{TR_SUBJECT}</th>
							<th>{TR_URGENCY}</th>
							<th>{TR_LAST_DATA}</th>
							<th>{TR_ACTION}</th>
						</tr>
					</thead>
					<tfoot>
						  <tr>
							<td colspan="5"><input name="Submit" type="submit" class="button" onclick="MM_goToURL('parent','ticket_delete.php?delete=closed');return document.MM_returnValue" value="{TR_DELETE_ALL}" /></td>
						  </tr>
					</tfoot>
					<tbody>
						<!-- BDP: tickets_item -->
							<tr>
								<td>{NEW}</td>
								<td><a href="ticket_view.php?ticket_id={ID}&amp;screenwidth='800'" class="link"> {SUBJECT}</a></td>
								<td>{URGENCY}</td>
								<td>{LAST_DATE}</td>
								<td><a href="ticket_delete.php?ticket_id={ID}" onclick="return action_delete('ticket_delete.php?ticket_id={ID}', '{SUBJECT2}')" class="icon i_delete">{TR_DELETE}</a></td>
							</tr>
						<!-- EDP: tickets_item -->
					</tbody>
				</table>
			<div class="paginator">
				<!-- BDP: scroll_next_gray -->
				<a class="icon i_next_gray" href="#" title="next">next</a>
				<!-- EDP: scroll_next_gray -->
				<!-- BDP: scroll_next -->
				<a class="icon i_next" href="ticket_system.php?psi={NEXT_PSI}" title="next">next</a>
				<!-- EDP: scroll_next -->
				<!-- BDP: scroll_prev_gray -->
				<a class="icon i_prev_gray" href="#" title="next">next</a>
				<!-- EDP: scroll_prev_gray -->
				<!-- BDP: scroll_prev -->
				<a class="icon i_prev" href="ticket_system.php?psi={PREV_PSI}" title="previous">previous</a>
				<!-- EDP: scroll_prev -->
			</div>
		<!-- EDP: tickets_list -->

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
