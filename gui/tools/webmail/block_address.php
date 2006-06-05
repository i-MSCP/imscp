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

$filename = $userfolder."_infos/filters.ucf";
$myfile = $UM->_read_file($filename);
$filters = Array();

if($myfile != "") 
	$filters = unserialize(base64_decode($myfile));

function is_in_filter($email) {
	global $filters;
	foreach($filters as $filter) {
		if($filter["type"] == FL_TYPE_DELETE && $filter["match"] == $email)
			return true;
	}
	return false;
}

$mail_info = $sess["headers"][base64_encode($folder)][$ix];

$emails = Array();
$from = $mail_info["from"];
$to = $mail_info["to"];
$cc = $mail_info["cc"];


for($i=0;$i<count($from);$i++) {
	if(!is_in_filter($from[$i]["mail"])) {
		$from[$i]["index"] = $i;
		$emails[] = $from[$i];
	}
}
$aval = array();


if(isset($fFilter)) {
	for($i=0;$i<count($fFilter);$i++) {

		$filters[] = Array(
					"type" 		=> 2,
					"field"		=> 1,
					"match"		=>  $emails[$fFilter[$i]]["mail"],
					);
	}

	$UM->_save_file($filename,base64_encode(serialize($filters)));

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
	$smarty->assign("umAvailableAddresses",count($emails));

	$smarty->assign("umAddressList",$emails);

	$smarty->display("$selected_theme/block-address.htm");
}
?>