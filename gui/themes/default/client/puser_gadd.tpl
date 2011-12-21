
			<h2 class="users"><span>{TR_ADD_GROUP}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="add_user_group" method="post" action="protected_group_add.php">
				<table>
					<tr>
						<td style="width: 300px;"><label for="groupname">{TR_GROUPNAME}</label></td>
						<td><input name="groupname" type="text" id="groupname" value="" /></td>
					</tr>
				</table>
				<div class="buttons">
					 <input name="Submit" type="submit" value="{TR_ADD_GROUP}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>

				<input type="hidden" name="uaction" value="add_group" />
			</form>
