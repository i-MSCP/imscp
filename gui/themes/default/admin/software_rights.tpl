<!-- INCLUDE "../shared/layout/header.tpl" -->
		<script language="JavaScript" type="text/JavaScript">
		/*<![CDATA[*/
			function action_remove_right() {
				if (!confirm("{TR_MESSAGE_REMOVE}"))
					return false;
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
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="software_manage.php">{TR_MENU_MANAGE_SOFTWARE}</a></li>
				<li><a href="software_rights.php?id={SOFTWARE_RIGHTS_ID}">{TR_SOFTWARE_RIGHTS}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>

		<div class="body">
			<h2 class="apps_installer"><span>{TR_ADD_RIGHTS} {TR_SOFTWARE_NAME}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->


			<table>
				<!-- BDP: no_select_reseller -->
				<tr>
					<td colspan="3"><div class="info">{NO_RESELLER_AVAILABLE}</div></td>
				</tr>
				<!-- EDP: no_select_reseller -->
				<!-- BDP: select_reseller -->
				<tr>
					<td colspan="3">
						<form action="software_change_rights.php" method="post">
							<table>
								<tr>
									<td>
										<select name="selected_reseller" id="selected_reseller">
											<option value="all">{ALL_RESELLER_NAME}</option>
											<!-- BDP: reseller_item -->
											<option value="{RESELLER_ID}">{RESELLER_NAME}</option>
											<!-- EDP: reseller_item -->
										</select>
									</td>
								</tr>
								<tr>
									<td colspan="3">
											<div class="buttons">
												<input name="Button" type="submit" class="button" value="{TR_ADD_RIGHTS_BUTTON}" />
												<input type="hidden" value="add" name="change" />
												<input type="hidden" value="{SOFTWARE_ID_VALUE}" name="id" />
											</div>
									</td>
								</tr>
							</table>
						</form>
					</td>
				</tr>
				<!-- EDP: select_reseller -->
				<tr>
					<th>{TR_RESELLER}</th>
					<th align="center" width="180">{TR_ADDED_BY}</th>
					<th align="center" width="80">{TR_REMOVE_RIGHTS}</th>
				</tr>
				<!-- BDP: no_reseller_list -->
				<tr>
					<td colspan="3"><div class="info">{NO_RESELLER}</div></td>
				</tr>
				<!-- EDP: no_reseller_list -->
				<!-- BDP: list_reseller -->
				<tr>
					<td>{RESELLER}</td>
					<td>{ADMINISTRATOR}</td>
					<td align="center"><span class="icon i_delete"><a href="{REMOVE_RIGHT_LINK}" onClick="return action_remove_right()">{TR_REMOVE_RIGHT}</a></span></td>
				</tr>
				<!-- EDP: list_reseller -->
				<tr>
					<th colspan="3">{TR_RESELLER_COUNT}:&nbsp;{TR_RESELLER_NUM}</th>
				</tr>
			</table>
			<div class="paginator">

			</div>

		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
