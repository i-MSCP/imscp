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
sqgetGlobalVar('folder', $folder, SQ_GET);
if (!isset($folder)) sqgetGlobalVar('folder', $folder, SQ_POST);

$passval = urldecode($passval);
$folder = urldecode($folder);

$bookmarksdata = $_SESSION['bookmarksdata'];
if (isset($passval)){
	if (isset($confirmed)){

		foreach ($bookmarksdata as $bookfoo) {
			if ($bookfoo["folder"] == $passval) {
				unset($bookmarksdata[$bookfoo["index"]]);
			}
		}

		writebookmarksdata();
		echo '<br><br>Folder deleted!<BR><A HREF="bookmarks.php?passval=' . urlencode($folder) . '">Return to previous folder</A>';
	} else {
		$containsfolders = false;
		foreach ($bookmarksdata as $bookfoo) {
			if ((ParseBookmarkString( $bookfoo["folder"], '|', -1 ) == $passval) and ($bookfoo["url"] == '')) {
				$containsfolders = true;
			}
		}

		if ($containsfolders == false) {
			echo '<br><br>Delete this folder?';
			echo '<br><br>' . $passval . '<br>';
			echo '<form method=POST action="folder_delete.php">';
			echo '<input type="hidden" name="folder" value="' . $folder . '">';
			echo '<input type="hidden" name="passval" value="' . $passval . '">';
			echo '<input type="hidden" name="confirmed" value="yes">';
			echo '<input type="submit" value="Yes">';
			echo '</form>';
			echo '<form method=POST action="bookmarks.php">';
			echo '<input type="hidden" name="passval" value="' . $folder . '">';
			echo '<input type="submit" value="No">';
			echo '</form>';
		} else {
			echo '<br><br>This folder contains subfolders and cannot be deleted.';
			echo '<br><br>Please delete the subfolders and try again.';
			echo '<br><form method=POST action="bookmarks.php">';
			echo '<input type="hidden" name="passval" value="' . $folder . '">';
			echo '<input type="submit" value="Ok">';
			echo '</form>';
		}
	}
} else {
	echo '<br>Nothing to delete!';
}
?>
</body></html>
