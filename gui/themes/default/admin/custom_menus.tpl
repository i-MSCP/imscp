<!-- INCLUDE "../shared/layout/header.tpl" -->
        <script type="text/javascript">
            /* <![CDATA[ */
            function action_delete(url, subject) {
                return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
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
                <h1 class="general">{TR_MENU_QUESTIONS_AND_COMMENTS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
                <li><a href="custom_menus.php">{TR_TITLE_CUSTOM_MENUS}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="support"><span>{TR_TITLE_CUSTOM_MENUS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <table>
                <tr>
                    <th style="width:300px;">{TR_MENU_NAME}</th>
                    <th>{TR_LEVEL}</th>
                    <th>{TR_ACTON}</th>
                </tr>
                <!-- BDP: button_list -->
                <tr>
                    <td>
                        <a href="{LINK}" class="link" target="_blank"><strong>{MENU_NAME}</strong></a>
                        <br />
                        {LINK}
                    </td>
                    <td>{LEVEL}</td>
                    <td>
                        <a href="custom_menus.php?edit_id={BUTONN_ID}" class="icon i_edit">{TR_EDIT}</a>
                        <a href="custom_menus.php?delete_id={BUTONN_ID}" class="icon i_delete" onclick="return action_delete('custom_menus.php?delete_id={BUTONN_ID}', '{MENU_NAME2}')">{TR_DELETE}</a>
                    </td>
                </tr>
                <!-- EDP: button_list -->
            </table>
            <form name="add_new_button_frm" method="post" action="custom_menus.php">
                <!-- BDP: add_button -->
                <fieldset>
                    <legend>{TR_ADD_NEW_BUTTON}</legend>

                    <table>
                        <tr>
                            <td style="width:300px;">
                                <label for="bname1">{TR_BUTTON_NAME}</label>
                            </td>
                            <td><input type="text" name="bname" id="bname1" /></td>
                        </tr>
                        <tr>
                            <td><label for="blink1">{TR_BUTTON_LINK}</label></td>
                            <td><input type="text" name="blink" id="blink1" /></td>
                        </tr>
                        <tr>
                            <td><label for="btarget1">{TR_BUTTON_TARGET}</label></td>
                            <td><input type="text" name="btarget" id="btarget1" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="bview1">{TR_VIEW_FROM}</label></td>
                            <td>
                                <select name="bview" id="bview1">
                                    <option value="admin">{ADMIN}</option>
                                    <option value="reseller">{RESELLER}</option>
                                    <option value="user">{USER}</option>
                                    <option value="all">{RESSELER_AND_USER}</option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <div class="buttons">
                        <input name="Button" type="button" class="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0],'new_button');" />
                    </div>
                </fieldset>
                <!-- EDP: add_button -->
                <!-- BDP: edit_button -->
                <fieldset>
                    <legend>{TR_EDIT_BUTTON}</legend>

                    <table>
                        <tr>
                            <td><label for="bname2">{TR_BUTTON_NAME}</label></td>
                            <td>
                                <input type="text" name="bname" id="bname2" value="{BUTON_NAME}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="blink2">{TR_BUTTON_LINK}</label></td>
                            <td>
                                <input type="text" name="blink" id="blink2" value="{BUTON_LINK}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="btarget2">{TR_BUTTON_TARGET}</label></td>
                            <td>
                                <input type="text" name="btarget" id="btarget2" value="{BUTON_TARGET}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="bview2">{TR_VIEW_FROM}</label></td>
                            <td>
                                <select name="bview" id="bview2">
                                    <option value="admin">{ADMIN}</option>
                                    <option value="reseller">{RESELLER}</option>
                                    <option value="user">{USER}</option>
                                    <option value="all">{RESSELER_AND_USER}</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <div class="buttons">
                        <input name="Button" type="button" class="button" value="{TR_SAVE}" onClick="return sbmt(document.forms[0],'edit_button');" />
                    </div>
                    <input type="hidden" name="eid" value="{EID}" />
                </fieldset>
                <!-- EDP: edit_button -->
                <input type="hidden" name="uaction" value="" />
            </form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
