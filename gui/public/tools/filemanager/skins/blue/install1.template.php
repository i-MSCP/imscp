<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/install1.template.php begin -->

<?php echo __("The net2ftp installer script has been copied to the FTP server."); ?><br />
<?php echo __("This script runs on your web server and requires PHP to be installed."); ?><br /><br />

<?php echo __("In order to run it, click on the link below."); ?><br />
<a href="<?php echo $net2ftp_installer_url; ?>" target="_blank"><?php echo $net2ftp_installer_url; ?></a><br /><br />

<div style="font-size: 90%; margin-left: 20px;">
<?php echo __("net2ftp has tried to determine the directory mapping between the FTP server and the web server."); ?><br />
<?php echo __("Should this link not be correct, enter the URL manually in your web browser."); ?><br />
</div>

<!-- Template /skins/blue/install1.template.php end -->
