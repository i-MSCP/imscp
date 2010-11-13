<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_EDIT_ALIAS_PAGE_TITLE}</title>
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
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="alias.php">{TR_MENU_DOMAIN_ALIAS}</a></li>
				<li>{TR_EDIT_ALIAS} {ALIAS_NAME}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<div id="fwd_help" class="tooltip">{TR_FWD_HELP}</div>

			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<h2 class="domains"><span>{TR_MANAGE_DOMAIN_ALIAS}</span></h2>

			<form name="edit_alias_frm" method="post" action="alias_edit.php?edit_id={ID}">
				<fieldset>
					<legend>{TR_EDIT_ALIAS}</legend>
					<table>
						<tr>
							<td>{TR_ALIAS_NAME}</td>
							<td>{ALIAS_NAME}</td>
						</tr>
						<tr>
							<td>{TR_DOMAIN_IP}</td>
							<td>{DOMAIN_IP}</td>
						</tr>
						<tr>
							<td>{TR_ENABLE_FWD}</td>
							<td>
								<input type="radio" name="status" id="status_enable" {CHECK_EN} value="1" /><label for="status_enable">{TR_ENABLE}</label><br />
								<input type="radio" name="status" id="status_disable" {CHECK_DIS} value="0" /><label for="status_disable">{TR_DISABLE}</label><br />
							</td>
						</tr>
						<tr>
							<td><label for="forward">{TR_FORWARD}</label><span class="icon i_help" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" >Help</span></td>
							<td><input name="forward" type="text" id="forward" value="{FORWARD}" /></td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_MODIFY}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','alias.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
				<input type="hidden" name="uaction" value="modify" />
			</form>

		</div>

	</body>
</html>
<!--
					  <tr>
						<td width="25">&nbsp;</td>
						<td width="200" class="content2" style="vertical-align:top;"></td>
						<td class="content">
							<br />
							<input type="radio" name="status" {CHECK_DIS} value="0" /> {}
						</td>
					  </tr>
					  <tr>
						<td width="25">&nbsp;</td>
						<td width="200" class="content2">
						 {TR_FORWARD} <img src="{THEME_COLOR_PATH}/images/icons/help.png" width="16" height="16" onmouseover="showTip('fwd_help', event)" onmouseout="hideTip('fwd_help')" />
						</td>
						<td class="content"><input name="forward" type="text" class="textinput" id="forward" style="width:210px" value="{FORWARD}" /></td>
					  </tr>
					  <tr>
						<td width="25">&nbsp;</td>
						<td colspan="2"><input name="Submit" type="submit" class="button" value="  {TR_MODIFY}  " />
						  &nbsp;&nbsp;&nbsp;
						  </td>
					  </tr>
					</table>

				</form></td>
				<td>&nbsp;</td>
			  </tr>
			  <tr>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			  </tr>
			</table></td>
		  </tr>
		</table>
	  </td>
	</tr>
</table>
</body>
</html>
 -->