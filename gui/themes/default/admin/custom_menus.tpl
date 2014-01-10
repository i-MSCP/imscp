
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		var errFieldsStack = {ERR_FIELDS_STACK};

		$.each(errFieldsStack, function () { $('#' + this).css('border-color', '#ca1d11'); });

		$(".datatable").dataTable({ oLanguage:{DATATABLE_TRANSLATIONS}});
	});

	function action_delete(url, subject) {
		return confirm(sprintf({TR_MESSAGE_DELETE}, subject));
	}
	/*]]>*/
</script>

<!-- BDP: menus_list_block -->
<table class="firstColFixed datatable">
	<thead>
	<tr>
		<th>{TR_MENU_NAME_AND_LINK}</th>
		<th>{TR_TH_LEVEL}</th>
		<th>{TR_MENU_ORDER}</th>
		<th>{TR_ACTIONS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: menu_block -->
	<tr>
		<td>
			<p><a href="{LINK}" class="link" target="_blank"><strong>{MENU_NAME}</strong></a></p>
			<span>{LINK}</span>
		</td>
		<td>{LEVEL}</td>
		<td>{ORDER}</td>
		<td>
			<a href="custom_menus.php?edit_id={MENU_ID}" class="icon i_edit">{TR_EDIT}</a>
			<a href="custom_menus.php?delete_id={MENU_ID}" class="icon i_delete"
			   onclick="return action_delete('custom_menus.php?delete_id={MENU_ID}', '{MENU_NAME}')">{TR_DELETE}</a>
		</td>
	</tr>
	<!-- EDP: menu_block -->
	</tbody>
</table>
<!-- EDP: menus_list_block -->

<form name="menuFrm" method="post" action="custom_menus.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CUSTOM_MENU_PROPERTIES}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="menu_name">{TR_MENU_NAME}</label></td>
			<td><input type="text" name="menu_name" id="menu_name" value="{MENU_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="menu_link">{TR_MENU_LINK}</label></td>
			<td><input type="text" name="menu_link" id="menu_link" value="{MENU_LINK}"/></td>
		</tr>
		<tr>
			<td><label for="menu_target">{TR_MENU_TARGET}</label></td>
			<td>
				<select id="menu_target" name="menu_target">
					<!-- BDP: menu_target_block -->
					<option value="{TARGET_VALUE}" {SELECTED_TARGET}>{TR_TARGET}</option>
					<!-- EDP: menu_target_block -->
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="menu_level">{TR_VIEW_FROM}</label></td>
			<td>
				<select name="menu_level" id="menu_level">
					<!-- BDP: menu_level_block -->
					<option value="{LEVEL_VALUE}" {SELECTED_LEVEL}>{TR_LEVEL}</option>
					<!-- EDP: menu_level_block -->
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="menu_order">{TR_MENU_ORDER} <span class="italic">({TR_OPTIONAL})</span></label></td>
			<td><input type="text" name="menu_order" id="menu_order" value="{MENU_ORDER}"/></td>
		</tr>
		</tbody>
	</table>

	<!-- BDP: add_menu -->
	<div class="buttons">
		<input name="addMenu" type="button" value="{TR_ADD}" onclick="return sbmt(document.forms[0], 'menu_add');"/>
		<a class="link_as_button" href="settings.php" tabindex="4">{TR_CANCEL}</a>
	</div>
	<!-- EDP: add_menu -->

	<!-- BDP: edit_menu -->
	<div class="buttons">
		<input name="editMenu" type="button" value="{TR_UPDATE}"
			   onclick="return sbmt(document.forms[0], 'menu_update');"/>
		<a class="link_as_button" href="custom_menus.php">{TR_CANCEL}</a>
		<input type="hidden" name="edit_id" value="{EDIT_ID}" /">
	</div>
	<!-- EDP: edit_menu -->

	<input type="hidden" name="uaction" value=""/>
</form>
