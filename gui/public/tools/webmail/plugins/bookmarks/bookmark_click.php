<?php

define('SM_PATH','../../');
include_once('bookmarks_data.php');
include_once('functions.php');
if (file_exists(SM_PATH . 'include/validate.php'))
   include_once(SM_PATH . 'include/validate.php');
else if (file_exists(SM_PATH . 'src/validate.php'))
   include_once(SM_PATH . 'src/validate.php');

sqgetGlobalVar('passval', $passval, SQ_GET);
if (!isset($passval)) sqgetGlobalVar('passval', $passval, SQ_POST);

$bookmarksdata = $_SESSION['bookmarksdata'];

$url = $bookmarksdata[$passval]["url"];

$bookmarksdata[$passval]["visit_count"] = $bookmarksdata[$passval]["visit_count"] + 1;
$bookmarksdata[$passval]["last_visit"] = time();

writebookmarksdata();
header('Location: ' . $url);

?>
