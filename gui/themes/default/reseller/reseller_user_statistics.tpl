<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
        	{MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="statistics">{TR_MENU_DOMAIN_STATISTICS}</h1>
            </div>
            <ul class="location-menu">
                <!-- BDP: logged_from -->
                <li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="user_statistics.php">{TR_MENU_DOMAIN_STATISTICS}</a>
                </li>
                <li><a href="user_statistics.php">{TR_MENU_OVERVIEW}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="stats"><span>{TR_RESELLER_USER_STATISTICS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <!-- BDP: props_list -->
            <form name="rs_frm" method="post" action="user_statistics.php?psi={POST_PREV_PSI}">
                <label for="month">{TR_MONTH}</label>
                <select id="month" name="month">
                    <!-- BDP: month_list -->
                    <option {OPTION_SELECTED}>{MONTH_VALUE}</option>
                    <!-- EDP: month_list -->
                </select>
                <label for="year">{TR_YEAR}</label>
                <select id="year" name="year">
                    <!-- BDP: year_list -->
                    <option {OPTION_SELECTED}>{YEAR_VALUE}</option>
                    <!-- EDP: year_list -->
                </select>
                <input name="Submit" type="submit" class="button" value="{TR_SHOW}" />
                <input type="hidden" name="uaction" value="show" />
                <input type="hidden" name="name" value="{VALUE_NAME}" />
                <input type="hidden" name="rid" value="{VALUE_RID}" />
            </form>

            <!-- BDP: domain_list -->
            <table>
                <thead>
                    <tr>
                        <th>{TR_DOMAIN_NAME}</th>
                        <th>{TR_TRAFF}</th>
                        <th>{TR_DISK}</th>
                        <th>{TR_WEB}</th>
                        <th>{TR_FTP_TRAFF}</th>
                        <th>{TR_SMTP}</th>
                        <th>{TR_POP3}</th>
                        <th>{TR_SUBDOMAIN}</th>
                        <th>{TR_ALIAS}</th>
                        <th>{TR_MAIL}</th>
                        <th>{TR_FTP}</th>
                        <th>{TR_SQL_DB}</th>
                        <th>{TR_SQL_USER}</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- BDP: domain_entry -->
                    <tr>
                        <td>
                            <a href="domain_statistics.php?month={MONTH}&amp;year={YEAR}&amp;domain_id={DOMAIN_ID}" class="icon i_domain_icon">{DOMAIN_NAME}</a>
                        </td>
                        <td>
                            <div class="graph"><span style="width: {TRAFF_PERCENT}%">&nbsp;</span><strong>{TRAFF_SHOW_PERCENT}
                                &nbsp;%</strong></div>{TRAFF_MSG}</td>
                        <td>
                            <div class="graph"><span style="width: {DISK_PERCENT}%">&nbsp;</span><strong>{DISK_SHOW_PERCENT}
                                &nbsp;%</strong></div>{DISK_MSG}</td>
                        <td>{WEB}</td>
                        <td>{FTP}</td>
                        <td>{SMTP}</td>
                        <td>{POP3}</td>
                        <td>{SUB_MSG}</td>
                        <td>{ALS_MSG}</td>
                        <td>{MAIL_MSG}</td>
                        <td>{FTP_MSG}</td>
                        <td>{SQL_DB_MSG}</td>
                        <td>{SQL_USER_MSG}</td>
                    </tr>
                    <!-- EDP: domain_entry -->
                </tbody>
            </table>
            <div class="paginator">
                <!-- BDP: scroll_next_gray -->
                <a class="icon i_next_gray" href="#">&nbsp;</a>
                <!-- EDP: scroll_next_gray -->

                <!-- BDP: scroll_next -->
                <a class="icon i_next" href="user_statistics.php?psi={NEXT_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="{TR_NEXT}">{TR_NEXT}</a>
                <!-- EDP: scroll_next -->

                <!-- BDP: scroll_prev -->
                <a class="icon i_prev" href="user_statistics.php?psi={PREV_PSI}&amp;month={MONTH}&amp;year={YEAR}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
                <!-- EDP: scroll_prev -->

                <!-- BDP: scroll_prev_gray -->
                <a class="icon i_prev_gray" href="#">&nbsp;</a>
                <!-- EDP: scroll_prev_gray -->
            </div>
            <!-- EDP: domain_list -->
            <!-- EDP: props_list -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
