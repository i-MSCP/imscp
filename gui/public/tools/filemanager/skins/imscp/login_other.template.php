<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/login_other.template.php begin -->
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/header_login.template.php"); ?>
	<div id="main">
		<div id="mainleft">
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/loginform.template.php"); ?>
		</div>
		<div id="mainright">
			<div id="tabelfeaturestop"><?php echo __("Once you are logged in, you will be able to:"); ?></div>
			<div id="tablefeature">
				<span><?php echo __("Navigate the FTP server"); ?></span>
				<p><?php echo __("Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."); ?></p>
				<span><?php echo __("Upload files"); ?></span>
				<p><?php echo __("There are 3 different ways to upload files: the standard upload form, the upload-and-unzip functionality, and the Java Applet."); ?></p>
				<span><?php echo __("Navigate the FTP server"); ?></span>
				<p><?php echo __("Once you have logged in, you can browse from directory to directory and see all the subdirectories and files."); ?></p>
				<span><?php echo __("Download files"); ?></span>
				<p><?php echo __("Click on a filename to quickly download one file.<br />Select multiple files and click on Download; the selected files will be downloaded in a zip archive."); ?></p>
				<span><?php echo __("Zip files"); ?></span>
				<p><?php echo __("... and save the zip archive on the FTP server, or email it to someone."); ?></p>
				<span><?php echo __("Unzip files"); ?></span>
				<p><?php echo __("Different formats are supported: .zip, .tar, .tgz and .gz."); ?></p>
				<span><?php echo __("Install software"); ?></span>
				<p><?php echo __("Choose from a list of popular applications (PHP required)."); ?></p>
				<span><?php echo __("Copy, move and delete"); ?></span>
				<p><?php echo __("Directories are handled recursively, meaning that their content (subdirectories and files) will also be copied, moved or deleted."); ?></p>
				<span><?php echo __("Copy or move to a 2nd FTP server"); ?></span>
				<p><?php echo __("Handy to import files to your FTP server, or to export files from your FTP server to another FTP server."); ?></p>
				<span><?php echo __("Rename and chmod"); ?></span>
				<p><?php echo __("Chmod handles directories recursively."); ?></p>
				<span><?php echo __("View code with syntax highlighting"); ?></span>
				<p><?php echo __("PHP functions are linked to the documentation on php.net."); ?></p>
				<span><?php echo __("Plain text editor"); ?></span>
				<p><?php echo __("Edit text right from your browser; every time you save the changes the new file is transferred to the FTP server."); ?></p>
				<span><?php echo __("HTML editors"); ?></span>
				<p><?php echo __("Edit HTML a What-You-See-Is-What-You-Get (WYSIWYG) form; there are 2 different editors to choose from."); ?></p>
				<span><?php echo __("Code editor"); ?></span>
				<p><?php echo __("Edit HTML and PHP in an editor with syntax highlighting."); ?></p>
				<span><?php echo __("Search for words or phrases"); ?></span>
				<p><?php echo __("Filter out files based on the filename, last modification time and filesize."); ?></p>
				<span><?php echo __("Calculate size"); ?></span>
				<p><?php echo __("Calculate the size of directories and files."); ?></p>
			</div>
		</div>
		<div style="clear: both"></div>
	</div>
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/blue/login_other.template.php end -->
