<?

/************************************************************************
UebiMiau is a GPL'ed software developed by 

 - Aldoir Ventura - aldoir@users.sourceforge.net
 - http://uebimiau.sourceforge.net

Fell free to contact, send donations or anything to me :-)
São Paulo - Brasil
*************************************************************************/

require("./inc/inc.php");

if(is_array($sess["headers"]) && file_exists($userfolder)) {

	$inboxdir = $userfolder."inbox/";
	$d = dir($userfolder."_attachments/");
	while($entry=$d->read()) {
		if($entry != "." && $entry != "..") 
			unlink($userfolder."_attachments/$entry");
	}
	$d->close();

	if(is_array($sess["folders"])) {
		$boxes = $sess["folders"];

		for($n=0;$n<count($boxes);$n++) {

			$entry = $boxes[$n]["name"];
			$file_list = Array();

			if(is_array($curfolder = $sess["headers"][base64_encode($entry)])) {

				for($j=0;$j<count($curfolder);$j++) 
					$file_list[] = $curfolder[$j]["localname"];

				$d = dir($userfolder."$entry/");

				while($curfile=$d->read()) {
					if($curfile != "." && $curfile != "..") {
						$curfile = $userfolder."$entry/$curfile";
						if(!in_array($curfile,$file_list)) 
							@unlink($curfile);
					}
				}
				$d->close();
			}
		}
	}


	if($prefs["empty-trash"]) {
		if(!$UM->mail_connect()) { redirect("error.php?err=1&tid=$tid&lid=$lid"); exit; }
		if(!$UM->mail_auth()) { redirect("badlogin.php?tid=$tid&lid=$lid&error=".urlencode($UM->mail_error_msg)); exit; }
		$trash = $sysmap["trash"];
		if(!is_array($sess["headers"][base64_encode($trash)])) $sess["headers"][base64_encode($trash)] = $UM->mail_list_msgs($trash);
		$trash = $sess["headers"][base64_encode($trash)];

		if(count($trash) > 0) {
			for($j=0;$j<count($trash);$j++) {
				$UM->mail_delete_msg($trash[$j],false);
			}
			$UM->mail_expunge();
		}
		$UM->mail_disconnect();
	}
	$SS->Kill();
}	

redirect("./index.php");
?> 