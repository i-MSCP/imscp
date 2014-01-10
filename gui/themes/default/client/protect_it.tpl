
<script type="text/javascript">
	/* <![CDATA[ */

	var js_i18n_tr_ftp_directories = '{TR_FTP_DIRECTORIES}';
	var js_i18n_tr_close = '{TR_CLOSE}';

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
		<thead>
		<tr>
			<th colspan="2">{TR_PROTECTED_AREA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="paname">{TR_AREA_NAME}</label></td>
			<td><input name="paname" type="text" class="textinput" id="paname" value="{AREA_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="ftp_directory">{TR_PATH}</label></td>
			<td>
				<input name="other_dir" type="text" class="textinput" id="ftp_directory" value="{PATH}"/>
				<a href="#" onclick="chooseFtpDir();" class="icon i_bc_folder">{CHOOSE_DIR}</a>
			</td>
		</tr>
		</tbody>
	</table>

	<table class="firstColFixed">
		<thead>
		<tr>
			<th>{TR_USER}</th>
			<th>{TR_GROUPS}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<label for="ptype_1">{TR_USER_AUTH}</label>
				<input type="radio" id="ptype_1" name="ptype" value="user" {USER_CHECKED}
					   onfocus="changeType('user');"/>
			</td>
			<td>
				<label for="ptype_2">{TR_GROUP_AUTH}</label>
				<input type="radio" id="ptype_2" name="ptype" value="group" {GROUP_CHECKED}
					   onfocus="changeType('group');"/>
			</td>
		</tr>
		<tr>
			<td>
				<label>
					<select name="users[]" multiple="multiple" size="5">
						<!-- BDP: user_item -->
						<option value="{USER_VALUE}" {USER_SELECTED}>{USER_LABEL}</option>
						<!-- EDP: user_item -->
					</select>
				</label>
			</td>
			<td>
				<label>
					<select name="groups[]" multiple="multiple" size="5">
						<!-- BDP: group_item -->
						<option value="{GROUP_VALUE}" {GROUP_SELECTED}>{GROUP_LABEL}</option>
						<!-- EDP: group_item -->
					</select>
				</label>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="use_other_dir"/>
		<input type="hidden" name="sub" value="YES"/>
		<input type="hidden" name="cdir" value="{CDIR}"/>
		<input type="hidden" name="uaction" value=""/>
		<input name="Button" type="button" value="{TR_PROTECT_IT}"
			   onclick="return sbmt(document.forms[0],'protect_it');"/>

		<!-- BDP: unprotect_it -->
		<a class="link_as_button" href="protected_areas_delete.php?id={CDIR}">{TR_UNPROTECT_IT}</a>
		<!-- EDP: unprotect_it -->

		<a class="link_as_button" href="protected_user_manage.php">{TR_MANAGE_USERS_AND_GROUPS}</a>
		<a class="link_as_button" href="protected_areas.php">{TR_CANCEL}</a>
	</div>
</form>
