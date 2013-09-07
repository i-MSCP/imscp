
<script type="text/javascript">
	/*<![CDATA[*/
	function action_delete(url, subject) {
		return confirm(sprintf("{TR_MESSAGE_DELETE}", subject));
	}
	/*]]>*/
</script>

<!-- BDP: protected_areas -->
<table>
	<thead>
	<tr>
		<th>{TR_HTACCESS}</th>
		<th>{TR_STATUS}</th>
		<th>{TR__ACTION}</th>
	</tr>
	</thead>
	<tbody>
	<!-- BDP: dir_item -->
	<tr>
		<td>{AREA_NAME}<br/><span style="text-decoration: underline;">{AREA_PATH}</span></td>
		<td>{STATUS}</td>
		<td>
			<a href="protected_areas_add.php?id={PID}" class="icon i_edit">{TR_EDIT}</a>
			<a href="protected_areas_delete.php?id={PID}"
			   onclick="return action_delete('protected_areas_delete.php?id={PID}', '{JS_AREA_NAME}')"
			   class="icon i_delete">{TR_DELETE}</a>
		</td>
	</tr>
	<!-- EDP: dir_item -->
	</tbody>
</table>

<!-- EDP: protected_areas -->
<div class="buttons">
	<a class="link_as_button" href="protected_areas_add.php">{TR_ADD_AREA}</a>
	<a class="link_as_button" href="protected_user_manage.php">{TR_MANAGE_USRES}</a>
</div>
