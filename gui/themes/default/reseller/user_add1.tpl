<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_CHANGE_PERSONAL_DATA_PAGE_TITLE}</title>
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
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="user_add1.php">{TR_ADD_USER}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<div id="dmn_help" class="tooltip">{TR_DMN_HELP}</div>

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="general"><span>{TR_ADD_USER}</span></h2>
			<!-- BDP: add_form -->
			<form name="reseller_add_users_first_frm" method="post" action="user_add1.php">
				<fieldset>
					<legend>{TR_CORE_DATA}</legend>
					<table>
						<tr>
							<td>{TR_DOMAIN_NAME} <span class="icon i_help" onmouseover="showTip('dmn_help', event)" onmouseout="hideTip('dmn_help')" >Help</span></td>
							<td><input type="text" name="dmn_name" value="{DMN_NAME_VALUE}"/></td>
						</tr>
						<!-- BDP: expire -->
							<tr>
								<td><label for="dmn_expire">{TR_DOMAIN_EXPIRE}</label></td>
								<td>
									<select id="dmn_expire" name="dmn_expire">
										<option value="0">{TR_DOMAIN_EXPIRE_NEVER}</option>
										<option value="1">{TR_DOMAIN_EXPIRE_1_MONTH}</option>
										<option value="2">{TR_DOMAIN_EXPIRE_2_MONTHS}</option>
										<option value="3">{TR_DOMAIN_EXPIRE_3_MONTHS}</option>
										<option value="6">{TR_DOMAIN_EXPIRE_6_MONTHS}</option>
										<option value="12">{TR_DOMAIN_EXPIRE_1_YEAR}</option>
										<option value="24">{TR_DOMAIN_EXPIRE_2_YEARS}</option>
									</select>
								</td>
							</tr>
						<!-- BDP: add_user -->
							<tr>
								<td>{TR_CHOOSE_HOSTING_PLAN}</td>
								<td>
									<select id="dmn_tpl" name="dmn_tpl">
										<!-- BDP: hp_entry -->
											<option value="{CHN}"{CH{CHN}}>{HP_NAME}</option>
										<!-- EDP: hp_entry -->
									</select>
								</td>
							</tr>
							<!-- BDP: personalize -->
								<tr>
									<td>{TR_PERSONALIZE_TEMPLATE}</td>
									<td>
										<input type="radio" id="chtpl_yes" name="chtpl" value="_yes_" {CHTPL1_VAL} /><label for="chtpl_yes">{TR_YES}</label>
										<input type="radio" id="chtpl_no" name="chtpl" value="_no_" {CHTPL2_VAL} /><label for="chtpl_no">{TR_NO}</label>
									</td>
								</tr>
							<!-- EDP: personalize -->
						<!-- EDP: add_user -->
					</table>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_NEXT_STEP}" />
				</div>
				<input type="hidden" name="uaction" value="user_add_nxt" />
			</form>
			<!-- EDP: add_form -->
		</div>
		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>