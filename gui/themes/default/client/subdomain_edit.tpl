<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script type="text/javascript">
			/* <![CDATA[ */
			function setForwardReadonly(obj){
				if(obj.value == 1) {
					document.getElementById('editFrm').elements['schemeSpecific'].readOnly = false;
					document.getElementById('editFrm').elements['scheme'].disabled = false;
				} else {
					document.getElementById('editFrm').elements['schemeSpecific'].readOnly = true;
					document.getElementById('editFrm').elements['schemeSpecific'].value = '';
					document.getElementById('editFrm').elements['scheme'].disabled = true;
				}
			}
			/* ]]> */
		</script>
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>

		<div class="location">
			<div class="location-area">
				<h1 class="domains">{TR_MENU_MANAGE_DOMAINS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li>
					<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
				</li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
				<li><a href="domains_manage.php">{TR_LMENU_OVERVIEW}</a></li>
				<li><a href="#" onclick="return false;">{TR_EDIT_SUBDOMAIN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="domains"><span>{TR_EDIT_SUBDOMAIN}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form action="subdomain_edit.php?id={SUBDOMAIN_ID}&type={SUBDOMAIN_TYPE}" method="post" id="editFrm">
				<table>
					<tr>
						<td style="width:200px;">{TR_SUBDOMAIN_NAME}</td>
						<td>{SUBDOMAIN_NAME}</td>
					</tr>
					<tr>
						<td>{TR_REDIRECT}</td>
						<td>
							<input type="radio" name="urlRedirect" id="urlRedirectEnabled"{RADIO_ENABLED} value="1" onchange="setForwardReadonly(this);" />
							<label for="urlRedirectEnabled">{TR_ENABLE}</label>
							<input type="radio" name="urlRedirect" id="urlRedirectDisabled"{RADIO_DISABLED} value="0" onchange="setForwardReadonly(this);" />
							<label for="urlRedirectDisabled">{TR_DISABLE}</label>
						</td>
					</tr>
					<tr>
						<td>
							<label for="scheme">{TR_REDIRECT_URL}</label>
						</td>
						<td>
							<select name="scheme" id="scheme" style="vertical-align:middle"{SELECT_DISABLED}>
								<!-- BDP: scheme_options -->
								<option value="{SCHEME}"{SELECTED}>{SCHEME}</option>
								<!-- EDP: scheme_options -->
							</select>
							<input name="schemeSpecific" type="text" id="schemeSpecific" value="{SCHEME_SPECIFIC}"{INPUT_READONLY} style="vertical-align:middle;width:300px;" />
						</td>
					</tr>
				</table>
				<input name="subdomainName" type="hidden" value="{SUBDOMAIN_NAME}" />
				<div class="buttons">
					<input name="update" type="submit" value="{TR_UPDATE}" />
					<input name="cancel" type="submit" value="{TR_CANCEL}" />
				</div>
			</form>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
