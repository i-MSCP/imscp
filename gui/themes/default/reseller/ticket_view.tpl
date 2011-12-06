<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
        {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="general">{TR_SUPPORT_SYSTEM}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="{SUPPORT_SYSTEM_PATH}">{TR_SUPPORT_SYSTEM}</a></li>
                <li><a href="ticket_view.php?ticket_id={TICKET_ID_VAL}">{TR_VIEW_SUPPORT_TICKET}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="support"><span>{TR_VIEW_SUPPORT_TICKET}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: tickets_list -->
            <table>
                <tr>
                    <th colspan="2">{TR_TICKET_INFO}</th>
                </tr>
                <tr>
                    <td style="width:200px;"><strong>{TR_TICKET_URGENCY}:</strong></td>
                    <td>{TICKET_URGENCY_VAL}</td>
                </tr>
                <tr>
                    <td><strong>{TR_TICKET_SUBJECT}</strong>:</td>
                    <td>{TICKET_SUBJECT_VAL}</td>
                </tr>
                <tr>
                    <td colspan="2">&nbsp;</td>
                </tr>
                <tr>
                    <th colspan="2">{TR_TICKET_MESSAGES}</th>
                </tr>
                <!-- BDP: tickets_item -->
                <tr>
                    <td><strong>{TR_TICKET_FROM}:</strong></td>
                    <td>{TICKET_FROM_VAL}</td>
                </tr>
                <tr>
                    <td><strong>{TR_TICKET_DATE}:</strong></td>
                    <td>{TICKET_DATE_VAL}</td>
                </tr>
                <tr>
                    <td><strong>{TR_TICKET_CONTENT}:</strong></td>
                    <td>
                        <div style="background:#fefefe;padding:5px;border:1px solid#dedede;">
                        {TICKET_CONTENT_VAL}
                        </div>
                    </td>
                </tr>
                <tr style="background:transparent;border: none;">
                    <td colspan="2" style="border:none;">&nbsp;</td>
                </tr>
                <!-- EDP: tickets_item -->
            </table>

            <h2 class="doc"><span>{TR_TICKET_NEW_REPLY}</span></h2>

            <form name="ticketFrm" method="post" action="ticket_view.php?ticket_id={TICKET_ID_VAL}">
                <table>
                    <tr>
                        <td style="text-align: center">
                            <textarea style="padding:3px;" name="user_message" cols="80" rows="12"></textarea>
                        </td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="button_reply" type="button" class="button" value="{TR_TICKET_REPLY}" onclick="return sbmt(document.forms[0], 'send_msg');" />
                    <input name="button_action" type="button" class="button" value="{TR_TICKET_ACTION}" onclick="return sbmt(document.forms[0],'{TICKET_ACTION_VAL}');" />
                </div>
                <input name="uaction" type="hidden" value="" />
                <input name="subject" type="hidden" value="{TICKET_SUBJECT_VAL}" />
                <input name="urgency" type="hidden" value="{TICKET_URGENCY_ID_VAL}" />
            </form>
            <!-- EDP: tickets_list -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
