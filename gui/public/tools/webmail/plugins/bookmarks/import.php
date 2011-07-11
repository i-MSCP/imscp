<?php

define('SM_PATH','../../');
include_once('functions.php');
if (file_exists(SM_PATH . 'include/validate.php'))
   include_once(SM_PATH . 'include/validate.php');
else if (file_exists(SM_PATH . 'src/validate.php'))
   include_once(SM_PATH . 'src/validate.php');

sqgetGlobalVar('passval', $passval, SQ_GET);
if (!isset($passval)) sqgetGlobalVar('passval', $passval, SQ_POST);

echo 'You may import any list of bookmarks that is saved in the HTML document format used by Internet Explorer and Netscape.  Most browsers use this file format.<br><br>';
echo '<form enctype="multipart/form-data" action="import_process.php" method="post">';
echo '<input type="hidden" name="target_folder" value="' . urldecode($passval) . '">';
echo 'Select bookmarks file to import: <input name="bookmarks" type="file">';
echo '<input type="submit" value="Import">';
echo '</form>';

?>
