<?
	$sess["last-update"] = time();
	if($quota_limit) {

		for($n=0;$n<count($boxes);$n++) {
			$entry = $boxes[$n]["name"];
			if(!array_key_exists(base64_encode($entry), $sess["headers"])) {
				$sess["headers"][base64_encode($entry)] = $UM->mail_list_msgs($entry);
			}
		}
	} else {
		$sess["headers"][$folder_key] = $UM->mail_list_msgs($folder);
	}
	$headers = $sess["headers"][$folder_key];
?>