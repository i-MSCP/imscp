<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/



// load configs
require("./inc/config.php");
require("./inc/lib.php");

define("SMARTY_DIR","./smarty/");
require_once(SMARTY_DIR."Smarty.class.php");
$smarty = new Smarty;
$smarty->compile_dir = $temporary_directory;
$smarty->security=true;
$smarty->secure_dir=array("./");
$smarty->assign("umLanguageFile",$selected_language.".txt");


$phpver = phpversion();
$phpver = doubleval($phpver[0].".".$phpver[2]);

if($phpver >= 4.1) {
	extract($_GET);
}

$smarty->assign("umSid",$sid);
$smarty->assign("umLid",$lid);
$smarty->assign("umTid",$tid);

$smarty->assign("umErrorCode",$err);

$smarty->display("$selected_theme/error.htm");
?>