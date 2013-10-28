
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"bStateSave": true
			}
		);
	});
	/*]]>*/
</script>

<form action="manage_reseller_users.php" method="post" name="admin_user_assignment" id="admin_user_assignment">
	<!-- BDP: src_reseller -->
	<div class="buttons">
		<label for="fromReseller">{TR_FROM_RESELLER}</label>
		<select name="src_reseller" id="fromReseller" onchange="return sbmt(document.forms[0],'change_src');">
			<!-- BDP: src_reseller_option -->
			<option {SRC_RSL_SELECTED} value="{SRC_RSL_VALUE}">{SRC_RSL_OPTION}</option>
			<!-- EDP: src_reseller_option -->
		</select>
	</div>
	<!-- EDP: src_reseller -->

	<!-- BDP: reseller_list -->
	<table class="firstColFixed datatable">
		<thead>
		<tr>
			<th>{TR_CUSTOMER_ID}</th>
			<th>{TR_MARK}</th>
			<th>{TR_USER_NAME}</th>
		</tr>
		</thead>
		<tbody>
		<!-- BDP: reseller_item -->
		<tr>
			<td>{CUSTOMER_ID}</td>
			<td><input id="{CKB_NAME}" type="checkbox" name="{CKB_NAME}"/></td>
			<td><label for="{CKB_NAME}">{USER_NAME}</label></td>
		</tr>
		<!-- EDP: reseller_item -->
		</tbody>
	</table>
	<!-- EDP: reseller_list -->

	<!-- BDP: dst_reseller -->
	<div class="buttons">
		<label for="toReseller">{TR_TO_RESELLER}</label>
		<select name="dst_reseller" id="toReseller">
			<!-- BDP: dst_reseller_option -->
			<option {DST_RSL_SELECTED} value="{DST_RSL_VALUE}">{DST_RSL_OPTION}</option>
			<!-- EDP: dst_reseller_option -->
		</select>
		<input name="Submit" type="submit" value="{TR_MOVE}"/>
		<input type="hidden" name="uaction" value="move_user"/>
	</div>
	<!-- EDP: dst_reseller -->
</form>
