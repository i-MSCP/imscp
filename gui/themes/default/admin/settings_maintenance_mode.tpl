<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>

        <div class="location">
            <div class="location-area">
                <h1 class="webtools">{TR_MENU_SYSTEM_TOOLS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="system_info.php">{TR_MENU_SYSTEM_TOOLS}</a></li>
                <li><a href="settings_maintenance_mode.php">{TR_MAINTENANCEMODE}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="maintenancemode"><span>{TR_MAINTENANCEMODE}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <form action="settings_maintenance_mode.php" method="post" name="maintenancemode_frm" id="maintenancemode_frm">
                <table>
					<tr>
						<th colspan="2">{TR_MAINTENANCE_MESSAGE}</th>
					</tr>
                    <tr>
                        <td style="vertical-align: top;">
                            <label for="maintenancemode_message">{TR_MESSAGE}</label>
                        </td>
                        <td><textarea name="maintenancemode_message" id="maintenancemode_message">{MESSAGE_VALUE}</textarea></td>
                    </tr>
                    <tr>
                        <td><label for="maintenancemode">{TR_MAINTENANCEMODE}</label></td>
                        <td>
							<select name="maintenancemode" id="maintenancemode">
                                <option value="0" {SELECTED_OFF}>{TR_DISABLED}</option>
                                <option value="1" {SELECTED_ON}>{TR_ENABLED}</option>
                        	</select>
						</td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
                    <input type="hidden" name="uaction" value="apply" />
                </div>
            </form>
            <div class="info">{TR_MESSAGE_TEMPLATE_INFO}</div>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
