<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}

            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>

        <div class="location">
            <div class="location-area">
                <h1 class="manage_users">{TR_SERVER_DAY_STATISTICS}</h1>
            </div>
            <ul class="location-menu">
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="server_statistic.php">{TR_SERVER_DAY_STATISTICS}</a></li>

            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">
            <h2 class="general"><span>{TR_SERVER_DAY_STATISTICS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <p>{TR_YEAR} {YEAR}&nbsp;&nbsp;&nbsp;&nbsp; {TR_MONTH} {MONTH}&nbsp;&nbsp;&nbsp;{TR_DAY} {DAY}</p>
            <table>
            	<thead>
	                <tr>
	                    <th>{TR_HOUR}</th>
	                    <th>{TR_WEB_IN}</th>
	                    <th>{TR_WEB_OUT}</th>
	                    <th>{TR_SMTP_IN}</th>
	                    <th>{TR_SMTP_OUT}</th>
	                    <th>{TR_POP_IN}</th>
	                    <th>{TR_POP_OUT}</th>
	                    <th>{TR_OTHER_IN}</th>
	                    <th>{TR_OTHER_OUT}</th>
	                    <th>{TR_ALL_IN}</th>
	                    <th>{TR_ALL_OUT}</th>
	                    <th>{TR_ALL}</th>
	                </tr>
				</thead>
	            <tfoot>
	                <tr>
	                    <td>{TR_ALL}</td>
	                    <td>{WEB_IN_ALL}</td>
	                    <td>{WEB_OUT_ALL}</td>
	                    <td>{SMTP_IN_ALL}</td>
	                    <td>{SMTP_OUT_ALL}</td>
	                    <td>{POP_IN_ALL}</td>
	                    <td>{POP_OUT_ALL}</td>
	                    <td>{OTHER_IN_ALL}</td>
	                    <td>{OTHER_OUT_ALL}</td>
	                    <td>{ALL_IN_ALL}</td>
	                    <td>{ALL_OUT_ALL}</td>
	                    <td>{ALL_ALL}</td>
	                </tr>
	            </tfoot>   
                <tbody>
	                <!-- BDP: hour_list -->
	                <tr>
	                    <td>{HOUR}</td>
	                    <td>{WEB_IN}</td>
	                    <td>{WEB_OUT}</td>
	                    <td>{SMTP_IN}</td>
	                    <td>{SMTP_OUT}</td>
	                    <td>{POP_IN}</td>
	                    <td>{POP_OUT}</td>
	                    <td>{OTHER_IN}</td>
	                    <td>{OTHER_OUT}</td>
	                    <td>{ALL_IN}</td>
	                    <td>{ALL_OUT}</td>
	                    <td>{ALL}</td>
	                </tr>
	                <!-- EDP: hour_list -->
                </tbody>
            </table>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
