<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_NEW_TICKET_PAGE_TITLE}</title>
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
			   	<li><a href="{SUPPORT_SYSTEM_PATH}">{TR_MENU_QUESTIONS_AND_COMMENTS}</a></li>
				<li><a href="ticket_create.php">{TR_NEW_TICKET}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="doc"><span>{TR_NEW_TICKET}</span></h2>
			<form name="question_frm" method="post" action="ticket_create.php">
					<table>
						<tbody>
							<tr>
								<td><label for="urgency">{TR_URGENCY}</label></td>
								<td><select id="urgency" name="urgency">
									  <option value="1" {OPT_URGENCY_1}>{TR_LOW}</option>
									  <option value="2" {OPT_URGENCY_2}>{TR_MEDIUM}</option>
									  <option value="3" {OPT_URGENCY_3}>{TR_HIGH}</option>
									  <option value="4" {OPT_URGENCY_4}>{TR_VERI_HIGH}</option>
									</select>
								</td>
							</tr>
							<tr>
								<td><label for="subj">{TR_SUBJECT}</label></td>
								<td><input type="text" id="subj" name="subj" value="{SUBJECT}" /></td>
							</tr>
							<tr>
								<td><label for="user_message">{TR_YOUR_MESSAGE}</label></td>
								<td><textarea name="user_message" cols="80" rows="12">{USER_MESSAGE}</textarea></td>
							</tr>
						</tbody>
					</table>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_SEND_MESSAGE}" />
				</div>
				<input name="uaction" type="hidden" value="send_msg" />
			</form>
		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>