<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/
// load session management
require("./inc/inc.php");


if(!$UM->mail_connect()) redirect("error.php?err=1&tid=$tid&lid=$lid\r\n");
if(!$UM->mail_auth()) { redirect("badlogin.php?tid=$tid&lid=$lid&error=".urlencode($UM->mail_error_msg)."\r\n"); exit; }

// check and create a new folder

$newfolder = trim($newfolder);

$require_update = false;


if($newfolder != "" && 
	ereg("[A-Za-z0-9 -]",$newfolder) && 
	!file_exists($userfolder.$newfolder)) {
	$UM->mail_create_box($newfolder);
	$require_update = true;
}


// check and delete the especified folder: system folders can not be deleted
if(	$delfolder != "" && 
	$delfolder != $sess["sysmap"]["inbox"] && 
	$delfolder != $sess["sysmap"]["sent"] && 
	$delfolder != $sess["sysmap"]["trash"] && 
	ereg("[A-Za-z0-9 -]",$delfolder) &&
	(strpos($delfolder,"..") === false)) {
	if($UM->mail_delete_box($delfolder)) {
		unset($sess["headers"][base64_encode($delfolder)]);
		$require_update = true;
	}
}

if($require_update)	$sess["folders"] = $UM->mail_list_boxes();

require("./folder_list.php");


if(isset($empty)) {
	$headers = $sess["headers"][base64_encode($empty)];
	for($i=0;$i<count($headers);$i++) {
		$UM->mail_delete_msg($headers[$i],$prefs["save-to-trash"],$prefs["st-only-read"]);
		$expunge = true;
	}
	if($expunge) {
		$UM->mail_expunge();
		unset($sess["headers"][base64_encode($empty)]);
		/* ops.. you have sent anything to trash, then you need refresh it */
		if($prefs["save-to-trash"])
			unset($sess["headers"][base64_encode("trash")]);
		$SS->Save($sess);
	}
	if(isset($goback)) redirect("process.php?folder=".urlencode($folder)."&tid=$tid&lid=$lid");

}

$jssource = "
<script language=\"JavaScript\">
function newmsg() { location = 'newmsg.php?pag=$pag&folder=".urlencode($folder)."&tid=$tid&lid=$lid'; }
function refreshlist() { location = 'folders.php?folder=".urlencode($folder)."&tid=$tid&lid=$lid'}
function goend() { location = 'logout.php?tid=$tid&lid=$lid'; }
function search() { location = 'search.php?tid=$tid&lid=$lid'; }
function goinbox() { location = 'messages.php?folder=".$sess["sysmap"]["inbox"]."&tid=$tid&lid=$lid'; }
function emptytrash() {	location = 'folders.php?empty=".$sess["sysmap"]["trash"]."&folder=".urlencode($folder)."&goback=true&tid=$tid&lid=$lid';}
function addresses() { location = 'addressbook.php?tid=$tid&lid=$lid'; }
function prefs() { location = 'preferences.php?tid=$tid&lid=$lid'; }
function create() {
	strPat = /[^A-Za-z0-9 -]/;
	frm = document.forms[0];
	strName = frm.newfolder.value
	mathArray = strName.match(strPat)
	if(mathArray != null) {
		alert('".ereg_replace("'","\\'",$error_invalid_name)."')
		return false;
	}else
		frm.submit();
}
</script>
";


$smarty->assign("umJS",$jssource);
$smarty->assign("umLid",$lid);
$smarty->assign("umTid",$tid);
$smarty->assign("umSid",$sid);
$smarty->assign("umUserEmail",$sess["email"]);


$boxes = $UM->mail_list_boxes();

$scounter = 0;
$pcounter = 0;

for($n=0;$n<count($boxes);$n++) {
	$entry = $boxes[$n]["name"];
	$unread = 0;

	if(!is_array($sess["headers"][base64_encode($entry)])) {
		$thisbox = $UM->mail_list_msgs($entry);
		$sess["headers"][base64_encode($entry)] = $thisbox;
	} else $thisbox = $sess["headers"][base64_encode($entry)];

	$boxsize = 0;

	for($i=0;$i<count($thisbox);$i++) {
		if(!eregi("\\SEEN",$thisbox[$i]["flags"])) $unread++;
		$boxsize += $thisbox[$i]["size"];
	}

	$delete = "&nbsp;";

	if(!in_array($entry, $sess["sysfolders"]))
		$delete = "<a href=\"folders.php?delfolder=$entry&folder=$folder&tid=$tid&lid=$lid\">OK</a>";

	$boxname = $entry;

	if($unread != 0) $unread = "<b>$unread</b>";

	if(in_array($entry, $sess["sysfolders"])) {
		switch(strtolower($entry)) {
		case strtolower($sess["sysmap"]["inbox"]):
			$boxname = $inbox_extended;
			break;
		case strtolower($sess["sysmap"]["sent"]):
			$boxname = $sent_extended;
			break;
		case strtolower($sess["sysmap"]["trash"]):
			$boxname = $trash_extended;
			break;
		}
		$system[$scounter]["entry"]     	= $entry;
		$system[$scounter]["name"]      	= $boxname;
		$system[$scounter]["msgs"]      	= count($thisbox)."/$unread";
		$system[$scounter]["del"]       	= $delete;
		$system[$scounter]["boxsize"]   	= ceil($boxsize/1024);
		$system[$scounter]["chlink"] 		= "process.php?folder=".$entry."&tid=$tid&lid=$lid";
		$system[$scounter]["emptylink"]		= "folders.php?empty=".$entry."&folder=".$entry."&tid=$tid&lid=$lid";

		$scounter++;
	} else {

		$personal[$pcounter]["entry"]   	= $entry;
		$personal[$pcounter]["name"]    	= $boxname;
		$personal[$pcounter]["msgs"]    	= count($thisbox)."/$unread";
		$personal[$pcounter]["del"]    		= $delete;
		$personal[$pcounter]["boxsize"]	 	= ceil($boxsize/1024);
		$personal[$pcounter]["chlink"]  	= "process.php?folder=".urlencode($entry)."&tid=$tid&lid=$lid";
		$personal[$pcounter]["emptylink"]	= "folders.php?empty=".urlencode($entry)."&folder=".urlencode($entry)."&tid=$tid&lid=$lid";

		$pcounter++;
	}
	$totalused += $boxsize;
}



$SS->Save($sess);
$UM->mail_disconnect();
unset($SS,$UM);
array_qsort2 ($system,"name");

if(!is_array($personal)) $personal = Array();

$umFolderList = array_merge($system, $personal);


$smarty->assign("umFolderList",$umFolderList);

$smarty->assign("umPersonal",$personal);
$smarty->assign("umTotalUsed",ceil($totalused/1024));
$quota_enabled = ($quota_limit)?1:0;
$smarty->assign("umQuotaEnabled",$quota_enabled);
$smarty->assign("umQuotaLimit",$quota_limit);
$usageGraph = get_usage_graphic(($totalused/1024),$quota_limit);
$smarty->assign("umUsageGraph",$usageGraph);
$noquota = (($totalused/1024) > $quota_limit)?1:0;
$smarty->assign("umNoQuota",$noquota);

echo($nocache);

$smarty->display("$selected_theme/folders.htm");

?>
