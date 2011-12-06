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
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
				<li><a href="#" onclick="return false;">{TR_LMENU_UPDATE_HOSTING_PLAN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
            <h2 class="purchasing"><span>{TR_TITLE_MENU_UPDATE_HP}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<table>
					<!-- BDP: hosting_plans -->
					<tr>
					  <td>
						<strong>{HP_NAME}</strong><br />
						{HP_DESCRIPTION}<br />
						<br />
						{HP_DETAILS}<br />
						<br />
						<strong>{HP_COSTS}</strong></td>
					</tr>
					<tr>
					  <td><a href="hosting_plan_update.php?{LINK}={ID}" class="icon i_details">{TR_PURCHASE}</a></td>
					</tr>
					<!-- EDP: hosting_plans -->
			</table>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
