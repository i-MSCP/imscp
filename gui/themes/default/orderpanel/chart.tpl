
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="chart" method="post" action="checkout.php">
				<table style="width:550px;">
					<tr>
						<th colspan="2">{YOUR_CHART}</th>
					</tr>
					<tr>
						<td style="width:200px;">
							<strong>{TR_PERSONAL_DATA}</strong>
						</td>
						<td><a href="address.php?edit=yes" class="link">{TR_CHANGE}</a></td>
					</tr>
					<tr>
						<td>{TR_FIRSTNAME}</td>
						<td>{VL_USR_NAME}</td>
					</tr>
					<tr>
						<td>{TR_LASTNAME}</td>
						<td>{VL_LAST_USRNAME}</td>
					</tr>
					<tr>
						<td>{TR_EMAIL}</td>
						<td>{VL_EMAIL}</td>
					</tr>
					<tr>
						<td>{TR_COMPANY}</td>
						<td>{VL_USR_FIRM}</td>
					</tr>
					<tr>
						<td>{TR_GENDER}</td>
						<td>{VL_USR_GENDER}</td>
					</tr>
					<tr>
						<td>{TR_POST_CODE}</td>
						<td>{VL_USR_POSTCODE}</td>
					</tr>
					<tr>
						<td>{TR_CITY}</td>
						<td>{VL_USRCITY}</td>
					</tr>
					<tr>
						<td>{TR_STATE}</td>
						<td>{VL_USRSTATE}</td>
					</tr>
					<tr>
						<td>{TR_COUNTRY}</td>
						<td>{VL_COUNTRY}</td>
					</tr>
					<tr>
						<td>{TR_STREET1}</td>
						<td>{VL_STREET1}</td>
					</tr>
					<tr>
						<td>{TR_STREET2}</td>
						<td>{VL_STREET2}</td>
					</tr>
					<tr>
						<td>{TR_PHONE}</td>
						<td>{VL_PHONE}</td>
					</tr>
					<tr>
						<td>{TR_FAX}</td>
						<td>{VL_FAX}</td>
					</tr>
				</table>
				<table style="width:550px;">
					<tr>
						<th colspan="2"><strong>{TR_COSTS}</strong></th>
					</tr>
					<tr>
						<td style="width:200px;">
							<strong>{TR_PACKAGE_NAME}</strong>
						</td>
						<td>
							<a href="index.php" class="link">{TR_CHANGE}</a>
						</td>
					</tr>
					<tr>
						<td>{TR_PACKAGE_PRICE}</td>
						<td>{PRICE}</td>
					</tr>
					<tr>
						<td>{TR_PACKAGE_SETUPFEE}</td>
						<td>{SETUP}</td>
					</tr>
				</table>

				<table style="width:550px;">
					<tr>
						<td><strong>{TR_TOTAL}</strong></td>
						<td align="right"><strong>{TOTAL}</strong></td>
					</tr>
				</table>

				<table style="width:550px;">
					<!-- BDP: tos_field -->
					<tr>
						<th colspan="2">
							<label for="tos"><strong>{TR_TOS_PROPS}</strong></label>
						</th>
					</tr>
					<tr>
						<td colspan="2">
							<textarea readonly="readonly" style="padding:5px;resize:none;" id="tos" cols="75"  rows="5">{TOS}</textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<input style="vertical-align: middle;" type="checkbox" id="tosAccept" name="tosAccept" value="1"/>
							<label for="tosAccept">{TR_TOS_ACCEPT}</label>
						</td>
					</tr>
					<tr>
						<td colspan="2">&nbsp;</td>
					</tr>
					<!-- EDP: tos_field -->
					<tr>
						<th colspan="2">
							<label for="capcode"><strong>{TR_CAPCODE}</strong></label>
						</th>
					</tr>
					<tr>
						<td colspan="2" style="text-align: center;">
							<a href="chart.php" title="Generate new code">{TR_IMGCAPCODE}</a>
						</td>
					</tr>
					<tr>
						<td colspan="2" style="text-align:center;">
							<input name="capcode" type="text" id="capcode" value=""/>
						</td>
					</tr>
					<tr>
						<td colspan="2">{TR_IMGCAPCODE_DESCRIPTION}</td>
					</tr>
				</table>
				<div class="buttons" style="width:550px;">
					<input name="submit" type="submit" value="{TR_CONTINUE}"/>
				</div>
			</form>
