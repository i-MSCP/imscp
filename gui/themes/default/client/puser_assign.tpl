<!-- INCLUDE "../shared/layout/header.tpl" -->
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
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<!-- BDP: logged_from -->
				<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
				<!-- EDP: logged_from -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
			</ul>
			<ul class="path">
				<li><a href="webtools.php.php">{TR_MENU_WEBTOOLS}</a></li>
				<li><a href="protected_areas.php">{TR_LMENU_HTACCESS}</a></li>
				<li><a href="protected_user_manage.php">{TR_HTACCESS_USER}</a></li>
				<li><a href="#" onclick="return false;">{TR_USER_ASSIGN}</a></li>
			</ul>
		</div>

		<div class="left_menu">
			{MENU}
		</div>


		<div class="body">
			<h2 class="users"><span>{TR_USER_ASSIGN}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="puser_assign" method="post" action="protected_user_assign.php?uname={UNAME}">
				<fieldset>
					<legend>{UNAME}</legend>
				</fieldset>
				<table>
					<!-- BDP: in_group -->
						<tr>
							<td>{TR_MEMBER_OF_GROUP}</td>
							<td>
								<select name="groups_in">
						  			<!-- BDP: already_in -->
						  				<option value="{GRP_IN_ID}">{GRP_IN}</option>
									<!-- EDP: already_in -->
								</select>
					  		</td>
					  		<td><!-- BDP: remove_button --><input name="Submit" type="submit"  value="{TR_REMOVE}" onclick="return sbmt(document.forms[0],'remove');" /><!-- EDP: remove_button --></td>
						</tr>
					<!-- EDP: in_group -->
					<!-- BDP: not_in_group -->
						<tr>
							<td>{TR_SELECT_GROUP}</td>
							<td>
								<select name="groups">
									<!-- BDP: grp_avlb -->
										<option value="{GRP_ID}">{GRP_NAME}</option>
									<!-- EDP: grp_avlb -->
								</select>
							</td>
							<td><!-- BDP: add_button --> <input name="Submit" type="submit"  value="{TR_ADD}" onclick="return sbmt(document.forms[0],'add');" /> <!-- EDP: add_button --></td>
						</tr>
					<!-- EDP: not_in_group -->
				</table>

				<div class="buttons">
					<input name="Submit" type="submit"  value="{TR_BACK}" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" />
				</div>

				<input type="hidden" name="nadmin_name" value="{UID}" />
				<input type="hidden" name="uaction" value="" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
