
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
			<!-- EDP: page_message -->

            <!-- BDP: purchase_list -->
			<table style="width:550px;">
				<thead>
				<tr>
					<th colspan="2">{PACK_NAME}</th>
				</tr>
                </thead>
				<tbody>
                <tr>
                    <td colspan="2">
                        <div>{PACK_INFO}</div>
                    </td>
                </tr>
				<tr>
					<td>{TR_PRICE} {PAYMENT_PERIOD}</td>
					<td align="right"><strong>{PRICE}</strong></td>
				</tr>
				<!-- BDP: setup_fee_block -->
				<tr>
					<td>{TR_SETUP_FEE}</td>
					<td align="right"><strong>{SETUP_FEE}</strong></td>
				</tr>
                <!-- EDP: setup_fee_block -->
                </tbody>
				<tfoot>
				<tr>
                    <th colspan="2" style="text-align: right">
                        <a title="{TR_MORE_DETAIL}" href="package_info.php?id={PACK_ID}&amp;user_id={USER_ID}">{TR_MORE_DETAIL}</a> /
						<a title="{TR_PURCHASE}" href="addon.php?id={PACK_ID}&amp;user_id={USER_ID}">{TR_PURCHASE}</a>
                    </th>
				</tr>
                </tfoot>
			</table>
            <!-- EDP: purchase_list -->
