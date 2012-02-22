
            <form name="search_user" method="post" action="user_edit.php">
                <fieldset>
                    <legend>{TR_CORE_DATA}</legend>
                    <table>
                        <tr>
                            <td style="width: 300px;">{TR_USERNAME}</td>
                            <td>{VL_USERNAME}</td>
                        </tr>
                        <tr>
                            <td><label for="userpassword">{TR_PASSWORD}</label></td>
                            <td>
                                <input type="password" name="userpassword" id="userpassword" value="{VAL_PASSWORD}" />
                                <input name="genpass" type="submit" value=" {TR_PASSWORD_GENERATE} " />
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label for="userpassword_repeat">{TR_REP_PASSWORD}</label>
                            </td>
                            <td>
                                <input type="password" name="userpassword_repeat" id="userpassword_repeat" value="{VAL_PASSWORD}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="useremail">{TR_USREMAIL}</label></td>
                            <td>
                                <input type="text" name="useremail" id="useremail" value="{VL_MAIL}" />
                            </td>
                        </tr>
                    </table>
                </fieldset>
                <fieldset>
                    <legend>{TR_ADDITIONAL_DATA}</legend>
                    <table class="firstColFixed">
                        <tr>
                            <td>
                                <label for="useruid">{TR_CUSTOMER_ID}</label></td>
                            <td>
                                <input type="text" name="useruid" id="useruid" value="{VL_USR_ID}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userfname">{TR_FIRSTNAME}</label></td>
                            <td>
                                <input type="text" name="userfname" id="userfname" value="{VL_USR_NAME}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userlname">{TR_LASTNAME}</label></td>
                            <td>
                                <input type="text" name="userlname" id="userlname" value="{VL_LAST_USRNAME}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="gender">{TR_GENDER}</label></td>
                            <td>
                                <select id="gender" name="gender">
                                    <option value="M" {VL_MALE}>{TR_MALE}</option>
                                    <option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userfirm">{TR_COMPANY}</label></td>
                            <td>
                                <input type="text" name="userfirm" id="userfirm" value="{VL_USR_FIRM}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userstreet1">{TR_STREET1}</label></td>
                            <td>
                                <input type="text" name="userstreet1" id="userstreet1" value="{VL_STREET1}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userzip">{TR_POST_CODE}</label></td>
                            <td>
                                <input type="text" name="userzip" id="userzip" value="{VL_USR_POSTCODE}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="usercity">{TR_CITY}</label></td>
                            <td>
                                <input type="text" name="usercity" id="usercity" value="{VL_USRCITY}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userstate">{TR_STATE}</label></td>
                            <td>
                                <input id="userstate" type="text" name="userstate" value="{VL_USRSTATE}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="usercountry">{TR_COUNTRY}</label></td>
                            <td>
                                <input type="text" name="usercountry" id="usercountry" value="{VL_COUNTRY}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userphone">{TR_PHONE}</label></td>
                            <td>
                                <input type="text" name="userphone" id="userphone" value="{VL_PHONE}" />
                            </td>
                        </tr>
                        <tr>
                            <td><label for="userfax">{TR_FAX}</label></td>
                            <td>
                                <input type="text" name="userfax" id="userfax" value="{VL_FAX}" />
                            </td>
                        </tr>
                    </table>
                    <div class="buttons">
                        <input name="Submit" type="submit" value="{TR_BTN_ADD_USER}" />
                        <input type="checkbox" id="send_data" name="send_data" checked="checked" /><label for="send_data">{TR_SEND_DATA}</label>
                    </div>
                </fieldset>
                <input type="hidden" name="uaction" value="save_changes" />
                <input type="hidden" name="edit_id" value="{EDIT_ID}" />
            </form>
