<?

if($folder == $sess["sysmap"]["inbox"] && $allow_filters) {

	$require_update = false;

	$filename = $userfolder."_infos/filters.ucf";
	$myfile = $UM->_read_file($filename);
	$filters = Array();

	if($myfile != "") 
		$filters = unserialize(base64_decode($myfile));

	/*
	
	examples:
	
	$filters = Array( 
		Array(
				"type" 		=> 1,
				"field"		=> 2,
				"match"		=> "filtering",
				"moveto"	=> "Test"
				),
		Array(
				"type" 		=> 2,
				"field"		=> 2,
				"match"		=> "ok",
				)
	);
	*/

	foreach($headers as $index => $message) {
		foreach($filters as $filter) {
			$match_text = "";

			switch($filter["field"]) {
			case FL_FIELD_FROM:
				foreach($message["from"] as $field) {
					$match_text .= " ".$field["name"]." ".$field["mail"];
				}
				break;
			case FL_FIELD_SUBJECT:
				$match_text = " ".$message["subject"];
				break;
			case FL_FIELD_TO:
				foreach($message["to"] as $field) {
					$match_text .= " ".$field["name"]." ".$field["mail"];
				}
				break;
			}

			if(!empty($match_text) && strpos($match_text,$filter["match"]) > 0) {
				switch($filter["type"]) {
				case FL_TYPE_MOVE:

					$UM->mail_move_msg($message,$filter["moveto"]);
					unset($sess["headers"][base64_encode($folder)]);
					unset($sess["headers"][base64_encode($filter["moveto"])]);

					$require_update = true;

					break;
				case FL_TYPE_DELETE:

					$UM->mail_delete_msg($message,$prefs["save-to-trash"],$prefs["st-only-read"]);
					unset($sess["headers"][base64_encode($folder)]);
					unset($sess["headers"][base64_encode("trash")]);

					$require_update = true;

					break;
				case FL_TYPE_MARK_READ:

					if(!eregi("SEEN",$message["flags"])) {
						$UM->mail_set_flag($message,"SEEN","+");
						$sess["headers"][base64_encode($folder)][$index] = $message;
					}

					break;
				}
			}

		}
	}
	reset($headers);
}
?>