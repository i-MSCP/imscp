
		<div class="body">
            <h2 class="errors"><span>{TR_ERROR_EDIT_PAGE} {EID}</span></h2>

			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="error_edit" method="post" action="error_pages.php">
				<textarea name="error" cols="80" rows="35" id="error">{ERROR}</textarea>
				<div class="buttons">
					<input type="hidden" name="uaction" value="updt_error" />
					<input type="hidden" name="eid" value="{EID}" />
					<input name="Submit" type="submit" value="{TR_SAVE}" />
					<input name="Button" type="button" onclick="MM_goToURL('parent','error_pages.php');return document.MM_returnValue" value="{TR_CANCEL}" />
				</div>
			</form>
		</div>
