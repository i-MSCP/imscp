
<script>
	$(function(){
		$("tbody tr:nth-child(odd)").removeClass('even').addClass('odd');
		$("tbody tr:nth-child(even)").removeClass('odd').addClass('even');
	});

	/*function copyFtpDir(ftpDir) {
		$('#ftp_directory', window.parent.document).val(ftpDir);
		window.parent.$("#dial_ftp_dir").dialog('close');
		return false;
	}*/
</script>

<!-- BDP: page_message -->
<div class="{MESSAGE_CLS}">{MESSAGE}</div>
<!-- EDP: page_message -->

<!-- BDP: ftp_chooser -->
<div class="ftp_chooser">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_DIRECTORIES}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: dir_item -->
		<tr>
			<!-- BDP: list_item -->
			<td><a href="{LINK}" class="icon i_bc_{ICON} ftp_choose_dir" title="{DIR_NAME}">{DIR_NAME}</a></td>
			<td>
				<!-- BDP: action_link -->
				<a href="#" data-directory="{DIRECTORY}" class="ftp_choose_dir"" title="{CHOOSE}">{CHOOSE}</a>
				<!-- EDP: action_link -->
			</td>
			<!-- EDP: list_item -->
		</tr>
		<!-- EDP: dir_item -->
		</tbody>
	</table>
</div>
<!-- EDP: ftp_chooser -->
