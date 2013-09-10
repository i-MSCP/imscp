
<!-- BDP: domain_alias_add_js -->
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready(function () {
		$('input[name=ndomain_name]').blur(function () {
			var dmnName = $('#ndomain_name').val();

			$.ajaxSetup({
				url: $(location).attr('pathname'),
				type: 'POST',
				data: 'domain=' + dmnName + '&uaction=toASCII',
				datatype: 'text',
				beforeSend: function (xhr){xhr.setRequestHeader('Accept','text/plain');},
				success: function (r){ $('#ndomain_mpoint').val(r); },
				error: iMSCPajxError
			});

			$.ajax();
		});
	});

	function setForwardReadonly(obj) {
		if (obj.value == 1) {
			document.forms[0].elements['forward'].readOnly = false;
			document.forms[0].elements['forward_prefix'].disabled = false;
		} else {
			document.forms[0].elements['forward'].readOnly = true;
			document.forms[0].elements['forward'].value = '';
			document.forms[0].elements['forward_prefix'].disabled = true;
		}
	}
	/* ]]> */
</script>
<!-- EDP: domain_alias_add_js -->

<!-- BDP: domain_alias_add_form -->
<form name="add_alias_frm" method="post" action="alias_add.php">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="3">{TR_DOMAIN_ALIAS_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<label for="ndomain_name">{TR_DOMAIN_ALIAS_NAME}</label>
				<span class="icon i_help" id="dmn_help" title="{TR_DMN_HELP}"></span>
			</td>
			<td><input name="ndomain_name" id="ndomain_name" type="text" value="{DOMAIN}"/></td>
		</tr>
		<tr>
			<td><label for="ndomain_mpoint">{TR_MOUNT_POINT}</label></td>
			<td><input name="ndomain_mpoint" type="text" id="ndomain_mpoint" value="{MP}"/></td>
		</tr>
		<tr>
			<td>{TR_ENABLE_FWD}</td>
			<td>
				<input type="radio" name="status" id="redirectEnabled" {CHECK_EN} value="1"
					   onchange='setForwardReadonly(this);'/>
				<label for="redirectEnabled">{TR_ENABLE}</label>
				<input type="radio" name="status" id="redirectDisabled" {CHECK_DIS} value="0"
					   onchange='setForwardReadonly(this);'/>
				<label for="redirectDisabled">{TR_DISABLE}</label>
			</td>
		</tr>
		<tr>
			<td><label for="forward">{TR_FORWARD}</label></td>
			<td colspan="2">
				<label>
					<select name="forward_prefix" id="forward_prefix"{DISABLE_FORWARD}>
						<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>{TR_PREFIX_HTTP}</option>
						<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>{TR_PREFIX_HTTPS}</option>
						<option value="{TR_PREFIX_FTP}"{FTP_YES}>{TR_PREFIX_FTP}</option>
					</select>
				</label>
				<input name="forward" type="text" class="textinput" id="forward" value="{FORWARD}"{READONLY_FORWARD} />
			</td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input type="hidden" name="uaction" value="add_alias"/>
		<input type="submit" name="add" value="{TR_ADD}"/>
	</div>
</form>
<!-- EDP: domain_alias_add_form -->
