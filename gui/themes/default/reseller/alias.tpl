
<!-- BDP: alias_js -->
<script type="text/javascript">
	/*<![CDATA[*/
	function delete_account(name) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", name));
	}
	/*]]>*/
</script>
<!-- EDP: alias_js -->

<div style="line-height:40px;vertical-align: middle;">
	<!-- BDP: search_form -->
	<form style="display: inline;" name="search_alias_frm" method="post" action="alias.php?psi={PSI}">
		<label><input name="search_for" type="text" value="{SEARCH_FOR}"/></label>
		<label>
			<select name="search_common">
				<option value="alias_name" {M_DOMAIN_NAME_SELECTED}>{M_ALIAS_NAME}</option>
				<option value="account_name" {M_ACCOUN_NAME_SELECTED}>{M_ACCOUNT_NAME}</option>
			</select>
		</label>
		<input name="Submit" type="submit" value="{TR_SEARCH}"/>
		<input type="hidden" name="uaction" value="go_search"/>
	</form>
	<!-- EDP: search_form -->
	<!-- BDP: als_add_button -->
	<div style="float:right;">
		<a class="link_as_button" href="alias_add.php">{TR_ADD_DOMAIN_ALIAS}</a>
	</div>
	<!-- EDP: als_add_button -->
</div>

<!-- BDP: alias_list -->
<table>
	<thead>
	<tr>
		<th>{TR_NAME}</th>
		<th>{TR_MOUNT_POINT}</th>
		<th>{TR_FORWARD}</th>
		<th>{TR_OWNER}</th>
		<th>{TR_STATUS}</th>
		<th>{TR_ACTION}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: alias_item -->
	<tr>
		<td>
			<!-- BDP: status_reload_true -->
			<a href="http://www.{NAME}/" target="_blank" class="icon i_domain_icon">{NAME}</a>
			<!-- EDP: status_reload_true -->
			<!-- BDP: status_reload_false -->
			<span class="icon i_domain_icon">{NAME}</span>
			<!-- EDP: status_reload_false -->
		</td>
		<td>{MOUNT_POINT}</td>
		<td>{FORWARD}</td>
		<td>{OWNER}</td>
		<td>{STATUS}</td>
		<td>
			<a href="{EDIT_LINK}" class="icon i_edit" title="{EDIT}">{EDIT}</a>
			<a href="{DELETE_LINK}" onclick="return delete_account('{NAME}')" class="icon i_delete"
			   title="{DELETE}">{DELETE}</a>
		</td>
	</tr>
	<!-- EDP: alias_item -->
	</tbody>
</table>

<div class="paginator">
	<!-- BDP: scroll_prev -->
	<a class="icon i_prev" href="alias.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
	<!-- EDP: scroll_prev -->
	<!-- BDP: scroll_prev_gray -->
	<span class="icon i_prev_gray"></span>
	<!-- EDP: scroll_prev_gray -->
	<!-- BDP: scroll_next_gray -->
	<span class="icon i_next_gray"></span>
	<!-- EDP: scroll_next_gray -->
	<!-- BDP: scroll_next -->
	<a class="icon i_next" href="alias.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
	<!-- EDP: scroll_next -->
</div>
<!-- EDP: alias_list -->
