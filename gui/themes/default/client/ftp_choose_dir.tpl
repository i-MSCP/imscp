
			<script type="text/javascript">
			/*<![CDATA[*/
				function CopyText(mp) {
					$('#ftp_directory', window.parent.document).val(mp);
					window.parent.$("#dial_ftp_dir").dialog('close');
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
							<a href="#" onclick="CopyText('{CHOOSE_IT}');">{CHOOSE}</a>
							<input type="hidden" name="{CHOOSE_IT}" value="{CHOOSE_IT}"/>
							<!-- EDP: action_link -->
						</td>
						<!-- EDP: list_item -->
					</tr>
					<!-- EDP: dir_item -->
				</table>
			</form>
			<!-- EDP: ftp_chooser -->
