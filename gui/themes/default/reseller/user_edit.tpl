<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_USER_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
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
				<li><a href="manage_users.php">{TR_MANAGE_USERS}</a></li>
				<li>{TR_EDIT_USER}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="general"><span>{TR_MANAGE_USERS}</span></h2>

			<form name="search_user" method="post" action="user_edit.php">
				<fieldset>
					<legend>{TR_CORE_DATA}</legend>
					<table>
						<tr>
							<td>{TR_USERNAME}</td>
							<td>{VL_USERNAME}</td>
						</tr>
						<tr>
							<td><label for="userpassword">{TR_PASSWORD}</label></td>
							<td>
								<input type="password" name="userpassword" id="userpassword" value="{VAL_PASSWORD}"/>
								<input name="genpass" type="submit" value=" {TR_PASSWORD_GENERATE} " />
							</td>
						</tr>
						<tr>
							<td><label for="userpassword_repeat">{TR_REP_PASSWORD}</label></td>
							<td><input type="password" name="userpassword_repeat" id="userpassword_repeat" value="{VAL_PASSWORD}"/></td>
						</tr>
						<tr>
							<td><label for="useremail">{TR_USREMAIL}</label></td>
							<td><input type="text" name="useremail" id="useremail" value="{VL_MAIL}"/></td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_ADDITIONAL_DATA}</legend>
					<table>
						<tr>
							<td><label for="useruid">{TR_CUSTOMER_ID}</label></td>
							<td><input type="text" name="useruid" id="useruid" value="{VL_USR_ID}"/></td>
						</tr>
						<tr>
							<td><label for="userfname">{TR_FIRSTNAME}</label></td>
							<td><input type="text" name="userfname" id="userfname" value="{VL_USR_NAME}"/></td>
						</tr>
						<tr>
							<td><label for="userlname">{TR_LASTNAME}</label></td>
							<td><input type="text" name="userlname" id="userlname" value="{VL_LAST_USRNAME}"/></td>
						</tr>
						<tr>
							<td><label for="gender">{TR_GENDER}</label></td>
							<td>
								<select id="gender" name="gender">
									<option value="M" {VL_MALE}>{TR_MALE}</option>
									<option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="userfirm">{TR_COMPANY}</label></td>
							<td><input type="text" name="userfirm" id="userfirm" value="{VL_USR_FIRM}" /></td>
						</tr>
						<tr>
							<td><label for="userstreet1">{TR_STREET1}</label></td>
							<td><input type="text" name="userstreet1" id="userstreet1" value="{VL_STREET1}" /></td>
						</tr>
						<tr>
							<td><label for="userzip">{TR_POST_CODE}</label></td>
							<td><input type="text" name="userzip" id="userzip" value="{VL_USR_POSTCODE}" /></td>
						</tr>
						<tr>
							<td><label for="usercity">{TR_CITY}</label></td>
							<td><input type="text" name="usercity" id="usercity" value="{VL_USRCITY}" /></td>
						</tr>
						<tr>
							<td><label for="useratate">{TR_STATE}</label></td>
							<td><input type="text" name="userstate" id="userstate" value="{VL_USRSTATE}" /></td>
						</tr>
						<tr>
							<td><label for="usercountry">{TR_COUNTRY}</label></td>
							<td><input type="text" name="usercountry" id="usercountry" value="{VL_COUNTRY}" /></td>
						</tr>
						<tr>
							<td><label for="userphone">{TR_PHONE}</label></td>
							<td><input type="text" name="userphone" id="userphone" value="{VL_PHONE}" /></td>
						</tr>
						<tr>
							<td><label for="userfax">{TR_FAX}</label></td>
							<td><input type="text" name="userfax" id="userfax" value="{VL_FAX}" /></td>
						</tr>
					</table>
					<div class="buttons">
						<input name="Submit" type="submit" class="button" value="{TR_BTN_ADD_USER}"/>
						<input type="checkbox" id="send_data" name="send_data" checked="checked" /><label for="send_data">{TR_SEND_DATA}</label>
					</div>
				</fieldset>
				<input type="hidden" name="uaction" value="save_changes" />
				<input type="hidden" name="edit_id" value="{EDIT_ID}" />
			</form>
		</div>
		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>
	</body>
</html>
