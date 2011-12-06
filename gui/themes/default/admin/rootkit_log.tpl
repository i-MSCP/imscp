<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="system_info.php">{TR_MENU_SYSTEM_TOOLS}</h1>
            </div>
            <ul class="location-menu">
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="general"><span>{TR_ROOTKIT_LOG}</span></h2>

            <!-- BDP: props_list -->
            <table>
                <tr>
                    <th>{FILENAME}:</th>
                </tr>
                <tr>
                    <td>{LOG}</td>
                </tr>
            </table>
            <!-- EDP: props_list -->
            
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
