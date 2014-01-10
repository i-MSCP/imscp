
<script language="JavaScript" type="text/JavaScript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$("input[name='url_forwarding']").change(
			function () {
				if ($("#url_forwarding_no").is(':checked')) {
					$("#tr_url_forwarding_data").hide();
				} else {
					$("#tr_url_forwarding_data").show();
				}
			}
		).trigger('change');
	});
	/*]]>*/
</script>

<form name="edit_subdomain_frm" method="post" action="subdomain_edit.php?id={SUBDOMAIN_ID}&amp;type={SUBDOMAIN_TYPE}">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_SUBDOMAIN}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="subdomain_name">{TR_SUBDOMAIN_NAME}</label></td>
			<td>
				<input type="text" name="subdomain_name" id="subdomain_name" value="{SUBDOMAIN_NAME}"
					   readonly="readonly"/>
			</td>
		</tr>
		<tr>
			<td>{TR_URL_FORWARDING} <span class="tips icon i_help" title="{TR_URL_FORWARDING_TOOLTIP}"></span></td>
			<td>
				<div class="radio">
					<input type="radio" name="url_forwarding" id="url_forwarding_yes"{FORWARD_URL_YES} value="yes"/>
					<label for="url_forwarding_yes">{TR_YES}</label>
					<input type="radio" name="url_forwarding" id="url_forwarding_no"{FORWARD_URL_NO} value="no"/>
					<label for="url_forwarding_no">{TR_NO}</label>
				</div>
			</td>
		</tr>
		<tr id="tr_url_forwarding_data">
			<td>{TR_FORWARD_TO_URL}</td>
			<td>
				<label for="forward_url_scheme">
					<select name="forward_url_scheme" id="forward_url_scheme">
						<option value="http://"{HTTP_YES}>{TR_HTTP}</option>
						<option value="https://"{HTTPS_YES}>{TR_HTTPS}</option>
						<option value="ftp://"{FTP_YES}>{TR_FTP}</option>
					</select>
				</label>
				<label>
					<input name="forward_url" type="text" id="forward_url" value="{FORWARD_URL}"/>
				</label>
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
		<a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
	</div>
</form>
