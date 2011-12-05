<!-- INCLUDE "../shared/layout/header.tpl" -->
<script type="text/javascript">
/* <![CDATA[ */
	function action_delete(url, subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
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
		<h1 class="general">{TR_MENU_QUESTIONS_AND_COMMENTS}</h1>
	</div>
	<ul class="location-menu">
		<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
	</ul>
	<ul class="path">
		<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
		<li><a href="custom_menus.php">{TR_TITLE_CUSTOM_MENUS}</a></li>
	</ul>
</div>
<div class="left_menu">
	{MENU}
</div>
<div class="body">
	<h2 class="support"><span>{TR_TITLE_CUSTOM_MENUS}</span></h2>

	<!-- BDP: page_message -->
	<div class="{MESSAGE_CLS}">{MESSAGE}</div>
	<!-- EDP: page_message -->

	<!-- BDP: menus_list_block -->
	<table>
		<tr>
			<th>{TR_MENU_NAME}</th>
			<th>{TR_LEVEL}</th>
			<th>{TR_ACTON}</th>
		</tr>
		<!-- BDP: menu_block -->
		<tr>
			<td>
				<a href="{LINK}" class="link" target="_blank"><strong>{MENU_NAME}</strong></a>
				<br/>
				{LINK}
			</td>
			<td>{LEVEL}</td>
			<td>
				<a href="custom_menus.php?edit_id={BUTONN_ID}" class="icon i_edit">{TR_EDIT}</a>
				<a href="custom_menus.php?delete_id={BUTONN_ID}" class="icon i_delete" onclick="return action_delete('custom_menus.php?delete_id={MENU_ID}', '{MENU_NAME}')">{TR_DELETE}</a>
			</td>
		</tr>
		<!-- EDP: menu_block -->
	</table>
	<!-- EDP: menus_list_block -->

	<form name="addFrm" method="post" action="custom_menus.php">
		<!-- BDP: add_menu -->
		<table>
			<tr>
				<th colspan="2">{TR_ADD_NEW_MENU}</th>
			</tr>
			<tr>
				<td>
					<label for="bname1">{TR_MENU_NAME}</label>
				</td>
				<td><input type="text" name="bname" id="bname1"/></td>
			</tr>
			<tr>
				<td><label for="blink1">{TR_MENU_LINK}</label></td>
				<td><input type="text" name="blink" id="blink1"/></td>
			</tr>
			<tr>
				<td><label for="btarget1">{TR_MENU_TARGET}</label></td>
				<td><input type="text" name="btarget" id="btarget1"/>
				</td>
			</tr>
			<tr>
				<td><label for="bview1">{TR_VIEW_FROM}</label></td>
				<td>
					<select name="bview" id="bview1">
						<option value="admin">{ADMIN}</option>
						<option value="reseller">{RESELLER}</option>
						<option value="user">{USER}</option>
						<option value="all">{RESSELER_AND_USER}</option>
					</select>
				</td>
			</tr>
		</table>
		<div class="buttons">
			<input name="addMenu" type="button" class="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0],'add_menu');"/>
			<input name="cancel" type="button" class="button" value="{TR_CANCEL}" onclick="location.href='settings.php'"/>
		</div>
		<!-- EDP: add_menu -->

		<!-- BDP: edit_menu -->
		<table>
			<tr>
				<th colspan="2">{TR_EDIT_MENU}</th>
			</tr>
			<tr>
				<td><label for="bname2">{TR_MENU_NAME}</label></td>
				<td>
					<input type="text" name="bname" id="bname2" value="{MENU_NAME}"/>
				</td>
			</tr>
			<tr>
				<td><label for="blink2">{TR_MENU_LINK}</label></td>
				<td>
					<input type="text" name="blink" id="blink2" value="{MENU_LINK}"/>
				</td>
			</tr>
			<tr>
				<td><label for="btarget2">{TR_MENU_TARGET}</label></td>
				<td>
					<input type="text" name="btarget" id="btarget2" value="{MENU_TARGET}"/>
				</td>
			</tr>
			<tr>
				<td><label for="bview2">{TR_VIEW_FROM}</label></td>
				<td>
					<select name="bview" id="bview2">
						<option value="admin">{ADMIN}</option>
						<option value="reseller">{RESELLER}</option>
						<option value="user">{USER}</option>
						<option value="all">{RESSELER_AND_USER}</option>
					</select>
				</td>
			</tr>
		</table>
		<div class="buttons">
			<input name="editMenu" type="button" class="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0],'edit_button');"/>
			<input name="cancel" type="button" class="button" value="{TR_CANCEL}" onclick="location.href='settings.php'"/>
		</div>
		<!-- EDP: edit_menu -->

		<input type="hidden" name="eid" value="{EID}"/>
		<input type="hidden" name="uaction" value=""/>
	</form>
</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
