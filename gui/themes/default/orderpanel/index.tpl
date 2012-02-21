
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<table style="width:550px;">
				<tr>
					<th colspan="2">Hosting Plans Availables</th>
				</tr>
				<!-- BDP: purchase_list -->
				<tr>
					<td>
						<a href="package_info.php?id={PACK_ID}&amp;user_id={USER_ID}"><strong>{PACK_NAME}</strong></a> {PRICE}
					</td>
					<td><a href="addon.php?id={PACK_ID}&amp;user_id={USER_ID}">{PURCHASE}</a></td>
				</tr>
				<tr>
					<td colspan="2">
						<div>{PACK_INFO}</div>
					</td>
				</tr>
				<tr>
					<td colspan="2"></td>
				</tr>
				<!-- EDP: purchase_list -->
			</table>
