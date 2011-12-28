<script type="text/javascript">
	/* <![CDATA[ */
	function action_delete(url, subject) {
	return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}

			function begin_js() {
			document.forms[0].elements["users[]"].disabled = {USER_FORM_ELEMENS};
			document.forms[0].elements["groups[]"].disabled = {GROUP_FORM_ELEMENS};
			}

					function changeType(wath) {
	document.forms[0].elements["users[]"].disabled = wath != "user";
	document.forms[0].elements["groups[]"].disabled = wath == "user";
	}
	/* ]]> */
</script>
<form name="addProtectedAreaFrm" method="post" action="protected_areas_add.php">
	<table class="firstColFixed">
		<tr>
			<th colspan="2">{TR_PROTECTED_AREA}</th>
		</tr>
		<tr>
			<td><label for="paname">{TR_AREA_NAME}</label></td>
			<td><input name="paname" type="text" class="textinput" id="paname" value="{AREA_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="path">{TR_PATH}</label></td>
			<td>
				<input name="other_dir" type="text" class="textinput" id="path" value="{PATH}"/>
				<a href="#" onclick="showFileTree();return false;" class="icon i_bc_folder">{CHOOSE_DIR}</a>
			</td>
		</tr>
	</table>
	<table class="firstColFixed">
		<tr>
			<th>{TR_USER}</th>
			<th>{TR_GROUPS}</th>
		</tr>
		<tr>
			<td>
				<label for="ptype_1">{TR_USER_AUTH}</label>
				<input type="radio" id="ptype_1" name="ptype" value="user" {USER_CHECKED} onfocus="changeType('user');"/>
			</td>
			<td>
				<label for="ptype_2">{TR_GROUP_AUTH}</label>
				<input type="radio" id="ptype_2" name="ptype" value="group" {GROUP_CHECKED} onfocus="changeType('group');"/>
			</td>
		</tr>
		<tr>
			<td>
				<select name="users[]" multiple="multiple" size="5">
					<!-- BDP: user_item -->
					<option value="{USER_VALUE}" {USER_SELECTED}>{USER_LABEL}</option>
					<!-- EDP: user_item -->
				</select>
			</td>
			<td>
				<select name="groups[]" multiple="multiple" size="5">
					<!-- BDP: group_item -->
					<option value="{GROUP_VALUE}" {GROUP_SELECTED}>{GROUP_LABEL}</option>
					<!-- EDP: group_item -->
				</select>
			</td>
		</tr>
	</table>
	<div class="buttons">
		<input type="hidden" name="use_other_dir"/>
		<input type="hidden" name="sub" value="YES"/>
		<input type="hidden" name="cdir" value="{CDIR}"/>
		<input type="hidden" name="uaction" value=""/>
		<input name="Button" type="button" value="{TR_PROTECT_IT}" onclick="return sbmt(document.forms[0],'protect_it');"/>
		<!-- BDP: unprotect_it -->
		<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas_delete.php?id={CDIR}');return document.MM_returnValue" value="{TR_UNPROTECT_IT}"/>
		<!-- EDP: unprotect_it -->
		<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_MANAGE_USERS_AND_GROUPS}"/>
		<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas.php');return document.MM_returnValue" value="{TR_CANCEL}"/>
	</div>
</form>
