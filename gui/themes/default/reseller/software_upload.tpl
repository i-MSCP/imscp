
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

<table>
	<thead>
	<tr>
		<th>{TR_SOFTWARE_NAME}</th>
		<th>{TR_SOFTWARE_VERSION}</th>
		<th>{TR_SOFTWARE_LANGUAGE}</th>
		<th>{TR_SOFTWARE_STATUS}</th>
		<th>{TR_SOFTWARE_TYPE}</th>
		<th>{TR_SOFTWARE_DELETE}</th>
	</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="6">{TR_SOFTWARE_COUNT}: {TR_SOFTWARE_NUM}</td>
		</tr>
	</tfoot>
	<tbody>
	<!-- BDP: no_software_list -->
	<tr>
		<td colspan="6">
			<div class="info">{NO_SOFTWARE}</div>
		</td>
	</tr>
	<!-- EDP: no_software_list -->
	<!-- BDP: list_software -->
	<tr>
		<td><span class="tips icon i_app_installer" title="{SW_DESCRIPTION}">{SW_NAME}</span></td>
		<td>{SW_VERSION}</td>
		<td>{SW_LANGUAGE}</td>
		<td><span class="tips icon i_app_installed" title="{SW_INSTALLED}">{SW_STATUS}</span></td>
		<td>{SW_TYPE}</td>
		<td><a href="{DELETE}" class="icon i_{SOFTWARE_ICON}" onclick="return action_delete()">{TR_DELETE}</a></td>
	</tr>
	<!-- EDP: list_software -->
	</tbody>
</table>

<br/>

<h2 class="apps_installer"><span>{TR_UPLOAD_SOFTWARE}</span></h2>

<form action="software_upload.php" name="sw_upload_form" id="sw_upload_form" method="post" enctype="multipart/form-data">
	<table>
		<thead>
		<tr>
			<th colspan="2">{TR_SOFTWARE_UPLOAD}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_SOFTWARE_FILE}</td>
			<td><input type="file" name="sw_file" id="sw_file" size="60"/></td>
		</tr>
		<tr>
			<td><label for="sw_wget">{TR_SOFTWARE_URL}<label</td>
			<td><input type="text" name="sw_wget" id="sw_wget" value="{VAL_WGET}" size="60"/></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input type="button" class="button" value="{TR_UPLOAD_SOFTWARE_BUTTON}"
			   onClick="document.getElementById('sw_upload_form').submit();"/>
		<input name="upload" type="hidden" value="upload"/>
		<input type="hidden" name="send_software_upload_token" id="send_software_upload_token"
			   value="{SOFTWARE_UPLOAD_TOKEN}"/>
	</div>
</form>

<!-- BDP: webdepot_list -->
<br/>

<h2 class="apps_installer"><span>{TR_WEBDEPOT}</span></h2>

<!-- BDP: no_webdepotsoftware_list -->
<div class="info">{NO_WEBDEPOTSOFTWARE_AVAILABLE}</div>
<!-- EDP: no_webdepotsoftware_list -->

<form action="software_upload.php" method="post" name="update_webdepot" id="update_webdepot">
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
