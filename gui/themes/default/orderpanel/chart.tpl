<!-- BDP: purchase_header -->
<!-- EDP: purchase_header -->

<!-- BDP: page_message -->
<div class="{MESSAGE_CLS}" style="width:550px;">{MESSAGE}</div>
<!-- EDP: page_message -->

<form name="chart" method="post" action="checkout.php">
    <table style="width:550px;">
        <tr>
            <th colspan="2">{YOUR_CHART}</th>
        </tr>
        <tr>
            <td>
                <strong>{TR_PERSONAL_DATA}</strong>
            </td>
            <td><a href="address.php?edit=yes" class="link">{TR_CHANGE}</a></td>
        </tr>
        <tr>
            <td style="width:200px;">{TR_FIRSTNAME}</td>
            <td>{VL_USR_NAME}</td>
        </tr>
        <tr>
            <td>{TR_LASTNAME}</td>
            <td>{VL_LAST_USRNAME}</td>
        </tr>
        <tr>
            <td>{TR_EMAIL}</td>
            <td class="content">{VL_EMAIL}</td>
        </tr>
        <tr>
            <td class="content">{TR_COMPANY}</td>
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
        <tr style="background:none;border: none;">
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <th colspan="2"><strong>{TR_COSTS}</strong></th>
        </tr>
        <tr>
            <td>
                <strong>{TR_PACKAGE_NAME}</strong>
            </td>
            <td>
                <a href="index.php" class="link">{TR_CHANGE}</a>
            </td>
        </tr>
        <tr>
            <td style="width:200px;">{TR_PACKAGE_PRICE}</td>
            <td>{PRICE}</td>
        </tr>
        <tr>
            <td>{TR_PACKAGE_SETUPFEE}</td>
            <td class="content2">{SETUP}</td>
        </tr>
        <tr style="background:none;border: none;">
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td><strong>{TR_TOTAL}</strong></td>
            <td align="right"><strong>{TOTAL}</strong></td>
        </tr>
        <tr style="background:none;border: none;">
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2">
                <label for="capcode"><strong>{TR_CAPCODE}</strong></label>
            </td>
        </tr>
        <tr style="background:none;">
            <td colspan="2" style="text-align: center;">{TR_IMGCAPCODE}</td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:center;">
                <input name="capcode" type="text" id="capcode" value=""></td>
        </tr>
        <tr>
            <td colspan="2">{TR_IMGCAPCODE_DESCRIPTION}</td>
        </tr>
        <!-- BDP: tos_field -->
        <tr>
            <td colspan="2">
                <label for="tosAccept"<strong>{TR_TOS_PROPS}</strong></label></td>
        </tr>
        <tr>
            <td colspan="2">
                <textarea readonly="readonly" style="width=400px" cols="50" rows="5">{TOS}</textarea>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <input type="checkbox" id="tosAccept" name="tosAccept" value="1" />{TR_TOS_ACCEPT}
            </td>
        </tr>
        <!-- EDP: tos_field -->
    </table>
    <div class="buttons" style="width:550px;margin-top:25px;">
        <input name="Submit" type="submit" class="button" value="{TR_CONTINUE}" />
    </div>
</form>
<!-- BDP: purchase_footer -->
<!-- EDP: purchase_footer -->
