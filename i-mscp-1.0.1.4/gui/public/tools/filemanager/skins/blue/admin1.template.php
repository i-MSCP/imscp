<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/admin1.template.php begin -->

<input type="hidden" name="input_admin_username" value="<?php echo $input_admin_username; ?>" />
<input type="hidden" name="input_admin_password" value="<?php echo $input_admin_password; ?>" />

<h2><?php echo __("Version information"); ?></h2><br />
<div style="border: 1px solid black; background-color: #DDDDDD; margin-top: 10px; margin-<?php echo __("left"); ?>: 100px; margin-<?php echo __("right"); ?>: 100px; padding: 10px;">
<table border="0" cellspacing="2" cellpadding="2"><tr><td>
<script type="text/javascript"><!--
var current_build = <?php echo $application_build_nr; ?>;
if (typeof(latest_stable_build)!="undefined" && typeof(latest_beta_build)!="undefined") {
	if (latest_stable_build > current_build) {
		document.write('There is a <a href="' + latest_stable_url + '"> new stable version<\/a> of net2ftp available for download (' + latest_stable_version + ').<br \/>');
	}
	else if (latest_beta_build > current_build) {
		document.write('There is a <a href="' + latest_beta_url + '">new beta version<\/a> of net2ftp available for download (' + latest_beta_version + ').<br \/>');
	}
	else {
		document.write('<?php echo __("This version of net2ftp is up-to-date."); ?><br />');
	}
}
else {
		document.write('<?php echo __("The latest version information could not be retrieved from the net2ftp.com server. Check the security settings of your browser, which may prevent the loading of a small file from the net2ftp.com server."); ?><br />');
}
//--></script>
</td></tr></table>
</div><br /><br />
<h2><?php echo __("Logging"); ?></h2><br />
<?php echo __("Date from:"); ?> <input type="text" name="datefrom" value="<?php echo $datefrom; ?>" />  <?php echo __("to:"); ?> <input type="text" name="dateto" value="<?php echo $dateto; ?>" />
<input type="button" class="button" value="<?php echo __("View logs"); ?>" onclick="document.forms['AdminForm'].state.value='admin_viewlogs'; document.forms['AdminForm'].submit();" /> &nbsp; 
<input type="button" class="button" value="<?php echo __("Empty logs"); ?>" onclick="document.forms['AdminForm'].state.value='admin_emptylogs'; document.forms['AdminForm'].submit();" /><br />
<br /><br />
<h2><?php echo __("Setup MySQL tables"); ?></h2><br />
<input type="button" class="smallbutton" value="<?php echo __("Go"); ?>" onclick="document.forms['AdminForm'].state.value='admin_createtables'; document.forms['AdminForm'].submit();" /> <?php echo __("Create the MySQL database tables"); ?><br /><br />

<!-- Template /skins/blue/admin1.template.php end -->
