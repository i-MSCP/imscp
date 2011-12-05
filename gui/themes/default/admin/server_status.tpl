<!-- INCLUDE "../shared/layout/header.tpl" -->
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>
		<div class="location">
			<div class="location-area">
				<h1 class="general">{TR_MENU_GENERAL_INFORMATION}</h1>
			</div>
			<ul class="location-menu">
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
				<li><a href="server_status.php">{TR_SERVER_STATUS}</a></li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
			<h2 class="doc"><span>{TR_SERVER_STATUS}</span></h2>
			<!-- BDP: props_list -->
			<table>
				<tr>
					<th style="width:300px;">{TR_HOST}</th>
					<th>{TR_SERVICE}</th>
					<th>{TR_STATUS}</th>
				</tr>
				<!-- BDP: service_status -->
				<tr>
					<td class="{CLASS}">{HOST} (Port {PORT})</td>
					<td class="{CLASS}">{SERVICE}</td>
					<td class="{CLASS}">{STATUS}</td>
				</tr>
				<!-- EDP: service_status -->
			</table>
			<!-- EDP: props_list -->
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
