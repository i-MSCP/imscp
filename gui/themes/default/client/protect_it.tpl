<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script type="text/javascript">
		/* <![CDATA[ */
		function action_delete(url, subject) {
			return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
		}

		function begin_js() {
			document.forms[0].elements["users[]"].disabled = {USER_FORM_ELEMENS};
			document.forms[0].elements["groups[]"].disabled = {GROUP_FORM_ELEMENS};
		}

		function changeType(wath) {
			document.forms[0].elements["users[]"].disabled = wath != "user";
			document.forms[0].elements["groups[]"].disabled = wath == "user";
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
				<h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
			</div>
			<ul class="location-menu">
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
				<li><a href="protected_areas.php">{TR_HTACCESS}</a></li>
				<li><a href="#" onclick="return false;">{TR_TITLE}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="htaccess"><span>{TR_TITLE}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="edit_ftp_acc_frm" method="post" action="protected_areas_add.php">
				<table>
					<tr>
						<th colspan="2">Protected area data</th>
					</tr>
					<tr>
						<td style="width: 300px"><label for="paname">{TR_AREA_NAME}</label></td>
						<td>
							<input name="paname" type="text" class="textinput" id="paname" value="{AREA_NAME}" />
						</td>
					</tr>
					<tr>
						<td><label for="path">{TR_PATH}</label></td>
						<td>
							<input name="other_dir" type="text" class="textinput" id="path" value="{PATH}" />
							<a href="#" onclick="showFileTree();" class="icon i_bc_folder">{CHOOSE_DIR}</a>
						</td>
					</tr>
				</table>
				<br />
				<table>
					<thead>
						<tr>
							<th style="width: 300px;">{TR_USER}</th>
							<th>{TR_GROUPS}</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>
								<label for="ptype_1">{TR_USER_AUTH}</label><input type="radio" id="ptype_1" name="ptype" value="user" {USER_CHECKED} onfocus="changeType('user');" />
							</td>
							<td>
								<label for="ptype_2">{TR_GROUP_AUTH}</label><input type="radio" id="ptype_2" name="ptype" value="group" {GROUP_CHECKED} onfocus="changeType('group');" />
							</td>
						</tr>
						<tr>
							<td>
								<select name="users[]" multiple="multiple" size="5">
									<!-- BDP: user_item -->
									<option value="{USER_VALUE}" {USER_SELECTED}>{USER_LABEL}</option>
									<!-- EDP: user_item -->
								</select>
							</td>
							<td>
								<select name="groups[]" multiple="multiple" size="5">
									<!-- BDP: group_item -->
									<option value="{GROUP_VALUE}" {GROUP_SELECTED}>{GROUP_LABEL}</option>
									<!-- EDP: group_item -->
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<div class="buttons">
					<input type="hidden" name="use_other_dir" />
					<input type="hidden" name="sub" value="YES" />
					<input type="hidden" name="cdir" value="{CDIR}" />
					<input type="hidden" name="uaction" value="" />
					<input name="Button" type="button" value="{TR_PROTECT_IT}" onclick="return sbmt(document.forms[0],'protect_it');" />
					<!-- BDP: unprotect_it -->
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas_delete.php?id={CDIR}');return document.MM_returnValue" value="{TR_UNPROTECT_IT}" />
					<!-- EDP: unprotect_it -->
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_MANAGE_USRES}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
