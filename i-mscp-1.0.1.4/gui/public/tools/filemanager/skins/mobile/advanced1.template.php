<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/advanced1.template.php begin -->

<div class="header31"><?php echo __("Advanced FTP functions"); ?></div><br />

<?php	if (function_exists("ftp_raw") == true) { ?>
	<input type="button" class="smallbutton" value="<?php echo __("Go"); ?>" onclick="document.forms['AdvancedForm'].state.value='raw';   document.forms['AdvancedForm'].submit();" />
<?php } else { ?>
	<input type="button" class="smallbutton" value="<?php echo __("Disabled"); ?>" disabled title="<?php echo __("This function is available on PHP 5 only"); ?>" />
<?php } ?>
<?php echo __("Send arbitrary FTP commands to the FTP server"); ?><br /><br />

<div class="header31"><?php echo __("Troubleshooting functions"); ?></div><br />
<input type="button" class="smallbutton" value="<?php echo __("Go"); ?>" onclick="document.forms['AdvancedForm'].state.value='advanced_webserver'; document.forms['AdvancedForm'].submit();" /> <?php echo __("Troubleshoot net2ftp on this webserver"); ?><br /><br />
<input type="button" class="smallbutton" value="<?php echo __("Go"); ?>" onclick="document.forms['AdvancedForm'].state.value='advanced_ftpserver'; document.forms['AdvancedForm'].submit();" /> <?php echo __("Troubleshoot an FTP server"); ?><br /><br />
<input type="button" class="smallbutton" value="<?php echo __("Go"); ?>" onclick="document.forms['AdvancedForm'].state.value='advanced_parsing';   document.forms['AdvancedForm'].submit();" /> <?php echo __("Test the net2ftp list parsing rules"); ?><br /><br />

<!-- Template /skins/blue/advanced1.template.php end -->
