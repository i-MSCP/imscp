
		<script type="text/javascript">
		/* <![CDATA[ */
		function delete_order(url, subject) {
			if (!confirm(sprintf("{TR_MESSAGE_DELETE_ACCOUNT}", subject)))
				return false;
			location = url;
		}
		/* ]]> */
		</script>
			<form name="order_details" method="post" action="orders_detailst.php?order_id={ID}">
				<fieldset>
					<legend>{TR_HOSTING_INFO}</legend>
					<table>
						<tr>
							<td>{TR_DATE}</td>
							<td>{DATE}</td>
						</tr>
						<tr>
							<td>{TR_HP}</td>
							<td>{HP}</td>
						</tr>
						<tr>
							<td><label for="domain">{TR_DOMAIN}</label></td>
							<td><input name="domain" type="text" id="domain" value="{DOMAINNAME}" /></td>
						</tr>
						<tr>
							<td><label for="domain_ip">{TR_DMN_IP}</label></td>
							<td>
								<select name="domain_ip" id="domain_ip">
									<!-- BDP: ip_entry -->
										<option value="{IP_VALUE}" {IP_SELECTED}>{IP_NUM}&nbsp;({IP_NAME})</option>
									<!-- EDP: ip_entry -->
								</select>
							</td>
						</tr>
					</table>
				</fieldset>
				<fieldset>
					<legend>{TR_CUSTOMER_DATA}</legend>
					<table>
						<tr>
							<td><label for="customer_id">{TR_CUSTOMER_ID}</label></td>
							<td><input name="customer_id" type="text" id="customer_id" value="{CUSTOMER_ID}" /></td>
						</tr>
						<tr>
							<td><label for="fname">{TR_FIRST_NAME}</label></td>
							<td><input id="fname" type="text" name="fname" value="{FNAME}" /></td>
						</tr>
						<tr>
							<td><label for="lname">{TR_LAST_NAME}</label></td>
							<td><input id="lname" type="text" name="lname" value="{LNAME}" /></td>
						</tr>
						<tr>
							<td><label for="gender">{TR_GENDER}</label></td>
							<td>
								<select id="gender" name="gender" size="1">
									<option value="M" {VL_MALE}>{TR_MALE}</option>
									<option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
									<option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
								</select>
							</td>
						</tr>
						<tr>
							<td><label for="firm">{TR_COMPANY}</label></td>
							<td><input id="firm" type="text" name="firm" value="{FIRM}"/></td>
						</tr>
						<tr>
							<td><label for="zip">{TR_ZIP_POSTAL_CODE}</label></td>
							<td><input type="text" name="zip" id="zip" value="{ZIP}" /></td>
						</tr>
						<tr>
							<td><label for="city">{TR_CITY}</label></td>
							<td><input id="city" type="text" name="city" value="{CITY}" /></td>
						</tr>
						<tr>
							<td><label for="state">{TR_STATE}</label></td>
							<td><input id="state" type="text" name="state" value="{STATE}" /></td>
						</tr>
						<tr>
							<td><label for="country">{TR_COUNTRY}</label></td>
							<td><input id="country" type="text" name="country" value="{COUNTRY}" /></td>
						</tr>
						<tr>
							<td><label for="street1">{TR_STREET_1}</label></td>
							<td><input id="street1"  type="text" name="street1" value="{STREET1}"/></td>
						</tr>
						<tr>
							<td><label for="street2">{TR_STREET_2}</label></td>
							<td><input id="street2" type="text" name="street2" value="{STREET2}"/></td>
						</tr>
						<tr>
							<td><label for="email">{TR_EMAIL}</label></td>
							<td><input id="email" type="text" name="email" value="{EMAIL}"/></td>
						</tr>
						<tr>
							<td><label for="phone">{TR_PHONE}</label></td>
							<td><input id="phone" type="text" name="phone" value="{PHONE}"/></td>
						</tr>
						<tr>
							<td><label for="fax">{TR_FAX}</label></td>
							<td><input id="fax" type="text" name="fax" value="{FAX}"/></td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="add" type="button" onclick="return sbmt(document.forms[0],'add_user');" value="{TR_ADD}" />
					<input name="update" type="button" onclick="return sbmt(document.forms[0],'update_data');"  value="{TR_UPDATE_DATA}" />
					<input name="delete" type="button" onclick="delete_order('orders_delete.php?order_id={ID}', '{DOMAIN}')" value="{TR_DELETE_ORDER}" />
				</div>
				<input type="hidden" name="uaction" value="" />
				<input name="order_id" type="hidden" value="{ID}" />
			</form>
