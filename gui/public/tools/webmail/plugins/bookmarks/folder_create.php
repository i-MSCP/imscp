<?php

define('SM_PATH','../../');

include_once('bookmarks_data.php');
include_once('functions.php');
if (file_exists(SM_PATH . 'include/validate.php'))
   include_once(SM_PATH . 'include/validate.php');
else if (file_exists(SM_PATH . 'src/validate.php'))
   include_once(SM_PATH . 'src/validate.php');
include_once(SM_PATH . 'functions/page_header.php');

sqgetGlobalVar('passval', $passval, SQ_GET);
if (!isset($passval)) sqgetGlobalVar('passval', $passval, SQ_POST);
sqgetGlobalVar('parent', $parent, SQ_GET);
if (!isset($parent)) sqgetGlobalVar('parent', $parent, SQ_POST);
sqgetGlobalVar('folder', $folder, SQ_GET);
if (!isset($folder)) sqgetGlobalVar('folder', $folder, SQ_POST);

$passval = urldecode($passval);

if (!isset($folder)) {
	displayPageHeader($color, 'None');
	echo '<br><br>';
	echo '<form method="POST" action="folder_create.php">';
	echo '<input type="hidden" name="parent" value="' . urlencode($passval) . '">';
	echo 'Name of Folder:<br>';
	echo '<input type="text" name="folder" value="">';
	echo '<br><br>';
	echo '<input type="submit" value="Save">';
	echo '</form>';
	echo '</body></html>';
} else {
  $parent = urldecode($parent);
  $folder = urldecode($folder);

	$bookmarksdata = $_SESSION['bookmarksdata'];
	$bookindex = count($bookmarksdata) + 1;
	if ($parent == '') {
		$bookmarksdata[$bookindex]["folder"] = $folder;
	} else {
		$bookmarksdata[$bookindex]["folder"] = $parent . '|' . $folder;
	}

	writebookmarksdata();
	header('Location: bookmarks.php?passval=' . urlencode($parent) );
}
?>