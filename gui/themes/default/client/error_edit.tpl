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
				<li><a href="error_pages.php">{TR_LMENU_CUSTOM_ERROR_PAGES}</a></li>
				<li><a href="#" onclick="return false;">{TR_ERROR_EDIT_PAGE} {EID}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
            <h2 class="errors"><span>{TR_ERROR_EDIT_PAGE} {EID}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="error_edit" method="post" action="error_pages.php">
				<textarea name="error" cols="80" rows="35" id="error">{ERROR}</textarea>
				<div class="buttons">
					<input type="hidden" name="uaction" value="updt_error" />
					<input type="hidden" name="eid" value="{EID}" />
					<input name="Submit" type="submit" value="{TR_SAVE}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','error_pages.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
