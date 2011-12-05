<!-- INCLUDE "../shared/layout/header.tpl" -->
	<div class="header">
            {MAIN_MENU}

	    <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>

        <div class="location">
            <div class="location-area">
                <h1 class="general">{TR_MENU_GENERAL_INFORMATION}</h1>
            </div>
            <ul class="location-menu">
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
                <li><a href="personal_change.php">{TR_PERSONAL_DATA}</a></li>
            </ul>
        </div>

        <div class="left_menu">
            {MENU}
        </div>

       <div class="body">
            <h2 class="doc"><span>{TR_PERSONAL_DATA}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <form action="personal_change.php" method="post" name="client_change_personal_frm">
                <table>
                    <tr>
                      <td style="width:300px;">
                          <label for="fname">{TR_FIRST_NAME}</label>
                      </td>
                      <td><input type="text" name="fname" id="fname" value="{FIRST_NAME}"/></td>
                    </tr>
                    <tr>
                      <td><label for="lname">{TR_LAST_NAME}</label></td>
                      <td><input type="text" name="lname" id="lname" value="{LAST_NAME}"/></td>
                    </tr>
                    <tr>
                      <td><label for="gender">{TR_GENDER}</label></td> 
                      <td><select id="gender" name="gender"> 
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
                      <td><label for="street1">{TR_STREET_1}</label></td>
                      <td><input type="text" name="street1" id="street1" value="{STREET_1}" /></td>
                    </tr>
                    <tr>
                      <td><label for="street2">{TR_STREET_2}</label></td>
                      <td><input type="text" name="street2" id="street2" value="{STREET_2}" /></td>
                    </tr>
                    <tr>
                      <td><label for="zip">{TR_ZIP_POSTAL_CODE}</label></td>
                      <td><input type="text" name="zip" id="zip" value="{ZIP}" /></td>
                    </tr>
                    <tr>
                      <td><label for="city">{TR_CITY}</label></td>
                      <td><input type="text" name="city" id="city" value="{CITY}" /></td>
                    </tr>
                    <tr>
                      <td><label for="state">{TR_STATE}</label></td>
                      <td><input type="text" name="state" id="state" value="{STATE}" /></td>
                    </tr>
                    <tr>
                      <td><label for="country">{TR_COUNTRY}</label></td>
                      <td><input type="text" name="country" id="country" value="{COUNTRY}" /></td>
                    </tr>
                    <tr>
                      <td><label for="email">{TR_EMAIL}</label></td>
                      <td><input type="text" name="email" id="email" value="{EMAIL}" /></td>
                    </tr>
                    <tr>
                      <td><label for="phone">{TR_PHONE}</label></td>
                      <td><input type="text" name="phone" id="phone" value="{PHONE}" /></td>
                    </tr>
                    <tr>
                      <td><label for="fax">{TR_FAX}</label></td>
                      <td><input type="text" name="fax" id="fax" value="{FAX}" /></td>
                    </tr>
                </table>
                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_UPDATE_DATA}" />
                    <input type="hidden" name="uaction" value="updt_data" />
                </div>
            </form>
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
