<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>{TR_RESELLER_MAIN_INDEX_PAGE_TITLE}</title>
<meta name="robots" content="nofollow, noindex" />
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
<meta http-equiv="Content-Style-Type" content="text/css" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="{THEME_COLOR_PATH}/css/ispcp.js"></script>
<script type="text/javascript">
<!--
function delete_order(url, name) {
	if (!confirm(sprintf("{TR_MESSAGE_DELETE_ACCOUNT}", name)))
		return false;
	location = url;
}
//-->
</script>
</head>

<body onload="MM_preloadImages('{THEME_COLOR_PATH}/images/icons/database_a.gif','{THEME_COLOR_PATH}/images/icons/hosting_plans_a.gif','{THEME_COLOR_PATH}/images/icons/domains_a.gif','{THEME_COLOR_PATH}/images/icons/general_a.gif' ,'{THEME_COLOR_PATH}/images/icons/manage_users_a.gif','{THEME_COLOR_PATH}/images/icons/webtools_a.gif','{THEME_COLOR_PATH}/images/icons/statistics_a.gif','{THEME_COLOR_PATH}/images/icons/support_a.gif')">
<!-- BDP: logged_from --><table width="100%" border="0" cellspacing="0" cellpadding="0">
      <tr>
        <td height="20" nowrap="nowrap" class="backButton">&nbsp;&nbsp;&nbsp;<a href="change_user_interface.php?action=go_back"><img src="{THEME_COLOR_PATH}/images/icons/close_interface.png" width="16" height="16" border="0" style="vertical-align:middle" alt="" /></a> {YOU_ARE_LOGGED_AS}</td>
      </tr>
    </table>
	<!-- EDP: logged_from -->
<table width="100%" border="0" cellspacing="0" cellpadding="0" style="height:100%;padding:0;margin:0;">
<tr>
<td align="left" valign="top" style="vertical-align: top; width: 195px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_left.jpg" width="195" height="53" border="0" alt="ispCP Logogram" /></td>
<td style="height: 56px; width:100%; background-color: #0f0f0f"><img src="{THEME_COLOR_PATH}/images/top/top_left_bg.jpg" width="582" height="53" border="0" alt="" /></td>
<td style="width: 73px; height: 56px;"><img src="{THEME_COLOR_PATH}/images/top/top_right.jpg" width="73" height="56" border="0" alt="" /></td>
</tr>
	<tr>
		<td style="width: 195px; vertical-align: top;">{MENU}</td>
	    <td colspan="2" style="vertical-align: top;"><table style="width: 100%; padding:0;margin:0;" cellspacing="0">
          <tr style="height:95px;">
            <td style="padding-left:30px; width: 100%; background-image: url({THEME_COLOR_PATH}/images/top/middle_bg.jpg);">{MAIN_MENU}</td>
            <td style="padding:0;margin:0;text-align: right; width: 73px;vertical-align: top;"><img src="{THEME_COLOR_PATH}/images/top/middle_right.jpg" width="73" height="95" border="0" alt="" /></td>
          </tr>
          <tr>
            <td colspan="3"><table width="100%" border="0" cellspacing="0" cellpadding="0">
              <tr>
                <td align="left"><table width="100%" cellpadding="5" cellspacing="5">
                    <tr>
                      <td width="25"><img src="{THEME_COLOR_PATH}/images/content/table_icon_domains.png" width="25" height="25" alt="" /></td>
                      <td colspan="2" class="title">{TR_ORDER_DETAILS}</td>
                    </tr>
                </table></td>
                <td width="27" align="right">&nbsp;</td>
              </tr>
              <tr>
                <td><form name="order_details" method="post" action="orders_detailst.php?order_id={ID}">
                    <table width="100%" cellpadding="5" cellspacing="5">
                      <!-- BDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="title"><span class="message">{MESSAGE}</span></td>
                      </tr>
                      <!-- EDP: page_message -->
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="content3"><strong>{TR_HOSTING_INFO}</strong></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_DATE}</td>
                        <td class="content"><span class="content2"> {DATE}</span></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_HP}</td>
                        <td class="content">{HP}</td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_DOMAIN}</td>
                        <td class="content"><input name="domain" type="text" class="textinput" id="domain" style="width:210px" value="{DOMAINNAME}" />
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_DMN_IP}</td>
                        <td class="content"><select name="domain_ip">
                            <!-- BDP: ip_entry -->
                            <option value="{IP_VALUE}" {IP_SELECTED}>{IP_NUM}&nbsp;({IP_NAME})</option>
                            <!-- EDP: ip_entry -->
                          </select>
                        </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2" class="content3"><b>{TR_CUSTOMER_DATA}</b></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_CUSTOMER_ID}</td>
                        <td class="content"><input name="customer_id" type="text" class="textinput" id="customer_id" style="width:210px" value="{CUSTOMER_ID}" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td width="203" class="content2"> {TR_FIRST_NAME}</td>
                        <td class="content"><input type="text" name="fname" value="{FNAME}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2"> {TR_LAST_NAME}</td>
                        <td width="516" class="content"><input type="text" name="lname" value="{LNAME}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_GENDER}</td>
                        <td class="content">
                            <select name="gender" size="1">
                               <option value="M" {VL_MALE}>{TR_MALE}</option>
                               <option value="F" {VL_FEMALE}>{TR_FEMALE}</option>
                               <option value="U" {VL_UNKNOWN}>{TR_UNKNOWN}</option>
                            </select>
                         </td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_COMPANY}</td>
                        <td class="content"><input type="text" name="firm" value="{FIRM}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_ZIP_POSTAL_CODE}</td>
                        <td class="content"><input type="text" name="zip" value="{ZIP}" style="width:80px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_CITY}</td>
                        <td class="content"><input type="text" name="city" value="{CITY}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_STATE}</td>
                        <td class="content"><input type="text" name="state" value="{STATE}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_COUNTRY}</td>
                        <td class="content"><input type="text" name="country" value="{COUNTRY}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_STREET_1}</td>
                        <td class="content"><input type="text" name="street1" value="{STREET1}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_STREET_2}</td>
                        <td class="content"><input type="text" name="street2" value="{STREET2}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_EMAIL}</td>
                        <td class="content"><input type="text" name="email" value="{EMAIL}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_PHONE}</td>
                        <td class="content"><input type="text" name="phone" value="{PHONE}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td class="content2">{TR_FAX}</td>
                        <td class="content"><input type="text" name="fax" value="{FAX}" style="width:210px" class="textinput" /></td>
                      </tr>
                      <tr>
                        <td width="25">&nbsp;</td>
                        <td colspan="2"><input name="add" type="button" onclick="return sbmt(document.forms[0],'add_user');" class="button" value="{TR_ADD}" />
                          &nbsp;&nbsp;
                          <input name="update" type="button" onclick="return sbmt(document.forms[0],'update_data');" class="button" value="{TR_UPDATE_DATA}" />
                          &nbsp;&nbsp;
                          <input name="delete" type="button" onclick="delete_order('orders_delete.php?order_id={ID}', '{DOMAIN}')" class="button" value="{TR_DELETE_ORDER}" />
                          <input type="hidden" name="uaction" value="" />
                          <input name="order_id" type="hidden" value="{ID}" /></td>
                      </tr>
                    </table>
                </form></td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
            </table></td>
          </tr>
        </table></td>
	</tr>
</table>
</body>
</html>
