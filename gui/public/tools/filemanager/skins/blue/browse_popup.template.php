<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/browse_popup.template.php begin -->
<form id="DirectoryTreeForm"  action="<?php echo $net2ftp_globals["action_url"]; ?>" method="post">
<?php printLoginInfo(); ?>
<input type="hidden" name="state"  value="browse" />
<input type="hidden" name="state2" value="popup" />
<input type="hidden" name="entry"  value="" />
<input type="hidden" name="updirectory" value="<?php echo $updirectory_html; ?>" />
<input type="hidden" name="FormAndFieldName" value="<?php echo $FormAndFieldName; ?>" />
<input type="text" name="directory" value="<?php echo $directory_html; ?>" style="width: 65%;" /> 
<input type="button" class="smallbutton" value="<?php echo __("Choose"); ?>" onclick="opener.document.<?php echo $FormAndFieldName; ?>.value=document.forms['DirectoryTreeForm'].directory.value; self.close();" /><br /><br />
<div style="font-size: 90%;"><?php echo __("Double-click to go to a subdirectory:"); ?></div><br />
<select name="DirectoryTreeSelect" id="DirectoryTreeSelect" size="15" style="width: 100%;" onDblClick="submitDirectoryTreeForm(<?php echo sizeof($list["directories"]); ?>);">
<option value="up" selected><?php echo __("Up"); ?></option>
<?php /* ----- Directories ----- */ ?>
<?php	for ($i=1; $i<=sizeof($list["directories"]); $i++) {
		if ($list_directories[$i]["selectable"] == "ok") { ?>
		<option value="<?php echo $list_directories[$i]["dirfilename_html"]; ?>"><?php echo $list_directories[$i]["dirfilename_html"]; ?></option>
<?php		} // end if
	} // end for ?>
<?php /* ----- Symlinks ----- */ ?>
<?php	for ($i=1; $i<=sizeof($list["symlinks"]); $i++) {
		if ($list_symlinks[$i]["selectable"] == "ok") { ?>
		<option value="<?php echo $list_symlinks[$i]["dirfilename_html"]; ?>"><?php echo $list_symlinks[$i]["dirfilename_html"]; ?></option>
<?php		} // end if
	} // end for ?>
</select>
</form>
<br />
<!-- Template /skins/blue/browse_popup.template.php end -->
