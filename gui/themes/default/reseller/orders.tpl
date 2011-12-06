<!-- INCLUDE "../shared/layout/header.tpl" -->
        <script type="text/javascript">
            /* <![CDATA[ */
            function delete_order(url, subject) {
                if (!confirm(sprintf("{TR_MESSAGE_DELETE_ACCOUNT}", subject))) {
                    return false;
                }
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
                <h1 class="purchasing">{TR_MENU_ORDERS}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
                <li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="orders.php">{TR_MENU_ORDERS}</a></li>
                <li><a href="orders.php">{TR_MENU_OVERVIEW}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="billing"><span>{TR_MANAGE_ORDERS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: props_list -->
            <!-- BDP: orders_table -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_ID}</th>
                        <th>{TR_DOMAIN}</th>
                        <th>{TR_HP}</th>
                        <th>{TR_USER}</th>
                        <th>{TR_STATUS}</th>
                        <th>{TR_ACTION}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: order -->
                    <tr>
                        <td>{ID}</td>
                        <td>{DOMAIN}</td>
                        <td>{HP}</td>
                        <td>{USER}v</td>
                        <td>{STATUS}</td>
                        <td>
                            <a href="{LINK}" class="icon i_add_user">{TR_ADD}</a>
                            <a href="#" onclick="delete_order('orders_delete.php?order_id={ID}', '{DOMAIN}')" class="icon i_delete">{TR_DELETE}</a>
                        </td>
                    </tr>
                    <!-- EDP: order -->
                </tbody>
            </table>
            <!-- EDP: orders_table -->
            <div class="paginator">
                <!-- BDP: scroll_next_gray -->
                <a class="icon i_next_gray" href="#">&nbsp;</a>
                <!-- EDP: scroll_next_gray -->

                <!-- BDP: scroll_next -->
                <a class="icon i_next" href="orders.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
                <!-- EDP: scroll_next -->

                <!-- BDP: scroll_prev -->
                <a class="icon i_prev" href="orders.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
                <!-- EDP: scroll_prev -->

                <!-- BDP: scroll_prev_gray -->
                <a class="icon i_prev_gray" href="#">&nbsp;</a>
                <!-- EDP: scroll_prev_gray -->
            </div>
            <!-- EDP: props_list -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
