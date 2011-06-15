<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/browse_main_icons.template.php begin -->

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

			<table width="780" style="margin-left: auto; margin-right: auto;" border="0" cellpadding="0" cellspacing="0">
				<tr style="vertical-align: top;">
					<td style="text-align: left; font-size: 0.8em;">
<?php						printLoginInfo(); ?>
						<input type="hidden" name="state"     value="browse" />
						<input type="hidden" name="state2"    value="main" />
						<input type="hidden" name="entry"     value="" />

<?php						if (isset($warning_directory) == true && $warning_directory != "") { ?>
							<div class="warning-box"><div class="warning-text">
							<?php echo $warning_directory; ?>
							</div></div><br />
<?php						} ?>

<?php						if (isset($warning_consumption) == true && $warning_consumption != "") { ?>
							<div class="warning-box"><div class="warning-text">
							<?php echo $warning_consumption; ?>
							</div></div><br />
<?php						} ?>

<?php						if (isset($warning_message) == true && $warning_message != "") { ?>
							<div class="warning-box"><div class="warning-text">
							<?php echo $warning_message; ?>
							</div></div><br />
<?php						} ?>

						<input name="directory" type="text" accesskey="g" title="(accesskey g)" value="<?php echo $directory_html; ?>" size="50" /><br />
						<?php echo __("Directory Tree"); ?>: <?php echo $directory_tree; ?>
						</td>
						<td style="text-align: right; padding-right: 20px;">
							<select class="" name="BrowseSelect" onchange="eval(document.BrowseForm.BrowseSelect.options[document.BrowseForm.BrowseSelect.selectedIndex].value);">
								<option value="0" selected style="font-weight: bold; text-decoration: underline;"><?php echo __("Actions"); ?>: </option>
<?php	if ($net2ftp_settings["functionuse_newdir"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'newdir', '');"               title="<?php echo __("Make a new subdirectory in directory %1\$s", $net2ftp_globals["directory_html"]); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("New dir"); ?></option><?php } // end if ?>
<?php if ($net2ftp_settings["functionuse_newfile"]        == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'edit', 'newfile');"          title="<?php echo __("Create a new file in directory %1\$s", $net2ftp_globals["directory_html"]); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("New file"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_easyWebsite"]    == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'easyWebsite', '');"          title="<?php echo __("Create a website easily using ready-made templates"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("HTML templates"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_upload"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'upload', '');"               title="<?php echo __("Upload new files in directory %1\$s", $net2ftp_globals["directory_html"]); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Upload"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_jupload"]        == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'jupload', '');"              title="<?php echo __("Upload directories and files using a Java applet"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Java Upload"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_install"]        == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'install', '');"              title="<?php echo __("Install software packages (requires PHP on web server)"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Install"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_advanced"]       == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'advanced', 'main');"         title="<?php echo __("Go to the advanced functions"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Advanced"); ?></option><?php } // end if ?>
                                                                        <option value="" style="font-weight: bold; text-decoration: underline;"><?php echo __("Transform selected entries: "); ?></option>
<?php	if ($net2ftp_settings["functionuse_copy"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'copy');"   title="<?php echo __("Copy the selected entries"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Copy"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_move"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'move');"   title="<?php echo __("Move the selected entries"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Move"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_delete"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'copymovedelete', 'delete');" title="<?php echo __("Delete the selected entries"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Delete"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_rename"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'rename', '');"               title="<?php echo __("Rename the selected entries"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Rename"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_chmod"]          == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'chmod', '');"                title="<?php echo __("Chmod the selected entries (only works on Unix/Linux/BSD servers)"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Chmod"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_downloadzip"]    == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'downloadzip', '');"          title="<?php echo __("Download a zip file containing all selected entries"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Download"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_zip"]            == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'zip', 'zip');"               title="<?php echo __("Zip the selected entries to save or email them"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Zip"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_zip"]            == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'unzip', '');"                title="<?php echo __("Unzip the selected archives on the FTP server"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Unzip"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_calculatesize"]  == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'calculatesize', '');"        title="<?php echo __("Calculate the size of the selected entries"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Size"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_findstring"]     == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'findstring', '');"           title="<?php echo __("Find files which contain a particular word"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Search"); ?></option><?php } // end if ?>
                                                                        <option value="" style="font-weight: bold; text-decoration: underline;"><?php echo __("Transform selected entry: "); ?></option>
<?php	if ($net2ftp_settings["functionuse_view"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'view', '');"                 title="<?php echo __("View the highlighted source code of file %1\$s", $list_files[$i]["dirfilename_html"]);               ?>">&nbsp;&nbsp;&nbsp;<?php echo __("View"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_edit"]           == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'edit', '');"                 title="<?php echo __("Edit the source code of file %1\$s", $list_files[$i]["dirfilename_html"]);                           ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Edit"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_update"]         == "yes") { ?><option value="submitBrowseForm('<?php echo $directory_js; ?>', '', 'update', '');"               title="<?php echo __("Upload a new version of the file %1\$s and merge the changes", $list_files[$i]["dirfilename_html"]); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Update"); ?></option><?php } // end if ?>
<?php	if ($net2ftp_settings["functionuse_open"]           == "yes") { ?><option value="window.open('<?php echo $list_links_js[$i]; ?>');"                                 title="<?php echo __("View the file %1\$s from your HTTP web server", $list_files[$i]["dirfilename_html"]); ?> &#13; <?php echo __("(Note: This link may not work if you don't have your own domain name.)"); ?>">&nbsp;&nbsp;&nbsp;<?php echo __("Open"); ?></option><?php } // end if ?>
							</select>
						</td>
                            	</tr>
					<tr title="<?php echo __("Go to the parent directory"); ?>" style="cursor: pointer; cursor: hand; border: 0px; text-align: center;">
						<td colspan="2" align="center" onclick="javascript:submitBrowseForm('<?php echo $updirectory_js; ?>', '', 'browse', 'main');">
							<?php printActionIcon("up", "", ""); ?>
						</td>
					</tr>
				</table>

				<table width="780" style="margin-left: auto; margin-right: auto;" border="0" cellpadding="0" cellspacing="0">
<?php $rowcounter = 0; ?>
<?php /* ----- Directories ----- */ ?>
<?php	                    if ($list["stats"]["directories"]["total_number"] > 0) { ?>
                            <tr>
                              <td colspan="6">
                                <b><?php echo __("Directories"); ?></b> (<?php echo $list["stats"]["directories"]["total_number"]; ?>)
                              </td>
                            </tr>
<?php                       for ($i=1; $i<=sizeof($list_directories);) { ?>
                              <tr>
<?php                         for ($column_counter=1; $column_counter<=6; $column_counter++) { ?>
<?php                           if ($i<=sizeof($list_directories)) {
// ----- Some PHP stuff -----
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
                                  $rowcounter++;
// -------------------------- ?>
                                    <td id="row<?php echo $rowcounter; ?>" title="<?php echo $title; ?> | <?php echo __("Size"); ?>: <?php echo $list_directories[$i]["size"]; ?> | <?php echo __("Owner"); ?>: <?php echo $list_directories[$i]["owner"]; ?> | <?php echo __("Group"); ?>: <?php echo $list_directories[$i]["group"]; ?> | <?php echo __("Perms"); ?>: <?php echo $list_directories[$i]["permissions"]; ?> | <?php echo __("Mod Time"); ?>: <?php echo $list_directories[$i]["mtime"]; ?>" onmouseover="this.style.color='#000000'; this.style.backgroundColor='#ffcc00';" onmouseout="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'checkbox');">
                                      <div class="browse_cell">
<?php				              printDirFileProperties($rowcounter, $list_directories[$i], "checkbox", "onclick=\"setColor_js($rowcounter, 'checkbox');\""); ?>
<?php					        printMime("icon", $list_directories[$i]); ?><br />
                                      <?php echo $href; ?>
                                      </div>
                                    </td>
<?php                               $i++; ?>
<?php                           } else { ?>
                                  <td><div class="browse_cell">&nbsp;</div></td>
<?php                           } ?>
<?php		                  } // end for column_counter ?>
                              </tr>
<?php		                } // end for i ?>
<?php	                    } // end if ?>

<?php /* ----- Files ----- */ ?>
<?php	                    if ($list["stats"]["files"]["total_number"] > 0) { ?>
                            <tr>
                              <td colspan="6">
                                <b><?php echo __("Files"); ?></b> (<?php echo $list["stats"]["files"]["total_number"]; ?> / <?php echo $list["stats"]["files"]["total_size_formated"]; ?>)
                              </td>
                            </tr>
<?php                       for ($i=1; $i<=sizeof($list_files);) { ?>
                              <tr>
<?php                         for ($column_counter=1; $column_counter<=6; $column_counter++) { ?>
<?php                           if ($i<=sizeof($list_files)) {
// ----- Some PHP stuff -----
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
                                  $rowcounter++;
// -------------------------- ?>
                                    <td id="row<?php echo $rowcounter; ?>" title="<?php echo $title; ?> | <?php echo __("Size"); ?>: <?php echo $list_files[$i]["size"]; ?> | <?php echo __("Owner"); ?>: <?php echo $list_files[$i]["owner"]; ?> | <?php echo __("Group"); ?>: <?php echo $list_files[$i]["group"]; ?> | <?php echo __("Perms"); ?>: <?php echo $list_files[$i]["permissions"]; ?> | <?php echo __("Mod Time"); ?>: <?php echo $list_files[$i]["mtime"]; ?>" onmouseover="this.style.color='#000000'; this.style.backgroundColor='#ffcc00';" onmouseout="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'checkbox');">
                                      <div class="browse_cell">
<?php				              printDirFileProperties($rowcounter, $list_files[$i], "checkbox", "onclick=\"setColor_js($rowcounter, 'checkbox');\""); ?>
<?php					        printMime("icon", $list_files[$i]); ?><br />
                                      <?php echo $href; ?>
						  </div>
                                    </td>
<?php                               $i++; ?>
<?php                             } else { ?>
                                    <td><div class="browse_cell">&nbsp;</div></td>
<?php                             } ?>
<?php		                  } // end for column_counter ?>
                              </tr>
<?php		                } // end for i ?>
<?php	                    } // end if ?>

<?php /* ----- Symlinks ----- */ ?>
<?php	                    if ($list["stats"]["symlinks"]["total_number"] > 0) { ?>
                            <tr>
                              <td colspan="6">
                                <b><?php echo __("Symlinks"); ?></b> (<?php echo $list["stats"]["symlinks"]["total_number"]; ?>)
                              </td>
                            </tr>
<?php                       for ($i=1; $i<=sizeof($list_symlinks);) { ?>
                              <tr>
<?php                         for ($column_counter=1; $column_counter<=6; $column_counter++) { ?>
<?php                           if ($i<=sizeof($list_symlinks)) {
// ----- Some PHP stuff -----
                                  if ($list_symlinks[$i]["selectable"] == "ok") { 
                                    $onClick = "submitBrowseForm('" . $directory_js . "','" . $list_symlinks[$i]["dirfilename_js"] . "','followsymlink','');"; 
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
                                  $rowcounter++;
// -------------------------- ?>
                                    <td id="row<?php echo $rowcounter; ?>" title="<?php echo __("Follow symlink %1\$s", $list_symlinks[$i]["dirfilename_html"]); ?> | <?php echo __("Size"); ?>: <?php echo $list_symlinks[$i]["size"]; ?> | <?php echo __("Owner"); ?>: <?php echo $list_symlinks[$i]["owner"]; ?> | <?php echo __("Group"); ?>: <?php echo $list_symlinks[$i]["group"]; ?> | <?php echo __("Perms"); ?>: <?php echo $list_symlinks[$i]["permissions"]; ?> | <?php echo __("Mod Time"); ?>: <?php echo $list_symlinks[$i]["mtime"]; ?>" onmouseover="this.style.color='#000000'; this.style.backgroundColor='#ffcc00';" onmouseout="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'checkbox');">
                                      <div class="browse_cell">
<?php				              printDirFileProperties($rowcounter, $list_files[$i], "checkbox", "onclick=\"setColor_js($rowcounter, 'checkbox');\""); ?>
<?php					        printMime("icon", $list_files[$i]); ?><br />
                                      <?php echo $href; ?>
						  </div>
                                    </td>
<?php                               $i++; ?>
<?php                             } else { ?>
                                    <td><div class="browse_cell">&nbsp;</div></td>
<?php                             } ?>
<?php		                  } // end for column_counter ?>
                              </tr>
<?php		                } // end for i ?>
<?php	                    } // end if ?>

<?php /* ----- Unrecognized ----- */ ?>
<?php	                    if ($list["stats"]["unrecognized"]["total_number"] > 0) { ?>
                            <tr>
                              <td colspan="6">
                                <b><?php echo __("Unrecognized FTP output"); ?></b> (<?php echo $list["stats"]["unrecognized"]["total_number"]; ?>)
                              </td>
                            </tr>
<?php                       for ($i=1; $i<=sizeof($list_unrecognized);) { ?>
                              <tr>
<?php                         for ($column_counter=1; $column_counter<=6; $column_counter++) { ?>
<?php                             if ($i<=sizeof($list_unrecognized)) { ?>
<?php                               $rowcounter++; ?>
                                    <td onmouseover="this.style.color='#000000'; this.style.backgroundColor='#ffcc00';" onmouseout="this.style.color='#000000'; setColor_js(<?php echo $rowcounter; ?>, 'checkbox');">
                                      <div class="browse_cell">
<?php					        printMime("icon", $list_unrecognized[$i]); ?><br />
                                      <?php echo $list_unrecognized[$i]["dirfilename_html"]; ?>
                                      </div>
                                    </td>
<?php                               $i++; ?>
<?php                             } else { ?>
                                    <td><div class="browse_cell">&nbsp;</div></td>
<?php                             } ?>
<?php		                  } // end for column_counter ?>
                              </tr>
<?php		                } // end for i ?>
<?php	                    } // end if ?>

<?php /* ----- Empty folder ----- */ ?>
<?php	                    if ($rowcounter == 0) { ?>
                            <tr>
                              <td colspan="6" style="text-align: center;">
                                <br /><?php echo __("This folder is empty"); ?><br /><br />
                              </td>
                            </tr>
<?php                     } // end if ?>

				</table>

			</form>

<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/google_browse.template.php"); ?>

	</div>
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/blue/browse_main_icons.template.php end -->
