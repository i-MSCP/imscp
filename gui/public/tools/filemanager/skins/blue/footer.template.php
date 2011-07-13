<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/footer.php begin -->
	<div id="foot">
		<a href="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/help.html" class="text_white"><?php echo __("Help Guide"); ?></a> | 
		<a href="javascript:go_to_forums();" class="text_white"><?php echo __("Forums"); ?></a>| 
		<a href="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/LICENSE.txt" class="text_white"><?php echo __("License"); ?></a>
	</div>
	<div id="poweredby">
		<?php echo __("Powered by"); ?> net2ftp - a web based FTP client<br />
		Add to: <a href="http://del.icio.us/post?url=http://www.net2ftp.com">Del.icio.us</a> | 
			<a href="http://digg.com/submit?phase=2&amp;url=http://www.net2ftp.com">Digg</a> | 
			<a href="http://reddit.com/submit?url=http://www.net2ftp.com&amp;title=net2ftp%20-%20a%20web%20based%20FTP%20client">Reddit</a>
	</div>
</div>
<script type="text/javascript">
	function go_to_forums() {
		alert('<?php echo __("You are now taken to the net2ftp forums. These forums are for net2ftp related topics only - not for generic webhosting questions."); ?>');
		document.location = "http://www.net2ftp.org/forums";
	} // end function forums
</script>
<!-- Template /skins/blue/footer.php end -->
