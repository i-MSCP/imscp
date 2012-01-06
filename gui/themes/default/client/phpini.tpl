		<!-- BDP: php_editor_first_block_js -->
		<script type="text/javascript">
			/*<![CDATA[*/
				$(document).ready(function() {
					// Fix for http://bugs.jqueryui.com/ticket/7856
					$('[type=checkbox]').change(function() {
						if(!$(this).is(':checked')) {
							$(this).blur();
						}
					});
				});
			/*]]>*/
		</script>
		<!-- EDP: php_editor_first_block_js -->

			<p class="hint" style="font-variant: small-caps;font-size: small;">{TR_PAGE_TEXT}</p>

			<form name="editFrm" method="post" action="phpini.php">
				<!-- BDP: php_editor_first_block -->
				<table class="firstColFixed">
					<tr>
						<th>{TR_DIRECTIVE_NAME}</th>
						<th>{TR_DIRECTIVE_VALUE}</th>
					</tr>
					<!-- BDP: allow_url_fopen_block -->
					<tr>
						<td>
							<label for="allow_url_fopen">{TR_ALLOW_URL_FOPEN}</label>
						</td>
						<td>
							<select name="allow_url_fopen" id="allow_url_fopen">
								<option value="Off"{ALLOW_URL_FOPEN_OFF}>{TR_VALUE_OFF}</option>
								<option value="On"{ALLOW_URL_FOPEN_ON}>{TR_VALUE_ON}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: allow_url_fopen_block -->
					<!-- BDP: register_globals_block -->
					<tr>
						<td>
							<label for="register_globals">{TR_REGISTER_GLOBALS}</label>
						</td>
						<td>
							<select name="register_globals" id="register_globals">
								<option value="Off"{REGISTER_GLOBALS_OFF}>{TR_VALUE_OFF}</option>
								<option value="On"{REGISTER_GLOBALS_ON}>{TR_VALUE_ON}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: register_globals_block -->
					<!-- BDP: display_errors_block -->
					<tr>
						<td>
							<label for="display_errors">{TR_DISPLAY_ERRORS}</label>
						</td>
						<td>
							<select name="display_errors" id="display_errors">
								<option value="Off"{DISPLAY_ERRORS_OFF}>{TR_VALUE_OFF}</option>
								<option value="On"{DISPLAY_ERRORS_ON}>{TR_VALUE_ON}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: display_errors_block -->
					<!-- BDP: error_reporting_block -->
					<tr>
						<td>
							<label for="error_reporting">{TR_ERROR_REPORTING}</label>
						</td>
						<td>
							<select name="error_reporting" id="error_reporting">
								<option value="E_ALL &amp; ~E_NOTICE"{ERROR_REPORTING_0}>{TR_ERROR_REPORTING_DEFAULT}</option>
								<option value="E_ALL | E_STRICT"{ERROR_REPORTING_1}>{TR_ERROR_REPORTING_DEVELOPEMENT}</option>
								<option value="E_ALL &amp; ~E_DEPRECATED"{ERROR_REPORTING_2}>{TR_ERROR_REPORTING_PRODUCTION}</option>
								<option value="0"{ERROR_REPORTING_3}>{TR_ERROR_REPORTING_NONE}</option>
							</select>
						</td>
					</tr>
					<!-- EDP: error_reporting_block -->
					<!-- BDP: disable_functions_block -->
					<tr>
						<td>
							<label>{TR_DISABLE_FUNCTIONS}</label>
						</td>
						<td>
							<div class="checkbox">
								<input name="show_source" id="show_source" type="checkbox" {SHOW_SOURCE} value="show_source" />
								<label for="show_source">show_source</label>
								<input name="system" id="system" type="checkbox"{SYSTEM} value="system" />
								<label for="system">system</label>
								<input name="shell_exec" id="shell_exec" type="checkbox"{SHELL_EXEC} value="shell_exec" />
								<label for="shell_exec">shell_exec</label>
								<input name="passthru" id="passthru" type="checkbox"{PASSTHRU} value="passthru" />
								<label for="passthru">passthru</label>
								<input name="exec" id="exec" type="checkbox"{EXEC} value="exec" />
								<label for="exec">exec</label>
								<input name="phpinfo" id="phpinfo" type="checkbox"{PHPINFO} value="phpinfo" />
								<label for="phpinfo">phpinfo</label>
								<input name="shell" id="shell" type="checkbox"{SHELL} value="shell" />
								<label for="shell">shell</label>
								<input name="symlink" id="symlink" type="checkbox"{SYMLINK} value="symlink" />
								<label for="symlink">symlink</label>
							</div>
						</td>
					</tr>
					<!-- EDP: disable_functions_block -->
				</table>
				<!-- EDP: php_editor_first_block -->
				<!-- BDP: php_editor_second_block -->
				<table class="firstColFixed">
					<tr>
						<th>{TR_PARAMETER}</th>
						<th>{TR_STATUS}</th>
					</tr>
					<tr>
						<td>
							<label>{TR_DISABLE_FUNCTIONS_EXEC}</label><span style="vertical-align: middle;" class="icon i_help" id="exec_help" title="{TR_EXEC_HELP}">{TR_HELP}</span>
						</td>
						<td>
							<div class="ui-buttonset">
								<input type="radio" name="exec" id="exec_allowed" value="allows" {EXEC_ALLOWED}/>
								<label for="exec_allowed">{TR_ALLOWED}</label>
								<input type="radio" name="exec" value="disallows" id="exec_disallowed" {EXEC_DISALLOWED}/>
								<label for="exec_disallowed">{TR_DISALLOWED}</label>
							</div>
						</td>
					</tr>
				</table>
				<!-- EDP: php_editor_second_block -->
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_UPDATE_DATA}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
			</form>
