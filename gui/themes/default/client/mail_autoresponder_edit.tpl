<!-- INCLUDE "../shared/layout/header.tpl" -->
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo" />
		</div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="email">{TR_MENU_MAIL_ACCOUNTS}</h1>
		</div>
		<ul class="location-menu">
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="mail_accounts.php">{TR_MENU_MAIL_ACCOUNTS}</a></li>
			<li><a href="mail_accounts.php">{TR_LMENU_OVERVIEW}</a></li>
			<li><a href="#" onclick="return false;">{TR_EDIT_MAIL_AUTORESPONDER}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="support"><span>{TR_EDIT_MAIL_AUTORESPONDER}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form action="" method="post" id="client_mail_autoresponder_edit">
			<fieldset>
				<legend>{TR_ARSP_MESSAGE}</legend>
				<textarea name="arsp_message" cols="50" rows="15">{ARSP_MESSAGE}</textarea>
			</fieldset>
			<div class="buttons">
				<input type="hidden" name="id" value="{ID}" />
				<input type="hidden" name="uaction" value="enable_arsp" />
				<input type="submit" name="submit" value="{TR_ENABLE}" />
				<input type="button" name="Submit2" value="{TR_CANCEL}" onclick="location = 'mail_accounts.php'" />
			</div>
		</form>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
