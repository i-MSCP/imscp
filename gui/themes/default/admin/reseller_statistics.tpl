
            <!-- BDP: statistics_form -->
            <form action="reseller_statistics.php?psi={POST_PREV_PSI}" method="post" name="rs_frm" id="rs_frm">
                <label for="month">{TR_MONTH}</label>
                <select name="month" id="month">
                    <!-- BDP: month_list -->
                    <option{OPTION_SELECTED}>{MONTH_VALUE}</option>
                    <!-- EDP: month_list -->
                </select>
                <label for="year">{TR_YEAR}</label>
                <select name="year" id="year">
                    <!-- BDP: year_list -->
                    <option{OPTION_SELECTED}>{YEAR_VALUE}</option>
                    <!-- EDP: year_list -->
                </select>
                <input name="Submit" type="submit" class="button" value="{TR_SHOW}" />
                <input type="hidden" name="uaction" value="show" />
            </form>
            <!-- EDP: statistics_form -->

            <!-- BDP: traffic_table -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_RESELLER_NAME}</th>
                        <th>{TR_TRAFF}</th>
                        <th>{TR_DISK}</th>
                        <th>{TR_DOMAIN}</th>
                        <th>{TR_SUBDOMAIN}</th>
                        <th>{TR_ALIAS}</th>
                        <th>{TR_MAIL}</th>
                        <th>{TR_FTP}</th>
                        <th>{TR_SQL_DB}</th>
                        <th>{TR_SQL_USER}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: reseller_entry -->
                    <tr>
                        <td>

                            <a href="reseller_user_statistics.php?rid={RESELLER_ID}&amp;name={RESELLER_NAME}&amp;month={MONTH}&amp;year={YEAR}" title="{RESELLER_NAME}" class="icon i_domain_icon">{RESELLER_NAME}</a>
                        </td>
                        <td>
                            <div class="graph"><span style="width: {TRAFF_PERCENT}%">&nbsp;</span><strong>{TRAFF_SHOW_PERCENT}
                                &nbsp;%</strong></div>{TRAFF_MSG}</td>
                        <td>
                            <div class="graph"><span style="width: {DISK_PERCENT}%">&nbsp;</span><strong>{DISK_SHOW_PERCENT}
                                &nbsp;%</strong></div>{DISK_MSG}</td>
                        <td>{DMN_MSG}</td>
                        <td>{SUB_MSG}</td>
                        <td>{ALS_MSG}</td>
                        <td>{MAIL_MSG}</td>
                        <td>{FTP_MSG}</td>
                        <td>{SQL_DB_MSG}</td>
                        <td>{SQL_USER_MSG}</td>
                    </tr>
                    <!-- EDP: reseller_entry -->
                </tbody>
            </table>
            <div class="paginator">
                <!-- BDP: scroll_next_gray -->
                <a class="icon i_next_gray" href="#" title="next">next</a>
                <!-- EDP: scroll_next_gray -->
                <!-- BDP: scroll_next -->
                <a class="icon i_next" href="reseller_statistics.php?psi={NEXT_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="next">next</a>
                <!-- EDP: scroll_next -->
                <!-- BDP: scroll_prev_gray -->
                <a class="icon i_prev_gray" href="#" title="next">next</a>
                <!-- EDP: scroll_prev_gray -->
                <!-- BDP: scroll_prev -->
                <a class="icon i_prev" href="reseller_statistics.php?psi={PREV_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="previous">previous</a>
                <!-- EDP: scroll_prev -->
            </div>
            <!-- EDP: traffic_table -->
