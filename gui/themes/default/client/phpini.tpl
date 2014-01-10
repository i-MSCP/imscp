
<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_PAGE_TEXT}</p>

<form name="editFrm" method="post" action="phpini.php">
	<!-- BDP: php_editor_first_block -->
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_PHP_SETTINGS}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: allow_url_fopen_block -->
		<tr>
			<td><label for="allow_url_fopen">{TR_ALLOW_URL_FOPEN}</label></td>
			<td>
				<div class="radio">
					<input type="radio" name="allow_url_fopen" id="allow_url_fopen_on" value="on"{ALLOW_URL_FOPEN_ON}>
					<label for="allow_url_fopen_on">{TR_YES}</label>
					<input type="radio" name="allow_url_fopen" id="allow_url_fopen_off"
						   value="off"{ALLOW_URL_FOPEN_OFF}>
					<label for="allow_url_fopen_off">{TR_NO}</label>
				</div>
			</td>
		</tr>
		<!-- EDP: allow_url_fopen_block -->
		<!-- BDP: display_errors_block -->
		<tr>
			<td><label for="display_errors">{TR_DISPLAY_ERRORS}</label></td>
			<td>
				<div class="radio">
					<input type="radio" name="display_errors" id="display_errors_on" value="on"{DISPLAY_ERRORS_ON}>
					<label for="display_errors_on">{TR_YES}</label>
					<input type="radio" name="display_errors" id="display_errors_off" value="off"{DISPLAY_ERRORS_OFF}>
					<label for="display_errors_off">{TR_NO}</label>
				</div>
			</td>
		</tr>
		<!-- EDP: display_errors_block -->
		<!-- BDP: disable_functions_block -->
		<tr>
			<td><label>{TR_DISABLE_FUNCTIONS}</label></td>
			<td>
				<div class="checkbox">
					<input name="show_source" id="show_source" type="checkbox" {SHOW_SOURCE} value="show_source"/>
					<label for="show_source">show_source</label>
					<input name="system" id="system" type="checkbox"{SYSTEM} value="system"/>
					<label for="system">system</label>
					<input name="shell_exec" id="shell_exec" type="checkbox"{SHELL_EXEC} value="shell_exec"/>
					<label for="shell_exec">shell_exec</label>
					<input name="passthru" id="passthru" type="checkbox"{PASSTHRU} value="passthru"/>
					<label for="passthru">passthru</label>
					<input name="exec" id="exec" type="checkbox"{EXEC} value="exec"/>
					<label for="exec">exec</label>
					<input name="phpinfo" id="phpinfo" type="checkbox"{PHPINFO} value="phpinfo"/>
					<label for="phpinfo">phpinfo</label>
					<input name="shell" id="shell" type="checkbox"{SHELL} value="shell"/>
					<label for="shell">shell</label>
					<input name="symlink" id="symlink" type="checkbox"{SYMLINK} value="symlink"/>
					<label for="symlink">symlink</label>
					<input name="proc_open" id="proc_open" type="checkbox"{PROC_OPEN} value="proc_open"/>
					<label for="proc_open">proc_open</label>
					<input name="popen" id="popen" type="checkbox"{POPEN} value="popen"/>
					<label for="popen">popen</label>
				</div>
			</td>
		</tr>
		<!-- EDP: disable_functions_block -->
		<!-- BDP: php_editor_second_block -->
		<tr>
			<td>
				<label>{TR_DISABLE_FUNCTIONS_EXEC}</label>
				<span style="vertical-align: middle;" class="tips icon i_help" id="exec_help" title="{TR_EXEC_HELP}"></span>
			</td>
			<td>
				<div class="radio">
					<input type="radio" name="exec" id="exec_yes" value="allows"{EXEC_ALLOWED}/>
					<label for="exec_yes">{TR_YES}</label>
					<input type="radio" name="exec" value="disallows" id="exec_no"{EXEC_DISALLOWED}/>
					<label for="exec_no">{TR_NO}</label>
				</div>
			</td>
		</tr>
		<!-- EDP: php_editor_second_block -->
		<!-- BDP: error_reporting_block -->
		<tr>
			<td><label for="error_reporting">{TR_ERROR_REPORTING}</label></td>
			<td>
				<select name="error_reporting" id="error_reporting">
					<option value="E_ALL &amp; ~E_NOTICE"{ERROR_REPORTING_0}>{TR_ERROR_REPORTING_DEFAULT}</option>
					<option value="E_ALL | E_STRICT"{ERROR_REPORTING_1}>{TR_ERROR_REPORTING_DEVELOPEMENT}</option>
					<option
						value="E_ALL &amp; ~E_DEPRECATED"{ERROR_REPORTING_2}>{TR_ERROR_REPORTING_PRODUCTION}</option>
					<option value="0"{ERROR_REPORTING_3}>{TR_ERROR_REPORTING_NONE}</option>
				</select>
			</td>
		</tr>
		<!-- EDP: error_reporting_block -->
		</tbody>
	</table>

	<!-- EDP: php_editor_first_block -->
	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_UPDATE_DATA}"/>
		<a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
	</div>
</form>
