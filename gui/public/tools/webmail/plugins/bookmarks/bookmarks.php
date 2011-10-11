<?php

define('SM_PATH','../../');

include_once('bookmarks_data.php');
if ($INSTALL_CHECK == 'OK') {
  include_once('functions.php');
  if (file_exists(SM_PATH . 'include/validate.php'))
     include_once(SM_PATH . 'include/validate.php');
  else if (file_exists(SM_PATH . 'src/validate.php'))
     include_once(SM_PATH . 'src/validate.php');
  include_once(SM_PATH . 'functions/page_header.php');
  
  displayPageHeader($color, 'None');
  
  sqgetGlobalVar('passval', $passval, SQ_GET);
  if (!isset($passval)) {
  	$passval = '';
  }
  $passval = urldecode($passval);
  
  global $bookmarksdata;
  
  readbookmarksdata();
  
  $folder = $passval;
  $foldername = ParseBookmarkString( $folder, '|', 0 );
  $parent = ParseBookmarkString( $folder, '|', -1 );
  
  echo '<table width="100%" border="0"><tr>';
  echo '<td align="left"><font color="' . $color[1] . '"><a href="import.php?passval=' . urlencode($passval) . '">Import Bookmarks</a></font></td>';
  echo '<td align="right"><font color="' . $color[1] . '"><a href="bookmarks_bycount.php">Most Visits</a>&nbsp;|&nbsp;<a href="bookmarks_bydate.php">Last Visited</a></font></td>';
  echo '</tr></table>';
  echo '<TABLE BGCOLOR="' . $color[0] . '" BORDER=0 WIDTH="100%" CELLSPACING=0 CELLPADDING=2><TR><TD ALIGN=left>';
  echo '<FONT color="' . $color[1] . '">Folders in folder ' . CookieTrail( $folder, 0 ) . ':&nbsp;&nbsp;';
  if ($folder == '') {
  	echo "(top level)";
  } else {
  	displayInternalLink("plugins/bookmarks/bookmarks.php?passval=" . urlencode($parent),_("(up one level)"),"right");
  }
  echo '&nbsp;&nbsp<br>';
  echo '</font></TD><td align=right>';
  if ($folder != 'global') {
  	echo '<a href="folder_create.php?passval=' . urlencode($folder) . '">New Folder</a>';
  } else {
  	echo '&nbsp;';
  }
  echo '</TD></TR></TABLE>';
  
  $foldercount = 0;
  echo '<TABLE BORDER=0 WIDTH="100%" CELLSPACING=0 CELLPADDING=2><TR><TD ALIGN=left>';
  
  foreach ($bookmarksdata as $bookfoo) {
  	if (( $bookfoo["title"] == '' ) and ( ParseBookmarkString( $bookfoo["folder"], '|', -1 ) == $folder )) {
  		echo '&bull;&nbsp;<a href="bookmarks.php?passval=' . urlencode($bookfoo["folder"]) . '">' . ParseBookmarkString( $bookfoo["folder"], '|', 0 ) . '</a>';
  		echo '&nbsp;&nbsp;<a href="folder_delete.php?folder=' . urlencode(ParseBookmarkString( $bookfoo["folder"], '|', -1 )) . '&passval=' . urlencode($bookfoo["folder"]) .'"><font size="-2">(DELETE)</font></a>';
  		echo '</TD><TD ALIGN=left>';
  		$foldercount = $foldercount + 1;
  		if ($foldercount == 2) {
  			echo "</TD></TR><TR><TD ALIGN=left>";
  			$foldercount = 0;
  		}
  	}
  }
  echo "</TD></TR></TABLE><br><br>";
  
  echo '<table bgcolor="' . $color[0] . '" width="100%" border="0"><tr><td align="left"><font color="' . $color[1] . '">Bookmarks in folder ' . CookieTrail( $folder, 0 ) . '</font></td>';
  echo '<td align="right">';
  echo '<a href="bookmark_create.php?passval=' . urlencode($folder) . '">New Bookmark</a>';
  echo '</td></tr></table>';
  echo '<br>';
  
  foreach ($bookmarksdata as $bookfoo) {
  	if (( $bookfoo["title"] <> '' ) and ( $bookfoo["url"] <> '') and ( $bookfoo["folder"] == $folder )) {
  		echo '&bull;&nbsp;<a href="bookmark_click.php?passval=' . urlencode($bookfoo["index"]) . '" target="_blank">' . $bookfoo["title"] . '</a>';
  		echo '&nbsp;&nbsp;<a href="bookmark_edit.php?passval=' . urlencode($bookfoo["index"]) . '"><font size="-2">(EDIT)</font></a>';
  		echo '<a href="bookmark_delete.php?passval=' . urlencode($bookfoo["index"]) . '"><font size="-2">(DELETE)</font></a>';
  		echo '<br>';
  	}
  }
} else {
  echo '<p><center>Your bookmarks plugin is not properly installed!<br><br>Please refer to the instructions in the README file, located in your bookmarks plugin directory.</center></p>';
}
?>
</body></html>
