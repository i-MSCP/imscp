<html>
 <head>
  <title>VHCS ERROR</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <link href="/vhcs2/themes/omega_original/css/vhcs.css" rel="stylesheet" type="text/css">
  <script type="text/javascript" src="/vhcs2/themes/omega_original/css/vhcs.js"></script>
  <meta name="robots" content="noindex">
  <meta name="robots" content="nofollow">
 </head>
 <body style="color:#000000;">
 <table width="100%" height="100%" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td>
     <table width="453" style="border:solid 1px #CCCCCC;" align="center" cellpadding="0" cellspacing="0">
      <tr>
       <td>
        <table width="453" border="0" align="center" cellpadding="0" cellspacing="0">
         <tr>
          <td width="453" height="69" background="/vhcs2/themes/omega_original/images/login/login_top.jpg">&nbsp;</td>
         </tr>
         <tr>
          <td>
           <table width="453" border="0" cellpadding="0" cellspacing="1">
            <tr>
             <td align="center" valign="middle" bgcolor="#FFFFFF"></strong>
              <h2><font color="#FF0000">Error <?php print htmlspecialchars($_SERVER['REDIRECT_STATUS']);?>!</font></h2><br />
              <b><?php print htmlspecialchars($_SERVER['REQUEST_URI']);?></b><br />
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
       </table>
      </td>
     </tr>
    </table>
   </td>
  </tr>
 </table>
</body>
</html>
