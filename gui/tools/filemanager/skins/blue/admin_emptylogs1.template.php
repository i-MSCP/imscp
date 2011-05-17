<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/admin_emptylogs2.template.php begin -->

<input type="hidden" name="input_admin_username" value="<?php echo $input_admin_username; ?>" />
<input type="hidden" name="input_admin_password" value="<?php echo $input_admin_password; ?>" />

<?php	for ($i=0; $i<sizeof($net2ftp_output["admin_emptylogs"]); $i++) {
		echo $net2ftp_output["admin_emptylogs"][$i] . "<br />\n";
	} // end for ?>

<!-- Template /skins/blue/admin_emptylogs2.template.php end -->
