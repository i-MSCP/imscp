
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
