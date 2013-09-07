
<form name="user_edit" method="post" action="user_edit.php?edit_id={EDIT_ID}" autocomplete="off">
	<table class="firstColFixed">
		<thead>
		<tr>
			<th colspan="2">{TR_CORE_DATA}</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>{TR_USERNAME}</td>
			<td>{VL_USERNAME}</td>
		</tr>
		<tr>
			<td><label for="userpassword">{TR_PASSWORD}</label></td>
			<td>
				<input type="password" name="userpassword" id="userpassword" value="{VAL_PASSWORD}" autocomplete="off"/>
				<input name="genpass" type="submit" value="{TR_PASSWORD_GENERATE}"/>
			</td>
		</tr>
		<tr>
			<td><label for="userpassword_repeat">{TR_REP_PASSWORD}</label></td>
			<td>
				<input type="password" name="userpassword_repeat" id="userpassword_repeat" value="{VAL_PASSWORD}"
					   autocomplete="off"/>
			</td>
		</tr>
		<tr>
			<td><label for="useremail">{TR_USREMAIL}</label></td>
			<td><input type="text" name="useremail" id="useremail" value="{VL_MAIL}"/></td>
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
			<td><label for="useruid">{TR_CUSTOMER_ID}</label></td>
			<td><input type="text" name="useruid" id="useruid" value="{VL_USR_ID}"/></td>
		</tr>
		<tr>
			<td><label for="userfname">{TR_FIRSTNAME}</label></td>
			<td><input type="text" name="userfname" id="userfname" value="{VL_USR_NAME}"/></td>
		</tr>
		<tr>
			<td><label for="userlname">{TR_LASTNAME}</label></td>
			<td><input type="text" name="userlname" id="userlname" value="{VL_LAST_USRNAME}"/></td>
		</tr>
		<tr>
			<td><label for="gender">{TR_GENDER}</label></td>
			<td>
				<select id="gender" name="gender">
					<option value="M"{VL_MALE}>{TR_MALE}</option>
					<option value="F"{VL_FEMALE}>{TR_FEMALE}</option>
					<option value="F"{VL_UNKNOWN}>{TR_UNKNOWN}</option>
				</select>
			</td>
		</tr>
		<tr>
			<td><label for="userfirm">{TR_COMPANY}</label></td>
			<td><input type="text" name="userfirm" id="userfirm" value="{VL_USR_FIRM}"/></td>
		</tr>
		<tr>
			<td><label for="userstreet1">{TR_STREET1}</label></td>
			<td><input type="text" name="userstreet1" id="userstreet1" value="{VL_STREET1}"/></td>
		</tr>
		<tr>
			<td><label for="userstreet2">{TR_STREET2}</label></td>
			<td><input type="text" name="userstreet2" id="userstreet2" value="{VL_STREET2}"/></td>
		</tr>
		<tr>
			<td><label for="userzip">{TR_POST_CODE}</label></td>
			<td><input type="text" name="userzip" id="userzip" value="{VL_USR_POSTCODE}"/></td>
		</tr>
		<tr>
			<td><label for="usercity">{TR_CITY}</label></td>
			<td><input type="text" name="usercity" id="usercity" value="{VL_USRCITY}"/></td>
		</tr>
		<tr>
			<td><label for="userstate">{TR_STATE}</label></td>
			<td><input id="userstate" type="text" name="userstate" value="{VL_USRSTATE}"/></td>
		</tr>
		<tr>
			<td><label for="usercountry">{TR_COUNTRY}</label></td>
			<td><input type="text" name="usercountry" id="usercountry" value="{VL_COUNTRY}"/></td>
		</tr>
		<tr>
			<td><label for="userphone">{TR_PHONE}</label></td>
			<td><input type="text" name="userphone" id="userphone" value="{VL_PHONE}"/></td>
		</tr>
		<tr>
			<td><label for="userfax">{TR_FAX}</label></td>
			<td><input type="text" name="userfax" id="userfax" value="{VL_FAX}"/></td>
		</tr>
		</tbody>
	</table>

	<div class="buttons">
		<input name="Submit" type="submit" value="{TR_UPDATE}"/>
		<input type="checkbox" id="send_data" name="send_data"/>
		<label for="send_data">{TR_SEND_DATA}</label>
		<input type="hidden" name="uaction" value="save_changes"/>
		<input type="hidden" name="edit_id" value="{EDIT_ID}"/>
	</div>
</form>
