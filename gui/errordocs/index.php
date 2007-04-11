<html>
 <head>
  <title>ispCP OMEGA ERROR</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
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
          <td width="453" height="69" background="/errors/<?php echo $_SERVER['REDIRECT_STATUS']; ?>/error_top.jpg">&nbsp;</td>
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
						print "Unauthorized!\n";
						break;
					case 403:
						print "Forbidden!\n";
						break;
					case 404:
						print "File Not Found!\n";
						break;
					case 500:
						print "Internal Server Error!\n";
						break;
					default:
						print "Unknown Error!\n";
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
