			<script type="text/javascript">
			/*<![CDATA[*/
				function action_delete(url, subject) {
					return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
				}
			/*]]>*/
		</script>
		<!-- BDP: protected_areas -->
		<table>
			<tr>
				<th>{TR_HTACCESS}</th>
				<th>{TR_STATUS}</th>
				<th>{TR__ACTION}</th>
			</tr>
			<!-- BDP: dir_item -->
			<tr>
				<td>{AREA_NAME}<br/><u>{AREA_PATH}</u></td>
				<td>{STATUS}</td>
				<td>
					<a href="protected_areas_add.php?id={PID}" class="icon i_edit">{TR_EDIT}</a>
					<a href="protected_areas_delete.php?id={PID}" onclick="return action_delete('protected_areas_delete.php?id={PID}', '{JS_AREA_NAME}')" class="icon i_delete">{TR_DELETE}</a>
				</td>
			</tr>
			<!-- EDP: dir_item -->
		</table>
		<!-- EDP: protected_areas -->

		<div class="buttons">
			<input name="Button" type="button" onclick="MM_goToURL('parent','protected_areas_add.php');return document.MM_returnValue" value="{TR_ADD_AREA}"/>
			<input name="Button2" type="button" onclick="MM_goToURL('parent','protected_user_manage.php');return document.MM_returnValue" value="{TR_MANAGE_USRES}"/>
		</div>
