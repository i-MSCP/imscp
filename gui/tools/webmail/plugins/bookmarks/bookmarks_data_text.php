<?php
$INSTALL_CHECK = 'OK';

$bookmarksdata = array();

function readbookmarksdata() {
	global $bookmarksdata, $username, $data_dir;
	
	$bookmarksdata = array();
	$n = 1;

	$filename = getHashedFile($username, $data_dir, $username. '.bookmark');
	if (file_exists($filename)) {
		ConvertBookmarks( '', '' );
		writebookmarksdata();
	} else {
		$filename = getHashedFile($username, $data_dir, $username . '.bookmarks');

		if (file_exists($filename)){
			$fp = fopen ($filename,'r');
	
	        	if ($fp){
				while ($fdata = fgetcsv ($fp, 4096, chr(9))) {
				  if (($fdata[0] != '') or ($fdata[1] != '') or ($fdata[2] != '')) {
					  $bookmarksdata[$n]["folder"] = $fdata[0];
					  $bookmarksdata[$n]["title"] = $fdata[1];
					  $bookmarksdata[$n]["url"] = $fdata[2];
					  $bookmarksdata[$n]["index"] = $n;
						if ($fdata[3] == '') {
						  $bookmarksdata[$n]["visit_count"] = 0;
						} else {
						  $bookmarksdata[$n]["visit_count"] = $fdata[3];
						}

						$bookmarksdata[$n]["last_visit"] = $fdata[4];
					  $n = $n + 1;
					}
				}
				fclose ($fp);
			}
		}
	}
	asort($bookmarksdata);

	$_SESSION['bookmarksdata'] = $bookmarksdata;
}

function writebookmarksdata() {
	global $bookmarksdata, $username, $data_dir;

	$filename = getHashedFile($username, $data_dir, $username. '.bookmarks');
	$fp = fopen ($filename . '.tmp',"w");
	if ($fp) {
		foreach($bookmarksdata as $bookfoo) {
		  if (($bookfoo["folder"] != '') or ($bookfoo["title"] != '') or ($bookfoo["url"] != '')) {
			  $bookstring = $bookfoo["folder"] . chr(9) . $bookfoo["title"] . chr(9) . $bookfoo["url"] . chr(9) . $bookfoo["visit_count"] . chr(9) . $bookfoo["last_visit"] . "\n";
			  fwrite($fp, $bookstring );
			}
		}
	}

	fclose ($fp);
	if (file_exists($filename)){
		unlink($filename);
	}
	rename($filename . '.tmp',$filename);
}

function ConvertBookmarks( $sFilename, $sParent ) {
	global $bookmarksdata, $username, $data_dir, $n;

	if ($sFilename == '') {
		$foldertmp = '';
	} else {
		$foldertmp = $sFilename . '.';
	}
	$filename = getHashedFile($username, $data_dir, $username. '.' . $foldertmp . 'bookmark');

	if ($sParent == '') {
		$sDelim = '';
	} else {
		$sDelim = '|';
	}

	if (file_exists($filename)){
		$fp = fopen ($filename,'r');
		if ($fp){
			if ($sFilename <> '') {
				$bookmarksdata[$n]["folder"] = $sParent . $sDelim . $sFilename;
				$n = $n + 1;
			}
			while ($fdata = fgetcsv ($fp, 4096, '|')) {
				if (substr($fdata[0],0,6) == "folder") {
					ConvertBookmarks( $fdata[1], $sParent . $sDelim . $sFilename );
				} else {
					if ( $fdata[0] <> 'parent') {
						$bookmarksdata[$n]["folder"] = $sParent . $sDelim . $sFilename;
						$bookmarksdata[$n]["title"] = $fdata[1];
						$bookmarksdata[$n]["url"] = $fdata[0];
						$bookmarksdata[$n]["index"] = $n;
						$bookmarksdata[$n]["visit_count"] = 0;
						$bookmarksdata[$n]["last_visit"] = '';
						$n = $n + 1;
					}
				}
			}
		}
		fclose ($fp);
		unlink($filename);
	}
}
?>
