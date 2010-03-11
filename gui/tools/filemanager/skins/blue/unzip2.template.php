<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/unzip2.template.php begin -->
<?php	for ($i=0; $i<sizeof($net2ftp_output["unzip"]); $i++) {
		if ($net2ftp_output["unzip"][$i] == "<ul>" || $net2ftp_output["unzip"][$i] == "</ul>") { echo $net2ftp_output["unzip"][$i] . "\n"; }
		else { echo "<li>" . $net2ftp_output["unzip"][$i] . "</li>\n"; }
	} // end for ?>
<!-- Template /skins/blue/unzip2.template.php end -->
