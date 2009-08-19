<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/upload1.template.php begin -->
<?php echo __("Upload to directory:"); ?> <input type="text" class="longinput" name="directory" value="<?php echo $net2ftp_globals["directory_html"]; ?>" />
<?php printActionIcon("listdirectories", "createDirectoryTreeWindow('" . $net2ftp_globals["directory_js"] . "','" . $formname . "','directory');"); ?>
<br /><br />
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td style="vertical-align: top;" style="width: 50%;">
			<div class="header31"><?php echo __("Files"); ?></div><div style="font-size: 80%;"><?php echo __("Files entered here will be transferred to the FTP server."); ?></div><br />
		</td>
		<td style="vertical-align: top;" style="width: 50%;">
			<div class="header31"><?php echo __("Archives"); ?> (zip, tar, tgz, gz)</div><div style="font-size: 80%;"><?php echo __("Archives entered here will be decompressed, and the files inside will be transferred to the FTP server."); ?></div><br />
		</td>
	</tr>
	<tr>
		<td style="vertical-align: top;" style="width: 50%;">
			<input type="file" class="uploadinputbutton" maxsize="2097152" name="file[]" onchange="add_file('file', 1);" /><br />
			<span id="file_1"><input type="button" value="<?php echo __("Add another"); ?>" onclick="add_file('file', 1);" /></span><br />
		</td>
		<td style="vertical-align: top;" style="width: 50%;">
			<input type="file" class="uploadinputbutton" maxsize="2097152" name="archive[]" onchange="add_file('archive', 1);" /><br />
			<span id="archive_1"><input type="button" value="<?php echo __("Add another"); ?>" onclick="add_file('archive', 1);" /></span><br />
			<div style="font-size: 80%;"><input type="checkbox" name="use_folder_names" value="yes" checked /> <?php echo __("Use folder names (creates subdirectories automatically)"); ?></div><br />
		</td>
	</tr>
</table>
<u><?php echo __("Restrictions:"); ?></u>
<div style="font-size: 80%">
<ul>
	<li> <?php echo __("The maximum size of one file is restricted by net2ftp to <b>%1\$s kB</b> and by PHP to <b>%2\$s</b>", $max_filesize_net2ftp, $max_upload_filesize_php); ?></li>
	<li> <?php echo __("The maximum execution time is <b>%1\$s seconds</b>", $max_execution_time); ?></li>
	<li> <?php echo __("The FTP transfer mode (ASCII or BINARY) will be automatically determined, based on the filename extension"); ?></li>
	<li> <?php echo __("If the destination file already exists, it will be overwritten"); ?></li>
</ul>
</div><br />
<!-- Template /skins/blue/upload1.template.php end -->
