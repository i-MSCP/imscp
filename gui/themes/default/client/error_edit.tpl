
<form name="error_edit" method="post" action="error_pages.php">
	<label><textarea name="error" id="error">{ERROR}</textarea></label>

	<div class="buttons">
		<input type="hidden" name="uaction" value="updt_error"/>
		<input type="hidden" name="eid" value="{EID}"/>
		<input name="Submit" type="submit" value="{TR_SAVE}"/>
		<a class="link_as_button" href="error_pages.php">{TR_CANCEL}</a>
	</div>
</form>
