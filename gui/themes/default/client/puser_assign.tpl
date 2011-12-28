
			<form name="assignGroupFrm" method="post" action="protected_user_assign.php?uname={UNAME}">
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{UNAME}</th>
					</tr>
					<!-- BDP: in_group -->
						<tr>
							<td><label for="groups_in">{TR_MEMBER_OF_GROUP}</label></td>
							<td>
								<select name="groups_in" id="groups_in">
						  			<!-- BDP: already_in -->
						  				<option value="{GRP_IN_ID}">{GRP_IN}</option>
									<!-- EDP: already_in -->
								</select>
								<!-- BDP: remove_button -->
								<input name="Submit" type="submit"  value="{TR_REMOVE}" onclick="return sbmt(document.forms[0],'remove');" />
								<!-- EDP: remove_button -->
							</td>
						</tr>
					<!-- EDP: in_group -->
					<!-- BDP: not_in_group -->
						<tr>
							<td><label for="groups">{TR_SELECT_GROUP}</label></td>
							<td>
								<select name="groups" id="groups">
									<!-- BDP: grp_avlb -->
										<option value="{GRP_ID}">{GRP_NAME}</option>
									<!-- EDP: grp_avlb -->
								</select>
								<!-- BDP: add_button -->
								<input name="Submit" type="submit"  value="{TR_ADD}" onclick="return sbmt(document.forms[0],'add');" />
								<!-- EDP: add_button -->
							</td>
						</tr>
					<!-- EDP: not_in_group -->
				</table>

				<div class="buttons">
					<input name="Submit" type="submit"  value="{TR_CANCEL}" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" />
				</div>

				<input type="hidden" name="nadmin_name" value="{UID}" />
				<input type="hidden" name="uaction" value="" />
			</form>
