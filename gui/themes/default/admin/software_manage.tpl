
<script language="JavaScript" type="text/JavaScript">
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

	function action_delete() {
		return confirm("{TR_MESSAGE_DELETE}");
	}

	function action_activate() {
		return confirm("{TR_MESSAGE_ACTIVATE}");
	}

	function action_import() {
		return confirm("{TR_MESSAGE_IMPORT}");
	}

	function action_install(url) {
		if (!confirm("{TR_MESSAGE_INSTALL}")) {
			return false;
		}

		document.getElementById('sw_wget').value = url;
		document.getElementById('sw_upload_form').submit();

		return true;
	}
	/*]]>*/
</script>
<form action="software_manage.php" name="sw_upload_form" id="sw_upload_form" method="post"
	  enctype="multipart/form-data">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_SOFTWARE_UPDLOAD}</th>
		</tr>
		</thead>
		<tr>
			<td><label for="sw_file">{TR_SOFTWARE_FILE}</label></td>
			<td><input type="file" name="sw_file" id="sw_file" size="60"/></td>
		</tr>
		<tr>
			<td><label for="sw_wget">{TR_SOFTWARE_URL}</label></td>
			<td><input type="text" name="sw_wget" id="sw_wget" value="{VAL_WGET}" size="60"/></td>
		</tr>
	</table>
	<div class="buttons">
		<input type="button" value="{TR_UPLOAD_SOFTWARE_BUTTON}"
			   onClick="document.getElementById('sw_upload_form').submit();"/>
		<input name="upload" type="hidden" value="upload"/>
		<input type="hidden" name="send_software_upload_token" id="send_software_upload_token"
			   value="{SOFTWARE_UPLOAD_TOKEN}"/>
	</div>
</form>
<table>
	<thead>
	<tr>
		<th>{TR_SOFTWARE_NAME}</th>
		<th>{TR_SOFTWARE_VERSION}</th>
		<th>{TR_SOFTWARE_LANGUAGE}</th>
		<th>{TR_SOFTWARE_TYPE}</th>
		<th>{TR_SOFTWARE_ADMIN}</th>
		<th>{TR_SOFTWARE_DOWNLOAD}</th>
		<th>{TR_SOFTWARE_DELETE}</th>
		<th>{TR_SOFTWARE_RIGHTS}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="8">{TR_SOFTWAREDEPOT_COUNT}: {TR_SOFTWAREDEPOT_NUM}</td>
	</tr>
	</tfoot>
	<!-- BDP: no_softwaredepot_list -->
	<tbody>
	<tr>
		<td colspan="8"><div class="info">{NO_SOFTWAREDEPOT}</div></td>
	</tr>
	<!-- EDP: no_softwaredepot_list -->
	<!-- BDP: list_softwaredepot -->
	<tr>
		<td><span class="tips icon i_app_installer" title="{TR_TOOLTIP}">{TR_NAME}</span></td>
		<td>{TR_VERSION}</td>
		<td>{TR_LANGUAGE}</td>
		<td>{TR_TYPE}</td>
		<td>{TR_ADMIN}</td>
		<td><a target="_blank" class="icon i_app_download" href="{DOWNLOAD_LINK}">{TR_DOWNLOAD}</a></td>
		<td><a href="{DELETE_LINK}" class="icon i_delete" onClick="return action_delete()">{TR_DELETE}</a></td>
		<td><a href="{SOFTWARE_RIGHTS_LINK}" class="icon i_{SOFTWARE_ICON}">{RIGHTS_LINK}</a></td>
	</tr>
	<!-- EDP: list_softwaredepot -->
	</tbody>
</table>
<!-- BDP: webdepot_list -->

<h2 class="apps_installer"><span>{TR_WEBDEPOT}</span></h2>

<!-- BDP: no_webdepotsoftware_lists -->
<div class="info">{NO_WEBDEPOTSOFTWARE_AVAILABLE}</div>
<!-- EDP: no_webdepotsoftware_list -->

<form action="software_manage.php" method="post" name="update_webdepot" id="update_webdepot">
	<!-- BDP: web_software_repository -->
	<table class="datatable">
		<thead>
		<tr>
			<th>{TR_PACKAGE_TITLE}</th>
			<th>{TR_PACKAGE_INSTALL_TYPE}</th>
			<th>{TR_PACKAGE_VERSION}</th>
			<th>{TR_PACKAGE_LANGUAGE}</th>
			<th>{TR_PACKAGE_TYPE}</th>
			<th>{TR_PACKAGE_VENDOR_HP}</th>
			<th>{TR_PACKAGE_ACTION}</th>
		</tr>
		</thead>
		<tfoot>
		<tr>
			<td colspan="7">{TR_WEBDEPOTSOFTWARE_COUNT}: {TR_WEBDEPOTSOFTWARE_ACT_NUM}</td>
		</tr>
		</tfoot>
		<tbody>
		<!-- BDP: list_webdepotsoftware -->
		<tr>
			<td><span class="tips icon i_app_installer" title="{TR_PACKAGE_TOOLTIP}">{TR_PACKAGE_NAME}</span></td>
			<td>{TR_PACKAGE_INSTALL_TYPE}</td>
			<td>{TR_PACKAGE_VERSION}</td>
			<td>{TR_PACKAGE_LANGUAGE}</td>
			<td>{TR_PACKAGE_TYPE}</td>
			<td>{TR_PACKAGE_VENDOR_HP}</td>
			<!-- BDP: package_install_link -->
			<td><a href="#" onClick="return action_install('{PACKAGE_HTTP_URL}')">{TR_PACKAGE_INSTALL}</a></td>
			<!-- EDP: package_install_link -->
			<!-- BDP: package_info_link -->
			<td><span class="icon i_help">Help</span>{TR_PACKAGE_INSTALL}</td>
			<!-- EDP: package_info_link -->
		</tr>
		<!-- EDP: list_webdepotsoftware -->
		</tbody>
	</table>
	<!-- EDP: web_software_repository -->

	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_APPLY_CHANGES}"/>
		<input type="hidden" name="uaction" value="updatewebdepot"/>
	</div>
</form>
<!-- EDP: webdepot_list -->

<h2 class="apps_installer"><span>{TR_AWAITING_ACTIVATION}</span></h2>

<table>
	<thead>
	<tr>
		<th>{TR_SOFTWARE_NAME}</th>
		<th>{TR_SOFTWARE_VERSION}</th>
		<th>{TR_SOFTWARE_LANGUAGE}</th>
		<th>{TR_SOFTWARE_TYPE}</th>
		<th>{TR_SOFTWARE_RESELLER}</th>
		<th>{TR_SOFTWARE_IMPORT}</th>
		<th>{TR_SOFTWARE_DOWNLOAD}</th>
		<th>{TR_SOFTWARE_ACTIVATION}</th>
		<th>{TR_SOFTWARE_DELETE}</th>
	</tr>
	</thead>
	<tfoot>
	<tr>
		<td colspan="9">{TR_SOFTWARE_ACT_COUNT}: {TR_SOFTWARE_ACT_NUM}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: no_software_list -->
	<tr>
		<td colspan="9">
			<div class="info">{NO_SOFTWARE}</div>
		</td>
	</tr>
	<!-- EDP: no_software_list -->
	<!-- BDP: list_software -->
	<tr>
		<td><span class="tips icon i_app_installer" title="{TR_TOOLTIP}">{TR_NAME}</span></td>
		<td>{TR_VERSION}</td>
		<td>{TR_LANGUAGE}</td>
		<td>{TR_TYPE}</td>
		<td>{TR_RESELLER}</td>
		<td><a href="{IMPORT_LINK}" class="icon i_app_download" onClick="return action_import()">{TR_IMPORT}</a></td>
		<td><a href="{DOWNLOAD_LINK}" class="icon i_app_download" target="_blank">{TR_DOWNLOAD}</a></td>
		<td><a href="{ACTIVATE_LINK}" class="icon i_edit" onClick="return action_activate()">{TR_ACTIVATION}</a></td>
		<td><a href="{DELETE_LINK}" class="icon i_delete" onClick="return action_delete()">{TR_DELETE}</a></td>
	</tr>
	<!-- EDP: list_software -->
	</tbody>
</table>

<h2 class="apps_installer"><span>{TR_RESELLER_SOFTWARES_LIST}</span></h2>

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
		<td colspan="5">{TR_RESELLER_ACT_COUNT}: {TR_RESELLER_ACT_NUM}</td>
	</tr>
	</tfoot>
	<tbody>
	<!-- BDP: no_reseller_list -->
	<tr>
		<td colspan="5">
			<div class="info">{NO_RESELLER}</div>
		</td>
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
