<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="settings">{TR_MENU_SETTINGS}</h1>
            </div>
            <ul class="location-menu">
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
				<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
                <li><a href="ip_manage.php">{TR_SERVER_TRAFFIC_SETTINGS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
	        <h2 class="settings"><span>{TR_SERVER_TRAFFIC_SETTINGS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

	    	<form action="settings_server_traffic.php" method="post" name="admin_modify_server_traffic_settings" id="admin_modify_server_traffic_settings">
		    		<table>
						<tr>
							<th colspan="2">
								{TR_SET_SERVER_TRAFFIC_SETTINGS}
							</th>
						</tr>
						<tr>
			    			<td>
                    			<label for="max_traffic">{TR_MAX_TRAFFIC}</label>
                			</td>
			    			<td>
								<input name="max_traffic" type="text" id="max_traffic" value="{MAX_TRAFFIC}" /> {TR_MIB}
			    			</td>
						</tr>
						<tr>
			    			<td><label for="traffic_warning">{TR_WARNING}</label></td>
			    			<td><input name="traffic_warning" type="text" id="traffic_warning" value="{TRAFFIC_WARNING}" /> {TR_MIB}</td>
						</tr>
		   	 		</table>
				<div class="buttons">
		    		<input name="Submit" type="submit" value="{TR_MODIFY}" />
		    		<input type="hidden" name="uaction" value="modify" />
				</div>
			</form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
