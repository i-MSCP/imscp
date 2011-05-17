<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/admin_createtables1.template.php begin -->

<input type="hidden" name="input_admin_username" value="<?php echo $input_admin_username; ?>">
<input type="hidden" name="input_admin_password" value="<?php echo $input_admin_password; ?>">

<?php	for ($i=0; $i<sizeof($net2ftp_output["admin_createtables"]); $i++) {
		echo $net2ftp_output["admin_createtables"][$i] . "<br />\n";
	} // end for ?>

<?php echo __("Please enter your MySQL settings:"); ?><br /><br />

<table>
	<tr><td><?php echo __("MySQL username"); ?></td><td> <input type="text"     name="dbusername2" value="<?php echo $dbusername2; ?>" /></td></tr>
	<tr><td><?php echo __("MySQL password"); ?></td><td> <input type="password" name="dbpassword2" value="<?php echo $dbpassword2; ?>" /></td></tr>
	<tr><td><?php echo __("MySQL database"); ?></td><td> <input type="text"     name="dbname2"     value="<?php echo $dbname2; ?>"     /></td></tr>
	<tr><td><?php echo __("MySQL server"); ?></td>  <td> <input type="text"     name="dbserver2"   value="<?php echo $dbserver2; ?>"   /></td></tr>
</table><br /><br />

<?php echo __("This SQL query is going to be executed:"); ?><br /><br />
<textarea name="text" class="edit" rows="10" cols="100" wrap="off">
<?php echo $sqlquerystring; ?>
</textarea><br /><br />

<!-- Template /skins/blue/admin_createtables1.template.php end -->
