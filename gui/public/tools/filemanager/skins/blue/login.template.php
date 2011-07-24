<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/login.template.php begin -->
<?php 
if ($net2ftp_settings["net2ftpdotcom"] == "yes") {
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/login_n2fcom.template.php"); 
}
else {
	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/login_other.template.php"); 
}
?>
<!-- Template /skins/blue/login.template.php end -->
