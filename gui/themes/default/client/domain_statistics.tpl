<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="statistics">{TR_MENU_STATISTICS}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domain_statistics.php">{TR_MENU_STATISTICS}</a></li>
				<li><a href="#" onclick="return false;">{TR_LMENU_OVERVIEW}</a></li>
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
					<!-- BDP: month_item -->
					<option {MONTH_SELECTED}>{MONTH}</option>
					<!-- EDP: month_item -->
				</select>

				<label for="year">{TR_YEAR}</label>
				<select id="year" name="year">
					<!-- BDP: year_item -->
						 <option {YEAR_SELECTED}>{YEAR}</option>
					<!-- EDP: year_item -->
				</select>

				<input name="Submit" type="submit" class="button" value="{TR_SHOW}" />
				<input name="uaction" type="hidden" value="show_traff" />
			</form>

			<table width="100%" cellspacing="3">
				<thead>
					<tr>
						<th>{TR_DATE}</th>
						<th>{TR_WEB_TRAFF}</th>
						<th>{TR_FTP_TRAFF}</th>
						<th>{TR_SMTP_TRAFF}</th>
						<th>{TR_POP_TRAFF}</th>
						<th>{TR_SUM}</th>
					</tr>
				</thead>
				<!-- BDP: traff_list -->
					<tfoot>
						<tr>
							<td>{TR_ALL}</td>
							<td>{WEB_ALL}</td>
							<td>{FTP_ALL}</td>
							<td>{SMTP_ALL}</td>
							<td>{POP_ALL}</td>
							<td>{SUM_ALL}</td>
						</tr>
					</tfoot>
					<tbody>
						<!-- BDP: traff_item -->
							<tr>
								<td>{DATE}</td>
								<td>{WEB_TRAFF}</td>
								<td>{FTP_TRAFF}</td>
								<td>{SMTP_TRAFF}</td>
								<td>{POP_TRAFF}</td>
								<td>{SUM_TRAFF}</td>
							</tr>
						<!-- EDP: traff_item -->
					</tbody>
				<!-- EDP: traff_list -->
			</table>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
