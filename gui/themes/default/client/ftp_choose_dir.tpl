
			<script type="text/javascript">
			/*<![CDATA[*/
				/** @return boolean */
				function copyFtpDir(ftpDir) {
					$('#ftp_directory', window.parent.document).val(ftpDir);
					window.parent.$("#dial_ftp_dir").dialog('close');
					return false;
				}
			/*]]>*/
			</script>
			<!-- BDP: ftp_chooser -->
			<form>
				<table class="firstColFixed">
					<tr>
						<th>{TR_DIRS}</th>
						<th>{TR_ACTION}</th>
					</tr>
					<!-- BDP: dir_item -->
					<tr>
						<!-- BDP: list_item -->
						<td><a href="{LINK}" class="icon i_bc_{ICON}">{DIR_NAME}</a></td>
						<td>
							<!-- BDP: action_link -->
							<a href="#" onclick="copyFtpDir('{CHOOSE_IT}');">{CHOOSE}</a>
							<!-- EDP: action_link -->
						</td>
						<!-- EDP: list_item -->
					</tr>
					<!-- EDP: dir_item -->
				</table>
			</form>
			<!-- EDP: ftp_chooser -->
