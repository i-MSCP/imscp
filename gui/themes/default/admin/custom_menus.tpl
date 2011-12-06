<!-- INCLUDE "../shared/layout/header.tpl" -->
<script type="text/javascript">
/* <![CDATA[ */
	$(document).ready(function() {
		errFieldsStack = {ERR_FIELDS_STACK};
		$.each(errFieldsStack, function(){$('#' + this).css('border-color', '#ca1d11');});
	});

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
		<h1 class="settings">{TR_MENU_SETTINGS}</h1>
	</div>
	<ul class="location-menu">
		<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
	</ul>
	<ul class="path">
		<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
		<li><a href="#" onclick="return false;">{TR_TITLE_CUSTOM_MENUS}</a></li>
	</ul>
</div>

<div class="left_menu">
	{MENU}
</div>

<div class="body">
	<h2 class="custom_link"><span>{TR_TITLE_CUSTOM_MENUS}</span></h2>

	<!-- BDP: page_message -->
	<div class="{MESSAGE_CLS}">{MESSAGE}</div>
	<!-- EDP: page_message -->

	<!-- BDP: menus_list_block -->
	<table>
		<tr>
			<th>{TR_MENU_NAME}</th>
			<th>{TR_LEVEL}</th>
			<th>{TR_ACTONS}</th>
		</tr>
		<!-- BDP: menu_block -->
		<tr>
			<td>
				<p><a href="{LINK}" class="link" target="_blank"><strong>{MENU_NAME}</strong></a></p>
				<span>{LINK}</span>
			</td>
			<td>{LEVEL}</td>
			<td>
				<a href="custom_menus.php?edit_id={MENU_ID}" class="icon i_edit">{TR_EDIT}</a>
				<a href="custom_menus.php?delete_id={MENU_ID}" class="icon i_delete" onclick="return action_delete('custom_menus.php?delete_id={MENU_ID}', '{MENU_NAME}')">{TR_DELETE}</a>
			</td>
		</tr>
		<!-- EDP: menu_block -->
	</table>
	<!-- EDP: menus_list_block -->

	<form name="menuFrm" method="post" action="custom_menus.php">
		<table>
			<tr>
				<th colspan="2">{TR_FORM_NAME}</th>
			</tr>
			<tr>
				<td><label for="menu_name">{TR_MENU_NAME}</label></td>
				<td>
					<input type="text" name="menu_name" id="menu_name" value="{MENU_NAME}"/>
				</td>
			</tr>
			<tr>
				<td><label for="menu_link">{TR_MENU_LINK}</label></td>
				<td>
					<input type="text" name="menu_link" id="menu_link" value="{MENU_LINK}"/>
				</td>
			</tr>
			<tr>
				<td><label for="menu_target">{TR_MENU_TARGET}</label></td>
				<td>
					<input type="text" name="menu_target" id="menu_target" value="{MENU_TARGET}"/>
				</td>
			</tr>
			<tr>
				<td><label for="menu_level">{TR_VIEW_FROM}</label></td>
				<td>
					<select name="menu_level" id="menu_level">
						<option value="admin" {ADMIN_VIEW}>{ADMIN}</option>
						<option value="reseller" {RESELLER_VIEW}>{RESELLER}</option>
						<option value="user" {USER_VIEW}>{USER}</option>
						<option value="all" {ALL_VIEW}>{RESSELER_AND_USER}</option>
					</select>
				</td>
			</tr>
		</table>
		<!-- BDP: add_menu -->
		<div class="buttons">
			<input name="addMenu" type="button" class="button" value="{TR_ADD}" onclick="return sbmt(document.forms[0], 'menu_add');"/>
			<input name="cancel" type="button" class="button" value="{TR_CANCEL}" onclick="location.href='settings.php'"/>
		</div>
		<!-- EDP: add_menu -->
		<!-- BDP: edit_menu -->
		<div class="buttons">
			<input name="editMenu" type="button" class="button" value="{TR_UPDATE}" onclick="return sbmt(document.forms[0], 'menu_update');"/>
			<input name="cancel" type="button" class="button" value="{TR_CANCEL}" onclick="location.href='custom_menus.php'"/>
			<input type="hidden" name="edit_id" value="{EDIT_ID}" /">
		</div>
		<!-- EDP: edit_menu -->

		<input type="hidden" name="uaction" value=""/>
	</form>
</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
