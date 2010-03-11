<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/raw1.template.php begin -->
<?php echo __("List of commands:"); ?> <br />
<textarea name="command" rows="5" cols="100" wrap="off">
<?php echo $command; ?>
</textarea>

<br /><br />

<?php echo __("FTP server response:"); ?> <br />
<textarea name="text" rows="10" cols="100" wrap="off">
<?php for ($i=0; $i<sizeof($net2ftp_output["ftp_raw"]); $i++) { ?>
<?php		echo $net2ftp_output["ftp_raw"][$i]; ?>
<?php } // end for ?>
</textarea>
<!-- Template /skins/blue/raw1.template.php end -->
