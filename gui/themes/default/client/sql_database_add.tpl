<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="database">{TR_MENU_MANAGE_SQL}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="sql_database_add.php">{TR_MENU_MANAGE_SQL}</a></li>
				<li><a href="sql_database_add.php">{TR_LMENU_ADD_SQL_DATABASE}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="sql"><span>{TR_TITLE_ADD_DATABASE}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="sql_add_database_frm" method="post" action="sql_database_add.php">
				<table>
					<tr>
						<th colspan="2">
							{TR_DATABASE}
						</th>
					</tr>
					<tr>
						<td style="width:300px;"><label for="db_name">{TR_DB_NAME}</label></td>
						<td><input type="text" id="db_name" name="db_name" value="{DB_NAME}" /></td>
					</tr>
					<tr>
						<td>
							<!-- BDP: mysql_prefix_yes -->
								<input type="checkbox" name="use_dmn_id" {USE_DMN_ID} />
							<!-- EDP: mysql_prefix_yes -->
							<!-- BDP: mysql_prefix_no -->
								<input type="hidden" name="use_dmn_id" value="on" />
							<!-- EDP: mysql_prefix_no -->
							{TR_USE_DMN_ID}
						</td>
						<td>
							<!-- BDP: mysql_prefix_all -->
								<input type="radio" name="id_pos" value="start" {START_ID_POS_CHECKED} />{TR_START_ID_POS}<br />
							   	<input type="radio" name="id_pos" value="end" {END_ID_POS_CHECKED} />{TR_END_ID_POS}
							<!-- EDP: mysql_prefix_all -->
							<!-- BDP: mysql_prefix_infront -->
								<input type="hidden" name="id_pos" value="start" checked="checked" />{TR_START_ID_POS}
							<!-- EDP: mysql_prefix_infront -->
							<!-- BDP: mysql_prefix_behind -->
								<input type="hidden" name="id_pos" value="end" checked="checked" />{TR_END_ID_POS}
							<!-- EDP: mysql_prefix_behind -->
						</td>
					</tr>
				</table>

				<div class="buttons">
					<input name="Add_New" type="submit" class="button" id="Add_New" value="{TR_ADD}" />
				</div>
				<input type="hidden" name="uaction" value="add_db" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
