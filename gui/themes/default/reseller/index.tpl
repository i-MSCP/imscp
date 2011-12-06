<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="general">{GENERAL_INFO}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
                <li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="#" onclick="return false;">{GENERAL_INFO}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="general"><span>{GENERAL_INFO}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <table>
				<tr>
					<th colspan="2">{TR_ACCOUNT_OVERVIEW}</th>
				</tr>
                <tr>
                    <td>{ACCOUNT_NAME}</td>
                    <td>{RESELLER_NAME}</td>
                </tr>
                <tr>
                    <th colspan="2">{TR_ACCOUNT_LIMITS}</th>
                </tr>
                <tr>
                    <td style="width: 300px;">{DOMAINS}</td>
                    <td>{DMN_MSG}</td>
                </tr>
                <tr>
                    <td>{SUBDOMAINS}</td>
                    <td>{SUB_MSG}</td>
                </tr>
                <tr>
                    <td>{ALIASES}</td>
                    <td>{ALS_MSG}</td>
                </tr>
                <tr>
                    <td>{MAIL_ACCOUNTS}</td>
                    <td>{MAIL_MSG}</td>
                </tr>
                <tr>
                    <td>{TR_FTP_ACCOUNTS}</td>
                    <td>{FTP_MSG}</td>
                </tr>
                <tr>
                    <td>{SQL_DATABASES}</td>
                    <td>{SQL_DB_MSG}</td>
                </tr>
                <tr>
                    <td>{SQL_USERS}</td>
                    <td>{SQL_USER_MSG}</td>
                </tr>
				<tr>
					<th colspan="2">
						{TR_FEATURES}
					</th>
				</tr>
				<tr>
					<td>{TR_SUPPORT}</td>
					<td>{SUPPORT_STATUS}</td>
				</tr>
				<tr>
					<td>{TR_PHP_EDITOR}</td>
					<td>{PHP_EDITOR_STATUS}</td>
				</tr>
                <tr>
                    <td>{TR_APS}</td>
                    <td>{APS_STATUS}</td>
                </tr>
            </table>

            <h2 class="traffic"><span>{TR_TRAFFIC_USAGE}</span></h2>

            <!-- BDP: traffic_warning_message -->
            <div class="warning">{TR_TRAFFIC_WARNING}</div>
            <!-- EDP: traffic_warning_message -->

            <p>{TRAFFIC_USAGE_DATA}</p>

            <div class="graph">
                <span style="width:{TRAFFIC_PERCENT}%">&nbsp;</span>
            </div>

            <h2 class="diskusage"><span>{TR_DISK_USAGE}</span></h2>

            <!-- BDP: disk_warning_message -->
            <div class="warning">{TR_DISK_WARNING}</div>
            <!-- EDP: disk_warning_message -->

            <p>{DISK_USAGE_DATA}</p>
            <div class="graph">
                <span style="width:{DISK_PERCENT}%">&nbsp;</span>
            </div>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->