<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/newdir2.template.php begin -->
<?php for ($i=1; $i<sizeof($net2ftp_output); $i++) { ?>
<?php		echo $net2ftp_output[$i]["message"]; ?><br />
<?php } // end for ?>
<!-- Template /skins/blue/newdir2.template.php end -->
