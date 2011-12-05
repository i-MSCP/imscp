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
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
                <li><a>{TR_DOMAIN_DETAILS}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="general"><span>{TR_DOMAIN_DETAILS}</span></h2>
            <table>
                <tr>
                    <td style="width: 300px;">{TR_DOMAIN_NAME}</td>
                    <td>{VL_DOMAIN_NAME}</td>
                </tr>
                <tr>
                    <td>{TR_DOMAIN_IP}</td>
                    <td>{VL_DOMAIN_IP}</td>
                </tr>
                <tr>
                    <td>{TR_STATUS}</td>
                    <td>{VL_STATUS}</td>
                </tr>
                <tr>
                    <td>{TR_PHP_SUPP}</td>
                    <td>{VL_PHP_SUPP}</td>
                </tr>
                <tr>
                    <td>{TR_CGI_SUPP}</td>
                    <td>{VL_CGI_SUPP}</td>
                </tr>
                <tr>
                    <td>{TR_DNS_SUPP}</td>
                    <td>{VL_DNS_SUPP}</td>
                </tr>
                <tr>
                    <td>{TR_SOFTWARE_SUPP}</td>
                    <td>{VL_SOFTWARE_SUPP}</td>
                </tr>
                <tr>
                    <td>{TR_MYSQL_SUPP}</td>
                    <td>{VL_MYSQL_SUPP}</td>
                </tr>
                <tr>
                    <td>{TR_TRAFFIC}</td>
                    <td>
                        <div class="graph"><span style="width:{VL_TRAFFIC_PERCENT}%">&nbsp;</span>
                        </div>
                    {VL_TRAFFIC_USED} / {VL_TRAFFIC_LIMIT}
                    </td>
                </tr>
                <tr>
                    <td>{TR_DISK}</td>
                    <td>
                        <div class="graph"><span style="width:{VL_DISK_PERCENT}%">&nbsp;</span>
                        </div>
                    {VL_DISK_USED} / {VL_DISK_LIMIT}
                    </td>
                </tr>
            </table>
            <table>
                <tr>
                    <th style="width: 300px;">{TR_FEATURE}</th>
                    <th>{TR_USED}</th>
                    <th>{TR_LIMIT}</th>
                </tr>
                <tr>
                    <td>{TR_MAIL_ACCOUNTS}</td>
                    <td>{VL_MAIL_ACCOUNTS_USED}</td>
                    <td>{VL_MAIL_ACCOUNTS_LIIT}</td>
                </tr>
                <tr>
                    <td>{TR_FTP_ACCOUNTS}</td>
                    <td>{VL_FTP_ACCOUNTS_USED}</td>
                    <td>{VL_FTP_ACCOUNTS_LIIT}</td>
                </tr>
                <tr>
                    <td>{TR_SQL_DB_ACCOUNTS}</td>
                    <td>{VL_SQL_DB_ACCOUNTS_USED}</td>
                    <td>{VL_SQL_DB_ACCOUNTS_LIIT}</td>
                </tr>
                <tr>
                    <td>{TR_SQL_USER_ACCOUNTS}</td>
                    <td>{VL_SQL_USER_ACCOUNTS_USED}</td>
                    <td>{VL_SQL_USER_ACCOUNTS_LIIT}</td>
                </tr>
                <tr>
                    <td>{TR_SUBDOM_ACCOUNTS}</td>
                    <td>{VL_SUBDOM_ACCOUNTS_USED}</td>
                    <td>{VL_SUBDOM_ACCOUNTS_LIIT}</td>
                </tr>
                <tr>
                    <td>{TR_DOMALIAS_ACCOUNTS}</td>
                    <td>{VL_DOMALIAS_ACCOUNTS_USED}</td>
                    <td>{VL_DOMALIAS_ACCOUNTS_LIIT}</td>
                </tr>
            </table>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
