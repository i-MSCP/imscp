<!-- BDP: purchase_header -->
<!-- EDP: purchase_header -->
<script>
<!--
function sbmt(form, uaction) {

    form.uaction.value = uaction;
    form.submit();
    
    return false;

}
//-->
</script>
<form name="address" method="post" action="address.php">
<table width="400">
  <tr>
    <td  colspan="2" class="content3"><strong>{TR_ADRESS}</strong> </td>
  </tr>

	
				 <!-- BDP: page_message -->
                  <tr>
                    <td  colspan="2" class=title><font color="#FF0000">{MESSAGE}</font></td>
                  </tr>
                  <!-- EDP: page_message -->
  <tr>
    <td>
	

                <tr>
                  <td width="200" class="content2">{TR_FIRSTNAME} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=fname value="{VL_USR_NAME}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_LASTNAME} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=lname value="{VL_LAST_USRNAME}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td class="content2">{TR_EMAIL} <font color="#FF0000">*</font></td>
                  <td class="content"><input name=email type="text" class="textinput" style="width:210px" value="{VL_EMAIL}"></td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_COMPANY}</td>
                  <td class="content"><input type="text" name=firm value="{VL_USR_FIRM}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_POST_CODE} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=zip value="{VL_USR_POSTCODE}" style="width:80px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_CITY} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=city value="{VL_USRCITY}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_COUNTRY} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=country value="{VL_COUNTRY}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_STREET1} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=street1 value="{VL_STREET1}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_STREET2}</td>
                  <td class="content"><input type="text" name=street2 value="{VL_STREET2}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_PHONE} <font color="#FF0000">*</font></td>
                  <td class="content"><input type="text" name=phone value="{VL_PHONE}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td width="200" class="content2">{TR_FAX}</td>
                  <td class="content"><input type="text" name=fax value="{VL_FAX}" style="width:210px" class="textinput">
                  </td>
                </tr>
                <tr>
                  <td colspan="2"><small>{NEED_FILLED}</small></td>
                </tr>
  <tr align="right">
    <td  colspan="2">
	<input type="hidden" name="uaction" value="address">
	<input name="Submit" type="submit" class="button" value="  {TR_CONTINUE}  "></td>
  </tr>
</table>
</form>
<br>

<!-- BDP: purchase_footer -->
<!-- EDP: purchase_footer -->
