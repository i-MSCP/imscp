<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_MANAGE_SOFTWARE_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
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
                <li><a href="software_delete.php?id={SOFTWARE_ID}">{TR_DELETE_RESELLER_SOFTWARE}</a></li>

            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
        
        	<!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->
         
             <h2 class="apps_installer"><span>{TR_DELETE_SOFTWARE}</span></h2>
             <table>
            	<tr>
					<td>
						<form name="admin_delete_email" method="post" action="software_delete.php">
							<table>
	                            <tr>
	                            	<td colspan="2">{TR_DELETE_DATA}</td>
	                            </tr>
	                            <tr>
	                              	<td>{TR_DELETE_SEND_TO}</td>
	                              	<td>{DELETE_SOFTWARE_RESELLER}</td>
                            	</tr>
                            	<tr>
                              		<td style="width:200px; vertical-align:top;">{TR_DELETE_MESSAGE_TEXT}</td>
                              		<td><textarea name="delete_msg_text" style="width:80%" cols="80" rows="20">{DELETE_MESSAGE_TEXT}</textarea></td>
                            	</tr>
                            	<tr>
                              		<td colspan="2">
                              			<div class="buttons">
                              				<input name="Submit" type="submit" class="button" value="{TR_SEND_MESSAGE}" />
                              				<input type="hidden" name="uaction" value="send_delmessage" />
											<input type="hidden" name="id" value="{SOFTWARE_ID}" />
											<input type="hidden" name="reseller_id" value="{RESELLER_ID}" />
                              			</div>
                              		</td>
                            	</tr>
                        	</table>
						</form>					
					</td>
				</tr>
			</table>
		</div>

        <div class="footer">
            i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    </body>
</html>