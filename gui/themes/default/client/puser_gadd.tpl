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
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
				<li><a href="protected_areas.php">{TR_LMENU_HTACCESS}</a></li>
				<li><a href="protected_user_manage.php">{TR_HTACCESS_USER}</a></li>
				<li><a href="#" onclick="return false;">{TR_ADD_GROUP}</a></li>
				
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="users"><span>{TR_ADD_GROUP}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="add_user_group" method="post" action="protected_group_add.php">
				<table>
					<tr>
						<td style="width: 300px;"><label for="groupname">{TR_GROUPNAME}</label></td>
						<td><input name="groupname" type="text" id="groupname" value="" /></td>
					</tr>
				</table>
				<div class="buttons">
					 <input name="Submit" type="submit" value="{TR_ADD_GROUP}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>

				<input type="hidden" name="uaction" value="add_group" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
