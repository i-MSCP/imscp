
			<table>
				<tr>
					<td>
						<form name="admin_delete_email" method="post" action="software_delete.php">
							<table class="firstColFixed">
								<tr>
									<td colspan="2">{TR_DELETE_DATA}</td>
								</tr>
								<tr>
									<td>{TR_DELETE_SEND_TO}</td>
									<td>{DELETE_SOFTWARE_RESELLER}</td>
								</tr>
								<tr>
									<td style="vertical-align:top;">{TR_DELETE_MESSAGE_TEXT}</td>
									<td><textarea name="delete_msg_text">{DELETE_MESSAGE_TEXT}</textarea></td>
								</tr>
								<tr>
									<td colspan="2">
										<div class="buttons">
											<input name="Submit" type="submit" class="button" value="{TR_SEND_MESSAGE}"/>
											<input type="hidden" name="uaction" value="send_delmessage"/>
											<input type="hidden" name="id" value="{SOFTWARE_ID}"/>
											<input type="hidden" name="reseller_id" value="{RESELLER_ID}"/>
										</div>
									</td>
								</tr>
							</table>
						</form>
					</td>
				</tr>
			</table>
