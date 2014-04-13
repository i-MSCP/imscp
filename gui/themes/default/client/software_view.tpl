
<!-- BDP: software_item -->
<table class="firstColFixed">
	<thead>
	<tr>
		<th colspan="2">{TR_TITLE}</th>
	</tr>
	</thead>
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
					<td>{SOFTWARE_DESC}</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr>
		<td>{TR_LINK}</td>
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
					<a class="link_as_button" href="software.php">{TR_BACK}</a>
					<!-- BDP: software_install -->
					<a class="link_as_button" href="{SOFTWARE_INSTALL_BUTTON}">{TR_INSTALL}</a>
					<!-- EDP: software_install -->
				</div>
			</form>
		</td>
	</tr>
</table>
<!-- EDP: software_item -->
