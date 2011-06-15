<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/chmod2.template.php begin -->
<?php	for ($i=0; $i<sizeof($net2ftp_output["ftp_chmod2"]); $i++) {
		if ($net2ftp_output["ftp_chmod2"][$i] == "<ul>" || $net2ftp_output["ftp_chmod2"][$i] == "</ul>") { echo $net2ftp_output["ftp_chmod2"][$i] . "\n"; }
		else { echo "<li>" . $net2ftp_output["ftp_chmod2"][$i] . "</li>\n"; }
	} // end for ?>
<!-- Template /skins/blue/chmod2.template.php end -->
