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
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
                <li><a href="sessions_manage.php">{TR_MANAGE_USER_SESSIONS}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="users2"><span>{TR_MANAGE_USER_SESSIONS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <table>
                <tr>
                    <th style="width:300px;">{TR_USERNAME}</th>
                    <th>{TR_LOGIN_ON}</th>
                    <th>{TR_OPTIONS}</th>
                </tr>
                <!-- BDP: user_session -->
                <tr>
                    <td>{ADMIN_USERNAME}</td>
                    <td>{LOGIN_TIME}</td>
                    <td><a href="{KILL_LINK}" class="icon i_delete">{TR_DELETE}</a></td>
                </tr>
                <!-- EDP: user_session -->
            </table>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
