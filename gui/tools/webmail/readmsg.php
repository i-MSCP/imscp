<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/

//defines
require("./inc/inc.php");

if(!isset($ix) || !isset($pag)) redirect("error.php?err=3&tid=$tid&lid=$lid");

$folderkey = base64_encode($folder);

$mysess 		= $sess["headers"][$folderkey];
$mail_info 		= $mysess[$ix];
$arAttachment 	= Array();


if(isset($attachment)) {

	$is_attached = true;
	$arAttachment 	= explode(",",$attachment);

	$UM->current_level = $arAttachment;

	$root = $mail_info;
	foreach($arAttachment as $item )
		if(is_numeric($item))
			$root = &$root["attachments"][$item];

	if( !is_array($root) || 
		!file_exists($root["filename"])) redirect("error.php?err=3&tid=$tid&lid=$lid");

	$result = $UM->_read_file($root["filename"]);

} else {
	$is_attached = false;
	$arAttachment = Array();

	if(!$UM->mail_connect()) { redirect("error.php?err=1&tid=$tid&lid=$lid"); exit; }
	if(!$UM->mail_auth()) { redirect("badlogin.php?tid=$tid&lid=$lid&error=".urlencode($UM->mail_error_msg)); exit; }

	if(!($result = $UM->mail_retr_msg($mail_info,1))) { 
		redirect("messages.php?err=2&folder=".urlencode($folder)."&pag=$pag&tid=$tid&lid=$lid&refr=true"); 
		exit; 
	}

	if($UM->mail_set_flag($mail_info,"\\SEEN","+")) {
		$sess["headers"][$folderkey][$ix] = $mail_info;
	}

	$UM->mail_disconnect(); 

}
echo($nocache);

$UM->displayimages = $prefs["display-images"];
$UM->allow_scripts = $allow_scripts;

$email = $UM->Decode($result);


if($ix > 0) {

	$umHavePrevious 	= 1;
	$umPreviousSubject 	= $mysess[($ix-1)]["subject"];
	$umPreviousLink 	= "readmsg.php?folder=".urlencode($folder)."&pag=$pag&ix=".($ix-1)."&tid=$tid&lid=$lid";

	$smarty->assign("umHavePrevious",$umHavePrevious);
	$smarty->assign("umPreviousSubject",$umPreviousSubject);
	$smarty->assign("umPreviousLink",$umPreviousLink);

}

if($ix < (count($mysess)-1)) {
	$umHaveNext 	= 1;
	$umNextSubject 	= $mysess[($ix+1)]["subject"];
	$umNextLink 	= "readmsg.php?folder=".urlencode($folder)."&pag=$pag&ix=".($ix+1)."&tid=$tid&lid=$lid";
	$smarty->assign("umHaveNext",$umHaveNext);
	$smarty->assign("umNextSubject",$umNextSubject);
	$smarty->assign("umNextLink",$umNextLink);
}



$body	= 	$email["body"];

if($block_external_images) 
	$body = eregi_replace("(src|background)=([\"]?)(http[s]?:\/\/[a-z0-9~#%@\&:=?+\/\.,_-]+[a-z0-9~#%@\&=?+\/_-]+)([\"]?)","\\1=\\2images/trans.gif\\4 original_url=\"\\3\"",$body);


$redir_path = getenv("PHP_SELF")?getenv("PHP_SELF"):$_SERVER["PHP_SELF"];
if(!$redir_path) $redir_path = $PHP_SELF;
$redir_path = dirname($redir_path)."/redir.php";

$body = eregi_replace("target=[\"]?[A-Z_]+[\"]?","target=\"blank\"",$body);
$body = eregi_replace("href=\"http([s]?)://","target=\"_blank\" href=\"$redir_path?http\\1://",$body);
$body = eregi_replace("href=\"mailto:","target=\"_top\" href=\"newmsg.php?tid=$tid&lid=$lid&to=",$body);

$uagent = 	$HTTP_SERVER_VARS["HTTP_USER_AGENT"];

$ns4    = 	(ereg("Mozilla/4",$uagent) && !ereg("MSIE",$uagent) && 
			!ereg("Gecko",$uagent));
$ns6moz = 	ereg("Gecko",$uagent);
$ie4up  = 	ereg("MSIE (4|5|6)",$uagent);
$other	= 	(!$ns4 && !$ns6moz && !$ie4up);


if ($other) {
	$body 	= 	eregi_replace("<base","<uebimiau_base_not_alowed",
				eregi_replace("<link","<uebimiau_link_not_alowed",
				$body));

	if(eregi("<[ ]*body.*background[ ]*=[ ]*[\"']?([A-Za-z0-9._&?=:/{}%+-]+)[\"']?.*>",$body,$regs))
		$backimg = 	" background=\"".$regs[1]."\"";
	$smarty->assign("umBackImg",$backimg);
	if(eregi("<[ ]*body[A-Z0-9._&?=:/\"' -]*bgcolor=[\"']?([A-Z0-9#]+)[\"']?[A-Z0-9._&?=:/\"' -]*>",$body,$regs))
		$backcolor = " bgcolor=\"".$regs[1]."\"";
	$smarty->assign("umBackColor",$backcolor);

	$body = eregi_replace("<body","<uebimiau_body_not_alowed",$body);
	$body = eregi_replace("a:(link|visited|hover)",".".uniqid(""),$body);
	$body = eregi_replace("(body)[ ]?\\{",".".uniqid(""),$body);

} elseif($ie4up || $ns6moz) {
	$sess["currentbody"] = $body;;
	$body = "<iframe src=\"show_body.php?tid=$tid&lid=$lid&folder=".htmlspecialchars($folder)."&ix=$ix\" width=\"100%\" height=\"400\" frameborder=\"0\"></iframe>";

} elseif($ns4) {
	$sess["currentbody"] = $body;;
	$body = "<ilayer width=\"100%\" left=\"0\" top=\"0\">$body</ilayer>";
}

$smarty->assign("umMessageBody",$body);


$ARFrom = $email["from"];
$useremail = $sess["email"];

// from
$name = $ARFrom[0]["name"];
$thismail = $ARFrom[0]["mail"];
$ARFrom[0]["link"] = "newmsg.php?nameto=".urlencode($name)."&mailto=$thismail&tid=$tid&lid=$lid";
$ARFrom[0]["title"] = "$name <$thismail>";

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

$jssource = "
<script language=\"JavaScript\">
function deletemsg() { 
	if(confirm('".ereg_replace("'","\\'",$confirm_delete)."')) 
		with(document.move) { decision.value = 'delete'; submit(); } 
}
function reply() { document.msg.submit(); }
function movemsg() { document.move.submit(); }
function newmsg() {	location = 'newmsg.php?folder=$folder&pag=$pag&tid=$tid&lid=$lid'; }
function headers() { mywin = window.open('headers.php?folder=".urlencode($folder)."&ix=$ix&tid=$tid&lid=$lid','Headers','width=550, top=100, left=100, height=320,directories=no,toolbar=no,status=no,scrollbars=yes,resizable=yes'); }
function catch_addresses() { window.open('catch.php?folder=".urlencode($folder)."&ix=$ix&tid=$tid&lid=$lid','Catch','width=550, top=100, left=100, height=320,directories=no,toolbar=no,status=no,scrollbars=yes'); }
function block_addresses() { window.open('block_address.php?folder=".urlencode($folder)."&ix=$ix&tid=$tid&lid=$lid','Block','width=550, top=100, left=100, height=320,directories=no,toolbar=no,status=no,scrollbars=yes'); }

function replyall() { with(document.msg) { rtype.value = 'replyall'; submit(); } }
function forward() { with(document.msg) { rtype.value = 'forward'; submit(); } }
function newmsg() { location = 'newmsg.php?pag=$pag&folder=".urlencode($folder)."&tid=$tid&lid=$lid'; }
function folderlist() { location = 'folders.php?folder=".urlencode($folder)."&tid=$tid&lid=$lid'}
function goend() { location = 'logout.php?tid=$tid&lid=$lid'; }
function goinbox() { location = 'messages.php?folder=inbox&tid=$tid&lid=$lid'; }
function goback() { location = 'messages.php?folder=".urlencode($folder)."&tid=$tid&lid=$lid&pag=$pag'; }
function search() { location = 'search.php?tid=$tid&lid=$lid'; }
function emptytrash() {	location = 'folders.php?empty=trash&folder=".urlencode($folder)."&goback=true&tid=$tid&lid=$lid';}
function addresses() { location = 'addressbook.php?tid=$tid&lid=$lid'; }
function prefs() { location = 'preferences.php?tid=$tid&lid=$lid'; }
function printit() { window.open('printmsg.php?tid=$tid&lid=$lid&folder=".urlencode($folder)."&ix=$ix','PrintView','resizable=1,top=10,left=10,width=600,heigth=500,scrollbars=1,status=0'); }
function openmessage(attach) { window.open('readmsg.php?folder=".urlencode($folder)."&pag=$pag&ix=$ix&tid=$tid&lid=$lid&attachment='+attach,'','resizable=1,top=10,left=10,width=600,height=400,scrollbars=1,status=0'); }
function openwin(targetUrl) { window.open(targetUrl); }
</script>
";

$umDeleteForm = "<input type=hidden name=lid value=$lid>
<input type=hidden name=sid value=\"$sid\">
<input type=hidden name=tid value=\"$tid\">
<input type=hidden name=decision value=move>
<input type=hidden name=folder value=\"".htmlspecialchars($folder)."\">
<input type=hidden name=pag value=$pag>
<input type=hidden name=start_pos value=$ix>
<input type=hidden name=end_pos value=".($ix+1).">
<input type=hidden name=msg_$ix value=X>
<input type=hidden name=back value=true>";

$umReplyForm = "<form name=msg action=\"newmsg.php\" method=POST>
<input type=hidden name=rtype value=\"reply\">
<input type=hidden name=sid value=\"$sid\">
<input type=hidden name=lid value=\"$lid\">
<input type=hidden name=tid value=\"$tid\">
<input type=hidden name=folder value=\"".htmlspecialchars($folder)."\">
<input type=hidden name=ix value=\"$ix\">
</form>
";

$smarty->assign("umDeleteForm",$umDeleteForm);
$smarty->assign("umReplyForm",$umReplyForm);
$smarty->assign("umJS",$jssource);

$smarty->assign("umSubject",$email["subject"]);
$smarty->assign("umDate",$email["date"]);

$anexos = $email["attachments"];
$haveattachs = (count($anexos) > 0)?1:0;

if(count($anexos) > 0) {
	$root = &$mail_info["attachments"];

	foreach($arAttachment as $item ) {
		if(is_numeric($item)) {
			$root = &$root[$item]["attachments"];
		}
	}

	$root = $email["attachments"];
	$sess["headers"][$folderkey][$ix] = $mail_info;

	$nIndex = count($arAttachment);
	$attachAr = Array();

	for($i=0;$i<count($anexos);$i++) {

		$arAttachment[$nIndex] 	= $i;
		$link1 = "download.php?folder=$folder&ix=$ix&attach=".join(",",$arAttachment)."&tid=$tid&lid=$lid";
		$link2 = "$link1&down=1";

		if(!$anexos[$i]["temp"]) {
			if($anexos[$i]["content-type"] == "message/rfc822") 
				$anexos[$i]["normlink"]	= "<a href=\"javascript:openmessage('".join(",",$arAttachment)."')\">";
			else
				$anexos[$i]["normlink"] = "$link1";

			$anexos[$i]["downlink"] = "$link2";
			$anexos[$i]["size"] = ceil($anexos[$i]["size"]/1024);
			$anexos[$i]["type"] = $anexos[$i]["content-type"];
			$attachAr[] = $anexos[$i];
		}
	}
	$smarty->assign("umHaveAttachments",(count($attachAr) > 0));
	$smarty->assign("umAttachList",$attachAr);
}

$SS->Save($sess);

$avalfolders = Array();
$d = dir($userfolder);
while($entry=$d->read()) {
	if(	is_dir($userfolder.$entry) && 
		$entry != ".." && 
		$entry != "." && 
		substr($entry,0,1) != "_" && 
		$entry != $folder &&
		($UM->mail_protocol == "imap" || $entry != "inbox")) {
		$entry = $UM->fix_prefix($entry,0);
		switch($entry) {
		case $sess["sysmap"]["inbox"]:
			$display = $inbox_extended;
			break;
		case $sess["sysmap"]["sent"]:
			$display = $sent_extended;
			break;
		case $sess["sysmap"]["trash"]:
			$display = $trash_extended;
			break;
		default:
			$display = $entry;
		}
		$avalfolders[] = Array("path" => $entry, "display" => $display);

	}
}
$d->close();
$smarty->assign("umAvalFolders",$avalfolders);
unset($UM);

if($is_attached)
	$smarty->display("$selected_theme/readmsg_popup.htm");
else
	$smarty->display("$selected_theme/readmsg.htm");
?>
