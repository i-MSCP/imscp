<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/ispCP/bookmark1.template.php begin -->

<?php if     $net2ftp_globals["browser_agent"] == "IE") { ?>
<?php 	echo __("Right-click on the link and choose \"Add to Favorites...\""); ?>
<?php } elseif $net2ftp_globals["browser_agent"] == "Safari") { ?>
<?php 	echo __("Right-click on the link and choose \"Add Link to Bookmarks...\""); ?>
<?php } elseif $net2ftp_globals["browser_agent"] == "Opera") { ?>
<?php 	echo __("Right-click on the link and choose \"Bookmark link...\""); ?>
<?php } else { ?>
<?php 	echo __("Right-click on the link and choose \"Bookmark This Link...\""); ?>
<?php } ?>
<br /><br />
<?php echo __("Link without password (a popup window will ask you for your username and password)"); ?> <a href="<?php echo $url; ?>"><?php echo $text; ?></a><br />
<?php echo __("Link with password (a popup window will ask you for your username and password)"); ?> <a href="<?php echo $url; ?>"><?php echo $text; ?></a><br />

<?php echo __("Note: when you will use this bookmark, a popup window will ask you for your username and password."); ?><br />

<!-- Template /skins/ispCP/bookmark1.template.php end -->
