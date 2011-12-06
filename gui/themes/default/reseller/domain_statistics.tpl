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
				<li><a href="user_statistics.php">{TR_MENU_DOMAIN_STATISTICS}</a></li>
				<li><a href="user_statistics.php">{TR_MENU_OVERVIEW}</a></li>
				<li>{TR_DOMAIN_STATISTICS}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
            <h2 class="stats"><span>{TR_DOMAIN_STATISTICS}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="domain_statistics_frm" method="post" action="domain_statistics.php">
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
				<input name="uaction" type="hidden" value="show_traff" />
				<input type="hidden" name="domain_id" value="{DOMAIN_ID}" />
			</form>

			<!--  BDP: traffic_table  -->
				<table width="100%" cellspacing="3">
					<thead>
						<tr>
							<th>{TR_DAY}</th>
							<th>{TR_WEB_TRAFFIC}</th>
							<th>{TR_FTP_TRAFFIC}</th>
							<th>{TR_SMTP_TRAFFIC}</th>
							<th>{TR_POP3_TRAFFIC}</th>
							<th>{TR_ALL_TRAFFIC}</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<td>{TR_ALL}</td>
							<td>{ALL_WEB_TRAFFIC}</td>
							<td>{ALL_FTP_TRAFFIC}</td>
							<td>{ALL_SMTP_TRAFFIC}</td>
							<td>{ALL_POP3_TRAFFIC}</td>
							<td>{ALL_ALL_TRAFFIC}</td>
						</tr>
					</tfoot>
					<tbody>
						<!-- BDP: traffic_table_item -->
							<tr>
								<td>{DATE}</td>
								<td>{WEB_TRAFFIC}</td>
								<td>{FTP_TRAFFIC}</td>
								<td>{SMTP_TRAFFIC}</td>
								<td>{POP3_TRAFFIC}</td>
								<td>{ALL_TRAFFIC}</td>
							</tr>
						<!-- EDP: traffic_table_item -->
					</tbody>
				</table>
			<!--  EDP: traffic_table  -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
