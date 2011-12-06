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
                <li><a>{TR_EDIT_ADMIN}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="user_{USER_ICON_COLOR}"><span>{TR_EDIT_ADMIN}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <form name="admin_edit_user" method="post" action="admin_edit.php">
                    <table>
						<tr>
							<th colspan="2">{TR_CORE_DATA}</th>
						</tr>
                        <tr>
                            <td>
                                <label for="username">{TR_USERNAME}</label>
                            </td>
                            <td class="content" id="username">{USERNAME}</td>
                        </tr>
                        <tr>
                            <td><label for="pass">{TR_PASSWORD}</label></td>
                            <td>
                                <input type="password" name="pass" id="pass" value="{VAL_PASSWORD}" />
                                <input name="genpass" type="submit" class="button" value="{TR_PASSWORD_GENERATE}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="pass_rep">{TR_PASSWORD_REPEAT}</label>
                            </td>
                            <td>
                                <input type="password" name="pass_rep" id="pass_rep" value="{VAL_PASSWORD}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="email">{TR_EMAIL}</label></td>
                            <td>
                                <input type="text" name="email" id="email" value="{EMAIL}" />
                            </td>
                        </tr>
                    </table>
                    <table>
						<tr>
							<th colspan="2">{TR_ADDITIONAL_DATA}</th>
						</tr>
                        <tr>
                            <td>
                                <label for="fname">{TR_FIRST_NAME}</label>
                            </td>
                            <td>
                                <input type="text" name="fname" id="fname" value="{FIRST_NAME}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="lname">{TR_LAST_NAME}</label></td>
                            <td>
                                <input type="text" name="lname" id="lname" value="{LAST_NAME}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="gender">{TR_GENDER}</label></td>
                            <td><select id="gender" name="gender">
                                <option value="M" {VL_MALE}>{TR_MALE}</option>
                                <option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
                                <option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
                            </select>
                            </td>
                        </tr>
                        <tr>
                            <td><label for="firm">{TR_COMPANY}</label></td>
                            <td>
                                <input type="text" name="firm" id="firm" value="{FIRM}" />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="zip">{TR_ZIP_POSTAL_CODE}</label>
                            </td>
                            <td>
                                <input type="text" name="zip" id="zip" value="{ZIP}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="city">{TR_CITY}</label></td>
                            <td>
                                <input type="text" name="city" id="city" value="{CITY}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="state">{TR_STATE_PROVINCE}</label></td>
                            <td>
                                <input type="text" name="state" id="state" value="{STATE_PROVINCE}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="country">{TR_COUNTRY}</label></td>
                            <td>
                                <input type="text" name="country" id="country" value="{COUNTRY}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="street1">{TR_STREET_1}</label></td>
                            <td>
                                <input type="text" name="street1" id="street1" value="{STREET_1}" />
                            </td>
                        </tr>

                        <tr>
                            <td><label for="street2">{TR_STREET_2}</label></td>
                            <td>
                                <input type="text" name="street2" id="street2" value="{STREET_2}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="phone">{TR_PHONE}</label></td>
                            <td>
                                <input type="text" name="phone" id="phone" value="{PHONE}" />
                            </td>
                        </tr>
                        <tr>
                            <td>{TR_FAX}</td>
                            <td><input type="text" name="fax" value="{FAX}" /></td>
                        </tr>
                    </table>
                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_UPDATE}" />
                    <input id="send_data" type="checkbox" name="send_data" checked="checked" />
                    <label for="send_data">{TR_SEND_DATA}</label>
                    <input type="hidden" name="uaction" value="edit_user" />
                    <input type="hidden" name="edit_id" value="{EDIT_ID}" />
                    <input type="hidden" name="edit_username" value="{USERNAME}" />
                </div>
            </form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
