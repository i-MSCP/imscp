
        <script language="JavaScript" type="text/JavaScript">
        /*<![CDATA[*/
			function action_delete() {
				if (!confirm("{TR_MESSAGE_DELETE}")) {
					return false;
				}
			}
			function action_install(url) {
				if (!confirm("{TR_MESSAGE_INSTALL}")) {
					return false;
				}
				document.getElementById('sw_wget').value = url;
                document.getElementById('sw_upload_form').submit();
			}
		/*]]>*/
		</script>
			<table>
            	<tr>
					<th><div style="float:left">{TR_SOFTWARE_NAME}</div><div style="float:left"><a href="{TR_SOFTWARE_NAME_ASC}" class="app_icon i_app_asc"></a><a href="{TR_SOFTWARE_NAME_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th width="100">{TR_SOFTWARE_VERSION}</th>
					<th width="100"><div style="float:left">{TR_SOFTWARE_LANGUAGE}</div><div style="float:left"><a href="{TR_LANGUAGE_ASC}" class="app_icon i_app_asc"></a><a href="{TR_LANGUAGE_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th width="170"><div style="float:left">{TR_SOFTWARE_STATUS}</div><div style="float:left"><a href="{TR_SOFTWARE_STATUS_ASC}" class="app_icon i_app_asc"></a><a href="{TR_SOFTWARE_STATUS_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th width="120"><div style="float:left">{TR_SOFTWARE_TYPE}</div><div style="float:left"><a href="{TR_SOFTWARE_TYPE_ASC}" class="app_icon i_app_asc"></a><a href="{TR_SOFTWARE_TYPE_DESC}" class="app_icon i_app_desc"></a></div></th>
					<th align="center" width="100">{TR_SOFTWARE_DELETE}</th>
				</tr>
				<!-- BDP: no_software_list -->
				<tr>
					<td colspan="6"><div class="info">{NO_SOFTWARE}</div></td>
				</tr>
				<!-- EDP: no_software_list -->
				<!-- BDP: list_software -->
				<tr>
					<td><span class="icon i_app_installer" title="{SW_DESCRIPTION}">{SW_NAME}</span></td>
					<td>{SW_VERSION}</td>
					<td>{SW_LANGUAGE}</td>
					<td><span class="icon i_app_installed" title="{SW_INSTALLED}">{SW_STATUS}</span></td>
					<td>{SW_TYPE}</td>
					<td><a href="{DELETE}" class="icon i_{SOFTWARE_ICON}" onclick="return action_delete()">{TR_DELETE}</a></td>
				</tr>
				<!-- EDP: list_software -->
				<tr>
					<th colspan="6">{TR_SOFTWARE_COUNT}:&nbsp;{TR_SOFTWARE_NUM}</th>
				</tr>
			</table>
			<br />
			<h2 class="apps_installer"><span>{TR_UPLOAD_SOFTWARE}</span></h2>
			<table>
				<tr>
					<td>
						<form action="software_upload.php" name="sw_upload_form" id="sw_upload_form" method="post" enctype="multipart/form-data">
							<table>
								<tr>
									<td width="200">{TR_SOFTWARE_FILE}</td>
									<td><input type="file" name="sw_file" id="sw_file" size="60" /></td>
								</tr>
								<tr>
									<td width="200">{TR_SOFTWARE_URL}</td>
									<td><input type="text" name="sw_wget" id="sw_wget" value="{VAL_WGET}" size="60" /></td>
								</tr>
								<tr>
									<td colspan="2">
										<div class="buttons">
											<input type="button" class="button" value="{TR_UPLOAD_SOFTWARE_BUTTON}" onClick="javascript:document.getElementById('sw_upload_form').submit();" />
											<input name="upload" type="hidden" value="upload" />
											<input type="hidden" name="send_software_upload_token" id="send_software_upload_token" value="{SOFTWARE_UPLOAD_TOKEN}" />
										</div>
									</td>
								</tr>
							</table>	
						</form>
					</td>
				</tr>
			</table>
			<!-- BDP: webdepot_list -->
			<br />
			<h2 class="apps_installer"><span>{TR_WEBDEPOT}</span></h2>
			<table>
				<tr>
					<th>{TR_PACKAGE_TITLE}</th>
					<th width="120">{TR_PACKAGE_INSTALL_TYPE}</th>
					<th width="120">{TR_PACKAGE_VERSION}</th>
					<th width="120">{TR_PACKAGE_LANGUAGE}</th>
					<th width="120">{TR_PACKAGE_TYPE}</th>
					<th width="120">{TR_PACKAGE_VENDOR_HP}</th>
					<th width="180">{TR_PACKAGE_ACTION}</th>
				</tr>
				<!-- BDP: no_webdepotsoftware_list -->
				<tr>
					<td colspan="7"><div class="info">{NO_WEBDEPOTSOFTWARE_AVAILABLE}</div></td>
				</tr>
				<!-- EDP: no_webdepotsoftware_list -->
				<!-- BDP: list_webdepotsoftware -->
				<tr>
					<td><span class="icon i_app_installer" title="{TR_PACKAGE_TOOLTIP}">{TR_PACKAGE_NAME}</span></td>
					<td align="center">{TR_PACKAGE_INSTALL_TYPE}</td>
					<td align="center">{TR_PACKAGE_VERSION}</td>
					<td align="center">{TR_PACKAGE_LANGUAGE}</td>
					<td align="center">{TR_PACKAGE_TYPE}</td>
					<td align="center">{TR_PACKAGE_VENDOR_HP}</td>
					<!-- BDP: package_install_link -->
					<td align="center"><a href="#" onClick="return action_install('{PACKAGE_HTTP_URL}')">{TR_PACKAGE_INSTALL}</a></td>
					<!-- EDP: package_install_link -->
					<!-- BDP: package_info_link -->
					<td align="left"><span class="icon i_help">Help</span>{TR_PACKAGE_INSTALL}</td>
					<!-- EDP: package_info_link -->
				</tr>
				<!-- EDP: list_webdepotsoftware -->
				<tr>
                    <td colspan="7">
                        <form action="software_upload.php" method="post" name="update_webdepot" id="update_webdepot">
                            <div class="buttons">
                                <input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" />
                                <input type="hidden" name="uaction" value="updatewebdepot" />
                            </div>
                        </form>
                    </td>
                </tr>
				<tr>
					<th colspan="7">{TR_WEBDEPOTSOFTWARE_COUNT}:&nbsp;{TR_WEBDEPOTSOFTWARE_ACT_NUM}</th>
				</tr>
			</table>
			<!-- EDP: webdepot_list -->
