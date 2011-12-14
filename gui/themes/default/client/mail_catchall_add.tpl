<!-- INCLUDE "../shared/layout/header.tpl" -->
<body>
	<script type="text/javascript">
	/* <![CDATA[ */
		function changeType(what) {
			if (what == "normal") {
				document.forms[0].mail_id.disabled = false;
				document.forms[0].forward_list.disabled = true;
			} else {
				document.forms[0].mail_id.disabled = true;
				document.forms[0].forward_list.disabled = false;
			}
		}

		$(window).load(function() {changeType('{DEFAULT}');});
	/* ]]> */
	</script>
	<div class="header">
		{MAIN_MENU}
		<div class="logo">
			<img src="{ISP_LOGO}" alt="i-MSCP logo"/>
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
			<li><a href="mail_catchall.php">{TR_LMENU_MAIL_CATCH_ALL}</a></li>
			<li><a href="#" onclick="return false;">{TR_CREATE_CATCHALL_MAIL_ACCOUNT}</a></li>
		</ul>
	</div>

	<div class="left_menu">
		{MENU}
	</div>

	<div class="body">
		<h2 class="email"><span>{TR_CREATE_CATCHALL_MAIL_ACCOUNT}</span></h2>

		<div id="fwd_help" class="tooltip">{TR_FWD_HELP}</div>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form name="create_catchall_frm" method="post" action="mail_catchall_add.php">
			<table>
				<tr>
					<td>
						<input type="radio" name="mail_type" id="mail_type1" value="normal" {NORMAL_MAIL} onclick="changeType('normal');"/>
						<label for="mail_type1">{TR_MAIL_LIST}</label>
					</td>
					<td>
						<select name="mail_id">
							<!-- BDP: mail_list -->
							<option value="{MAIL_ID};{MAIL_ACCOUNT_PUNNY};">{MAIL_ACCOUNT}</option>
							<!-- EDP: mail_list -->
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<input type="radio" name="mail_type" id="mail_type2" value="forward" {FORWARD_MAIL}
							   onclick="changeType('forward');"/>
						<label for="mail_type2">{TR_FORWARD_MAIL}</label>
						<span class="icon i_help" title="{TR_FWD_HELP}">{TR_HELP}</span>
					</td>
					<td><textarea name="forward_list" id="forward_list" cols="35" rows="5"></textarea></td>
				</tr>
			</table>

			<div class="buttons">
				<input type="hidden" name="uaction" value="create_catchall"/>
				<input type="hidden" name="id" value="{ID}"/>
				<input name="Submit" type="submit" value="{TR_CREATE_CATCHALL}"/>
			</div>
		</form>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
