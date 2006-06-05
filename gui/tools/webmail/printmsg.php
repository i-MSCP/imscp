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

echo($nocache);

if(!file_exists($mail_info["localname"])) die("File not found");;
$email = $UM->_read_file($mail_info["localname"]);

$UM->displayimages = $prefs["display-images"];
$UM->allow_scripts = $allow_scripts;

$email = $UM->Decode($email);
$body = $email["body"];

//cleanning bad tags to show
if(eregi("<[ ]*body.*background[ ]*=[ ]*[\"']?([A-Za-z0-9._&?=:/{}%+-]+)[\"']?.*>",$body,$regs))
	$backimg = 	" background=\"".$regs[1]."\"";
$smarty->assign("umBackImg",$backimg);
if(eregi("<[ ]*body[A-Z0-9._&?=:/\"' -]*bgcolor=[\"']?([A-Z0-9#]+)[\"']?[A-Z0-9._&?=:/\"' -]*>",$body,$regs))
	$backcolor = " bgcolor=\"".$regs[1]."\"";
$smarty->assign("umBackColor",$backcolor);

$body = eregi_replace("<body","<uebimiau_body_not_alowed",$body);
$body = eregi_replace("a:(link|visited|hover)",".".uniqid(""),$body);
$body = eregi_replace("(body)[ ]?\\{",".".uniqid(""),$body);

$smarty->assign("umMessageBody",$body);


$ARFrom = $email["from"];
$useremail = $sess["email"];
// from
$name = $ARFrom[0]["name"];
$thismail = $ARFrom[0]["mail"];
$ARFrom[0]["link"] = "newmsg.php?nameto=".urlencode($name)."&mailto=$thismail&tid=$tid&lid=$lid";
$ARRom[0]["title"] = "$name <$thismail>";

$smarty->assign("umFromList",$ARFrom);

// To
$ARTo = $email["to"];

for($i=0;$i<count($ARTo);$i++) {
	$name = $ARTo[$i]["name"];
	$thismail = $ARTo[$i]["mail"];
	$link = "newmsg.php?nameto=".urlencode($name)."&mailto=$thismail&tid=$tid&lid=$lid";
	$ARTo[$i]["link"] = $link;
	$ARTo[$i]["title"] = "$name <$thismail>";
	$smarty->assign("umTOList",$ARTo);
}

// CC
$ARCC = $email["cc"];
if(count($ARCC) > 0) {
	$smarty->assign("umHaveCC",1);
	for($i=0;$i<count($ARCC);$i++) {
		$name = $ARCC[$i]["name"];
		$thismail = $ARCC[$i]["mail"];
		$link = "newmsg.php?nameto=".urlencode($name)."&mailto=$thismail&tid=$tid&lid=$lid";
		$ARCC[$i]["link"] = $link;
		$ARCC[$i]["title"] = "$name <$thismail>";
	}
	$smarty->assign("umCCList",$ARCC);
}

$smarty->assign("umPageTitle",$email["subject"]);

$smarty->assign("umSubject",$email["subject"]);
$smarty->assign("umDate",$email["date"]);

$anexos = $email["attachments"];


$haveattachs = (count($anexos) > 0)?1:0;
$smarty->assign("umHaveAttachments",$haveattachs);

if(count($anexos) > 0) {
	for($i=0;$i<count($anexos);$i++) {
		$link1 = "download.php?folder=$folder&ix=$ix&mnum=$mnum&bound=".base64_encode($anexos[$i]["boundary"])."&part=".$anexos[$i]["part"]."&tid=$tid&lid=$lid";
		$link2 = "$link1&down=1";
		$anexos[$i]["normlink"] = $link1;
		$anexos[$i]["downlink"] = $link2;
		$anexos[$i]["size"] = ceil($anexos[$i]["size"]/1024);
		$anexos[$i]["type"] = trim($anexos[$i]["content-type"]);
	}
	$smarty->assign("umAttachList",$anexos);
}

unset($UM);

$smarty->display("$selected_theme/print-message.htm");

?>