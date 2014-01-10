
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		errFieldsStack = {ERR_FIELDS_STACK};
		$.each(errFieldsStack, function () {
			$('#' + this).css('border-color', '#ca1d11');
		});
		$('<img>').attr({src:'{THEME_ASSETS_PATH}/images/ajax/small-spinner.gif'}).addClass('small-spinner').
			insertAfter($('#password, #password_confirmation'));
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"bStateSave": true
			}
		);
		$.ajaxSetup({
			url: $(location).attr('pathname'),
			type: 'GET',
			data: 'edit_id={EDIT_ID}',
			datatype: 'text',
			beforeSend: function (xhr){xhr.setRequestHeader('Accept','text/plain');},
			success: function (r) { $('#password, #password_confirmation').val(r); },
			error: iMSCPajxError
		});

		$('#password ~ img, #password_confirmation ~ img').ajaxStart(function () { $(this).show() });
		$('#password ~ img, #password_confirmation ~ img').ajaxStop(function () { $(this).hide() });
		$('#generate_password').click(function () { $.ajax(); });
		$('#reset_password').click(function () { $('#password, #password_confirmation').val('');});
		$('#reset_password').trigger('click');

		// Create dialog box for some messages (password and notices)
		$('#dialog_box').dialog({
			modal: true,
			autoOpen: false,
			hide: 'blind',
			show: 'blind',
			buttons: { Ok: function () { $(this).dialog('close'); }}
		});

		// Show generated password in specific dialog box
		$('#show_password').click(function () {
			var password = $('#password').val();
			if (password == '') {
				password = '<br/>{TR_PASSWORD_GENERATION_NEEDED}';
			} else {
				password = '<br/>{TR_NEW_PASSWORD_IS}: <strong>' + $('#password').val() + '</strong>';
			}
			$('#dialog_box').dialog("option", "title", '{TR_PASSWORD}').html(password);
			$('#dialog_box').dialog('open');
		});

		// Disable enter key for form submission (really needed ?)
		$(':input').on('keypress', function (e) {
			if (e.keyCode == 13) {
				e.preventDefault();
				$('#dialog_box').dialog("option", "title", '{TR_NOTICE}').html('<br />{TR_EVENT_NOTICE}');
				$('#dialog_box').dialog("open");
			}
		});

		// Workaround to prevent click event on readonly input (and their labels)
		$('input, label').click(function (e) {
			if (this.type == 'checkbox' && $(this).is('[readonly]')) {
				e.preventDefault();
			}
		});

		$('#php_editor_dialog').dialog({
			hide: 'blind',
			show: 'slide',
			focus: false,
			autoOpen: false,
			width: 650,
			modal: true,
			buttons: { '{TR_CLOSE}': function(){ $(this).dialog('close'); } }
		});

		$('form').submit(function (){ $('#php_editor_dialog').parent().appendTo($('#dialogContainer')); });

		$('#php_editor_dialog_open').button({icons:{primary:'ui-icon-gear'}}).click(function (e) {
			$('#php_editor_dialog').dialog('open');
			return false;
		});

		if ($('#php_ini_system_no').is(':checked')) { $('#php_editor_dialog_open').hide(); }

		$('input[name="php_ini_system"]').change(function (){ $('#php_editor_dialog_open').fadeToggle(); });

		var errorMessages = $('.php_editor_error');

		function _updateErrorMesssages(k, t) {
			if (t != undefined) {
				if (!$('#err_' + k).length) {
					$("#msg_default").remove();
					errorMessages.append('<span style="display:block" id="err_' + k + '">' + t + '</span>').
						removeClass('success').addClass('error');
				}
			} else if ($('#err_' + k).length) {
				$('#err_' + k).remove();
			}

			if ($.trim(errorMessages.text()) == '') {
				errorMessages.empty().append('<span id="msg_default">{TR_FIELDS_OK}</span>').
					removeClass('error').addClass('success');
			}
		}

		$.each(['php_ini_max_post_max_size', 'php_ini_max_upload_max_filesize', 'php_ini_max_max_execution_time' ,
			'php_ini_max_max_input_time', 'php_ini_max_memory_limit'], function () {
			var k = this;
			$('#' + k).keyup(function () {
				var r = /^(0|[1-9]\d*)$/; // Regexp to check value syntax
				var nv = $(this).val(); // Get new value to be checked

				if (!r.test(nv) || parseInt(nv) < 0 || parseInt(nv) >= 10000) {
					$(this).addClass('ui-state-error');
					_updateErrorMesssages(k, sprintf('{TR_VALUE_ERROR}', k.substr(12), 0, 10000));
				} else {
					$(this).removeClass('ui-state-error');
					_updateErrorMesssages(k);
				}
			}).trigger('keyup');
		});
	});
	/*]]>*/
</script>

<div id="dialog_box"></div>

<form name="editFrm" method="post" action="reseller_edit.php?edit_id={EDIT_ID}">
<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_ACCOUNT_DATA}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td>{TR_RESELLER_NAME}</td>
		<td>{RESELLER_NAME}</td>
	</tr>
	<tr>
		<td><label for="password">{TR_PASSWORD}</label></td>
		<td>
			<input type="password" name="password" id="password" value="{PASSWORD}" autocomplete="off"/>
			<input type="button" id="generate_password" value="{TR_GENERATE}"/>
			<input type="button" id="show_password" value="{TR_SHOW}"/>
			<input type="button" id="reset_password" value="{TR_RESET}"/>
		</td>
	</tr>
	<tr>
		<td><label for="password_confirmation">{TR_PASSWORD_CONFIRMATION}</label></td>
		<td>
			<input type="password" name="password_confirmation" id="password_confirmation"
				   value="{PASSWORD_CONFIRMATION}" autocomplete="off"/>
		</td>
	</tr>
	<tr>
		<td><label for="email">{TR_EMAIL}</label></td>
		<td><input type="text" name="email" id="email" value="{EMAIL}"/></td>
	</tr>
	</tbody>
</table>

<!-- BDP: ips_block -->
<table class="datatable">
	<thead>
	<tr>
		<th>{TR_IP_ADDRESS}</th>
		<th>{TR_ASSIGN}</th>
		<th>{TR_STATUS}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: ip_block -->
	<tr>
		<td>{IP_NUMBER}</td>
		<td>
			<input type="checkbox" id="ip_{IP_ID}" name="reseller_ips[]" value="{IP_ID}" {IP_ASSIGNED} {IP_READONLY} />
		</td>
		<td>{IP_STATUS}</td>
	</tr>
	<!-- EDP: ip_block -->
	</tbody>
</table>
<!-- EDP: ips_block -->

<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_ACCOUNT_LIMITS}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><label for="max_dmn_cnt">{TR_MAX_DMN_CNT}</label></td>
		<td><input type="text" name="max_dmn_cnt" id="max_dmn_cnt" value="{MAX_DMN_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_sub_cnt">{TR_MAX_SUB_CNT}</label></td>
		<td><input type="text" name="max_sub_cnt" id="max_sub_cnt" value="{MAX_SUB_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_als_cnt">{TR_MAX_ALS_CNT}</label></td>
		<td><input type="text" name="max_als_cnt" id="max_als_cnt" value="{MAX_ALS_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_mail_cnt">{TR_MAX_MAIL_CNT}</label></td>
		<td><input type="text" name="max_mail_cnt" id="max_mail_cnt" value="{MAX_MAIL_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_ftp_cnt">{TR_MAX_FTP_CNT}</label></td>
		<td><input type="text" name="max_ftp_cnt" id="max_ftp_cnt" value="{MAX_FTP_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_sql_db_cnt">{TR_MAX_SQL_DB_CNT}</label></td>
		<td><input type="text" name="max_sql_db_cnt" id="max_sql_db_cnt" value="{MAX_SQL_DB_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_sql_user_cnt">{TR_MAX_SQL_USER_CNT}</label></td>
		<td><input type="text" name="max_sql_user_cnt" id="max_sql_user_cnt" value="{MAX_SQL_USER_CNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_traff_amnt">{TR_MAX_TRAFF_AMNT}</label></td>
		<td><input type="text" name="max_traff_amnt" id="max_traff_amnt" value="{MAX_TRAFF_AMNT}"/></td>
	</tr>
	<tr>
		<td><label for="max_disk_amnt">{TR_MAX_DISK_AMNT}</label></td>
		<td><input type="text" name="max_disk_amnt" id="max_disk_amnt" value="{MAX_DISK_AMNT}"/></td>
	</tr>
	</tbody>
</table>

<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_FEATURES}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><label>{TR_PHP_EDITOR}</label></td>
		<td id="dialogContainer" style="height: 30px; line-height: 30px;">
			<div class="radio" style="position:relative;">
				<input type="radio" name="php_ini_system" id="php_ini_system_yes" value="yes" {PHP_INI_SYSTEM_YES} />
				<label for="php_ini_system_yes">{TR_YES}</label>
				<input type="radio" name="php_ini_system" id="php_ini_system_no" value="no" {PHP_INI_SYSTEM_NO} />
				<label for="php_ini_system_no">{TR_NO}</label>
				<input type="button" id="php_editor_dialog_open" value="{TR_SETTINGS}"/>
			</div>
			<div style="margin:0" id="php_editor_dialog" title="{TR_PHP_EDITOR_SETTINGS}">
				<div class="php_editor_error success">
					<span id="msg_default">{TR_FIELDS_OK}</span>
				</div>
				<table class="firstColFixed">
					<thead>
					<tr>
						<th colspan="2">{TR_PERMISSIONS}</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td>
							{TR_PHP_INI_AL_ALLOW_URL_FOPEN}
							<span class="tips icon i_help" title="{TR_PHP_INI_PERMISSION_HELP}"></span>
						</td>
						<td>
							<div class="radio">
								<input type="radio" name="php_ini_al_allow_url_fopen"
									   id="php_ini_al_allow_url_fopen_yes"
									   value="yes" {PHP_INI_AL_ALLOW_URL_FOPEN_YES}/>
								<label for="php_ini_al_allow_url_fopen_yes">{TR_YES}</label>
								<input type="radio" name="php_ini_al_allow_url_fopen" id="php_ini_al_allow_url_fopen_no"
									   value="no" {PHP_INI_AL_ALLOW_URL_FOPEN_NO}/>
								<label for="php_ini_al_allow_url_fopen_no">{TR_NO}</label>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							{TR_PHP_INI_AL_DISPLAY_ERRORS}
							<span class="tips icon i_help" title="{TR_PHP_INI_PERMISSION_HELP}"></span>
						</td>
						<td>
							<div class="radio">
								<input type="radio" name="php_ini_al_display_errors" id="php_ini_al_display_errors_yes"
									   value="yes" {PHP_INI_AL_DISPLAY_ERRORS_YES}/>
								<label for="php_ini_al_display_errors_yes">{TR_YES}</label>
								<input type="radio" name="php_ini_al_display_errors" id="php_ini_al_display_errors_no"
									   value="no" {PHP_INI_AL_DISPLAY_ERRORS_NO}/>
								<label for="php_ini_al_display_errors_no">{TR_NO}</label>
							</div>
						</td>
					</tr>
					<!-- BDP: php_editor_disable_functions_block -->
					<tr>
						<td>
							{TR_PHP_INI_AL_DISABLE_FUNCTIONS}
							<span class="tips icon i_help" title="{TR_PHP_INI_PERMISSION_HELP}"></span>
						</td>
						<td>
							<div class="radio">
								<input type="radio" name="php_ini_al_disable_functions"
									   id="php_ini_al_disable_functions_yes"
									   value="yes" {PHP_INI_AL_DISABLE_FUNCTIONS_YES}/>
								<label for="php_ini_al_disable_functions_yes">{TR_YES}</label>
								<input type="radio" name="php_ini_al_disable_functions"
									   id="php_ini_al_disable_functions_no"
									   value="no" {PHP_INI_AL_DISABLE_FUNCTIONS_NO}/>
								<label for="php_ini_al_disable_functions_no">{TR_NO}</label>
							</div>
						</td>
					</tr>
					<!-- EDP: php_editor_disable_functions_block -->
					</tbody>
				</table>
				<table class="firstColFixed">
					<thead>
					<tr>
						<th colspan="2">{TR_DIRECTIVES_VALUES}</th>
					</tr>
					</thead>
					<tbody>
					<tr>
						<td><label for="php_ini_max_post_max_size">{TR_PHP_INI_MAX_POST_MAX_SIZE}</label></td>
						<td>
							<input name="php_ini_max_post_max_size" id="php_ini_max_post_max_size" type="text"
								   value="{PHP_INI_MAX_POST_MAX_SIZE}"/> <span>{TR_MIB}</span>
						</td>
					</tr>
					<tr>
						<td><label for="php_ini_max_upload_max_filesize">{TR_PHP_INI_MAX_UPLOAD_MAX_FILESIZE}</label>
						</td>
						<td>
							<input name="php_ini_max_upload_max_filesize" id="php_ini_max_upload_max_filesize"
								   type="text" value="{PHP_INI_MAX_UPLOAD_MAX_FILESIZE}"/> <span>{TR_MIB}</span>
						</td>
					</tr>
					<tr>
						<td><label for="php_ini_max_max_execution_time">{TR_PHP_INI_MAX_MAX_EXECUTION_TIME}</label></td>
						<td>
							<input name="php_ini_max_max_execution_time" id="php_ini_max_max_execution_time" type="text"
								   value="{PHP_INI_MAX_MAX_EXECUTION_TIME}"/> <span>{TR_SEC}</span>
						</td>
					</tr>
					<tr>
						<td><label for="php_ini_max_max_input_time">{TR_PHP_INI_MAX_MAX_INPUT_TIME}</label></td>
						<td>
							<input name="php_ini_max_max_input_time" id="php_ini_max_max_input_time" type="text"
								   value="{PHP_INI_MAX_MAX_INPUT_TIME}"/> <span>{TR_SEC}</span>
						</td>
					</tr>
					<tr>
						<td><label for="php_ini_max_memory_limit">{TR_PHP_INI_MAX_MEMORY_LIMIT}</label></td>
						<td>
							<input name="php_ini_max_memory_limit" id="php_ini_max_memory_limit" type="text"
								   value="{PHP_INI_MAX_MEMORY_LIMIT}"/> <span>{TR_MIB}</span>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</td>
	</tr>
	<tr>
		<td>{TR_SOFTWARES_INSTALLER}</td>
		<td>
			<div class="radio">
				<input type="radio" name="software_allowed" id="software_allowed_yes"
					   value="yes" {SOFTWARES_INSTALLER_YES} />
				<label for="software_allowed_yes">{TR_YES}</label>
				<input type="radio" name="software_allowed" id="software_allowed_no"
					   value="no" {SOFTWARES_INSTALLER_NO} />
				<label for="software_allowed_no">{TR_NO}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>{TR_SOFTWARES_REPOSITORY}</td>
		<td>
			<div class="radio">
				<input type="radio" name="softwaredepot_allowed" id="softwaredepot_allowed_yes"
					   value="yes" {SOFTWARES_REPOSITORY_YES} />
				<label for="softwaredepot_allowed_yes">{TR_YES}</label>
				<input type="radio" name="softwaredepot_allowed" id="softwaredepot_allowed_no"
					   value="no" {SOFTWARES_REPOSITORY_NO} />
				<label for="softwaredepot_allowed_no">{TR_NO}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>{TR_WEB_SOFTWARES_REPOSITORY}</td>
		<td>
			<div class="radio">
				<input type="radio" name="websoftwaredepot_allowed" id="websoftwaredepot_allowed_yes"
					   value="yes" {WEB_SOFTWARES_REPOSITORY_YES} />
				<label for="websoftwaredepot_allowed_yes">{TR_YES}</label>
				<input type="radio" name="websoftwaredepot_allowed" id="websoftwaredepot_allowed_no"
					   value="no" {WEB_SOFTWARES_REPOSITORY_NO} />
				<label for="websoftwaredepot_allowed_no">{TR_NO}</label>
			</div>
		</td>
	</tr>
	<tr>
		<td>{TR_SUPPORT_SYSTEM}</td>
		<td>
			<div class="radio">
				<input type="radio" name="support_system" id="support_system_yes" value="yes" {SUPPORT_SYSTEM_YES} />
				<label for="support_system_yes">{TR_YES}</label>
				<input type="radio" name="support_system" id="support_system_no" value="no" {SUPPORT_SYSTEM_NO}/>
				<label for="support_system_no">{TR_NO}</label>
			</div>
		</td>
	</tr>
	</tbody>
</table>

<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_PERSONAL_DATA}</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><label for="customer_id">{TR_CUSTOMER_ID}</label></td>
		<td><input type="text" name="customer_id" id="customer_id" value="{CUSTOMER_ID}"/></td>
	</tr>
	<tr>
		<td><label for="fname">{TR_FNAME}</label></td>
		<td><input type="text" name="fname" id="fname" value="{FNAME}"/></td>
	</tr>
	<tr>
		<td><label for="lname">{TR_LNAME}</label></td>
		<td><input type="text" name="lname" id="lname" value="{LNAME}"/></td>
	</tr>
	<tr>
		<td><label for="gender">{TR_GENDER}</label></td>
		<td>
			<select id="gender" name="gender">
				<option value="M" {MALE}>{TR_MALE}</option>
				<option value="F" {FEMALE}>{TR_FEMALE}</option>
				<option value="U" {UNKNOWN}>{TR_UNKNOWN}</option>
			</select>
		</td>
	</tr>
	<tr>
		<td><label for="firm">{TR_FIRM}</label></td>
		<td><input type="text" name="firm" id="firm" value="{FIRM}"/></td>
	</tr>
	<tr>
		<td><label for="street1">{TR_STREET1}</label></td>
		<td><input type="text" name="street1" id="street1" value="{STREET1}"/></td>
	</tr>
	<tr>
		<td><label for="street2">{TR_STREET2}</label></td>
		<td><input type="text" name="street2" id="street2" value="{STREET2}"/></td>
	</tr>
	<tr>
		<td><label for="zip">{TR_ZIP}</label></td>
		<td><input type="text" name="zip" id="zip" value="{ZIP}"/></td>
	</tr>
	<tr>
		<td><label for="city">{TR_CITY}</label></td>
		<td><input type="text" name="city" id="city" value="{CITY}"/></td>
	</tr>
	<tr>
		<td><label for="state">{TR_STATE}</label></td>
		<td><input type="text" name="state" id="state" value="{STATE}"/></td>
	</tr>
	<tr>
		<td><label for="country">{TR_COUNTRY}</label></td>
		<td><input type="text" name="country" id="country" value="{COUNTRY}"/></td>
	</tr>
	<tr>
		<td><label for="phone">{TR_PHONE}</label></td>
		<td><input type="text" name="phone" id="phone" value="{PHONE}"/></td>
	</tr>
	<tr>
		<td><label for="fax">{TR_FAX}</label></td>
		<td><input type="text" name="fax" id="fax" value="{FAX}"/></td>
	</tr>
	</tbody>
</table>

<div class="buttons">
	<input name="submit" type="submit" value="{TR_UPDATE}"/>
	<a class="link_as_button" href="manage_users.php">{TR_CANCEL}</a>
</div>
</form>
