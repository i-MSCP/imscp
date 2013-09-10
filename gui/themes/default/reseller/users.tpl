
<script type="text/javascript">
	/* <![CDATA[ */
	function action_status(dom_id, dmn_name) {
		if (!confirm(sprintf("{TR_MESSAGE_CHANGE_STATUS}", dmn_name))) {
			return false;
		}

		location = ("domain_status_change.php?domain_id=" + dom_id);
	}
	/* ]]> */
</script>

<!-- BDP: users_search -->
<form action="users.php" method="post" name="search_user" id="search_user">
	<div style="width=100%;vertical-align: middle;">
	<div style="display:inline;float:left;vertical-align: middle;line-height: 40px;">
		<label><input name="search_for" type="text" value="{SEARCH_FOR}"/></label>
		<label>
			<select name="search_common">
				<option value="domain_name"{M_DOMAIN_NAME_SELECTED}>{M_DOMAIN_NAME}</option>
				<option value="customer_id"{M_CUSTOMER_ID_SELECTED}>{M_CUSTOMER_ID}</option>
				<option value="lname"{M_LAST_NAME_SELECTED}>{M_LAST_NAME}</option>
				<option value="firm"{M_COMPANY_SELECTED}>{M_COMPANY}</option>
				<option value="city"{M_CITY_SELECTED}>{M_CITY}</option>
				<option value="country"{M_COUNTRY_SELECTED}>{M_COUNTRY}</option>
			</select>
		</label>
		<label>
			<select name="search_status">
				<option value="all"{M_ALL_SELECTED}>{M_ALL}</option>
				<option value="ok"{M_OK_SELECTED}>{M_OK}</option>
				<option value="disabled"{M_SUSPENDED_SELECTED}>{M_SUSPENDED}</option>
			</select>
		</label>
		<input name="Submit" type="submit" value="{TR_SEARCH}"/>
		<input type="hidden" name="uaction" value="go_search"/>
		<input type="hidden" name="details" value=""/>
	</div>
	<div style="display:inline;float:right;vertical-align: middle;line-height: 40px">
		<a class="icon i_show_alias" href="#" onclick="return sbmt_details(document.forms[0],'{SHOW_DETAILS}');">
			{TR_VIEW_DETAILS}
		</a>
	</div>
	</div>
	<div style="clear:both"></div>
</form>
<!-- EDP: users_search -->

<!-- BDP: usr_message -->
<div class="info">{USR_MESSAGE}</div>
<!-- EDP: usr_message -->

<!-- BDP: users_list -->
<table>
	<thead>
	<tr>
		<th>{TR_USER_STATUS}</th>
		<th>{TR_USERNAME}</th>
		<th>{TR_CREATION_DATE}</th>
		<th>{TR_DISK_USAGE}</th>
		<th>{TR_ACTION}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: user_entry -->
	<tr>
		<td>
			<a href="#" onclick="action_status('{URL_CHANGE_STATUS}', '{NAME}'); return false;"
			   class="icon i_{STATUS_ICON}" title="{STATUS_DOMAIN}">{STATUS_DOMAIN}</a>
		</td>
		<td>
			<!-- BDP: status_reload_true -->
			<a href="http://{NAME}/" target="_blank" class="icon i_goto" title="{NAME}">{NAME}</a>
			<!-- EDP: status_reload_true -->
			<!-- BDP: status_reload_false -->
			<span class="icon i_goto">{NAME}</span>
			<!-- EDP: status_reload_false -->
		</td>
		<td>{CREATION_DATE}</td>
		<td>{DISK_USAGE}</td>
		<td>
			<a class="icon i_identity" href="domain_details.php?domain_id={DOMAIN_ID}"
			   title="{TR_DETAILS}">{TR_DETAILS}</a>
			<!-- BDP: edit_option -->
			<a class="icon i_edit" href="domain_edit.php?edit_id={DOMAIN_ID}"
			   title="{TR_EDIT_DOMAIN}">{TR_EDIT_DOMAIN}</a>
			<a class="icon i_user" href="user_edit.php?edit_id={USER_ID}" title="{TR_EDIT_USER}">{TR_EDIT_USER}</a>
			<!-- EDP: edit_option -->
			<a class="icon i_details" href="change_user_interface.php?to_id={USER_ID}"
			   title="{CHANGE_INTERFACE}">{CHANGE_INTERFACE}</a>
			<a class="icon i_stats"
			   href="domain_statistics.php?month={VL_MONTH}&year={VL_YEAR}&domain_id={DOMAIN_ID}"
			   title="{TR_STAT}">{TR_STAT}</a>
			<a class="icon i_delete" href="user_delete.php?id={USER_ID}" title="{ACTION}">{ACTION}</a>
		</td>
	</tr>
	<!-- BDP: user_details -->
	<tr>
		<td colspan="5">
			<a href="http://www.{ALIAS_DOMAIN}/" target="_blank" class="icon i_goto"
			   title=""{ALIAS_DOMAIN}>{ALIAS_DOMAIN}</a>
		</td>
	</tr>
	<!-- EDP: user_details -->
	<!-- EDP: user_entry -->
	</tbody>
</table>

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="users.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<span class="icon i_prev_gray"></span>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<span class="icon i_next_gray"></span>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="users.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>
<!-- EDP: users_list -->
