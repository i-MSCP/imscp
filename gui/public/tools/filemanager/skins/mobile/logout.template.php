<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/mobile/logout.php begin -->
<h2>net2ftp - web based FTP</h2>
<div style="border: 1px solid black; background-color: #DDDDDD; margin-top: 5px; margin-<?php echo __("left"); ?>: 5px; margin-<?php echo __("right"); ?>: 5px; padding: 5px;">
<?php echo __("You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.", $url); ?><br /><br />
</div>
<br /><br />
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/google_ad.template.php"); ?>
<br /><br />
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/mobile/logout.php end -->
