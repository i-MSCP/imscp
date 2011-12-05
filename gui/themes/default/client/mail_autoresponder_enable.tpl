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
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="mail_accounts.php">{TR_MENU_MAIL_ACCOUNTS}</a></li>
				<li><a href="mail_accounts.php">{TR_LMENU_OVERVIEW}</a></li>
				<li><a href="#" onclick="return false;">{TR_ENABLE_MAIL_AUTORESPONDER}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="support"><span>{TR_ENABLE_MAIL_AUTORESPONDER}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->


			<form name="manage_users_common_frm" method="post" action="">
				<fieldset>
					<legend>{TR_ARSP_MESSAGE}</legend>
					<textarea name="arsp_message" cols="50" rows="15"></textarea>
				</fieldset>

				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_ENABLE}" />
					<input type="button" name="Submit2" value="{TR_CANCEL}" onclick="location = 'mail_accounts.php'" />
				</div>
				<input type="hidden" name="uaction" value="enable_arsp" />
				<input type="hidden" name="id" value="{ID}" />
			</form>

		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
