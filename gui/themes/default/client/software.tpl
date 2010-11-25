<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="X-UA-Compatible" content="IE=8" />
		<title>{TR_CLIENT_SOFTWARE_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
		<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltips.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
         <![endif]-->
        <script language="JavaScript" type="text/JavaScript">
        /*<![CDATA[*/
			$(document).ready(function(){
				// Tooltips - begin
				$('a.i_app_installer').sw_iMSCPtooltips('a.title');
				// Tooltips - end
			});
			function action_delete(url) {
				if (!confirm("{TR_MESSAGE_DELETE}"))
				return false;
				location = url;
			}
			function action_install(url) {
				if (!confirm("{TR_MESSAGE_INSTALL}"))
				return false;
				location = url;
			}
			function action_res_delete(url) {
				if (!confirm("{TR_RES_MESSAGE_DELETE}"))
				return false;
				location = url;
			}
		/*]]>*/
		</script>
	</head>

	<body>
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
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
				<li><a href="software.php">{TR_SOFTWARE_MENU_PATH}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<!-- BDP: page_message -->
			<div class="warning">{MESSAGE}</div>
			<!-- EDP: page_message -->
			
			<h2 class="apps_installer"><span>{TR_INSTALL_SOFTWARE}</span></h2>
			<table>
				<tr>
					<th><div style="float:left">{TR_SOFTWARE}</div><div style="float:left"><a href="{TR_SOFTWARE_ASC}" class="app_icon i_app_asc"></a><a href="{TR_SOFTWARE_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th align="center" width="70">{TR_VERSION}</th>
					<th align="center" width="100"><div style="float:left">{TR_LANGUAGE}</div><div style="float:left"><a href="{TR_LANGUAGE_ASC}" class="app_icon i_app_asc"></a><a href="{TR_LANGUAGE_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th align="center" width="70"><div style="float:left">{TR_TYPE}</div><div style="float:left"><a href="{TR_TYPE_ASC}" class="app_icon i_app_asc"></a><a href="{TR_TYPE_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th align="center" width="100"><div style="float:left">{TR_NEED_DATABASE}</div><div style="float:left"><a href="{TR_NEED_DATABASE_ASC}" class="app_icon i_app_asc"></a><a href="{TR_NEED_DATABASE_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th align="center" width="90">{TR_STATUS}</th>
					<th align="center" width="150">{TR_ACTION}</th>
				</tr>
				<!-- BDP: t_software_support -->
				<!-- BDP: software_item -->
				<tr>
					<td><a href="{VIEW_SOFTWARE_SCRIPT}" class="icon i_app_installer" title="{SOFTWARE_DESCRIPTION}">{SOFTWARE_NAME}</a></td>
					<td align="center">{SOFTWARE_VERSION}</td>
					<td align="center">{SOFTWARE_LANGUAGE}</td>
					<td align="center">{SOFTWARE_TYPE}</td>
					<td align="center">{SOFTWARE_NEED_DATABASE}</td>
					<td align="center">{SOFTWARE_STATUS}</td>
					<td align="center"><a href="#" class="icon i_{SOFTWARE_ICON}" <!-- BDP: software_action_delete -->  onClick="return action_delete('{SOFTWARE_ACTION_SCRIPT}')" <!-- EDP: software_action_delete --><!-- BDP: software_action_install -->  onClick="return action_install('{SOFTWARE_ACTION_SCRIPT}')" <!-- EDP: software_action_install --> >{SOFTWARE_ACTION}</a></td>
				</tr>
				<!-- EDP: software_item -->
				<!-- EDP: t_software_support -->
				<!-- BDP: no_software_support -->
				<tr>
					<td colspan="7"><div class="warning">{NO_SOFTWARE_AVAIL}</div></td>
				</tr>
				<!-- EDP: no_software_support -->
				<!-- BDP: software_total -->
				<tr>
					<th colspan="7">{TR_SOFTWARE_AVAILABLE}:&nbsp;{TOTAL_SOFTWARE_AVAILABLE}</th>
				</tr>
				<!-- EDP: software_total -->
				<!-- BDP: del_software_support -->
				<tr>
					<th colspan="5">{TR_DEL_SOFTWARE}</th>
					<th align="center" width="150">{TR_DEL_STATUS}</th>
					<th align="center" width="150">{TR_DEL_ACTION}</th>
				</tr>
				<!-- BDP: del_software_item -->
				<tr>
					<td colspan="5">{SOFTWARE_DEL_RES_MESSAGE}</td>
					<td align="center" width="150">{DEL_SOFTWARE_STATUS}</td>
					<td align="center" width="150"><img src="{THEME_COLOR_PATH}/images/icons/delete.png" width="16" height="16" border="0" align="middle" /> <a href="#" onClick="return action_res_delete('{DEL_SOFTWARE_ACTION_SCRIPT}')">{DEL_SOFTWARE_ACTION}</a></td>
				</tr>
				<!-- EDP: del_software_item -->
				<!-- EDP: del_software_support -->
			</table>			
		</div>

		<div class="footer">
			i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
		</div>

	</body>
</html>