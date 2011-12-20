
            <form action="settings_lostpassword.php" method="post" name="frmlostpassword" id="frmlostpassword">
                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE_INFO}</legend>

                    <table>
                        <tr>
                            <th colspan="2">{TR_ACTIVATION_EMAIL}</th>
                            <th colspan="2">{TR_PASSWORD_EMAIL}</th>
                        </tr>
                        <tr>
                            <td style="width:270px;"><strong>{TR_USER_LOGIN_NAME}</strong></td>
                            <td>{USERNAME}</td>
                            <td><strong>{TR_USER_LOGIN_NAME}</strong></td>
                            <td>{USERNAME}</td>
                        </tr>
                        <tr>
                            <td><strong>{TR_LOSTPW_LINK}</strong></td>
                            <td>{LINK}</td>
                            <td><strong>{TR_USER_PASSWORD}</strong></td>
                            <td>{PASSWORD}</td>
                        </tr>
                        <tr>
                            <td><strong>{TR_USER_REAL_NAME}</strong></td>
                            <td>{NAME}</td>
                            <td><strong>{TR_USER_REAL_NAME}</strong></td>
                            <td>{NAME}</td>
                        </tr>
                        <tr>
                            <td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
                            <td>{BASE_SERVER_VHOST}</td>
                            <td><strong>{TR_BASE_SERVER_VHOST}</strong></td>
                            <td>{BASE_SERVER_VHOST}</td>
                        </tr>
                        <tr>
                            <td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
                            <td>{BASE_SERVER_VHOST_PREFIX}</td>
                            <td><strong>{TR_BASE_SERVER_VHOST_PREFIX}</strong></td>
                            <td>{BASE_SERVER_VHOST_PREFIX}</td>
                        </tr>
                    </table>
                </fieldset>

                <fieldset>
                    <legend>{TR_MESSAGE_TEMPLATE}</legend>
                    <table>
                        <tr>
                            <td><strong>{TR_SUBJECT}</strong></td>
                            <td>
                                <input name="subject1" type="text" id="subject1" style="width:90%" value="{SUBJECT_VALUE1}" />
                            </td>
                            <td>
                                <input type="text" name="subject2" value="{SUBJECT_VALUE2}" style="width:90%" />
                            </td>
                        </tr>
                        <tr>
                            <td><strong>{TR_MESSAGE}</strong></td>
                            <td style="width:50%">
                                <textarea name="message1" cols="80" rows="20" id="message1" style="width:90%">{MESSAGE_VALUE1}</textarea>
                            </td>
                            <td style="width:50%">
                                <textarea name="message2" cols="80" rows="20" id="message2" style="width:90%">{MESSAGE_VALUE2}</textarea>
                            </td>
                        </tr>
                        <tr style="display:none;">
                            <td><strong>{TR_SENDER_EMAIL}</strong></td>
                            <td>{SENDER_EMAIL_VALUE}</td>
                            <td>
                                <input type="hidden" name="sender_email" value="{SENDER_EMAIL_VALUE}" />
                            </td>
                        </tr>
                        <tr style="display:none;">
                            <td><strong>{TR_SENDER_NAME}</strong></td>
                            <td>{SENDER_NAME_VALUE}</td>
                            <td><input type="hidden" name="sender_name"
                                       value="{SENDER_NAME_VALUE}" /></td>
                        </tr>
                    </table>
                </fieldset>
                <div class="buttons">
                    <input name="Submit" type="submit" value="{TR_APPLY_CHANGES}" />
                    <input type="hidden" name="uaction" value="apply" />
                </div>
            </form>
