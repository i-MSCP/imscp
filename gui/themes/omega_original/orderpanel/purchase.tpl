<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
<title>{TR_PURCHASE_PAGE_TITLE}</title>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
<link href="../themes/omega_original/css/ispcp.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="../themes/omega_original/css/ispcp.js"></script>
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

<body onLoad="MM_preloadImages('../themes/omega_original/images/icons/database_a.gif','../themes/omega_original/images/icons/hosting_plans_a.gif','../themes/omega_original/images/icons/domains_a.gif','../themes/omega_original/images/icons/general_a.gif','../themes/omega_original/images/icons/logout_a.gif','../themes/omega_original/images/icons/manage_users_a.gif','../themes/omega_original/images/icons/webtools_a.gif','../themes/omega_original/images/icons/statistics_a.gif','../themes/omega_original/images/icons/support_a.gif')">
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="100%">
  <tr>
    <td height="80" align="left" valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top"><table height="100%" width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td height="62" align="left" class="title">{TR_PURCHASE_FROM} {TR_PURCHASE_RESELLER_NAME}</td>
            <td width="27" align="right">&nbsp;</td>
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
    <td height="71">&nbsp;</td>
  </tr>
</table>
</body>
</html>