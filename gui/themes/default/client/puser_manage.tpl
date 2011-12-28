			<script type="text/javascript">
			/*<![CDATA[*/
				function action_delete(url, subject) {
					if(confirm(sprintf("{TR_MESSAGE_DELETE}", subject))) {
						location.href = url;
					}

				return false;
				}
			/*]]>*/
			</script>

			<h2 class="groups"><span>{TR_USERS}</span></h2>

			<!-- BDP: users_message_block -->
			<div class="info">{USERS_MESSAGE}</div>
			<!-- EDP: users_message_block -->

			<!-- BDP: users_block -->
			<table class="firstColFixed">
				<tr>
					<th>{TR_USERNAME}</th>
					<th>{TR_STATUS}</th>
					<th>{TR_ACTIONS}</th>
				</tr>
				<!-- BDP: user_block -->
				<tr>
					<td>{UNAME}</td>
					<td>{USTATUS}</td>
					<td>
						<a href="protected_user_assign.php?uname={USER_ID}" class="icon i_users">{TR_GROUP}</a>
						<a href="{USER_EDIT_SCRIPT}" class="icon i_edit">{USER_EDIT}</a>
						<a href="#" class="icon i_delete" onclick="return {USER_DELETE_SCRIPT}">{USER_DELETE}</a>
					</td>
				</tr>
				<!-- EDP: user_block -->
			</table>
			<!-- EDP: users_block -->

			<div class="buttons">
				<input name="Button" type="button"
					   onclick="MM_goToURL('parent','protected_user_add.php');return document.MM_returnValue" value="{TR_ADD_USER}"/>
			</div>

			<h2 class="groups"><span>{TR_GROUPS}</span></h2>

			<!-- BDP: groups_message_block -->
			<div class="info">{GROUPS_MESSAGE}</div>
			<!-- EDP: groups_message_block -->

			<!-- BDP: groups_block -->
			<table class="firstColFixed">
				<tr>
					<th>{TR_GROUPNAME}</th>
					<th>{TR_GROUP_MEMBERS}</th>
					<th>{TR_STATUS}</th>
					<th>{TR_ACTIONS}</th>
				</tr>
				<!-- BDP: group_block -->
				<tr>
					<td>{GNAME}</td>
					<td>
						<!-- BDP: group_members -->
					{MEMBER}
						<!-- EDP: group_members -->
					</td>
					<td>{GSTATUS}</td>
					<td>
						<a href="#" class="icon i_delete" onclick="{GROUP_DELETE_SCRIPT}">{GROUP_DELETE}</a>
					</td>
				</tr>
				<!-- EDP: group_block -->
			</table>
			<!-- EDP: groups_block -->

			<div class="buttons">
				<input name="Button2" type="button" value="{TR_ADD_GROUP}" onclick="MM_goToURL('parent', 'protected_group_add.php');return document.MM_returnValue"/>
			</div>
