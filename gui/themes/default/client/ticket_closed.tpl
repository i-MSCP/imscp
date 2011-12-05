<!-- INCLUDE "../shared/layout/header.tpl" -->
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                if(subject == 'all') {
                    if(confirm("{TR_TICKETS_DELETE_ALL_MESSAGE}")) {
                        document.location.href = url;
                    }
                } else {
                    if(confirm(sprintf("{TR_TICKETS_DELETE_MESSAGE}", subject))) {
                        document.location.href = url;
                    }
                }

                return false;
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
                <h1 class="support">{TR_MENU_SUPPORT}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <!-- BDP: logged_from -->
                <li>
                    <a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
                </li>
                <!-- EDP: logged_from -->
                <li>
                    <a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="{SUPPORT_SYSTEM_PATH}">{TR_MENU_SUPPORT}</a></li>
                <li><a href="ticket_closed.php">{TR_LMENU_CLOSED_TICKETS}</a></li>
            </ul>
        </div>
        <div class="left_menu">
        {MENU}
        </div>
        <div class="body">
            <h2 class="support"><span>{TR_CLOSED_TICKETS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: tickets_list -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_TICKET_STATUS}</th>
                        <th>{TR_TICKET_FROM}</th>
                        <th>{TR_TICKET_SUBJECT}</th>
                        <th>{TR_TICKET_URGENCY}</th>
                        <th>{TR_TICKET_LAST_ANSWER_DATE}</th>
                        <th>{TR_TICKET_ACTION}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: tickets_item -->
                    <tr>
                        <td><strong>{TICKET_STATUS_VAL}</strong></td>
                        <td>{TICKET_FROM_VAL}</td>
                        <td>
                            <img src="{THEME_COLOR_PATH}/images/icons/document.png" style="vertical-align:middle;" alt="" />&nbsp;
                            <a href="ticket_view.php?ticket_id={TICKET_ID_VAL}"
                               class="link" title="{TR_TICKET_READ_LINK}">{TICKET_SUBJECT_VAL}</a>
                        </td>
                        <td>{TICKET_URGENCY_VAL}</td>
                        <td>{TICKET_LAST_DATE_VAL}</td>
                        <td>
                            <img src="{THEME_COLOR_PATH}/images/icons/delete.png" style="vertical-align:middle;" alt=""/>&nbsp;
                            <a href="#" onclick="return action_delete('ticket_delete.php?ticket_id={TICKET_ID_VAL}', '{TICKET_SUBJECT2_VAL}')"
                               class="link" title="{TR_TICKET_DELETE_LINK}">{TR_TICKET_DELETE}</a>

                            <img src="{THEME_COLOR_PATH}/images/icons/open.png" style="vertical-align:middle;" alt="" />&nbsp;
                            <a href="ticket_closed.php?ticket_id={TICKET_ID_VAL}"
                               class="link" title="{TR_TICKET_REOPEN_LINK}">{TR_TICKET_REOPEN}</a>
                        </td>
                    </tr>
                    <!-- EDP: tickets_item -->
                    <tr>
                        <td colspan="6">
                            <div class="buttons">
                                <input name="deleteAll" type="button" onclick="return action_delete('ticket_delete.php?delete=closed', 'all');" value="{TR_TICKET_DELETE_ALL}" style="float:left;" />
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="paginator">
                <!-- BDP: scroll_next_gray -->
                <a class="icon i_next_gray" href="#" onclick="return false;">
                    &nbsp;</a>
                <!-- EDP: scroll_next_gray -->
                <!-- BDP: scroll_next -->
                <a class="icon i_next" href="ticket_system.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
                <!-- EDP: scroll_next -->
                <!-- BDP: scroll_prev -->
                <a class="icon i_prev" href="ticket_system.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
                <!-- EDP: scroll_prev -->
                <!-- BDP: scroll_prev_gray -->
                <a class="icon i_prev_gray" href="#" onclick="return false;">
                    &nbsp;</a>
                <!-- EDP: scroll_prev_gray -->
            </div>
            <!-- EDP: tickets_list -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
