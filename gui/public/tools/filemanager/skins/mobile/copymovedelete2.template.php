<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/copymovedelete2.template.php begin -->
<?php	for ($i=0; $i<sizeof($net2ftp_output["ftp_copymovedelete"]); $i++) {
		if ($net2ftp_output["ftp_copymovedelete"][$i] == "<ul>" || $net2ftp_output["ftp_copymovedelete"][$i] == "</ul>") { echo $net2ftp_output["ftp_copymovedelete"][$i] . "\n"; }
		else { echo "<li>" . $net2ftp_output["ftp_copymovedelete"][$i] . "</li>\n"; }
	} // end for ?>
<!-- Template /skins/blue/copymovedelete2.template.php end -->

