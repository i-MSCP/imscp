
			<!-- BDP: software_item -->
			<table class="firstColFixed">
				<tr>
					<th colspan="2">{TR_TITLE}</th>
				</tr>
				<tr>
					<td>{TR_NAME}</td>
					<td>{SOFTWARE_NAME}</td>
				</tr>
				<tr>
					<td>{TR_VERSION}</td>
					<td>{SOFTWARE_VERSION}</td>
				</tr>
				<tr>
					<td>{TR_LANGUAGE}</td>
					<td>{SOFTWARE_LANGUAGE}</td>
				</tr>
				<tr>
					<td>{TR_TYPE}</td>
					<td>{SOFTWARE_TYPE}</td>
				</tr>
				<tr>
					<td>{TR_DB}</td>
					<td><span style="color:{STATUS_COLOR}">{SOFTWARE_DB}</span></td>
				</tr>
				<!-- BDP: software_message -->
				<tr>
					<td colspan="2">{STATUS_MESSAGE}</td>
				</tr>
				<!-- EDP: software_message -->
				<tr>
					<td colspan="2">{TR_DESC}<br/><br/>
						<table>
							<tr>
								<td style="text-align: justify">{SOFTWARE_DESC}</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td>{TR_LINK}:</td>
					<td><a href="{SOFTWARE_LINK}" target="_blank">{SOFTWARE_LINK}</a></td>
				</tr>
				<!-- BDP: installed_software_info -->
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<th colspan="2">{TR_SOFTWARE_INFO}</th>
				</tr>
				<tr>
					<td>{TR_SOFTWARE_STATUS}</td>
					<td>{SOFTWARE_STATUS}</td>
				</tr>
				<tr>
					<td>{TR_SOFTWARE_INSTALL_PATH}</td>
					<td>{SOFTWARE_INSTALL_PATH}</td>
				</tr>
				<tr>
					<td>{TR_SOFTWARE_INSTALL_DATABASE}</td>
					<td>{SOFTWARE_INSTALL_DATABASE}</td>
				</tr>
				<!-- EDP: installed_software_info -->
				<tr>
					<td colspan="2">
						<form name="scriptActions" method="post" action="#">
							<div class="buttons">
								<input name="Submit" type="submit" onclick="MM_goToURL('parent','software.php');return document.MM_returnValue" value="{TR_BACK}"/>
								<!-- BDP: software_install -->
								<input name="Submit2" type="submit" onclick="MM_goToURL('parent','{SOFTWARE_INSTALL_BUTTON}');return document.MM_returnValue" value="{TR_INSTALL}"/>
								<!-- EDP: software_install -->
							</div>
						</form>
					</td>
				</tr>
			</table>
			<!-- EDP: software_item -->
