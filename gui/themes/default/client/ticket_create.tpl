<!-- INCLUDE "../shared/layout/header.tpl" -->
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
                <li><a href="ticket_create.php">{TR_NEW_TICKET}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="support"><span>{TR_NEW_TICKET}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <form style="margin:0" name="ticketFrm" method="post" action="ticket_create.php">
                <table>
                    <tr>
                        <th colspan="2">{TR_NEW_TICKET}</th>
                    </tr>
                    <tr>
                        <td style="width:200px;">
                            <label for="urgency"><strong>{TR_URGENCY}</strong></label>
                        </td>
                        <td>
                            <select id="urgency" name="urgency">
                                <option value="1"{OPT_URGENCY_1}>{TR_LOW}</option>
                                <option value="2"{OPT_URGENCY_2}>{TR_MEDIUM}</option>
                                <option value="3"{OPT_URGENCY_3}>{TR_HIGH}</option>
                                <option value="4"{OPT_URGENCY_4}>{TR_VERY_HIGH}</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="subject"><strong>{TR_SUBJECT}</strong></label>
                        </td>
                        <td>
                            <input type="text" id="subject" name="subject" value="{SUBJECT}" />
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="user_message"><strong>{TR_YOUR_MESSAGE}</strong></label>
                        </td>
                        <td>
                            <textarea style="padding:5px" id="user_message" name="user_message" cols="80" rows="12">{USER_MESSAGE}</textarea>
                        </td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_SEND_MESSAGE}" />
                    <input name="uaction" type="hidden" value="send_msg" />
                </div>
            </form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
