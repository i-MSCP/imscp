
<script language="JavaScript" type="text/JavaScript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$("input[name='shared_mount_point']").change(
			function () {
				if ($("#shared_mount_point_no").is(':checked')) {
					$("#shared_mount_point_domain").hide();
				} else {
					$("#shared_mount_point_domain").show();
				}
			}
		).trigger('change');

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

<form name="add_domain_alias_frm" method="post" action="alias_add.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_DOMAIN_ALIAS}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<label for="domain_alias_name">{TR_DOMAIN_ALIAS_NAME}</label>
				<span class="tips icon i_help" id="domain_alias_name_tooltip" title="{TR_DOMAIN_ALIAS_NAME_TOOLTIP}"></span>
			</td>
			<td>
				<span class="bold">www .</span>
				<input type="text" name="domain_alias_name" id="domain_alias_name" value="{DOMAIN_ALIAS_NAME}"/>
			</td>
		</tr>
		<tr>
			<td>{TR_SHARED_MOUNT_POINT}<span class="tips icon i_help" title="{TR_SHARED_MOUNT_POINT_TOOLTIP}"></span></td>
			<td>
				<div class="radio">
					<input type="radio" name="shared_mount_point" id="shared_mount_point_yes"
						   value="yes"{SHARED_MOUNT_POINT_YES}/>
					<label for="shared_mount_point_yes">{TR_YES}</label>
					<input type="radio" name="shared_mount_point" id="shared_mount_point_no"
						   value="no"{SHARED_MOUNT_POINT_NO}/>
					<label for="shared_mount_point_no">{TR_NO}</label>
				</div>
				<label for="shared_mount_point_domain">
					<select name="shared_mount_point_domain" id="shared_mount_point_domain">
						<!-- BDP: shared_mount_point_domain -->
						<option value="{DOMAIN_NAME}"{SHARED_MOUNT_POINT_DOMAIN_SELECTED}>{DOMAIN_NAME_UNICODE}</option>
						<!-- EDP: shared_mount_point_domain -->
					</select>
				</label>
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
		<input name="Submit" type="submit" value="{TR_ADD}"/>
		<a class="link_as_button" href="domains_manage.php">{TR_CANCEL}</a>
	</div>
</form>
