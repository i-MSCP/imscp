
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			function action_import() {
				if (!confirm("{TR_MESSAGE_IMPORT}"))
				return false;
			}
			function action_delete() {
				if (!confirm("{TR_MESSAGE_DELETE}"))
				return false;
			}
		/*]]>*/
		</script>
			<table>
				<tr>
					<th>{TR_SOFTWARE_NAME}</th>
					<th width="90" style="text-align: center">{TR_SOFTWARE_IMPORT}</th>
					<th width="90" style="text-align: center">{TR_SOFTWARE_DELETE}</th>
					<th width="90" style="text-align: center">{TR_SOFTWARE_INSTALLED}</th>
					<th width="90" style="text-align: center">{TR_SOFTWARE_VERSION}</th>
					<th width="90" style="text-align: center">{TR_SOFTWARE_LANGUAGE}</th>
					<th width="90" style="text-align: center">{TR_SOFTWARE_TYPE}</th>
				</tr>
				<!-- BDP: no_softwaredepot_list -->
				<tr>
					<td colspan="7"><div class="warning">{NO_SOFTWAREDEPOT}</div></td>
				</tr>
				<!-- EDP: no_softwaredepot_list -->
				<!-- BDP: list_softwaredepot -->
				<tr>
					<td><span class="icon i_app_installer" title="{TR_TOOLTIP}">{TR_NAME}</span></td>
					<!-- BDP: software_is_in_softwaredepot -->
					<td style="text-align: center">{IS_IN_SOFTWAREDEPOT}</td>
					<td style="text-align: center">{IS_IN_SOFTWAREDEPOT}</td>
					<!-- EDP: software_is_in_softwaredepot -->
					<!-- BDP: software_is_not_in_softwaredepot -->
					<td style="text-align: center"><a href="{IMPORT_LINK}" class="icon i_app_download" onClick="return action_import()">{TR_IMPORT}</a></td>
					<td style="text-align: center"><a href="{DELETE_LINK}" class="icon i_delete" onClick="return action_delete()">{TR_DELETE}</a></td>
					<!-- EDP: software_is_not_in_softwaredepot -->
					<td style="text-align: center"><span class="icon i_help" id="tld_help" title="{SW_INSTALLED}">help</span></td>
					<td style="text-align: center">{TR_VERSION}</td>
					<td style="text-align: center">{TR_LANGUAGE}</td>
					<td style="text-align: center">{TR_TYPE}</td>
				</tr>
				<!-- EDP: list_softwaredepot -->
				<tr>
					<th colspan="7">{TR_SOFTWAREDEPOT_COUNT}:&nbsp;{TR_SOFTWAREDEPOT_NUM}</th>
				</tr>
			</table>
			<br />
			<h2 class="apps_installer"><span>{TR_ACTIVATED_SOFTWARE}</span></h2>
			<table>
				<tr>
					<th>{TR_RESELLER_NAME}</th>
					<th align="center" width="150">{TR_RESELLER_COUNT_SWDEPOT}</th>
					<th align="center" width="150">{TR_RESELLER_COUNT_WAITING}</th>
					<th align="center" width="150">{TR_RESELLER_COUNT_ACTIVATED}</th>
					<th align="center" width="150">{TR_RESELLER_SOFTWARE_IN_USE}</th>
				</tr>
				<!-- BDP: no_reseller_list -->
				<tr>
					<td colspan="5"><div class="warning">{NO_RESELLER}</div></td>
				</tr>
				<!-- EDP: no_reseller_list -->
				<!-- BDP: list_reseller -->
				<tr>
					<td>{RESELLER_NAME}</td>
					<td align="center">{RESELLER_COUNT_SWDEPOT}</td>
					<td align="center">{RESELLER_COUNT_WAITING}</td>
					<td align="center">{RESELLER_COUNT_ACTIVATED}</td>
					<td align="center"><a href="software_reseller.php?id={RESELLER_ID}">{RESELLER_SOFTWARE_IN_USE}</a></td>
				</tr>
				<!-- EDP: list_reseller -->
				<tr>
					<th colspan="5">{TR_RESELLER_ACT_COUNT}:&nbsp;{TR_RESELLER_ACT_NUM}</th>
				</tr>
			</table>
			<div class="paginator"></div>
