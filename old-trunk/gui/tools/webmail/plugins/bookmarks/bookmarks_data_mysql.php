<?php
define('BOOKMARKS_DSN', 'mysql://username:password@servername/databasename');
$INSTALL_CHECK = 'OK';

require_once('DB.php');

$bookmarksdata = array();

function readbookmarkstext() {
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
	writebookmarksdata();
	
	asort($bookmarksdata);
  unlink($filename);
	
	$_SESSION['bookmarksdata'] = $bookmarksdata;
}

function readbookmarksdata() {
	global $bookmarksdata, $username, $data_dir;
	
	$filename = getHashedFile($username, $data_dir, $username. '.bookmark');
	if (file_exists($filename)) {
    readbookmarkstext();
	} else {
		$filename = getHashedFile($username, $data_dir, $username . '.bookmarks');
		if (file_exists($filename)){
		  readbookmarkstext();
		} else {
    	$bookmarksdata = array();
    	$n = 1;
    
    	$dbh = DB::connect(BOOKMARKS_DSN, true);
      $res = $dbh->query('SELECT * FROM bookmarks WHERE username="' . $username . '"');
    
      while ($row = $res->fetchRow(DB_FETCHMODE_ASSOC)) {
        if (($row["title"] != '') or ($row["folder"] != '') or ($row["url"] != '')) {
    	    $bookmarksdata[$n]["folder"] = $row["folder"];
    			$bookmarksdata[$n]["title"] = $row["title"];
    			$bookmarksdata[$n]["url"] = $row["url"];
    			$bookmarksdata[$n]["index"] = $n;
    			if ($row["visit_count"] == '') {
    			  $bookmarksdata[$n]["visit_count"] = 0;
    			} else {
    			  $bookmarksdata[$n]["visit_count"] = $row["visit_count"];
    			}
    
    			$bookmarksdata[$n]["last_visit"] = $row["last_visit"];
    			$n = $n + 1;
    		}
      }
		}
  }
 	asort($bookmarksdata);
	
	$_SESSION['bookmarksdata'] = $bookmarksdata;
}

function writebookmarksdata() {
	global $bookmarksdata, $username;

  $dbh = DB::connect(BOOKMARKS_DSN, true);
	
	foreach($bookmarksdata as $bookfoo) {
	  if (($bookfoo["folder"] != '') or ($bookfoo["title"] != '') or ($bookfoo["url"] != '')) {
		  $res = $dbh->query('INSERT INTO bookmarks (username,folder,title,url,visit_count,last_visit) VALUES ("' . $username . '_inserted","' . $bookfoo["folder"] . '","' . $bookfoo["title"] . '","' . $bookfoo["url"] . '","' . $bookfoo["visit_count"] . '","' . $bookfoo["last_visit"] . '")');
		}
	}
  $res = $dbh->query('DELETE FROM bookmarks WHERE username="' . $username . '"');
	$res = $dbh->query('UPDATE bookmarks SET username = "' . $username . '" WHERE username = "' . $username . '_inserted"');
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
