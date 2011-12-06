<!-- INCLUDE "../shared/layout/header.tpl" -->
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo"/>
		</div>
	</div>

	<div class="location">
		<div class="location-area">
			<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="manage_users.php">{TR_MENU_MANAGE_USERS}</a></li>
			<li><a href="#" onclick="return false;">{TR_EDIT_DOMAIN}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="domains"><span>{TR_EDIT_DOMAIN}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

	<!-- INCLUDE "../shared/partials/forms/domain_edit.tpl" -->
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
