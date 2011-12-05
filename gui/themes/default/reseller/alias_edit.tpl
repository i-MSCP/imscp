<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			function setForwardReadonly(obj){
				if(obj.value == 1) {
					document.forms[0].elements['forward'].readOnly = false;
					document.forms[0].elements['forward_prefix'].disabled = false;
				} else {
					document.forms[0].elements['forward'].readOnly = true;
					document.forms[0].elements['forward'].value = '';
					document.forms[0].elements['forward_prefix'].disabled = true;
				}
			}
		/*]]>*/
		</script>
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
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="users.php">{TR_MENU_MANAGE_USERS}</a></li>
				<li><a href="alias.php">{TR_MENU_DOMAIN_ALIAS}</a></li>
				<li>{TR_EDIT_ALIAS} {ALIAS_NAME}</li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
            			<h2 class="domains"><span>{TR_MANAGE_DOMAIN_ALIAS}</span></h2>
			<div id="fwd_help" class="tooltip">{TR_FWD_HELP}</div>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->



			<form name="edit_alias_frm" method="post" action="alias_edit.php?edit_id={ID}">
				<fieldset>
					<legend>{TR_EDIT_ALIAS}</legend>
					<table>
						<tr>
							<td>{TR_ALIAS_NAME}</td>
							<td>{ALIAS_NAME}</td>
						</tr>
						<tr>
							<td>{TR_DOMAIN_IP}</td>
							<td>{DOMAIN_IP}</td>
						</tr>
						<tr>
							<td>{TR_ENABLE_FWD}</td>
							<td>
								<input type="radio" name="status" id="status_enable"{CHECK_EN} value="1" onchange="setForwardReadonly(this);" /><label for="status_enable">{TR_ENABLE}</label><br />
								<input type="radio" name="status" id="status_disable"{CHECK_DIS} value="0" onchange="setForwardReadonly(this);" /><label for="status_disable">{TR_DISABLE}</label><br />
							</td>
						</tr>
						<tr>
							<td>
								<label for="forward">{TR_FORWARD}</label>
							</td>
							<td>
								<select name="forward_prefix" style="vertical-align:middle"{DISABLE_FORWARD}>
									<option value="{TR_PREFIX_HTTP}"{HTTP_YES}>{TR_PREFIX_HTTP}</option>
									<option value="{TR_PREFIX_HTTPS}"{HTTPS_YES}>{TR_PREFIX_HTTPS}</option>
									<option value="{TR_PREFIX_FTP}"{FTP_YES}>{TR_PREFIX_FTP}</option>
								</select>
								<input name="forward" type="text" class="textinput" id="forward" value="{FORWARD}"{READONLY_FORWARD} />
							</td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="Submit" type="submit" value="{TR_MODIFY}" />
					<input name="Submit" type="submit" onclick="MM_goToURL('parent','alias.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
				<input type="hidden" name="uaction" value="modify" />
			</form>

		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
