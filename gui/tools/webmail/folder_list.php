<?
$folders = $sess["folders"];
$scounter = 0;
$pcounter = 0;
foreach($folders as $entry) {
	$entry = $entry["name"];
	$boxname = $entry;
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
		$system[$scounter]["systemname"]    = $entry;
		$system[$scounter]["name"]      	= $boxname;
		$system[$scounter]["link"] 			= "process.php?folder=".$entry."&tid=$tid&lid=$lid";
		$scounter++;
	} else {
		$personal[$pcounter]["systemname"]  = $entry;
		$personal[$pcounter]["name"]    	= $boxname;
		$personal[$pcounter]["link"] 		= "process.php?folder=$entry&tid=$tid&lid=$lid";
		$pcounter++;
	}
}
array_qsort2 ($system,"name");
array_qsort2 ($personal,"name");

$smarty->assign("umSystemFolders",$system);
$smarty->assign("umPersonalFolders",$personal);
?>