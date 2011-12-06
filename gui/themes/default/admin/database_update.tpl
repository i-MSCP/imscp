<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>
		<div class="location">
			<div class="location-area">
				<h1 class="webtools">{TR_MENU_SYSTEM_TOOLS}</h1>
			</div>
			<ul class="location-menu">
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="system_info.php">{TR_MENU_SYSTEM_TOOLS}</a></li>
				<li><a href="database_update.php">{TR_SECTION_TITLE}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="update"><span>{TR_SECTION_TITLE}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<!-- BDP: database_updates -->
			<form name='database_update' action='database_update.php' method='post'>
				<table class="descriptions">
					<tr>
						<th style="width:200px">{TR_DATABASE_UPDATES}</th>
						<th>{TR_DATABASE_UPDATE_DETAIL}</th>
					</tr>
					<!-- BDP: database_update -->
					<tr>
						<td>{DB_UPDATE_REVISION}</td>
						<td>{DB_UPDATE_DETAIL}</td>
					</tr>
					<!-- EDP: database_update -->
				</table>
				<div class="buttons">
					<input type="hidden" name="uaction" id='execute' value="update" />
					<input type="submit" name="submit" value="{TR_PROCESS_UPDATES}" />
				</div>
			</form>
			<!-- EDP: database_updates -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
