<!-- INCLUDE "../shared/layout/header.tpl" -->
	<script type="text/javascript">
	/* <![CDATA[ */
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
			<!-- BDP: logged_from -->
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
		</ul>
		<ul class="path">
			<li><a href="domains_manage.php">{TR_MENU_MANAGE_DOMAINS}</a></li>
			<li><a href="domains_manage.php">{TR_LMENU_OVERVIEW}</a></li>
			<li><a href="#" onclick="return false;">{TR_TITLE_EDIT_ALIAS}</a></li>
		</ul>
	</div>
	<div class="left_menu">{MENU}</div>
	<div class="body">
		<h2 class="domains"><span>{TR_TITLE_EDIT_ALIAS}</span></h2>

		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->

		<form name="edit_alias_frm" method="post" action="alias_edit.php?edit_id={ID}">
			<table>
				<tr>
					<th colspan="2">{TR_DOMAIN_ALIAS_DATA}</th>
				</tr>
				<tr>
					<td>{TR_DOMAIN_IP}</td>
					<td>{ALIAS_NAME}</td>
				</tr>
				<tr>
					<td>{TR_DOMAIN_IP}</td>
					<td>{DOMAIN_IP}</td>
				</tr>
				<tr>
					<td>{TR_ENABLE_FWD}</td>
					<td>
						<input type="radio" name="status" id="status_enable"{CHECK_EN} value="1" onChange="setForwardReadonly(this);" /><label for="status_enable">{TR_ENABLE}</label><br />
						<input type="radio" name="status" id="status_disable"{CHECK_DIS} value="0" onChange="setForwardReadonly(this);" /><label for="status_disable">{TR_DISABLE}</label>
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
						<input type="text" name="forward" id="forward" class="textinput" value="{FORWARD}"{READONLY_FORWARD} />
					</td>
				</tr>
			</table>
			<div class="buttons">
				<input type="hidden" name="uaction" value="modify" />
				<input type="submit" name="update" value="{TR_MODIFY}" />
				<input type="submit" name="cancel" onclick="MM_goToURL('parent','domains_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
			</div>
		</form>
	</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
