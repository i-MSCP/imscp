<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>

        <div class="location">
            <div class="location-area">
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
             <h2 class="apps_installer"><span>{TR_DELETE_SOFTWARE}</span></h2>
        	<!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->
         

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
<!-- INCLUDE "../shared/layout/footer.tpl" -->
