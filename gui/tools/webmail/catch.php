<?
/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/




require("./inc/inc.php");
if(!isset($ix) || !isset($folder)) redirect("error.php?err=3&tid=$tid&lid=$lid");


$filename = $userfolder."_infos/addressbook.ucf";
$myfile = $UM->_read_file($filename);
$addressbook = Array();

if($myfile != "") 
	$addressbook = unserialize(base64_decode($myfile));

function valid_email($thismail) {
	if (!eregi("(^['+\./0-9A-Z^_`a-z{|}~-]+@[-a-z0-9_.]+[-a-z0-9_]+)", $thismail)) return 0;
	global $addressbook,$f_email;
	for($i=0;$i<count($addressbook);$i++)
		if(trim($addressbook[$i]["email"]) == trim($thismail)) return 0;
	if(trim($f_email) == trim($thismail)) return 0;
	return 1;
}

$mail_info = $sess["headers"][base64_encode($folder)][$ix];

$emails = Array();
$from = $mail_info["from"];
$to = $mail_info["to"];
$cc = $mail_info["cc"];


for($i=0;$i<count($from);$i++)
	$emails[] = $from[$i];
for($i=0;$i<count($to);$i++)
	$emails[] = $to[$i];
for($i=0;$i<count($cc);$i++)
	$emails[] = $cc[$i];

$aval = array();
for($i=0;$i<count($emails);$i++)
	if(valid_email($emails[$i]["mail"])) $aval[] = $emails[$i];
	
$aval_count = count($aval);

if(isset($ckaval)) {
	for($i=0;$i<count($ckaval);$i++) {
		$idchecked = $ckaval[$i];
		$id = count($addressbook);
		$addressbook[$id]["name"] = $emails[$idchecked]["name"];
		$addressbook[$id]["email"] = $emails[$idchecked]["mail"];
	}

	$UM->_save_file($filename,base64_encode(serialize($addressbook)));

	echo("
	<script language=javascript>
		self.close();
	</script>
	");
	exit;
} else {

	$smarty->assign("umLid",$lid);
	$smarty->assign("umSid",$sid);
	$smarty->assign("umFolder",$folder);
	$smarty->assign("umIx",$ix);
	$smarty->assign("umAvailableAddresses",$aval_count);

	if($aval_count > 0) {
		for($i=0;$i<$aval_count;$i++)
			$aval[$i]["index"] = $i;
		$smarty->assign("umAddressList",$aval);

	}
	$smarty->display("$selected_theme/catch-address.htm");
}
?>