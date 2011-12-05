<!-- INCLUDE "../shared/layout/header.tpl" -->
		<div class="header">
			{MAIN_MENU}

			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
			<h1 class="manage_users">{TR_MENU_MANAGE_USERS}</h1>
		</div>
		<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="settings.php">{TR_MENU_SETTINGS}</a></li>
				<li><a href="#" onclick="return false;">{TR_MENU_SOFTWARE_OPTIONS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="apps_installer"><span>{TR_OPTIONS_SOFTWARE}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form action="software_options.php" method="post" name="appssettings" id="appssettings">
				<fieldset>
					<legend>{TR_MAIN_OPTIONS}</legend>
					<table>
						<tr>
							<td style="width:300px;">
								<label for="use_webdepot">{TR_USE_WEBDEPOT}</label>
							</td>
							<td>
								<select name="use_webdepot" id="use_webdepot">
									<option value="0"{USE_WEBDEPOT_SELECTED_OFF}>{TR_DISABLED}</option>
									<option value="1"{USE_WEBDEPOT_SELECTED_ON}>{TR_ENABLED}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td width="300"><label for="webdepot_xml_url">{TR_WEBDEPOT_XML_URL}</label></td>
							<td><input type="text" name="webdepot_xml_url" id="webdepot_xml_url" value="{WEBDEPOT_XML_URL_VALUE}"/></td>
						</tr>
						<tr>
							<td width="300"><label>{TR_WEBDEPOT_LAST_UPDATE}</label></td>
							<td>{WEBDEPOT_LAST_UPDATE_VALUE}</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" class="button" value="{TR_APPLY_CHANGES}" />
					<input type="hidden" name="uaction" value="apply" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
