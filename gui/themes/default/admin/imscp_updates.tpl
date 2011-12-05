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
                <li><a href="imscp_updates.php">{TR_UPDATES_TITLE}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="update"><span>{TR_UPDATES_TITLE}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: props_list -->
            <table class="description">
                <!-- BDP: table_header -->
                <tr>
                    <th colspan="2">{TR_AVAILABLE_UPDATES}</th>
                </tr>
                <!-- EDP: table_header -->
                <!-- BDP: update_message -->
                <tr>
					<td style="width:300px;">{UPDATE}</td>
                    <td>{TR_MESSAGE}</td>
                </tr>
               <!-- EDP: update_message -->
                <!-- BDP: update_infos -->
                <tr>
                    <td style="width:300px;">{UPDATE}</td>
                    <td>{INFOS}</td>
                </tr>
                <!-- EDP: update_infos -->
            </table>
            <!-- EDP: props_list -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
