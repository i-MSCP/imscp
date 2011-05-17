<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/advanced_ftpserver1.template.php begin -->
<?php echo __("Connection settings:"); ?>
<table border="0" cellspacing="2" cellpadding="2" style="margin-<?php echo __("left"); ?>: 20px;">
	<tr>
		<td><?php echo __("FTP server"); ?></td>
		<td><input type="text" class="input" name="troubleshoot_ftpserver" value="<?php echo $net2ftp_globals["ftpserver_html"]; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo __("FTP server port"); ?></td>
		<td><input type="text" class="input" size="3" maxlength="5" name="troubleshoot_ftpserverport" value="<?php echo $net2ftp_globals["ftpserverport_html"]; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo __("Username"); ?></td>
		<td><input type="text" class="input" name="troubleshoot_username" value="<?php echo $net2ftp_globals["username_html"]; ?>" /></td>
	</tr>
	<tr>
		<td><?php echo __("Password"); ?></td>
		<td><input type="password" class="input" name="troubleshoot_password" /></td>
	</tr>
	<tr>
		<td><?php echo __("Passive mode"); ?></td>
		<td><input type="checkbox" class="input" name="troubleshoot_passivemode" value="yes"></td>
	</tr>
	<tr>
		<td><?php echo __("Directory"); ?></td>
		<td><input type="text" class="input" name="troubleshoot_directory" value="<?php echo $net2ftp_globals["directory_html"]; ?>"></td>
	</tr>
</table>
<!-- Template /skins/blue/advanced_ftpserver1.template.php end -->
