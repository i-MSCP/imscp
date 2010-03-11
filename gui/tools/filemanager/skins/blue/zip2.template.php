<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/zip2.template.php begin -->
<?php for ($i=0; $i<sizeof($net2ftp_output["ftp_zip"]); $i++) { ?>
<?php		echo $net2ftp_output["ftp_zip"][$i]; ?><br />
<?php } // end for ?>
<!-- Template /skins/blue/zip2.template.php end -->
