<?php

define('SM_PATH','../../');

include_once('bookmarks_data.php');
include_once('functions.php');
if (file_exists(SM_PATH . 'include/validate.php'))
   include_once(SM_PATH . 'include/validate.php');
else if (file_exists(SM_PATH . 'src/validate.php'))
   include_once(SM_PATH . 'src/validate.php');

$bookmarksdata = $_SESSION["bookmarksdata"];
$filename = $_FILES['bookmarks']['tmp_name'];

sqgetGlobalVar('passval', $passval, SQ_GET);
if (!isset($passval)) sqgetGlobalVar('passval', $passval, SQ_POST);

$target_folder = urldecode($passval);
$current_folder = '';

if (file_exists($filename)){
	 $fp = fopen ($filename,'r');
	 if ($fp){
	 		import_folder($fp,$target_folder,$current_folder);
   }
}

writebookmarksdata();
header('Location: bookmarks.php?passval=' . $target_folder );

function import_folder($file_ptr, $target, $foldername) {
   global $bookmarksdata, $target_folder;
	 
	 if (($target == '') or ($target == '|')) {
	 		$sFolder = $foldername;
	 } else {
	    if ($foldername == '') {
         $sFolder = $target;
			} else {
         $sFolder = $target . '|' . $foldername;
			}
	 }
	 if ($sFolder != $target_folder) {
     $bookindex = count($bookmarksdata) + 1;
     $bookmarksdata[$bookindex]["folder"] = $sFolder;
     $bookmarksdata[$bookindex]["title"] = '';
     $bookmarksdata[$bookindex]["url"] = '';
   }

   while (!feof($file_ptr) and strpos($sString,'</DL') == false) {
      $sString = fgets($file_ptr,4096);
			if (strpos($sString,'<H3') > 0) {
			   $explode = explode('>',$sString);
			   $explode = explode('<',$explode[2]);
			   $sString = $explode[0];
	       import_folder($file_ptr,$sFolder, $sString);
			}
			if (!(strpos($sString,'<DT>') == false)) {
			   $explode = explode('HREF="', $sString);
				 $explode = explode('"', $explode[1]);
				 $sURL = $explode[0];
				 $explode = explode('>', $sString);
				 $explode = explode('<', $explode[2]);
				 $sTitle = $explode[0];
				 
         $bookindex = count($bookmarksdata) + 1;
         $bookmarksdata[$bookindex]["folder"] = $sFolder;
         $bookmarksdata[$bookindex]["title"] = $sTitle;
				 $bookmarksdata[$bookindex]["url"] = $sURL;
			}
   }
}

?>
