
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
			<!-- EDP: page_message -->

			<form name="addressFrm" method="post" action="address.php">
				<table style="width:550px;">
					<tr>
						<th colspan="2"><strong>{TR_PERSONAL_DATA}</strong></th>
					</tr>
					<tr>
						<td><label for="fname">{TR_FIRSTNAME} <span style="color:red;">*</span></td>
						<td><input type="text" id="fname" name="fname" value="{VL_USR_NAME}"/></td>
					</tr>
					<tr>
						<td><label for="lname">{TR_LASTNAME} <span style="color:red;">*</span></label></td>
						<td><input type="text" id="lname" name="lname" value="{VL_LAST_USRNAME}"/></td>
					</tr>
					<tr>
						<td><label for="email">{TR_EMAIL} <span style="color:red;">*</span></label></td>
						<td><input id="email" name="email" type="text" value="{VL_EMAIL}"/></td>
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
						<td><input id="firm" type="text" name="firm" value="{VL_USR_FIRM}"/></td>
					</tr>
					<tr>
						<td><label for="zip">{TR_POST_CODE} <span style="color:red;">*</span></label></td>
						<td><input type="text" id="zip" name="zip" value="{VL_USR_POSTCODE}"/></td>
					</tr>
					<tr>
						<td><label for="city">{TR_CITY} <span style="color:red;">*</span></label></td>
						<td><input type="text" id="city" name="city" value="{VL_USRCITY}"/></td>
					</tr>
					<tr>
						<td><label for="state">{TR_STATE}</label></td>
						<td><input type="text" id="state" name="state" value="{VL_USRSTATE}"/></td>
					</tr>
					<tr>
						<td><label for="country">{TR_COUNTRY} <span style="color:red;">*</span></label></td>
						<td><input type="text" id="country" name="country" value="{VL_COUNTRY}"/></td>
					</tr>
					<tr>
						<td><label for="street1">{TR_STREET1} <span style="color:red;">*</span></label></td>
						<td><input type="text" id="street1" name="street1" value="{VL_STREET1}"/></td>
					</tr>
					<tr>
						<td><label for="street2">{TR_STREET2}</label></td>
						<td><input type="text" id="street2" name="street2" value="{VL_STREET2}"/></td>
					</tr>
					<tr>
						<td><label for="phone">{TR_PHONE} <span style="color:red;">*</span></label></td>
						<td><input type="text" id="phone" name="phone" value="{VL_PHONE}"/></td>
					</tr>
					<tr>
						<td><label for="fax"></label>{TR_FAX}</label></td>
						<td><input type="text" id="fax" name="fax" value="{VL_FAX}"/></td>
					</tr>
					<tr>
						<td colspan="2">
							<small>{NEED_FILLED}</small>
						</td>
					</tr>
				</table>
				<div class="buttons" style="width:550px;margin-top:25px;">
					<input type="hidden" name="uaction" value="address"/>
					<input name="submit" type="submit" value="{TR_CONTINUE}"/>
				</div>
			</form>
