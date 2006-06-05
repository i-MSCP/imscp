<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/




// load the configurations
require("./inc/config.php");
require("./inc/lib.php");

define("SMARTY_DIR","./smarty/");
require_once(SMARTY_DIR."Smarty.class.php");

$smarty = new Smarty;
$smarty->compile_dir = $temporary_directory;
$smarty->security=true;
$smarty->secure_dir=array("./");

$smarty->assign("umLanguageFile",$selected_language.".txt");

$error = ereg_replace("\[\]", "", $error);

$smarty->assign("umServerResponse",$error);
$smarty->assign("umLid",$lid);
$smarty->assign("umTid",$tid);

$smarty->display("$selected_theme/bad-login.htm");


?>