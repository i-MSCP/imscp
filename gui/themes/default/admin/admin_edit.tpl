
<form name="editCustomerFrm" method="post" action="admin_edit.php?edit_id={EDIT_ID}">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CORE_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_USERNAME}</td>
			<td>{USERNAME}</td>
		</tr>
		<tr>
			<td><label for="password">{TR_PASSWORD}</label></td>
			<td><input type="password" name="password" id="password" class="pwd_generator" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="cpassword">{TR_PASSWORD_REPEAT}</label></td>
			<td><input type="password" name="password_confirmation" id="cpassword" value="" autocomplete="off"/></td>
		</tr>
		<tr>
			<td><label for="email">{TR_EMAIL}</label></td>
			<td><input type="text" name="email" id="email" value="{EMAIL}"/></td>
		</tr>
		</tbody>
	</table>
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_ADDITIONAL_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td><label for="fname">{TR_FIRST_NAME}</label></td>
			<td><input type="text" name="fname" id="fname" value="{FIRST_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="lname">{TR_LAST_NAME}</label></td>
			<td><input type="text" name="lname" id="lname" value="{LAST_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="gender">{TR_GENDER}</label></td>
			<td>
				<select id="gender" name="gender">
					<option value="M" {VL_MALE}>{TR_MALE}</option>
					<option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
					<option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="firm">{TR_COMPANY}</label></td>
			<td><input type="text" name="firm" id="firm" value="{FIRM}"/></td>
		</tr>
		<tr>
			<td><label for="zip">{TR_ZIP_POSTAL_CODE}</label></td>
			<td><input type="text" name="zip" id="zip" value="{ZIP}"/></td>
		</tr>
		<tr>
			<td><label for="city">{TR_CITY}</label></td>
			<td><input type="text" name="city" id="city" value="{CITY}"/></td>
		</tr>
		<tr>
			<td><label for="state">{TR_STATE_PROVINCE}</label></td>
			<td><input type="text" name="state" id="state" value="{STATE_PROVINCE}"/></td>
		</tr>
		<tr>
			<td><label for="country">{TR_COUNTRY}</label></td>
			<td><input type="text" name="country" id="country" value="{COUNTRY}"/></td>
		</tr>
		<tr>
			<td><label for="street1">{TR_STREET_1}</label></td>
			<td><input type="text" name="street1" id="street1" value="{STREET_1}"/></td>
		</tr>
		<tr>
			<td><label for="street2">{TR_STREET_2}</label></td>
			<td><input type="text" name="street2" id="street2" value="{STREET_2}"/></td>
		</tr>
		<tr>
			<td><label for="phone">{TR_PHONE}</label></td>
			<td><input type="text" name="phone" id="phone" value="{PHONE}"/></td>
		</tr>
		<tr>
			<td><label for="fax">{TR_FAX}</label></td>
			<td><input type="text" name="fax" id="fax" value="{FAX}"/></td>
		</tr>
		</tbody>
	</table>
	<div class="buttons">
		<input name="submit" type="submit" value="{TR_UPDATE}"/>
		<input id="send_data" type="checkbox" name="send_data" {SEND_DATA_CHECKED}/>
		<label for="send_data">{TR_SEND_DATA}</label>
	</div>
</form>
