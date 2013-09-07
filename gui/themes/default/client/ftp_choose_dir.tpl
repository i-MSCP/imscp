
<script type="text/javascript">
	/*<![CDATA[*/
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
		<thead>
		<tr>
			<th>{TR_DIRS}</th>
			<th>{TR_ACTION}</th>
		</tr>
		</thead>
		<tbody>
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
		</tbody>
	</table>
</form>
<!-- EDP: ftp_chooser -->
