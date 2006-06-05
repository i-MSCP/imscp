<?php
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/

require("./inc/inc.php");

$smarty->assign("umUser",$f_user);
$smarty->assign("umJS",$jssource);

$refreshurl = "msglist.php?sid=$sid&tid=$tid&lid=$lid&folder=".urlencode($folder)."&pag=$pag&refr=true";

function my_connect() {
	global $UM,$sid,$tid,$lid;
	if(!$UM->mail_connect()) { Header("Location: error.php?err=1&sid=$sid&tid=$tid&lid=$lid\r\n"); exit; }
	if(!$UM->mail_auth(true)) { Header("Location: badlogin.php?sid=$sid&tid=$tid&lid=$lid\r\n"); exit; }
}

if( !is_array($sess["headers"][base64_encode(strtolower($folder))]) 

	|| isset($decision)
	|| isset($refr)) {

	my_connect();
	
	$sess["auth"] = true;

	$headers = $sess["headers"][base64_encode(strtolower($folder))];
	
	if(isset($start_pos) && isset($end_pos)) {

		for($i=$start_pos;$i<$end_pos;$i++) {
			if(isset(${"msg_$i"})) {
				if ($decision == "delete") {
					$UM->mail_delete_msg($headers[$i],$prefs["save-to-trash"],$prefs["st-only-read"]);
				}
				else {
					$UM->mail_move_msg($headers[$i],$aval_folders);
				}
				$expunge = true;
			}
		}

		if($expunge) {
			//$UM->mail_expunge();

			if($prefs["save-to-trash"])
				unset($sess["headers"][base64_encode("trash")]);
			if ($decision == "move")
				unset($sess["headers"][base64_encode(strtolower($aval_folders))]);

			//some servers, don't hide deleted messages until you don't disconnect
			$SS->Save($sess);
			$UM->mail_disconnect();

			my_connect();

			if ($back) {
				$back_to = $start_pos;
			}

		}
		unset($sess["headers"][base64_encode(strtolower($folder))]);

	} elseif (isset($refr)) {
		unset($sess["headers"][base64_encode(strtolower($folder))]);
	}

	$boxes = $UM->mail_list_boxes();
	$sess["folders"] = $boxes;

	$sess["last-update"] = time();

	if($quota_limit) {
		for($n=0;$n<count($boxes);$n++) {
			$entry = $boxes[$n]["name"];
			if(!is_array($sess["headers"][base64_encode(strtolower($entry))])) {
				$sess["headers"][base64_encode(strtolower($entry))] = $UM->mail_list_msgs($entry);
			}
		}
	} else {
		$sess["headers"][base64_encode(strtolower($folder))] = $UM->mail_list_msgs($folder);
	}

	$headers = $sess["headers"][base64_encode(strtolower($folder))];


	$UM->mail_disconnect();

	if($check_first_login && !$prefs["first-login"]) {

		$prefs["first-login"] = 1;
		save_prefs($prefs);
		$SS->Save($sess);

		Header("Location: preferences.php?sid=$sid&tid=$tid&lid=$lid&folder=".urlencode($folder));
		exit;
	}

} else {
	$headers = $sess["headers"][base64_encode(strtolower($folder))];
}


$arrow = ($sortorder == "ASC")?"images/arrow_up.gif":"images/arrow_down.gif";
$arrow = "&nbsp;<img src=$arrow width=8 height=7 border=0 alt=>";

$attach_arrow  	= "";
$subject_arrow 	= "";
$fromname_arrow = "";
$date_arrow 	= "";
$size_arrow 	= "";

switch($sortby) {
	case "subject":
		$subject_arrow  	= $arrow;
		break;
	case "fromname":
		$fromname_arrow  	= $arrow;
		break;
	case "date":
		$date_arrow  		= $arrow;
		break;
	case "size":
		$size_arrow   		= $arrow;
		break;
}


array_qsort2($headers,$sortby,$sortorder);
reset($headers);

$sess["headers"][base64_encode(strtolower($folder))] = $headers;
$SS->Save($sess);

if ($back_to) {
	if (count($headers) > $back_to) {
		Header("Location: readmsg.php?folder=".urlencode($folder)."&pag=$pag&ix=$back_to&sid=$sid&tid=$tid&lid=$lid");
		exit;
	}
}


$elapsedtime = (time()-$sess["last-update"])/60;
$timeleft = ($prefs["refresh-time"]-$elapsedtime);

if($timeleft > 0) {
	echo("<META HTTP-EQUIV=\"Refresh\" CONTENT=\"".(ceil($timeleft)*60)."; URL=$refreshurl\">");
} elseif ($prefs["refresh-time"]) {
	Header("Location: $refreshurl");
}

/* load total size */
while(list($box,$info) = each($sess["headers"])) {
	for($i=0;$i<count($info);$i++)
		$totalused += $info[$i]["size"];
}



unset($UM);

$smarty->assign("umTotalUsed",ceil($totalused/1024));
$quota_enabled = ($quota_limit)?1:0;
$smarty->assign("umQuotaEnabled",$quota_enabled);
$smarty->assign("umQuotaLimit",$quota_limit);
$usageGraph = get_usage_graphic(($totalused/1024),$quota_limit);
$smarty->assign("umUsageGraph",$usageGraph);

$exceeded = (($quota_limit) && (ceil($totalused/1024) >= $quota_limit));

// sorting arrays..


$smarty->assign("umAttachArrow",$attach_arrow);
$smarty->assign("umSubjectArrow",$subject_arrow);
$smarty->assign("umFromArrow",$fromname_arrow);
$smarty->assign("umDateArrow",$date_arrow);
$smarty->assign("umSizeArrow",$size_arrow);




$nummsg = count($headers);
if(!isset($pag) || !is_numeric(trim($pag))) $pag = 1;

$reg_pp    = $prefs["rpp"];
$start_pos = ($pag-1)*$reg_pp;
$end_pos   = (($start_pos+$reg_pp) > $nummsg)?$nummsg:$start_pos+$reg_pp;

if(($start_pos >= $end_pos) && ($pag != 1)) header("Location: msglist.php?folder=$folder&pag=".($pag-1)."&sid=$sid&tid=$tid&lid=$lid\r\n");

echo($nocache);

$jsquota = ($exceeded)?"true":"false";
$jssource = "
<script language=\"JavaScript\">
no_quota  = $jsquota;
quota_msg = '".ereg_replace("'","\\'",$quota_exceeded)."';
function readmsg(ix,read) {
	if(!read && no_quota)
		alert(quota_msg)
	else
		location = 'readmsg.php?folder=".urlencode($folder)."&pag=$pag&ix='+ix+'&sid=$sid&tid=$tid&lid=$lid'; 
}
function newmsg() { location = 'newmsg.php?pag=$pag&folder=".urlencode($folder)."&sid=$sid&tid=$tid&lid=$lid'; }
function refreshlist() { location = 'msglist.php?refr=true&folder=".urlencode($folder)."&pag=$pag&sid=$sid&tid=$tid&lid=$lid' }
function folderlist() { location = 'folders.php?folder=".urlencode($folder)."&sid=$sid&tid=$tid&lid=$lid'}
function delemsg() { document.form1.submit() }
function goend() { location = 'logout.php?sid=$sid&tid=$tid&lid=$lid'; }
function goinbox() { location = 'msglist.php?folder=inbox&sid=$sid&tid=$tid&lid=$lid'; }
function search() { location = 'search.php?sid=$sid&tid=$tid&lid=$lid'; }
function emptytrash() {	location = 'folders.php?empty=trash&folder=".urlencode($folder)."&goback=true&sid=$sid&tid=$tid&lid=$lid';}
function movemsg() { 
	if(no_quota) 
		alert(quota_msg);
	else {
		with(document.form1) { decision.value = 'move'; submit(); } 
	}
}
function addresses() { location = 'addressbook.php?sid=$sid&tid=$tid&lid=$lid'; }
function prefs() { location = 'preferences.php?sid=$sid&tid=$tid&lid=$lid'; }
function sel() {
	with(document.form1) {
		for(i=0;i<elements.length;i++) {
			thiselm = elements[i];
			if(thiselm.name.substring(0,3) == 'msg')
				thiselm.checked = !thiselm.checked
		}
	}
}
sort_colum = '$sortby';
sort_order = '$sortorder';

function sortby(col) {
	if(col == sort_colum) ord = (sort_order == 'ASC')?'DESC':'ASC';
	else ord = 'ASC';
	location = 'msglist.php?folder=$folder&pag=$pag&sortby='+col+'&sortorder='+ord+'&sid=$sid&tid=$tid&lid=$lid';
}

</script>
";


$smarty->assign("umErrorMessage",$msg);


$forms = "<input type=hidden name=lid value=$lid>
<input type=hidden name=sid value=\"$sid\">
<input type=hidden name=tid value=\"$tid\">
<input type=hidden name=decision value=delete>
<input type=hidden name=folder value=\"".htmlspecialchars($folder)."\">
<input type=hidden name=pag value=$pag>
<input type=hidden name=start_pos value=$start_pos>
<input type=hidden name=end_pos value=$end_pos>";


$smarty->assign("umJS",$jssource);
$smarty->assign("umForms",$forms);
$smarty->assign("umUserEmail",$sess["email"]);
$smarty->assign("umFolder",$folder);

$messagelist = Array();$func($textout);

if($nummsg > 0) {

	$newmsgs = 0;
	for($i=0;$i<count($headers);$i++)
		if(!eregi("\\SEEN",$headers[$i]["flags"])) $newmsgs++;

	if($nummsg == 1) $counttext = $msg_count_s;
	else $counttext = sprintf($msg_count_p,$nummsg);
	if($newmsgs == 1) $counttext .= $msg_unread_s;
	elseif ($newmsgs > 1) $counttext .= sprintf($msg_unread_p,$newmsgs);
	else $counttext .= $msg_no_unread;

	$counttext .= sprintf($msg_boxname,$boxname);

	for($i=$start_pos;$i<$end_pos;$i++) {
		$mnum = $headers[$i]["id"]; 

		$read = (eregi("\\SEEN",$headers[$i]["flags"]))?"true":"false";
		$readlink = "javascript:readmsg($i,$read)";
		$composelink = "newmsg.php?folder=$folder&nameto=".htmlspecialchars($headers[$i]["from"][0]["name"])."&mailto=".htmlspecialchars($headers[$i]["from"][0]["mail"])."&sid=$sid&tid=$tid&lid=$lid";
		$composelinksent = "newmsg.php?folder=$folder&nameto=".htmlspecialchars($headers[$i]["to"][0]["name"])."&mailto=".htmlspecialchars($headers[$i]["to"][0]["name"])."&sid=$sid&tid=$tid&lid=$lid";

		$from = $headers[$i]["from"][0]["name"];
		$to = $headers[$i]["to"][0]["name"];
		$subject = $headers[$i]["subject"];
		if(!eregi("\\SEEN",$headers[$i]["flags"])) {
			$msg_img = "./images/msg_unread.gif";
		} elseif (eregi("\\ANSWERED",$headers[$i]["flags"])) {
			$msg_img = "./images/msg_answered.gif";
		} else {
			$msg_img = "./images/msg_read.gif";
		}
		$prior = $headers[$i]["priority"];
		if($prior == 4 || $prior == 5)
			$img_prior = "&nbsp;<img src=\"./images/prior_low.gif\" width=5 height=11 border=0 alt=\"\">";
		elseif($prior == 1 || $prior == 2)
			$img_prior = "&nbsp;<img src=\"./images/prior_high.gif\" width=5 height=11 border=0 alt=\"\">";
		else
			$img_prior = "";

		$msg_img = "&nbsp;<img src=\"$msg_img\" width=14 height=14 border=0 alt=\"\">";
		$checkbox = "<input type=\"checkbox\" name=\"msg_$i\" value=1>";
		$attachimg = ($headers[$i]["attach"])?"&nbsp;<img src=images/attach.gif border=0>":"";

		$date = $headers[$i]["date"];
		$size = ceil($headers[$i]["size"]/1024);
		$index = count($messagelist);

		$messagelist[$index]["read"] = $read;
		$messagelist[$index]["readlink"] = $readlink;
		$messagelist[$index]["composelink"] = $composelink;
		$messagelist[$index]["composelinksent"] = $composelinksent;
		$messagelist[$index]["from"] = $from;
		$messagelist[$index]["to"] = $to;
		$messagelist[$index]["subject"] = $subject;
		$messagelist[$index]["date"] = $date;
		$messagelist[$index]["statusimg"] = $msg_img;
		$messagelist[$index]["checkbox"] = $checkbox;
		$messagelist[$index]["attachimg"] = $attachimg;
		$messagelist[$index]["priorimg"] = $img_prior;
		$messagelist[$index]["size"] = $size;
	}

} 
$smarty->assign("umNumMessages",$nummsg);
$smarty->assign("umNumUnread",$newmsgs);
$smarty->assign("umMessageList",$messagelist);

switch(strtolower($folder)) {
case "inbox":
	$display = $inbox_extended;
	break;
case "sent":
	$display = $sent_extended;
	break;
case "trash":
	$display = $trash_extended;
	break;
default:
	$display = $entry;
}

$smarty->assign("umBoxName",$display);

if($nummsg > 0) {
	if($pag > 1) $smarty->assign("umPreviousLink","msglist.php?folder=$folder&pag=".($pag-1)."&sid=$sid&tid=$tid&lid=$lid");
	for($i=1;$i<=ceil($nummsg / $reg_pp);$i++) 
		if($pag == $i) $navigation .= "$i ";
		else $navigation .= "<a href=\"msglist.php?folder=$folder&pag=$i&sid=$sid&tid=$tid&lid=$lid\" class=\"white\">$i</a> ";
	if($end_pos < $nummsg) $smarty->assign("umNextLink","msglist.php?folder=$folder&pag=".($pag+1)."&sid=$sid&tid=$tid&lid=$lid");
	$navigation .= " ($pag/".ceil($nummsg / $reg_pp).")";
}

$smarty->assign("umNavBar",$navigation);

$avalfolders = Array();
$d = dir($userfolder);
while($entry=$d->read()) {
	if(	is_dir($userfolder.$entry) && 
		$entry != ".." && 
		$entry != "." && 
		substr($entry,0,1) != "_" && 
		$entry != $folder &&
		($mail_protocol == "imap" || $entry != "inbox")) {
		switch(strtolower($entry)) {
		case "inbox":
			$display = $inbox_extended;
			break;
		case "sent":
			$display = $sent_extended;
			break;
		case "trash":
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
$smarty->display("$selected_theme/messagelist.htm");

?>
