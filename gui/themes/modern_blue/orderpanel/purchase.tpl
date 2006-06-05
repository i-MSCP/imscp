<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_PURCHASE_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="../themes/modern_blue/css/vhcs.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="../themes/modern_blue/css/vhcs.js"></script>
<script language="javascript" type="text/javascript">

//<!-- hide script from older browsers
function validateForm(contact)
{

	if(""==document.forms.client_info.client_name.value)
	{
		alert("Please enter your full name.");
		return false;
	}

	if(""==document.forms.client_info.client_email.value)
	{
		alert("Please enter your email address.");
		return false;
	}

	if(""==document.forms.client_info.client_domain.value)
	{
		alert("Please enter your domain name.");
		return false;
	}

}
//stop hiding script -->
</script>
</head>

<body onLoad="MM_preloadImages('../themes/modern_blue/images/icons/database_a.gif','../themes/modern_blue/images/icons/hosting_plans_a.gif','../themes/modern_blue/images/icons/domains_a.gif','../themes/modern_blue/images/icons/general_a.gif','../themes/modern_blue/images/icons/logout_a.gif','../themes/modern_blue/images/icons/manage_users_a.gif','../themes/modern_blue/images/icons/webtools_a.gif','../themes/modern_blue/images/icons/statistics_a.gif','../themes/modern_blue/images/icons/support_a.gif')">
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
			<!-- BDP: purchase_form -->
			<table width="100%" cellpadding="5" cellspacing="5">
			  <form method="POST" action="pay.php" name="client_info" onsubmit="return validateForm(client_info);">
			  	<input type="hidden" name="reseller_id" value="{RESELLER_ID}">
			  	<input type="hidden" name="purchase_id" value="{PURCHASE_ID}">
			  	<tr>
			  	   <td>
			  	   		{TR_CLIENT_NAME}
			  	   </td>
			  	   <td>
			  		<INPUT type="text" name="client_name">
			  	   </td>
			  	</tr>
			  	<tr>
			  	   <td>
			  	   		{TR_CLIENT_EMAIL}
			  	   </td>
			  	   <td>
			  		<INPUT type="text" name="client_email">
			  	   </td>
			  	</tr>
			  	<tr>
			  	   <td>
			  	   		{TR_CLIENT_DOMAIN}
			  	   </td>
			  	   <td>
			  		<INPUT type="text" name="client_domain">
			  	   </td>
			  	</tr>
			  	<tr>
			  	   <td>
			  	   		<INPUT type="reset" value="{TR_RESET}">
			  	   </td>			  
			  	   <td>
			  	   		<INPUT type="submit" value="{TR_SUBMIT}">
			  	   </td>
			  	</tr>
            </table>
            <tr>
            <td colspan="2" align="center">
            	<a href="index.php?reseller_id={RESELLER_ID}">{TR_GO_BACK}</a>
            </td>
			<!-- EDP: purchase_form -->
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