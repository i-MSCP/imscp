
			<script type="text/javascript">
				/* <![CDATA[ */
				function action_delete(url, subject) {
					if (!confirm(sprintf("{TR_MESSAGE_DELETE}", subject))) {
						return false;
					}
					location.href = url;
				}
				/* ]]> */
			</script>
			<!-- BDP: hp_table -->
			<table class="firstColFixed">
				<tr>
					<th>{TR_NUMBER}</th>
					<th>{TR_PLAN_NAME}</th>
					<th>{TR_PURCHASING}</th>
					<th>{TR_ACTIONS}</th>
				</tr>
				<!-- BDP: hp_entry -->
				<tr>
					<td>{PLAN_NUMBER}</td>
					<td>
						<a href="../orderpanel/package_info.php?coid={CUSTOM_ORDERPANEL_ID}&amp;user_id={ADMIN_ID}&amp;id={HP_ID}" target="_blank" title="{PLAN_SHOW}">{PLAN_NAME}</a>
					</td>
					<td>{PURCHASING}</td>
					<td>
						<a href="hosting_plan_edit.php?hpid={HP_ID}" class="icon i_edit">{TR_EDIT}</a>
						<!-- BDP: hp_delete -->
						<a href="#" onclick="return action_delete('hosting_plan_delete.php?hpid={HP_ID}', '{PLAN_NAME2}')" class="icon i_delete">{PLAN_ACTION}</a>
						<!-- EDP: hp_delete -->
					</td>
				</tr>
				<!-- EDP: hp_entry -->
			</table>
			<!-- EDP: hp_table -->
