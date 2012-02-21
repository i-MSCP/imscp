
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="addonFrm" method="post" action="addon.php">
				<table style="width:550px;">
					<tr>
						<th colspan="2">{DOMAIN_ADDON}</th>
					</tr>
					<tr>
						<td><label for="domainname"><strong>{TR_DOMAIN_NAME}</strong></label></td>
						<td>
							<p>
								<span><strong>www.</strong> </span> <input id="domainname" name="domainname" type="text" />
							</p>
							<small>{TR_EXAMPLE}</small>
						</td>
					</tr>
				</table>
				<div class="buttons" style="width:550px;">
					<input name="submit" type="submit" value="{TR_CONTINUE}" />
				</div>
			</form>
