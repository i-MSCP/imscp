<!-- INCLUDE "../shared/layout/header.tpl" -->
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject)))
                    return false;
                location = url;
            }
            /* ]]> */
        </script>
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="hosting_plans">{TR_MENU_HOSTING_PLANS}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
                <li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="hosting_plan.php">{TR_MENU_HOSTING_PLANS}</a></li>
                <li><a href="hosting_plan.php">{TR_MENU_OVERVIEW}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="hosting_plans"><span>{TR_HOSTING_PLANS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: hp_table -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_NOM}</th>
                        <th>{TR_PLAN_NAME}</th>
                        <th>{TR_PURCHASING}</th>
                        <th>{TR_ACTION}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: hp_entry -->
                    <tr>
                        <td>{PLAN_NOM}</td>
                        <td>
                            <a href="../orderpanel/package_info.php?coid={CUSTOM_ORDERPANEL_ID}&amp;user_id={RESELLER_ID}&amp;id={HP_ID}" target="_blank" title="{PLAN_SHOW}">{PLAN_NAME}</a>
                        </td>
                        <td>{PURCHASING}</td>
                        <td>
                            <a href="hosting_plan_edit.php?hpid={HP_ID}" class="icon i_edit">{TR_EDIT}</a>
                            <!-- BDP: hp_delete -->
                            <a href="#" onclick="return action_delete('hosting_plan_delete.php?hpid={HP_ID}', '{PLAN_NAME2}')" class="icon i_delete">{PLAN_ACTION}</a>
                            <!-- EDP: hp_delete -->
                        </td>
                    </tr>
                    <!-- EDP: hp_entry -->
                </tbody>
            </table>
            <!-- EDP: hp_table -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
