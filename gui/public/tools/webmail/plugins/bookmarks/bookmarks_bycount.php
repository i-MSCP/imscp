<?php

define('SM_PATH','../../');

include_once('bookmarks_data.php');
include_once('functions.php');
if (file_exists(SM_PATH . 'include/validate.php'))
   include_once(SM_PATH . 'include/validate.php');
else if (file_exists(SM_PATH . 'src/validate.php'))
   include_once(SM_PATH . 'src/validate.php');
include_once(SM_PATH . 'functions/page_header.php');

displayPageHeader($color, 'None');

global $bookmarksdata;

readbookmarksdata();
$bycount = array();

$n = 1;
foreach ($bookmarksdata as $bookfoo) {
  if (($bookfoo["url"] != '') and ($bookfoo["visit_count"] > 0)) {
    $bycount[$n]["visit_count"] = $bookfoo["visit_count"];
    $bycount[$n]["title"] = $bookfoo["title"];
  	$bycount[$n]["url"] = $bookfoo["url"];
  	$bycount[$n]["index"] = $bookfoo["index"];
  	$n = $n + 1;
	}
}
arsort($bycount);
	
echo '<table width="100%" border="0"><tr>';
echo '<td align="left"><font color="' . $color[1] . '"><a href="bookmarks.php">Return To Folder View</a></font></td>';
echo '<td align="right"><font color="' . $color[1] . '"><a href="bookmarks_bydate.php">Last Visited</a></font></td>';
echo '</tr></table>';
echo '<table bgcolor="' . $color[0] . '" width="100%" border="0"><tr><td align="left"><font color="' . $color[1] . '">Bookmarks Visited Most Often</font></td></tr></table>';
echo '<br>';

foreach ($bycount as $bookfoo) {
  echo '&bull;&nbsp;(';
	echo $bookfoo["visit_count"];
	echo ')&nbsp;<a href="bookmark_click.php?passval=' . urlencode($bookfoo["index"]) . '" target="_blank">' . $bookfoo["title"] . '</a>';
	echo '<br>';
}
?>
</body></html>
