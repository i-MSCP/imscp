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
sqgetGlobalVar('passval', $passval, SQ_GET);
if (!isset($passval)) sqgetGlobalVar('passval', $passval, SQ_POST);
sqgetGlobalVar('confirmed', $confirmed, SQ_GET);
if (!isset($confirmed)) sqgetGlobalVar('confirmed', $confirmed, SQ_POST);

$passval = urldecode($passval);

$bookmarksdata = $_SESSION['bookmarksdata'];

if ($confirmed == '') {
	$url = $bookmarksdata[$passval]["url"];
	$title = $bookmarksdata[$passval]["title"];
	$folder = $bookmarksdata[$passval]["folder"];
}


if (isset($passval)){
	if (isset($confirmed)){

		unset($bookmarksdata[$passval]);
		writebookmarksdata();
		readbookmarksdata();

		echo '<br><br>Bookmark deleted!<BR>';
		echo '<A HREF="bookmarks.php?folder=' . $passval . '">Return to previous folder</A>';
	} else {
		echo '<br><br>Delete this bookmark?';
		echo '<br><br>' . $bookmarksdata[$passval]["title"] . '<br>';
		echo '(<a href="' . $bookmarksdata[$passval]["url"] . '" target="_blank">' . $bookmarksdata[$passval]["url"] . '</a>)<br><br>';
		echo '<form method=POST action="bookmark_delete.php">';
		echo '<input type="hidden" name="passval" value="' . $passval . '">';
		echo '<input type="hidden" name="confirmed" value="yes">';
		echo '<input type="submit" value="Yes">';
		echo '</form>';
		echo '<form method=POST action="bookmarks.php">';
		echo '<input type="hidden" name="passval" value="' . $bookmarksdata[$passval]["folder"] . '">';
		echo '<input type="submit" value="No">';
		echo '</form>';
	}
} else {
	echo '<br>Nothing to delete!';
}
?>
</body></html>
