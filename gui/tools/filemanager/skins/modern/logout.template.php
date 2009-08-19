<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/ispCP/logout.php begin -->
<div id="container">
	<div id="head">
		<div id="headleft">
			<a href="http://www.isp-control.net" target="_blank"><?php echo printPngImage($net2ftp_globals["image_url"] . "/img/logo.png", "ispCP", "width: 193px; height: 59px; border: 0;"); ?></a>
		</div>
		<div id="headright">
		</div>
	</div>
	<div id="main">
		<p><?php echo __("You have logged out from the FTP server. To log back in, <a href=\"%1\$s\" title=\"Login page (accesskey l)\" accesskey=\"l\">follow this link</a>.", $url); ?></p><br />
		<p><?php echo __("Note: other users of this computer could click on the browser's Back button and access the FTP server."); ?></p><br />
		<p><?php echo __("To prevent this, you must close all browser windows."); ?></p><br />
		<div style="text-align: center;"><input type="button" onclick="javascript:window.close();" value="<?php echo __("Close"); ?>" title="<?php echo __("Click here to close this window"); ?>" /></div>
		<br /><br />
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/google_ad.template.php"); ?>
		<br /><br />
	</div>

<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/ispCP/logout.php end -->
