
		<script type="text/javascript">
			/* <![CDATA[ */
			function action_status(url, dmn_name) {
				if (!confirm(sprintf("{TR_MESSAGE_CHANGE_STATUS}", dmn_name))) {
					return false;
				}

				location = url;
			}

			function action_delete(url, dmn_name) {
				if (!confirm(sprintf("{TR_MESSAGE_DELETE}", dmn_name))) {
					return false;
				}

				location = url;
			}
			/* ]]> */
		</script>

			<h3 class="user_yellow"><span>{TR_ADMINISTRATORS}</span></h3>

			<!-- BDP: admin_message -->
			<div class="info">{ADMIN_MESSAGE}</div>
			<!-- EDP: admin_message -->

			<!-- BDP: admin_list -->
			<table>
				<tr>
					<th>{TR_ADMIN_USERNAME}</th>
					<th>{TR_CREATED_ON}</th>
					<th>{TR_ADMIN_CREATED_BY}</th>
					<th>{TR_ADMIN_ACTIONS}</th>
				</tr>
				<!-- BDP: admin_item -->
				<tr>
					<td>{ADMIN_USERNAME}</td>
					<td>{ADMIN_CREATED_ON}</td>
					<td>{ADMIN_CREATED_BY}</td>
					<td>
						<a href="{URL_EDIT_ADMIN}" class="icon i_user">{TR_EDIT}</a>

						<!-- BDP: admin_delete_show -->
						<!-- EDP: admin_delete_show -->

						<!-- BDP: admin_delete_link -->
						<a href="#" class="icon i_delete" onclick="action_delete('{URL_DELETE_ADMIN}', '{ADMIN_USERNAME}')" class="link">{TR_DELETE}</a>
						<!-- EDP: admin_delete_link -->
					</td>
				</tr>
				<!-- EDP: admin_item -->
			</table>
			<!-- EDP: admin_list -->

			<h3 class="user_green"><span>{TR_RESELLERS}</span></h3>

			<!-- BDP: rsl_message -->
			<div class="info">{RSL_MESSAGE}</div>
			<!-- EDP: rsl_message -->

			<!-- BDP: rsl_list -->
			<table>
				<tr>
					<th>{TR_RSL_USERNAME}</th>
					<th>{TR_CREATED_ON}</th>
					<th>{TR_RSL_CREATED_BY}</th>
					<th>{TR_RSL_ACTIONS}</th>
				</tr>
				<!-- BDP: rsl_item -->
				<tr>
					<td>{RSL_USERNAME}</td>
					<td>{RESELLER_CREATED_ON}</td>
					<td>{RSL_CREATED_BY}</td>
					<td>
						<a class="icon i_details" href="{URL_CHANGE_INTERFACE}" title="{TR_CHANGE_USER_INTERFACE}">{GO_TO_USER_INTERFACE}</a>
						<a href="{URL_EDIT_RSL}" class="icon i_user">{TR_EDIT}</a>
						<a href="#" class="icon i_delete" onclick="action_delete('{URL_DELETE_RSL}', '{RSL_USERNAME}')">{TR_DELETE}</a>
					</td>
				</tr>
				<!-- EDP: rsl_item -->
			</table>
			<!-- EDP: rsl_list -->

			<h3 class="user_blue"><span>{TR_CUSTOMERS}</span></h3>

			<!-- BDP: usr_message -->
			<div class="info">{USR_MESSAGE}</div>
			<!-- EDP: usr_message -->

			<!-- BDP: usr_list -->
			<form action="manage_users.php" method="post" name="search_user" id="search_user">
				<a class="icon i_show_alias" href="#" onclick="return sbmt_details(document.forms[0],'{SHOW_DETAILS}');">{TR_VIEW_DETAILS}</a>
				<input name="search_for" type="text" value="{SEARCH_FOR}" />
				<select name="search_common">
					<option value="domain_name" {M_DOMAIN_NAME_SELECTED}>{M_DOMAIN_NAME}</option>
					<option value="customer_id" {M_CUSTOMER_ID_SELECTED}>{M_CUSTOMER_ID}</option>
					<option value="lname" {M_LAST_NAME_SELECTED}>{M_LAST_NAME}</option>
					<option value="firm" {M_COMPANY_SELECTED}>{M_COMPANY}</option>
					<option value="city" {M_CITY_SELECTED}>{M_CITY}</option>
					<option value="state" {M_STATE_SELECTED}>{M_STATE}</option>
					<option value="country" {M_COUNTRY_SELECTED}>{M_COUNTRY}</option>
				</select>
				<select name="search_status">
					<option value="all" {M_ALL_SELECTED}>{M_ALL}</option>
					<option value="ok" {M_OK_SELECTED}>{M_OK}</option>
					<option value="disabled" {M_SUSPENDED_SELECTED}>{M_SUSPENDED}</option>
				</select>
				<input type="hidden" name="uaction" value="go_search" />
				<input type="hidden" name="details" value="" />
				<div class="buttons" style="display:inline">
					<input name="Submit" type="submit" value="{TR_SEARCH}" />
				</div>

			</form>
			<table>
				<tr>
					<th>{TR_USER_STATUS}</th>
					<th>{TR_USR_USERNAME}</th>
					<th>{TR_CREATED_ON}</th>
					<th>{TR_USR_CREATED_BY}</th>
					<th>{TR_USR_ACTIONS}</th>
				</tr>
				<!-- BDP: usr_item -->
				<tr>
					<td>
						<a href="#" onclick="action_status('{URL_CHANGE_STATUS}', '{USR_USERNAME}')" class="icon i_{STATUS}">{TR_STATUS}</a>
					</td>
					<td>
						<!-- BDP: usr_status_reload_true -->
						<a href="http://{USR_USERNAME}/" target="_blank" class="icon i_goto">{USR_USERNAME}</a>
						<!-- EDP: usr_status_reload_true -->

						<!-- BDP: usr_status_reload_false -->
						<span class="icon i_goto">{USR_USERNAME}</span>
						<!-- EDP: usr_status_reload_false -->
					</td>
					<td>{USER_CREATED_ON}</td>
					<td>{USR_CREATED_BY}</td>
					<td>
						<a class="icon i_identity" href="domain_details.php?domain_id={DOMAIN_ID}">{TR_DETAILS}</a>
						<a class="icon i_details" href="{URL_CHANGE_INTERFACE}" title="{TR_CHANGE_USER_INTERFACE}">{GO_TO_USER_INTERFACE}</a>

						<!-- BDP: edit_option -->
						<a class="icon i_edit" href="domain_edit.php?edit_id={DOMAIN_ID}">{TR_EDIT_DOMAIN}</a>
						<!-- EDP: edit_option -->

						<a class="icon i_user" href="{URL_EDIT_USR}">{TR_EDIT_USR}</a>

						<!-- BDP: usr_delete_show -->
						<!-- EDP: usr_delete_show -->

						<!-- BDP: usr_delete_link -->
						<a class="icon i_delete" href="user_delete.php?domain_id={DOMAIN_ID}">{TR_DELETE}</a>
						<!-- EDP: usr_delete_link -->
					</td>
				</tr>
				<!-- BDP: user_details -->
				<tr>
					<td colspan="5">
						<a href="http://www.{ALIAS_DOMAIN}/" target="_blank" class="icon i_goto">{ALIAS_DOMAIN}</a>
					</td>
				</tr>
				<!-- EDP: user_details -->
				<!-- EDP: usr_item -->
			</table>

			<div class="paginator">
				<!-- BDP: scroll_next_gray -->
				<a class="icon i_next_gray" href="#">&nbsp;</a>
				<!-- EDP: scroll_next_gray -->
				<!-- BDP: scroll_next -->
				<a class="icon i_next" href="manage_users.php?psi={NEXT_PSI}" title="next">next</a>
				<!-- EDP: scroll_next -->
				<!-- BDP: scroll_prev -->
				<a class="icon i_prev" href="manage_users.php?psi={PREV_PSI}" title="previous">previous</a>
				<!-- EDP: scroll_prev -->
				<!-- BDP: scroll_prev_gray -->
				<a class="icon i_prev_gray" href="#">&nbsp;</a>
				<!-- EDP: scroll_prev_gray -->
			</div>
			<!-- EDP: usr_list -->
