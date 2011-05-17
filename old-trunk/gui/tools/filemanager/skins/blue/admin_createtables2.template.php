<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/admin_createtables2.template.php begin -->

<input type="hidden" name="input_admin_username" value="<?php echo $input_admin_username; ?>" />
<input type="hidden" name="input_admin_password" value="<?php echo $input_admin_password; ?>" />
<input type="hidden" name="dbusername2" value="<?php echo $dbusername2_html; ?>" />
<input type="hidden" name="dbpassword2" value="<?php echo $dbpassword2_html; ?>" />
<input type="hidden" name="dbname2"     value="<?php echo $dbname2_html; ?>" />
<input type="hidden" name="dbserver2"   value="<?php echo $dbserver2_html; ?>" />

<div class="header31"><?php echo __("Settings used:"); ?></div>
<?php echo __("MySQL username"); ?>: <?php echo $dbusername2; ?><br />
<?php echo __("MySQL password length"); ?>: <?php echo $dbpassword2_length; ?><br />
<?php echo __("MySQL database"); ?>: <?php echo $dbname2; ?><br />
<?php echo __("MySQL server"); ?>: <?php echo $dbserver2; ?><br /><br />

<div class="header31"><?php echo __("Results:"); ?></div>
<?php	for ($i=0; $i<sizeof($net2ftp_output["admin_createtables"]); $i++) {
		echo $net2ftp_output["admin_createtables"][$i] . "<br />\n";
	} // end for ?>
<!-- Template /skins/blue/admin_createtables2.template.php end -->
