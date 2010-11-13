<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_USER_ASSIGN}</title>
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
				<li><a href="protected_user_manage.php">{TR_HTACCESS_USER}</a></li>
				<li>{TR_USER_ASSIGN}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="users"><span>{TR_USER_ASSIGN}</span></h2>

			<form name="puser_assign" method="post" action="protected_user_assign.php?uname={UNAME}">
				<fieldset>
					<legend>{UNAME}</legend>
				</fieldset>
				<table>
					<!-- BDP: in_group -->
						<tr>
							<td>{TR_MEMBER_OF_GROUP}</td>
							<td>
								<select name="groups_in">
						  			<!-- BDP: already_in -->
						  				<option value="{GRP_IN_ID}">{GRP_IN}</option>
									<!-- EDP: already_in -->
								</select>
					  		</td>
					  		<td><!-- BDP: remove_button --><input name="Submit" type="submit"  value="{TR_REMOVE}" onclick="return sbmt(document.forms[0],'remove');" /><!-- EDP: remove_button --></td>
						</tr>
					<!-- EDP: in_group -->
					<!-- BDP: not_in_group -->
						<tr>
							<td>{TR_SELECT_GROUP}</td>
							<td>
								<select name="groups">
									<!-- BDP: grp_avlb -->
										<option value="{GRP_ID}">{GRP_NAME}</option>
									<!-- EDP: grp_avlb -->
								</select>
							</td>
							<td><!-- BDP: add_button --> <input name="Submit" type="submit"  value="{TR_ADD}" onclick="return sbmt(document.forms[0],'add');" /> <!-- EDP: add_button --></td>
						</tr>
					<!-- EDP: not_in_group -->
				</table>

				<div class="buttons">
					<input name="Submit" type="submit"  value="{TR_BACK}" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" />
				</div>

				<input type="hidden" name="nadmin_name" value="{UID}" />
				<input type="hidden" name="uaction" value="" />
			</form>

		</div>

		<div class="footer">
			ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>
