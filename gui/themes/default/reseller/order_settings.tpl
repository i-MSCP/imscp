
			<form name="orderSettingsFrm" method="post" action="order_settings.php">
				<table class="firstColFixed">
					<tr>
						<th>{TR_IMPLEMENT_INFO}</th>
					</tr>
					<tr>
						<td>{TR_IMPLEMENT_URL}</td>
					</tr>
				</table>
				<table class="firstColFixed">
					<tr>
						<th colspan="2">{TR_ORDER_TEMPLATE}</th>
					</tr>
					<tr>
						<td><label for="orderTemplateHeader">{TR_HEADER}</label></td>
						<td><textarea name="header" id="orderTemplateHeader">{PURCHASE_HEADER}</textarea></td>
					</tr>
					<tr>
						<td><label for="orderTemplateFooter">{TR_FOOTER}</label></td>
						<td><textarea name="footer" id="orderTemplateFooter">{PURCHASE_FOOTER}</textarea></td>
					</tr>
				</table>
				<div class="buttons">
					<input name="button" type="button" onclick="window.open('/orderpanel/', 'preview', 'width=770,height=480')" value="{TR_PREVIEW}"/>
					<input name="submit" type="submit" value="{TR_UPDATE}"/>
				</div>
			</form>
