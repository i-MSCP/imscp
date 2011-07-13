<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/footer_versioncheck.template.php begin -->
<table border="0" cellspacing="0" cellpadding="0" style="margin-left: auto; margin-right: auto; margin-top: 20px;">
	<tr>
		<td style="text-align: center;">
			<a href="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/help.html"><?php echo __("Help Guide"); ?></a> | <a href="javascript:go_to_forums();"><?php echo __("Forums"); ?></a> | <a href="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/LICENSE.txt"><?php echo __("License"); ?></a><br /><br />			
			
			<?php echo __("Powered by"); ?> <a href="http://www.net2ftp.com">net2ftp</a> - a web based FTP client<br /><br />
			
			<script type="text/javascript">
			<!--
			var current_build = <?php echo $net2ftp_settings["application_build_nr"]; ?>;
			if (typeof(latest_stable_build)!="undefined" && typeof(latest_beta_build)!="undefined") {
				if (latest_stable_build > current_build) {
					document.write('There is a <a href="' + latest_stable_url + '"> new stable version<\/a> of net2ftp available for download (' + latest_stable_version + ').<br /><br />');
				}
				else if (latest_beta_build > current_build) {
					document.write('There is a <a href="' + latest_beta_url + '">new beta version<\/a> of net2ftp available for download (' + latest_beta_version + ').<br /><br />');
				}
				else {
					document.write('This version of net2ftp is up-to-date.<br /><br />');
				}
			}
			//-->
			</script>
			
			<a href="http://www.spreadfirefox.com/?q=affiliates&amp;id=54600&amp;t=82"><img border="0" alt="Get Firefox!" title="Get Firefox!" src="http://sfx-images.mozilla.org/affiliates/Buttons/80x15/white_1.gif" /></a><br /><br />
		</td>
	</tr>
</table>
<!-- net2ftp version <?php echo $net2ftp_settings["application_version"]; ?> -->
<!-- Template /skins/blue/footer_versioncheck.template.php end -->
