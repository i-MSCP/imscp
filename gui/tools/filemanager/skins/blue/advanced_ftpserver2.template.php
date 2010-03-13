<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/advanced_ftpserver2.template.php begin -->

<table border="0" cellspacing="2" cellpadding="2">
	<tr>
		<td><?php echo __("FTP server"); ?></td>
		<td><?php echo $troubleshoot_ftpserver_html; ?></td>
	</tr>
	<tr>
		<td><?php echo __("FTP server port"); ?></td>
		<td><?php echo $troubleshoot_ftpserverport_html; ?></td>
	</tr>
	<tr>
		<td><?php echo __("Username"); ?></td>
		<td><?php echo $troubleshoot_username_html; ?></td>
	</tr>
	<tr>
		<td><?php echo __("Password length"); ?></td>
		<td><?php echo strlen($troubleshoot_password); ?></td>
	</tr>
	<tr>
		<td><?php echo __("Passive mode"); ?></td>
		<td><?php echo $troubleshoot_passivemode_html; ?></td>
	</tr>
	<tr>
		<td><?php echo __("Directory"); ?></td>
		<td><?php echo $troubleshoot_directory_html; ?></td>
	</tr>
</table>

<ul>
<li> 
<?php echo __("Connecting to the FTP server: "); ?>
<?php if ($conn_id == true) { ?>
	<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php } else { ?>
	<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php } // end if else ?>
</li>

<li>
	<?php echo __("Logging into the FTP server: "); ?>
<?php if ($ftp_login_result == true) { ?>
	<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php } else { ?>
	<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php } // end if else ?>
</li>

<li>
	<?php echo __("Setting the passive mode: "); ?>
<?php if ($ftp_pasv_result == true) { ?>
	<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php } else { ?>
	<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php } // end if else ?>
</li>

<li>
	<?php echo __("Getting the FTP server system type: "); ?>
<?php if ($ftp_systype_result != false) { ?>
	<span style="color: green; font-weight: bold;"><?php echo $ftp_systype_result; ?></span>
<?php } else { ?>
	<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php } // end if else ?>
</li>

<li>
	<?php echo __("Changing to the directory %1\$s: ", $troubleshoot_directory); ?>
<?php if ($ftp_chdir_result == true) { ?>
	<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php } else { ?>
	<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php } // end if else ?>
</li>

<li>
	<?php echo __("Getting the raw list of directories and files: "); ?><br />
	<?php print_r($ftp_rawlist_result); ?>
</li>

<li>
	<?php echo __("Getting the raw list of directories and files: "); ?><br />
	<?php for($i=0; $i<count($parsedlist); $i++) {
			echo "<u>Line $i</u><br />\n";
			print_r($parsedlist[$i]);
			echo "<br />";
		} // End for 
	?>
</li>
</ul>

<!-- Template /skins/blue/advanced_ftpserver2.template.php end -->
