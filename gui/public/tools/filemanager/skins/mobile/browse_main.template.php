<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/mobile/browse_main.template.php begin -->

<form name="BrowseForm" id="BrowseForm" action="<?php echo $net2ftp_globals["action_url"]; ?>" method="post">
<?php printLoginInfo(); ?>
<input type="hidden" name="state"  value="browse" />
<input type="hidden" name="state2" value="main"  />
<input type="hidden" name="entry"  value="" />

<div class="browse_top">
<?php echo __("Directory"); ?> <input type="text" style="width: 80%;" name="directory" value="<?php echo $directory_html; ?>" title="(accesskey g)" accesskey="g" />
<input type="submit" name="submit" value="<?php echo __("Go"); ?>" />
<br />
</div>

<?php if (isset($warning_directory) == true && $warning_directory != "") { ?>
	<div class="browse_warning">
	<?php echo $warning_directory; ?><br />
	</div>
<?php } ?>
<?php if (isset($warning_consumption) == true && $warning_consumption != "") { ?>
	<div class="browse_warning">
	<?php echo $warning_consumption; ?><br />
	</div>
<?php } ?>
<?php if (isset($warning_message) == true && $warning_message != "") { ?>
	<div class="browse_warning">
	<?php echo $warning_message; ?><br />
	</div>
<?php } ?>

<div class="browse_actions">
<select name="BrowseSelect" onchange="eval(document.BrowseForm.BrowseSelect.options[document.BrowseForm.BrowseSelect.selectedIndex].value);">
	<option value="0" selected style="font-weight: bold; text-decoration: underline;"><?php echo __("Actions"); ?>: </option>
<?php	if ($net2ftp_settings["functionuse_newdir"]         == "yes") { ?><option value="submitBrowseForm('', '', 'logout', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Logout"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_newdir"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'newdir', '');">&nbsp;&nbsp;&nbsp;<?php echo __("New dir"); ?></option><?php } // end if ?>
<?php if ($net2ftp_settings["functionuse_newfile"]        == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'edit', 'newfile');">&nbsp;&nbsp;&nbsp;<?php echo __("New file"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_upload"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'upload', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Upload"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_install"]        == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'install', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Install"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_advanced"]       == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'advanced', 'main');">&nbsp;&nbsp;&nbsp;<?php echo __("Advanced"); ?></option><?php } // end if ?>
                                                                        <option value="" style="font-weight: bold; text-decoration: underline;"><?php echo __("Transform selected entries: "); ?></option>
<?php	if ($net2ftp_settings["functionuse_copy"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'copy');">&nbsp;&nbsp;&nbsp;<?php echo __("Copy"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_move"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'move');">&nbsp;&nbsp;&nbsp;<?php echo __("Move"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_delete"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'delete');">&nbsp;&nbsp;&nbsp;<?php echo __("Delete"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_rename"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'rename', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Rename"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_chmod"]          == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'chmod', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Chmod"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_downloadzip"]    == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'downloadzip', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Download"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_zip"]            == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'zip', 'zip');">&nbsp;&nbsp;&nbsp;<?php echo __("Zip"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_calculatesize"]  == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'calculatesize', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Size"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_findstring"]     == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'findstring', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Search"); ?></option><?php } // end if ?>
                                                                        <option value="" style="font-weight: bold; text-decoration: underline;"><?php echo __("Transform selected entry: "); ?></option>
<?php	if ($net2ftp_settings["functionuse_view"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'view', '');">&nbsp;&nbsp;&nbsp;<?php echo __("View"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_edit"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'edit', '');">&nbsp;&nbsp;&nbsp;<?php echo __("Edit"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_open"]           == "yes") { ?><option value="window.open('<?php echo $list_links_js[$i]; ?>');">&nbsp;&nbsp;&nbsp;<?php echo __("Open"); ?></option><?php } // end if ?>
</select>
</div>

<div class="browse_up">
<a href="<?php echo $action_url . "&amp;directory=" . $updirectory_url . "&amp;state=browse&amp;state2=main"; ?>"><?php echo __("Up"); ?> ..</a>
</div>

<?php /* ----- Directories ----- */ ?>
<?php	if ($list["stats"]["directories"]["total_number"] > 0) { ?>
<div class="browse_row"><?php echo __("Directories"); ?> (<?php echo $list["stats"]["directories"]["total_number"]; ?>)</div>
<?php		for ($i=1; $i<=sizeof($list_directories); $i++) {
			$rowcounter++;
			if ($list_directories[$i]["selectable"] == "ok") { $href = "<a href=\"" . $action_url . "&amp;directory=" . $list_directories[$i]["newdir_url"] . "&amp;state=browse&amp;state2=main\">" . $list_directories[$i]["dirfilename"] . "</a>"; }
			else                                              { $href = $list_directories[$i]["dirfilename"]; }
			printDirFileProperties($rowcounter, $list_directories[$i], "checkbox", ""); ?>
<?php echo $href; ?><br />
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Files ----- */ ?>
<?php	if ($list["stats"]["files"]["total_number"]> 0) { ?>
<div class="browse_row"><?php echo __("Files"); ?> (<?php echo $list["stats"]["files"]["total_number"]; ?> / <?php echo $list["stats"]["files"]["total_size_formated"]; ?>)</div>
<?php		for ($i=1; $i<=sizeof($list_files); $i++) {
			$rowcounter++;
			if ($list_files[$i]["selectable"] == "ok") { $href = "<a href=\"" . $action_url . "&amp;directory=" . $directory_url . "&amp;entry=" . $list_files[$i]["dirfilename_url"] . "&amp;state=downloadfile\">" . $list_files[$i]["dirfilename"] . "</a>"; }
			else                                        { $href = $list_files[$i]["dirfilename"]; }
			printDirFileProperties($rowcounter, $list_files[$i], "checkbox", ""); ?>
<?php echo $href; ?><br />
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Symlinks ----- */ ?>
<?php	if ($list["stats"]["symlinks"]["total_number"]> 0) { ?>
<div class="browse_row"><?php echo __("Symlinks"); ?> (<?php echo $list["stats"]["symlinks"]["total_number"]; ?> / <?php echo $list["stats"]["symlinks"]["total_size_formated"]; ?>)</div>
<?php		for ($i=1; $i<=sizeof($list_symlinks); $i++) {
			$rowcounter++;
			if ($list_symlinks[$i]["selectable"] == "ok") { $href = "<a href=\"" . $action_url . "&amp;directory=" . $directory_url . "&amp;entry=" . $list_symlinks[$i]["dirfilename_url"] . "&amp;state=followsymlink&amp;state2=main\">" . $list_symlinks[$i]["dirfilename"] . "</a>"; }
			else                                           { $href = $list_symlinks[$i]["dirfilename"]; }
			printDirFileProperties($rowcounter, $list_symlinks[$i], "checkbox", ""); ?>
<?php echo $href; ?><br />
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Unrecognized ----- */ ?>
<?php	if ($list["stats"]["unrecognized"]["total_number"] > 0) { ?>
<div class="browse_row"><?php echo __("Unrecognized FTP output"); ?> (<?php echo $list["stats"]["unrecognized"]["total_number"]; ?>)</div>
<?php		for ($i=1; $i<=sizeof($list_unrecognized); $i++) { ?>
<?php			$rowcounter++; ?>
<?php 		echo $list_unrecognized[$i]["dirfilename"]; ?><br />
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Empty folder ----- */ ?>
<?php	if ($rowcounter == 0) { ?>
<br /><?php echo __("This folder is empty"); ?><br /><br />
<?php	} // end if ?>

</form>
<br />
<div style="text-align: center;"><?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/google_ad.template.php"); ?></div>
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/mobile/browse_main.template.php end -->
