<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/
require("./inc/inc.php");
if(!isset($folder) || !isset($ix)) die("Expected parameters");
$mail_info = $sess["headers"][base64_encode($folder)][$ix];
$smarty->assign("umPageTitle",$mail_info["subject"]);
$smarty->assign("umHeaders",preg_replace("/\t/","&nbsp;&nbsp;&nbsp;&nbsp;",nl2br(htmlspecialchars($mail_info["header"]))));
$smarty->display("$selected_theme/headers-window.htm");

?>
