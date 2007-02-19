<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/omega/browse_main_details.template.php begin -->

<script type="text/javascript"><!--\n";	
function setColor_js(i, checkbox_hidden) {
	// i contains the row number
	// checkbox_hidden determines if the row has a checkbox, or hidden properties

// Set the colors for the rows
	if (i%2 == 1) { bgcolor_true = '#ABABAB'; fontcolor_true = '#000000'; bgcolor_false = '#F2F2F5'; fontcolor_false = '#000000'; }
	else          { bgcolor_true = '#ABABAB'; fontcolor_true = '#000000'; bgcolor_false = '#F2F2F5'; fontcolor_false = '#000000'; }

// Checkbox ==> set the colors depending on the checkbox status
// Hidden ==> set the colors as for an unchecked checkbox
	row_id = 'row' + i;
	checkbox_id = 'list_' + i + '_dirfilename';
	if (document.getElementById) {
		if (checkbox_hidden == 'checkbox' && document.getElementById(checkbox_id).checked == true) { 
			document.getElementById(row_id).style.background = bgcolor_true;  document.getElementById(row_id).style.color = fontcolor_true; 
		} else { 
			document.getElementById(row_id).style.background = bgcolor_false; document.getElementById(row_id).style.color = fontcolor_false; 
		}
	}
	else if (document.all) {
		if (checkbox_hidden == 'checkbox' && document.all[checkbox_id].checked == true) { 
			document.all[row_id].style.background = bgcolor_true;  document.all[row_id].style.color = fontcolor_true; 
		} else { 
			document.all[row_id].style.background = bgcolor_false; document.all[row_id].style.color = fontcolor_false; 
		}
	}
}
//--></script>

	<div id="main">
		<form name="BrowseForm" id="BrowseForm" action="<?php echo $net2ftp_globals["action_url"]; ?>" method="post">
<?php			printLoginInfo(); ?>
			<input type="hidden" name="state"     value="browse" />
			<input type="hidden" name="state2"    value="main" />
			<input type="hidden" name="entry"     value="" />

<table align="center" style="width: 100%; margin-top: 5px;" border="0" cellspacing="0" cellpadding="0">
	<tr valign="bottom">
		<td rowspan="2" width="40">&nbsp;  </td>
		<td style="text-align: <?php echo __("left"); ?>;">
			<input type="text" name="directory" value="<?php echo $directory_html; ?>" style="width: 400px;" title="(accesskey g)" accesskey="g" /> 
			<?php printActionIcon("listdirectories", "createDirectoryTreeWindow('$directory_js','BrowseForm.directory');"); ?>
		</td>
		<td valign="middle" style="text-align: <?php echo __("right"); ?>;">
		<?php echo __("Language:"); ?><br /></td>
		<td valign="middle" style="text-align: <?php echo __("left"); ?>;">
<?php			printLanguageSelect("language2", $language_onchange, "width:120px;;", ""); ?>
		<br /></td>
	</tr>
	<tr valign="top">
		<td colspan="<?php echo $action_colspan; ?>">
			<span style="font-size: 80%;"><?php echo __("Directory Tree"); ?>: <?php echo $directory_tree; ?></span>
		</td>
	</tr>
</table>

<br />

<?php if (isset($warning_directory) == true && $warning_directory != "") { ?>
	<div class="warning-box"><div class="warning-text">
	<?php echo $warning_directory; ?>
	</div></div><br />
<?php } ?>

<?php if (isset($warning_consumption) == true && $warning_consumption != "") { ?>
	<div class="warning-box"><div class="warning-text">
	<?php echo $warning_consumption; ?>
	</div></div><br />
<?php } ?>

<?php if (isset($warning_message) == true && $warning_message != "") { ?>
	<div class="warning-box"><div class="warning-text">
	<?php echo $warning_message; ?>
	</div></div><br />
<?php } ?>

<table align="center" style="width: 100%; margin-top: 5px; border: 1px solid #bbd2e0;" border="0" cellpadding="2" cellspacing="2">
	<tr class="browse_rows_actions">
		<td colspan="<?php echo $total_colspan; ?>">
			<table style="width: 100%;" border="0" cellpadding="2" cellspacing="0"><tr><td valign="top" style="text-align: <?php echo __("left"); ?>;">
<?php				if ($net2ftp_settings["functionuse_newdir"]         == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("New dir"); ?>"        onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'newdir', '');"               title="<?php echo __("Make a new subdirectory in directory %1\$s", $net2ftp_globals["directory_html"]); ?> (accesskey w)" accesskey="w" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_newfile"]        == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("New file"); ?>"       onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'edit', 'newfile');"          title="<?php echo __("Create a new file in directory %1\$s", $net2ftp_globals["directory_html"]); ?> (accesskey y)" accesskey="y" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_easyWebsite"]    == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("HTML templates"); ?>" onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'easyWebsite', '');"          title="<?php echo __("Create a website easily using ready-made templates"); ?> (accesskey e)" accesskey="e" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_upload"]         == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Upload"); ?>"         onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'upload', '');"               title="<?php echo __("Upload new files in directory %1\$s", $net2ftp_globals["directory_html"]); ?> (accesskey u)" accesskey="u" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_jupload"]        == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Java Upload"); ?>"    onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'jupload', '');"              title="<?php echo __("Upload directories and files using a Java applet"); ?> (accesskey j)" accesskey="j" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_install"]        == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Install"); ?>"        onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'install', '');"              title="<?php echo __("Install software packages (requires PHP on web server)"); ?> (accesskey k)" accesskey="k" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_advanced"]       == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Advanced"); ?>"       onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'advanced', 'main');"         title="<?php echo __("Go to the advanced functions"); ?> (accesskey n)" accesskey="n" /> <?php } // end if ?>
			</td><td valign="top" style="text-align: <?php echo __("right"); ?>;">
				<?php echo __("Transform selected entries: "); ?>
<?php				if ($net2ftp_settings["functionuse_copy"]           == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Copy"); ?>"           onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'copy');"   title="<?php echo __("Copy the selected entries"); ?> (accesskey c)" accesskey="c" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_move"]           == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Move"); ?>"           onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'move');"   title="<?php echo __("Move the selected entries"); ?> (accesskey m)" accesskey="m" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_delete"]         == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Delete"); ?>"         onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'delete');" title="<?php echo __("Delete the selected entries"); ?> (accesskey d)" accesskey="d" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_rename"]         == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Rename"); ?>"         onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'rename', '');"               title="<?php echo __("Rename the selected entries"); ?> (accesskey o)" accesskey="o" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_chmod"]          == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Chmod"); ?>"          onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'chmod', '');"                title="<?php echo __("Chmod the selected entries (only works on Unix/Linux/BSD servers)"); ?> (accesskey p)" accesskey="p" /> <?php } // end if ?>
				<div style="margin-top: 3px;">
<?php				if ($net2ftp_settings["functionuse_downloadzip"]    == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Download"); ?>"       onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'downloadzip', '');"          title="<?php echo __("Download a zip file containing all selected entries"); ?> (accesskey x)" accesskey="x" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_zip"]            == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Zip"); ?>"            onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'zip', 'zip');"               title="<?php echo __("Zip the selected entries to save or email them"); ?> (accesskey z)" accesskey="z" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_unzip"]          == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Unzip"); ?>"          onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'unzip', '');"                title="<?php echo __("Unzip the selected archives on the FTP server"); ?> (accesskey i)" accesskey="i" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_calculatesize"]  == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Size"); ?>"           onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'calculatesize', '');"        title="<?php echo __("Calculate the size of the selected entries"); ?> (accesskey q)" accesskey="q" /> <?php } // end if ?>
<?php				if ($net2ftp_settings["functionuse_findstring"]     == "yes") { ?><input type="button" class="smallbutton" value="<?php echo __("Search"); ?>"         onClick="submitBrowseForm('<?php echo $directory_js; ?>', '', 'findstring', '');"           title="<?php echo __("Find files which contain a particular word"); ?> (accesskey f)" accesskey="f" /> <?php } // end if ?>
				</div>
			</td></tr></table>
		</td>
	</tr>

	<tr class="browse_rows_heading">
		<td style="width: 32px;"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="CheckAll(document.BrowseForm);"  title="Click to check or uncheck all rows (accesskey t)" accesskey="t"><?php echo __("All"); ?></a></td>
		<td colspan="2"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["dirfilename"]["onclick"]; ?>" title="<?php echo $sortArray["dirfilename"]["title"]; ?>"><?php echo $sortArray["dirfilename"]["text"]; ?></a><?php echo $sortArray["dirfilename"]["icon"]; ?></td>
		<td colspan="1"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["type"]["onclick"];        ?>" title="<?php echo $sortArray["type"]["title"];        ?>"><?php echo $sortArray["type"]["text"];        ?></a><?php echo $sortArray["type"]["icon"];        ?></td>
		<td colspan="1"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["size"]["onclick"];        ?>" title="<?php echo $sortArray["size"]["title"];        ?>"><?php echo $sortArray["size"]["text"];        ?></a><?php echo $sortArray["size"]["icon"];        ?></td>
		<td colspan="1"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["owner"]["onclick"];       ?>" title="<?php echo $sortArray["owner"]["title"];       ?>"><?php echo $sortArray["owner"]["text"];       ?></a><?php echo $sortArray["owner"]["icon"];       ?></td>
		<td colspan="1"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["group"]["onclick"];       ?>" title="<?php echo $sortArray["group"]["title"];       ?>"><?php echo $sortArray["group"]["text"];       ?></a><?php echo $sortArray["group"]["icon"];       ?></td>
		<td colspan="1"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["permissions"]["onclick"]; ?>" title="<?php echo $sortArray["permissions"]["title"]; ?>"><?php echo $sortArray["permissions"]["text"]; ?></a><?php echo $sortArray["permissions"]["icon"]; ?></td>
		<td colspan="1"><a style="text-decoration: underline; cursor: pointer; cursor: hand;" onClick="<?php echo $sortArray["mtime"]["onclick"];       ?>" title="<?php echo $sortArray["mtime"]["title"];       ?>"><?php echo $sortArray["mtime"]["text"];       ?></a><?php echo $sortArray["mtime"]["icon"];       ?></td>
		<td colspan="3"><?php echo __("Actions"); ?></td>
	</tr>

<?php /* ----- Up ----- */ ?>
		<tr class="browse_rows_even" onMouseOver="this.style.color='#000000'; this.style.backgroundColor='#FFCC00';" onMouseOut="this.style.color='#000000'; this.style.backgroundColor='#F2F2F5';">
			<td></td>
			<td title="<?php echo __("Go to the parent directory"); ?>" style="cursor: pointer; cursor: hand;" onClick="javascript:submitBrowseForm('<?php echo $updirectory_js; ?>', '', 'browse', 'main');">
<?php				printMime("icon", $list_directories[$i]); ?>
			</td>
			<td colspan="10" title="<?php echo __("Go to the parent directory"); ?>" style="cursor: pointer; cursor: hand;" onClick="javascript:submitBrowseForm('<?php echo $updirectory_js; ?>', '', 'browse', 'main');">
				<a href="javascript:submitBrowseForm('<?php echo $updirectory_url; ?>','','browse','main');"><?php echo __("Up"); ?> ..</a>
			</td>
		</tr>

<?php /* ----- Directories ----- */ ?>
<?php	if ($list["stats"]["directories"]["total_number"] > 0) { ?>

<?php		for ($i=1; $i<=sizeof($list_directories); $i++) { ?>
<?php 
// ----- Some PHP stuff -----
			$rowcounter++;
			if ($rowcounter % 2 == 1) { $odd_even = "odd"; }
			else                      { $odd_even = "even"; }
			if ($list_directories[$i]["selectable"] == "ok") { 
				$onClick = "submitBrowseForm('" . $list_directories[$i]["newdir_js"] . "','','browse','main');"; 
				$title = __("Go to the subdirectory %1\$s", $list_directories[$i]["dirfilename_html"]); 
				$style = "cursor: pointer; cursor: hand; width: 32px;"; 
				$href = "<a style=\"white-space: nowrap;\" href=\"javascript:" . $onClick . "\">" . $list_directories[$i]["dirfilename_html"] . "</a>\n";
			}
			else { 
				$onClick = "";
				$title = "";
				$style = "";
				$href = "<span style=\"white-space: nowrap;\">" . $list_directories[$i]["dirfilename_html"] . "</span>"; 
			}
// -------------------------- ?>
			<tr class="browse_rows_<?php echo $odd_even; ?>" id="row<?php echo $rowcounter; ?>" onMouseOver="this.style.color='#000000'; this.style.backgroundColor='#FFCC00';" onMouseOut="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'checkbox');">
				<td title="<?php echo __("Select the directory %1\$s", $list_directories[$i]["newdir_html"]); ?>" style="text-align: center; width: 32px;">
<?php				printDirFileProperties($rowcounter, $list_directories[$i], "checkbox", "onClick=\"setColor_js($rowcounter, 'checkbox');\""); ?>
				</td>
				<td onClick="<?php echo $onClick; ?>" title="<?php echo $title; ?>" style="<?php echo $style; ?>">
<?php					printMime("icon", $list_directories[$i]); ?>
				</td>
				<td onClick="<?php echo $onClick; ?>" title="<?php echo $title; ?>" style="<?php echo $style; ?>">
					<?php echo $href; ?>
				</td>
				<td>
<?php					printMime("type", $list_directories[$i]); ?>
				</td>
				<td><?php echo $list_directories[$i]["size"]; ?></td>
				<td><?php echo $list_directories[$i]["owner"]; ?></td>
				<td><?php echo $list_directories[$i]["group"]; ?></td>
				<td><?php echo $list_directories[$i]["permissions"]; ?></td>
				<td><?php echo $list_directories[$i]["mtime"]; ?></td>
				<td colspan="3"></td>
			</tr>
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Files ----- */ ?>
<?php	if ($list["stats"]["files"]["total_number"]> 0) { ?>

<?php		for ($i=1; $i<=sizeof($list_files); $i++) { ?>
<?php 
// ----- Some PHP stuff -----
			$rowcounter++;
			if ($rowcounter % 2 == 1) { $odd_even = "odd"; }
			else                      { $odd_even = "even"; }
			if ($list_files[$i]["selectable"] == "ok") { 
				$onClick = "submitBrowseForm('" . $directory_js . "','" . $list_files[$i]["dirfilename_js"] . "','downloadfile','');"; 
				$title = __("Download the file %1\$s", $list_files[$i]["dirfilename_html"]); 
				$style = "cursor: pointer; cursor: hand; width: 32px;"; 
				$href = "<a style=\"white-space: nowrap;\" href=\"javascript:" . $onClick . "\">" . $list_files[$i]["dirfilename_html"] . "</a>\n";
			}
			else { 
				$onClick = "";
				$title = "";
				$style = "";
				$href = "<span style=\"white-space: nowrap;\">" . $list_files[$i]["dirfilename_html"] . "</span>"; 
			}
// -------------------------- ?>
			<tr class="browse_rows_<?php echo $odd_even; ?>" id="row<?php echo $rowcounter; ?>" onMouseOver="this.style.color='#000000'; this.style.backgroundColor='#FFCC00';" onMouseOut="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'checkbox');">
				<td title="<?php echo __("Select the file %1\$s", $list_files[$i]["dirfilename_html"]); ?>" style="text-align: center; width: 32px;">
<?php				printDirFileProperties($rowcounter, $list_files[$i], "checkbox", "onClick=\"setColor_js($rowcounter, 'checkbox');\""); ?>
				</td>
				<td onClick="<?php echo $onClick; ?>" title="<?php echo $title; ?>" style="<?php echo $style; ?>">
<?php					printMime("icon", $list_files[$i]); ?>
				</td>
				<td onClick="<?php echo $onClick; ?>" title="<?php echo $title; ?>" style="<?php echo $style; ?>">
					<?php echo $href; ?>
				</td>
				<td>
<?php					printMime("type", $list_files[$i]); ?>
				</td>
				<td><?php echo $list_files[$i]["size"]; ?></td>
				<td><?php echo $list_files[$i]["owner"]; ?></td>
				<td><?php echo $list_files[$i]["group"]; ?></td>
				<td><?php echo $list_files[$i]["permissions"]; ?></td>
				<td><?php echo $list_files[$i]["mtime"]; ?></td>
<?php				if ($list_files[$i]["selectable"] == "ok") { ?>
<?php					if ($net2ftp_settings["functionuse_view"]   == "yes") { ?><td onClick="submitBrowseForm('<?php echo $directory_js; ?>', '<?php echo $list_files[$i]["dirfilename_js"]; ?>', 'view', '');"       title="<?php echo __("View the highlighted source code of file %1\$s", $list_files[$i]["dirfilename_html"]);               ?>" style="cursor: pointer; cursor: hand;">         <a href="#"><?php echo __("View");    ?></a></td><?php } ?>
<?php					if ($net2ftp_settings["functionuse_edit"]   == "yes") { ?><td onClick="submitBrowseForm('<?php echo $directory_js; ?>', '<?php echo $list_files[$i]["dirfilename_js"]; ?>', 'edit', '');"       title="<?php echo __("Edit the source code of file %1\$s", $list_files[$i]["dirfilename_html"]);                           ?>" style="cursor: pointer; cursor: hand;">         <a href="#"><?php echo __("Edit");    ?></a></td><?php } ?>
<?php					if ($net2ftp_settings["functionuse_update"] == "yes") { ?><td onClick="submitBrowseForm('<?php echo $directory_js; ?>', '<?php echo $list_files[$i]["dirfilename_js"]; ?>', 'updatefile', '');" title="<?php echo __("Upload a new version of the file %1\$s and merge the changes", $list_files[$i]["dirfilename_html"]); ?>" style="cursor: pointer; cursor: hand;">         <a href="#"><?php echo __("Update");  ?></a></td><?php } ?>
<?php				} else { ?>
<?php					if ($net2ftp_settings["functionuse_view"]   == "yes") { ?><td></td><?php } ?>
<?php					if ($net2ftp_settings["functionuse_edit"]   == "yes") { ?><td></td><?php } ?>
<?php					if ($net2ftp_settings["functionuse_update"] == "yes") { ?><td></td><?php } ?>
<?php				} ?>
<?php				if ($net2ftp_settings["functionuse_open"]   == "yes") { ?><td onClick="window.open('<?php echo $list_links_js[$i]; ?>');" style="cursor: pointer; cursor: hand;" title="<?php echo __("View the file %1\$s from your HTTP web server", $list_files[$i]["dirfilename_html"]); ?> &#13; <?php echo __("(Note: This link may not work if you don't have your own domain name.)"); ?>"><a href="<?php echo $list_links_js[$i]; ?>">                                                                                                         <?php echo __("Open");    ?></a></td><?php } ?>
			</tr>
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Symlinks ----- */ ?>
<?php	if ($list["stats"]["symlinks"]["total_number"] > 0) { ?>

<?php		for ($i=1; $i<=sizeof($list_symlinks); $i++) { ?>
<?php 
// ----- Some PHP stuff -----
			$rowcounter++;
			if ($rowcounter % 2 == 1) { $odd_even = "odd"; }
			else                      { $odd_even = "even"; }
			if ($list_symlinks[$i]["selectable"] == "ok") { 
				$onClick = "submitBrowseForm('" . $directory_js . "','" . $list_symlinks[$i]["dirfilename_js"] . "','followsymlink','main');"; 
				$title = __("Follow symlink %1\$s", $list_symlinks[$i]["dirfilename_html"]); 
				$style = "cursor: pointer; cursor: hand; width: 32px;"; 
				$href = "<a style=\"white-space: nowrap;\" href=\"javascript:" . $onClick . "\">" . $list_symlinks[$i]["dirfilename_html"] . "</a>\n";
			}
			else { 
				$onClick = "";
				$title = "";
				$style = "";
				$href = "<span style=\"white-space: nowrap;\">" . $list_symlinks[$i]["dirfilename_html"] . "</span>"; 
			}
// -------------------------- ?>
			<tr class="browse_rows_<?php echo $odd_even; ?>" id="row<?php echo $rowcounter; ?>" onMouseOver="this.style.color='#000000'; this.style.backgroundColor='#FFCC00';" onMouseOut="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'hidden');">
				<td title="<?php echo __("Select the symlink %1\$s", $list_symlinks[$i]["dirfilename_html"]); ?>" style="text-align: center; width: 32px;">
<?php				printDirFileProperties($rowcounter, $list_symlinks[$i], "checkbox", "onClick=\"setColor_js($rowcounter, 'checkbox');\""); ?>
				</td>
				<td onClick="<?php echo $onClick; ?>" title="<?php echo $title; ?>" style="<?php echo $style; ?>">
<?php					printMime("icon", $list_symlinks[$i]); ?>
				</td>
				<td onClick="<?php echo $onClick; ?>" title="<?php echo $title; ?>" style="<?php echo $style; ?>">
					<?php echo $href; ?>
				</td>
				<td>
<?php					printMime("type", $list_symlinks[$i]); ?>
				</td>
				<td><?php echo $list_symlinks[$i]["size"]; ?></td>
				<td><?php echo $list_symlinks[$i]["owner"]; ?></td>
				<td><?php echo $list_symlinks[$i]["group"]; ?></td>
				<td><?php echo $list_symlinks[$i]["permissions"]; ?></td>
				<td><?php echo $list_symlinks[$i]["mtime"]; ?></td>
<?php				if ($net2ftp_settings["functionuse_view"]   == "yes") { ?><td></td><?php } ?>
<?php				if ($net2ftp_settings["functionuse_edit"]   == "yes") { ?><td></td><?php } ?>
<?php				if ($net2ftp_settings["functionuse_update"] == "yes") { ?><td></td><?php } ?>
<?php				if ($net2ftp_settings["functionuse_open"]   == "yes") { ?><td></td><?php } ?>
			</tr>
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Unrecognized ----- */ ?>
<?php	if ($list["stats"]["unrecognized"]["total_number"] > 0) { ?>

<?php		for ($i=1; $i<=sizeof($list_unrecognized); $i++) { ?>
<?php			$rowcounter++; ?>
<?php			if ($rowcounter % 2 == 1) { $odd_even = "odd"; }
			else                      { $odd_even = "even"; } ?>
			<tr class="browse_rows_<?php echo $odd_even; ?>" id="row<?php echo $i; ?>" onMouseOver="this.style.color='#000000'; this.style.backgroundColor='#FFCC00';" onMouseOut="this.style.color='#000000'; setColor_js(<?php echo $i; ?>);">
				<td><?php echo $list_unrecognized[$i]["dirfilename_html"]; ?></td>
			</tr>
<?php		} // end for ?>
<?php	} // end if ?>

<?php /* ----- Empty folder ----- */ ?>
<?php	if ($rowcounter == 0) { ?>
		<tr class="browse_rows_odd">
			<td style="text-align: center;" colspan="12">
				<br /><?php echo __("This folder is empty"); ?><br /><br />
			</td>
		</tr>
<?php	} // end if ?>

</table>
</form>

<?php /* ----- Statistics ----- */ ?>
<div style="font-size: 90%; margin-top: 10px; margin-left: 780px;">
<?php echo __("Directories"); ?>: <?php echo $list["stats"]["directories"]["total_number"]; ?><br />
<?php echo __("Files"); ?>: <?php echo $list["stats"]["files"]["total_number"]; ?> / <?php echo $list["stats"]["files"]["total_size_formated"]; ?><br />
<?php echo __("Symlinks"); ?>: <?php echo $list["stats"]["symlinks"]["total_number"]; ?><br />
<?php echo __("Unrecognized FTP output"); ?>: <?php echo $list["stats"]["unrecognized"]["total_number"]; ?>
</div>

<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/google_browse.template.php"); ?>

	</div>
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/omega/browse_main_details.template.php end -->
