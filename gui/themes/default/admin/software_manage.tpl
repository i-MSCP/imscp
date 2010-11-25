<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_SOFTWARE_PAGE_TITLE}</title>
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
				$('span.i_app_installer').sw_iMSCPtooltips('span.title');
				// Tooltips - end
			});
			function action_delete() {
				if (!confirm("{TR_MESSAGE_DELETE}"))
				return false;
			}
			function action_activate() {
				if (!confirm("{TR_MESSAGE_ACTIVATE}"))
				return false;
			}
			function action_import() {
				if (!confirm("{TR_MESSAGE_IMPORT}"))
				return false;
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
			<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
		</div>
		<ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="software_manage.php">{TR_MENU_MANAGE_SOFTWARE}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->
            
            <h2 class="apps_installer"><span>{TR_UPLOAD_SOFTWARE}</span></h2>
            <table>
            	<tr>
					<td>
						<form action="software_manage.php" method="post" enctype="multipart/form-data">
							<table>
								<tr>
									<td width="200">{TR_SOFTWARE_FILE}</td>
									<td><input type="file" name="sw_file" size="60" /></td>
								</tr>
								<tr>
									<td width="200">{TR_SOFTWARE_URL}</td>
									<td><input type="text" name="sw_wget" value="{VAL_WGET}" size="60" /></td>
								</tr>
								<tr>
									<td colspan="2">
										<div class="buttons">
											<input name="upload" type="submit" class="button" value="{TR_UPLOAD_SOFTWARE_BUTTON}" />
											<input type="hidden" name="send_software_upload_token" id="send_software_upload_token" value="{SOFTWARE_UPLOAD_TOKEN}" />
										</div>
									</td>
								</tr>
							</table>
						</form>
					</td>
				</tr>
				</table>
				<table>
				<tr>
					<th>{TR_SOFTWARE_NAME}</th>
					<th width="60">{TR_SOFTWARE_VERSION}</td>
					<th width="60">{TR_SOFTWARE_LANGUAGE}</td>
					<th width="60">{TR_SOFTWARE_TYPE}</td>
					<th width="120">{TR_SOFTWARE_ADMIN}</td>
					<th align="center" width="65">{TR_SOFTWARE_DOWNLOAD}</td>
					<th align="center" width="65">{TR_SOFTWARE_DELETE}</td>
					<th align="center" width="90">{TR_SOFTWARE_RIGHTS}</td>
				</tr>
				<!-- BDP: no_softwaredepot_list -->
				<tr>
					<td colspan="8"><div class="warning">{NO_SOFTWAREDEPOT}</div></td>
				</tr>
				<!-- EDP: no_softwaredepot_list -->
				<!-- BDP: list_softwaredepot -->
				<tr>
					<td><span class="icon i_app_installer" title="{TR_TOOLTIP}">{TR_NAME}</span></td>
					<td>{TR_VERSION}</td>
					<td>{TR_LANGUAGE}</td>
					<td>{TR_TYPE}</td>
					<td>{TR_ADMIN}</td>
					<td align="center"><a target="_blank" class="icon i_app_download" href="{DOWNLOAD_LINK}">{TR_DOWNLOAD}</a></td>
					<td align="center"><a href="{DELETE_LINK}" class="icon i_delete" onClick="return action_delete()">{TR_DELETE}</a></td>
					<td align="center"><a href="{SOFTWARE_RIGHTS_LINK}" class="icon i_{SOFTWARE_ICON}">{RIGHTS_LINK}</a></td>
				</tr>
				<!-- EDP: list_softwaredepot -->
				<tr>
					<th colspan="8">{TR_SOFTWAREDEPOT_COUNT}:&nbsp;{TR_SOFTWAREDEPOT_NUM}</th>
				</tr>
			</table>
			<br />
			<h2 class="apps_installer"><span>{TR_AWAITING_ACTIVATION}</span></h2>
			<table>
				<tr>
					<th>{TR_SOFTWARE_NAME}</td>
					<th width="60">{TR_SOFTWARE_VERSION}</td>
					<th width="60">{TR_SOFTWARE_LANGUAGE}</td>
					<th width="60">{TR_SOFTWARE_TYPE}</td>
					<th width="120">{TR_SOFTWARE_RESELLER}</td>
					<th align="center" width="65">{TR_SOFTWARE_IMPORT}</td>
					<th align="center" width="65">{TR_SOFTWARE_DOWNLOAD}</td>
					<th align="center" width="65">{TR_SOFTWARE_ACTIVATION}</td>
					<th align="center" width="65">{TR_SOFTWARE_DELETE}</td>
				</tr>
				<!-- BDP: no_software_list -->
				<tr>
					<td colspan="9"><div class="warning">{NO_SOFTWARE}</div></td>
				</tr>
				<!-- EDP: no_software_list -->
				<!-- BDP: list_software -->
				<tr>
					<td><span class="icon i_app_installer" title="{TR_TOOLTIP}">{TR_NAME}</span></td>
					<td>{TR_VERSION}</td>
					<td>{TR_LANGUAGE}</td>
					<td>{TR_TYPE}</td>
					<td>{TR_RESELLER}</td>
					<td align="center"><a href="{IMPORT_LINK}" class="icon i_app_download" onClick="return action_import()">{TR_IMPORT}</a></td>
					<td align="center"><a href="{DOWNLOAD_LINK}" class="icon i_app_download" target="_blank">{TR_DOWNLOAD}</a></td>
					<td align="center"><a href="{ACTIVATE_LINK}" class="icon i_edit" onClick="return action_activate()">{TR_ACTIVATION}</a></td>
					<td align="center"><a href="{DELETE_LINK}" class="icon i_delete" onClick="return action_delete()">{TR_DELETE}</a></td>
				</tr>
				<!-- EDP: list_software -->
				<tr>
					<th colspan="9">{TR_SOFTWARE_ACT_COUNT}:&nbsp;{TR_SOFTWARE_ACT_NUM}</td>
				</tr>
			</table>
			<br />
			<h2 class="apps_installer"><span>{TR_ACTIVATED_SOFTWARE}</span></h2>
			<table>
				<tr>
					<th>{TR_RESELLER_NAME}</td>
					<th align="center" width="150">{TR_RESELLER_COUNT_SWDEPOT}</th>
					<th align="center" width="150">{TR_RESELLER_COUNT_WAITING}</th>
					<th align="center" width="150">{TR_RESELLER_COUNT_ACTIVATED}</th>
					<th align="center" width="150">{TR_RESELLER_SOFTWARE_IN_USE}</th>
				</tr>
				<!-- BDP: no_reseller_list -->
				<tr>
					<td colspan="5"><div class="warning">{NO_RESELLER}</div></td>
				</tr>
				<!-- EDP: no_reseller_list -->
				<!-- BDP: list_reseller -->
				<tr>
					<td>{RESELLER_NAME}</td>
					<td align="center" width="100">{RESELLER_COUNT_SWDEPOT}</td>
					<td align="center" width="100">{RESELLER_COUNT_WAITING}</td>
					<td align="center" width="100">{RESELLER_COUNT_ACTIVATED}</td>
					<td align="center" width="100"><a href="software_reseller.php?id={RESELLER_ID}">{RESELLER_SOFTWARE_IN_USE}</a></td>
				</tr>
				<!-- EDP: list_reseller -->
				<tr>
					<th colspan="5">{TR_RESELLER_ACT_COUNT}:&nbsp;{TR_RESELLER_ACT_NUM}</td>
				</tr>
			</table>
        	<div class="paginator">
                
            </div>

        </div>

    </body>
</html>