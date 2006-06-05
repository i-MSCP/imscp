<html>
 <head>
  <title>VHCS ERROR</title>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <link href="/vhcs2/themes/modern_blue/css/vhcs.css" rel="stylesheet" type="text/css">
  <script type="text/javascript" src="/vhcs2/themes/modern_blue/css/vhcs.js"></script>
 </head>
 <body style="color:#000000;">
  <table width="100%" height="99%"  border="0" cellpadding="0" cellspacing="0" bgcolor="#334163">
   <tr>
	<td height="551">
	 <table width="100%"  border="0" cellpadding="0" cellspacing="0">
      <tr bgcolor="#334163">
       <td>&nbsp;</td>
       <td align="right"><span class="login_time"><?php print date("g:i a"); ?></span>&nbsp;&nbsp;&nbsp;<br />
                         <span class="login_bottom"><?php print date("l dS of F Y"); ?></span>&nbsp;&nbsp;&nbsp;
	   </td>
      </tr>
      <tr>
       <td width="1" background="/vhcs2/themes/modern_blue/images/login/content_background.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_background.gif" width="1" height="348"></td>
       <td height="348" align="center" background="/vhcs2/themes/modern_blue/images/login/content_background.gif">
        <table width="600" height="200" border="00" cellpadding="1" cellspacing="3" bgcolor="#FF0000">
         <tr>
	      <td align="center" bgcolor="#FFFFFF"></strong>
	       <h2><font color="#FF0000">Error <?php print htmlspecialchars($_SERVER['REDIRECT_STATUS']);?>!</font></h2><br />
	       <b><?php print htmlspecialchars($_SERVER['REDIRECT_URL']);?></b><br>
	       <?php
				switch ($_SERVER['REDIRECT_STATUS']) {
					case 401:
						print "Unauthorized!";
						break;
					case 403:
						print "Forbidden!";
						break;
					case 404:
						print "File Not Found!";
						break;
					case 500:
						print "Internal Server Error!";
						break;
					default:
						print "Unknown Error!";
						break;
				}
		   ?>
          </td>
         </tr>
        </table>
       </td>
      </tr>
      <tr>
       <td width="1" height="2" background="/vhcs2/themes/modern_blue/images/login/content_down.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_down.gif" width="2" height="2"></td>
       <td height="2" background="/vhcs2/themes/modern_blue/images/login/content_down.gif"><img src="/vhcs2/themes/modern_blue/images/login/content_down.gif" width="2" height="2"></td>
      </tr>
      <tr>
       <td width="1" bgcolor="#334163">&nbsp;</td>
       <td bgcolor="#334163"><a href="http://www.vhcs.net" target="_blank"><img src="/vhcs2/themes/modern_blue/images/login/vhcs_logo.gif" alt="VHCS - Virtual Hosting Control System - Control Panel" width="68" height="60" border="0"></a></td>
      </tr>
     </table>
    </td>
   </tr>
  </table>
 </body>
</html>