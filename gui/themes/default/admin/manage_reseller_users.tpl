
			<form action="manage_reseller_users.php" method="post" name="admin_user_assignment" id="admin_user_assignment">
				<!-- BDP: src_reseller -->
				<div class="buttons">
					{TR_FROM_RESELLER}
					<select name="src_reseller" onchange="return sbmt(document.forms[0],'change_src');">
						<!-- BDP: src_reseller_option -->
						<option {SRC_RSL_SELECTED} value="{SRC_RSL_VALUE}">{SRC_RSL_OPTION}</option>
						<!-- EDP: src_reseller_option -->
					</select>
				</div>
				<!-- EDP: src_reseller -->

				<!-- BDP: reseller_list -->
				<table>
					<tr>
						<th>{TR_NUMBER}</th>
						<th>{TR_MARK}</th>
						<th>{TR_USER_NAME}</th>
					</tr>
					<!-- BDP: reseller_item -->
					<tr>
						<td>{NUMBER}</td>
						<td><input id="{CKB_NAME}" type="checkbox" name="{CKB_NAME}" /></td>
						<td><label for="{CKB_NAME}">{USER_NAME}</label></td>
					</tr>
					<!-- EDP: reseller_item -->
				</table>
				<!-- EDP: reseller_list -->

				<!-- BDP: dst_reseller -->
				<div class="buttons">
					{TR_TO_RESELLER}
					<select name="dst_reseller">
						<!-- BDP: dst_reseller_option -->
						<option {DST_RSL_SELECTED} value="{DST_RSL_VALUE}">{DST_RSL_OPTION}</option>
						<!-- EDP: dst_reseller_option -->
					</select>
					<input name="Submit" type="submit" class="button" value="{TR_MOVE}" />
					<input type="hidden" name="uaction" value="move_user" />
				</div>
				<!-- EDP: dst_reseller -->

			</form>
