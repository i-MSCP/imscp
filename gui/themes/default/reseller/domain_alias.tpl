
		<script type="text/javascript">
			/*<![CDATA[*/
				function delete_account(url, name) {
					if (!confirm(sprintf("{TR_MESSAGE_DELETE}", name))) {
						return false;
					}
					location = url;
				}
			/*]]>*/
		</script>

			<!-- BDP: search_form -->
			<form name="search_alias_frm" method="post" action="alias.php?psi={PSI}">
				<input name="search_for" type="text" value="{SEARCH_FOR}" />
				<select name="search_common">
					<option value="alias_name" {M_DOMAIN_NAME_SELECTED}>{M_ALIAS_NAME}</option>
					<option value="account_name" {M_ACCOUN_NAME_SELECTED}>{M_ACCOUNT_NAME}</option>
				</select>
				<input name="Submit" type="submit" value="{TR_SEARCH}" />
				<input type="hidden" name="uaction" value="go_search" />
			</form>
			<!-- EDP: search_form -->
			<!-- BDP: table_list -->
			<table>
				<thead>
					<tr>
						<th>{TR_NAME}</th>
						<th>{TR_REAL_DOMAIN}</th>
						<th>{TR_FORWARD}</th>
						<th>{TR_STATUS}</th>
						<th>{TR_ACTION}</th>
					</tr>
				</thead>
				<tbody>
					<!-- BDP: table_item -->
					<tr>
						<td>
							<!-- BDP: status_reload_true -->
							<a href="http://www.{NAME}/" target="_blank" class="icon i_domain_icon">{NAME}</a>
							<!-- EDP: status_reload_true -->

							<!-- BDP: status_reload_false -->
							<span class="icon i_domain_icon">{NAME}</span>
							<!-- EDP: status_reload_false -->

							<br />{ALIAS_IP}
						</td>
						<td>{REAL_DOMAIN}<br />{REAL_DOMAIN_MOUNT}</td>
						<td>{FORWARD}</td>
						<td>{STATUS}</td>
						<td>
							<a href="{EDIT_LINK}" class="icon i_edit" title="{EDIT}">{EDIT}</a>
							<a href="#" onclick="delete_account('{DELETE_LINK}', '{NAME}')" class="icon i_delete" title="{DELETE}">{DELETE}</a>
						</td>
					</tr>
					<!-- EDP: table_item -->
				</tbody>
			</table>
			<!-- EDP: table_list -->
			<div class="buttons">
				<input name="Submit" type="submit" onclick="MM_goToURL('parent','alias_add.php');return document.MM_returnValue" value="{TR_ADD_ALIAS}" />
			</div>
			<div class="paginator">
				<!-- BDP: scroll_next_gray -->
				<a class="icon i_next_gray" href="#">&nbsp;</a>
				<!-- EDP: scroll_next_gray -->

				<!-- BDP: scroll_next -->
				<a class="icon i_next" href="alias.php?psi={NEXT_PSI}" title="{TR_NEXT}">{TR_NEXT}</a>
				<!-- EDP: scroll_next -->

				<!-- BDP: scroll_prev -->
				<a class="icon i_prev" href="alias.php?psi={PREV_PSI}" title="{TR_PREVIOUS}">{TR_PREVIOUS}</a>
				<!-- EDP: scroll_prev -->

				<!-- BDP: scroll_prev_gray -->
				<a class="icon i_prev_gray" href="#">&nbsp;</a>
				<!-- EDP: scroll_prev_gray -->
			</div>
