
<script language="JavaScript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$('.datatable').dataTable(
			{
				"oLanguage": {DATATABLE_TRANSLATIONS},
				"iDisplayLength": 5,
				"bStateSave": true
			}
		);
	});

	function action_delete(url) {
		if (!confirm("{TR_MESSAGE_DELETE}")) {
			return false;
		}

		location = url;

		return true;
	}

	function action_install(url) {
		if (!confirm("{TR_MESSAGE_INSTALL}")) {
			return false;
		}

		location = url;

		return true;
	}

	function action_res_delete(url) {
		if (!confirm("{TR_RES_MESSAGE_DELETE}")) {
			return false;
		}

		location = url;

		return true;
	}
	/*]]>*/
</script>

<!-- BDP: no_software_support -->
<div class="info">{NO_SOFTWARE_AVAIL}</div>
<!-- EDP: no_software_support -->

<!-- BDP: software_list -->
<table class="datatable">
	<thead>
	<tr>
		<th>{TR_SOFTWARE}</th>
		<th>{TR_VERSION}</th>
		<th>{TR_LANGUAGE}</th>
		<th>{TR_TYPE}</th>
		<th>{TR_NEED_DATABASE}</th>
		<th>{TR_STATUS}</th>
		<th>{TR_ACTION}</th>
	</tr>
	</thead>
	<tfoot>
	<!-- BDP: software_total -->
	<tr>
		<td colspan="7">{TR_SOFTWARE_AVAILABLE}: {TOTAL_SOFTWARE_AVAILABLE}</td>
	</tr>
	<!-- EDP: software_total -->
	</tfoot>
	<tbody>
	<!-- BDP: t_software_support -->
	<!-- BDP: software_item -->
	<tr>
		<td>
			<a href="{VIEW_SOFTWARE_SCRIPT}" class="icon i_app_installer"
			   title="{SOFTWARE_DESCRIPTION}">{SOFTWARE_NAME}</a>
		</td>
		<td>{SOFTWARE_VERSION}</td>
		<td>{SOFTWARE_LANGUAGE}</td>
		<td>{SOFTWARE_TYPE}</td>
		<td>{SOFTWARE_NEED_DATABASE}</td>
		<td>{SOFTWARE_STATUS}</td>
		<td>
			<a href="#" class="icon i_{SOFTWARE_ICON}"
			<!-- BDP: software_action_delete -->
			onClick="return action_delete('{SOFTWARE_ACTION_SCRIPT}')"
			<!-- EDP: software_action_delete -->
			<!-- BDP: software_action_install -->
			onClick="return action_install('{SOFTWARE_ACTION_SCRIPT}')"
			<!-- EDP: software_action_install -->
			>{SOFTWARE_ACTION}</a>
		</td>
	</tr>
	<!-- EDP: software_item -->
	<!-- EDP: t_software_support -->
	<!-- BDP: del_software_support -->
	<tr>
		<th colspan="5">{TR_DEL_SOFTWARE}</th>
		<th>{TR_DEL_STATUS}</th>
		<th>{TR_DEL_ACTION}</th>
	</tr>
	<!-- BDP: del_software_item -->
	<tr>
		<td colspan="5">{SOFTWARE_DEL_RES_MESSAGE}</td>
		<td>{DEL_SOFTWARE_STATUS}</td>
		<td>
			<a href="#" class="icon i_delete"
			   onclick="return action_res_delete('{DEL_SOFTWARE_ACTION_SCRIPT}')">{DEL_SOFTWARE_ACTION}</a>
		</td>
	</tr>
	<!-- EDP: del_software_item -->
	<!-- EDP: del_software_support -->
	</tbody>
</table>
<!-- EDP: software_list -->
