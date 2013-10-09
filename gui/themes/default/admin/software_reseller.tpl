
<script language="JavaScript" type="text/JavaScript">
	/*<![CDATA[*/
	function action_import() {
		return confirm("{TR_MESSAGE_IMPORT}");
	}

	function action_delete() {
		return confirm("{TR_MESSAGE_DELETE}");
	}
	/*]]>*/
</script>
<table>
	<thead>
	<tr>
		<th>{TR_SOFTWARE_NAME}</th>
		<th>{TR_SOFTWARE_IMPORT}</th>
		<th>{TR_SOFTWARE_DELETE}</th>
		<th>{TR_SOFTWARE_INSTALLED}</th>
		<th>{TR_SOFTWARE_VERSION}</th>
		<th>{TR_SOFTWARE_LANGUAGE}</th>
		<th>{TR_SOFTWARE_TYPE}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="7">{TR_SOFTWAREDEPOT_COUNT}:&nbsp;{TR_SOFTWAREDEPOT_NUM}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: no_softwaredepot_list -->
	<tr>
		<td colspan="7"><div class="warning">{NO_SOFTWAREDEPOT}</div></td>
	</tr>
	<!-- EDP: no_softwaredepot_list -->
	<!-- BDP: list_softwaredepot -->
	<tr>
		<td><span class="tips icon i_app_installer" title="{TR_TOOLTIP}">{TR_NAME}</span></td>
		<!-- BDP: software_is_in_softwaredepot -->
		<td>{IS_IN_SOFTWAREDEPOT}</td>
		<td>{IS_IN_SOFTWAREDEPOT}</td>
		<!-- EDP: software_is_in_softwaredepot -->
		<!-- BDP: software_is_not_in_softwaredepot -->
		<td><a href="{IMPORT_LINK}" class="icon i_app_download" onclick="return action_import()">{TR_IMPORT}</a></td>
		<td><a href="{DELETE_LINK}" class="icon i_delete" onclick="return action_delete()">{TR_DELETE}</a></td>
		<!-- EDP: software_is_not_in_softwaredepot -->
		<td><span class="tips icon i_help" id="tld_help" title="{SW_INSTALLED}"></span></td>
		<td>{TR_VERSION}</td>
		<td>{TR_LANGUAGE}</td>
		<td>{TR_TYPE}</td>
	</tr>
	<!-- EDP: list_softwaredepot -->
	</tbody>
</table>

<h2 class="apps_installer"><span>{TR_ACTIVATED_SOFTWARE}</span></h2>

<table>
	<thead>
	<tr>
		<th>{TR_RESELLER_NAME}</th>
		<th>{TR_RESELLER_COUNT_SWDEPOT}</th>
		<th>{TR_RESELLER_COUNT_WAITING}</th>
		<th>{TR_RESELLER_COUNT_ACTIVATED}</th>
		<th>{TR_RESELLER_SOFTWARE_IN_USE}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="5">{TR_RESELLER_ACT_COUNT}:&nbsp;{TR_RESELLER_ACT_NUM}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: no_reseller_list -->
	<tr>
		<td colspan="5"><div class="warning">{NO_RESELLER}</div></td>
	</tr>
	<!-- EDP: no_reseller_list -->
	<!-- BDP: list_reseller -->
	<tr>
		<td>{RESELLER_NAME}</td>
		<td>{RESELLER_COUNT_SWDEPOT}</td>
		<td>{RESELLER_COUNT_WAITING}</td>
		<td>{RESELLER_COUNT_ACTIVATED}</td>
		<td><a href="software_reseller.php?id={RESELLER_ID}">{RESELLER_SOFTWARE_IN_USE}</a></td>
	</tr>
	<!-- EDP: list_reseller -->
	</tbody>
</table>
