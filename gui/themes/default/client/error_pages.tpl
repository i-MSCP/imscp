<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
					<h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li>
					<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
				</li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
				<li><a href="#" onclick="return false;">{TR_LMENU_CUSTOM_ERROR_PAGES}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="errors"><span>{TR_ERROR_PAGES}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<table>
				<tr>
					<td style="width: 300px;">
						<span class="icon big i_error401">{TR_ERROR_401}</span></td>
					<td style="width: 20px;">
						<a href="error_edit.php?eid=401" class="icon i_edit">{TR_EDIT}</a>
					</td>
					<td>
						<a href="{DOMAIN}/errors/401.html" target="_blank" class="icon i_preview">{TR_VIEW}</a>
					</td>
				</tr>
				<tr>
					<td><span class="icon big i_error403">{TR_ERROR_403}</span></td>
					<td>
						<a href="error_edit.php?eid=403" class="icon i_edit">{TR_EDIT}</a>
					</td>
					<td>
						<a href="{DOMAIN}/errors/403.html" target="_blank" class="icon i_preview">{TR_VIEW}</a>
					</td>
				</tr>
				<tr>
					<td><span class="icon big i_error404">{TR_ERROR_404}</span></td>
					<td>
						<a href="error_edit.php?eid=404" class="icon i_edit">{TR_EDIT}</a>
					</td>
					<td>
						<a href="{DOMAIN}/errors/404.html" target="_blank" class="icon i_preview">{TR_VIEW}</a>
					</td>
				</tr>
				<tr>
					<td><span class="icon big i_error500">{TR_ERROR_500}</span></td>
					<td>
						<a href="error_edit.php?eid=500" class="icon i_edit">{TR_EDIT}</a>
					</td>
					<td>
						<a href="{DOMAIN}/errors/500.html" target="_blank" class="icon i_preview">{TR_VIEW}</a>
					</td>
				</tr>
				<tr>
					<td><span class="icon big i_error503">{TR_ERROR_503}</span></td>
					<td>
						<a href="error_edit.php?eid=503" class="icon i_edit">{TR_EDIT}</a>
					</td>
					<td>
						<a href="{DOMAIN}/errors/503.html" target="_blank" class="icon i_preview">{TR_VIEW}</a>
					</td>
				</tr>
			</table>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
