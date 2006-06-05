<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_PAY_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="../themes/modern_blue/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="../themes/modern_blue/css/vhcs.js"></script>

</head>

<body onLoad="MM_preloadImages('../themes/modern_blue/images/icons/database_a.gif','../themes/modern_blue/images/icons/hosting_plans_a.gif','../themes/modern_blue/images/icons/domains_a.gif','../themes/modern_blue/images/icons/general_a.gif','../themes/modern_blue/images/icons/logout_a.gif','../themes/modern_blue/images/icons/manage_users_a.gif','../themes/modern_blue/images/icons/webtools_a.gif','../themes/modern_blue/images/icons/statistics_a.gif','../themes/modern_blue/images/icons/support_a.gif'); {TR_BODY_JOB}">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">
    <table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr>
          <td width="17"><img src="../themes/modern_blue/images/top/left.jpg" width="17" height="80"></td>
          <td width="198" align="center" background="../themes/modern_blue/images/top/logo_background.jpg"><img src="{ISP_LOGO}"></td>
          <td background="../themes/modern_blue/images/top/left_fill.jpg"><img src="../themes/modern_blue/images/top/left_fill.jpg" width="2" height="80"></td>
          <td width="766"><img src="../themes/modern_blue/images/top/middle_background.jpg" width="766" height="80"></td>
          <td background="../themes/modern_blue/images/top/right_fill.jpg"><img src="../themes/modern_blue/images/top/right_fill.jpg" width="3" height="80"></td>
          <td width="9"><img src="../themes/modern_blue/images/top/right.jpg" width="9" height="80"></td>
        </tr>
    </table></td>
  </tr>
  <tr background="images/content_background_mainpage.gif">
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" background="../themes/modern_blue/images/content/table_background.jpg" class="title"><img src="../themes/modern_blue/images/content/table_icon_users.jpg" width="85" height="62" align="absmiddle">{TR_PURCHASE_FROM} {TR_PURCHASE_RESELLER_NAME}</td>
            <td width="27" align="right" background="../themes/modern_blue/images/content/table_background.jpg"><img src="../themes/modern_blue/images/content/table_icon_close.jpg" width="27" height="62"></td>
          </tr>
          <tr>
            <td valign="top">
			<!-- BDP: pay_page -->
			<form name="paypal_form" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="{TR_RESELLER_MAIL}">
				<input type="hidden" name="item_name" value="{TR_PACKAGE_NAME}">
				<input type="hidden" name="item_number" value="{TR_PACKAGE_ID}">
				<input type="hidden" name="custom" value="{TR_RESELLER_ID}">
				<input type="hidden" name="return" value="{TR_RETURN_ADDRESS}">
				<input type="hidden" name="amount" value="{TR_PACKAGE_PRICE}">
				<input type="hidden" name="no_note" value="1">
				<input type="hidden" name="currency_code" value="{TR_CURRENCY}">
			</form>
			<br><br><br><br><br><br><br>
			<p align="center"><b>{TR_PLEASE_WAIT}</b></p>
			<!-- EDP: pay_page -->
      </tr>
    </table></td>
  </tr>
  <tr>
    <td height="71" background="images/background_down.gif"><table width="100%"  border="0" cellspacing="0" cellpadding="0">
        <tr><td width="17"><img src="../themes/modern_blue/images/top/down_left.jpg" width="17" height="71"></td><td width="198" valign="top" background="../themes/modern_blue/images/top/downlogo_background.jpg"><table width="100%" border="0" cellpadding="0" cellspacing="0" >
          <tr>
            <td width="55"><a href="http://www.vhcs.net" target="_blank"><img src="../themes/modern_blue/images/vhcs.gif" alt="" width="51" height="71" border="0"></a></td>
            <td class="bottom">{VHCS_LICENSE}</td>
          </tr>
        </table>          </td>
          <td background="../themes/modern_blue/images/top/down_left_fill.jpg"><img src="../themes/modern_blue/images/top/down_left_fill.jpg" width="2" height="71"></td><td width="766" background="../themes/modern_blue/images/top/middle_background.jpg"><img src="../themes/modern_blue/images/top/down_middle_background.jpg" width="766" height="71"></td>
          <td background="../themes/modern_blue/images/top/down_right_fill.jpg"><img src="../themes/modern_blue/images/top/down_right_fill.jpg" width="3" height="71"></td>
          <td width="9"><img src="../themes/modern_blue/images/top/down_right.jpg" width="9" height="71"></td></tr>
    </table></td>
  </tr>
</table>
</body>
</html>