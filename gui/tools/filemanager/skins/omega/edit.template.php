<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/omega/edit.template.php begin -->
<form id="<?php echo $formname; ?>" action="<?php echo $net2ftp_globals["action_url"]; ?>" onsubmit="<?php echo $onSubmit; ?>" method="post">
<?php	printLoginInfo(); ?>
<input type="hidden" name="state"        value="edit" />
<input type="hidden" name="state2"       value="" />
<input type="hidden" name="directory"    value="<?php echo $net2ftp_globals["directory_html"]; ?>" />
<input type="hidden" name="screen"       value="2" />
<input type="hidden" name="textareaType" value="<?php echo $textareaType; ?>" />
<table style="padding: 2px; width: 100%; height: 100%; border: 0px;">
	<tr>
		<td style="vertical-align: top; text-align: <?php echo __("left"); ?>; width: 25%;">
<?php			printActionIcon("back", "document.forms['" . $formname . "'].state.value='browse';document.forms['" . $formname . "'].state2.value='main';document.forms['" . $formname . "'].submit();", ""); ?> &nbsp; 
<?php	  		printActionIcon("save", "document.forms['" . $formname . "'].screen.value=3;" . $getCode . "document.forms['" . $formname . "'].submit();", ""); ?> &nbsp; 
<?php			printTextareaSelect($onchange); ?>
		</td>
		<td style="vertical-align: top; text-align: <?php echo __("left"); ?>; width: 50%;">
<?php			if ($net2ftp_globals["state2"] == "") { ?>
				<input type="hidden" name="entry" value="<?php echo $net2ftp_globals["entry_html"]; ?>" />
				<?php echo __("File: "); ?><?php echo $dirfilename; ?><br />
<?php				if ($textareaType == "fckeditor" || $textareaType == "tinymce") { ?>
					<span style="font-size: 90%;"><a href="javascript:toggleTextarea('header');">Toggle header</a> &nbsp; <a href="javascript:toggleTextarea('body');">Toggle body</a> &nbsp; <a href="javascript:toggleTextarea('footer');">Toggle footer</a></span>
<?php				}
			}
			elseif ($net2ftp_globals["state2"] == "newfile") { ?>
				<?php echo __("New file name: "); ?><input class="input" type="text" name="entry" /><br />
<?php			} ?>
<?php 	if (function_exists("mb_detect_encoding") == true && $net2ftp_globals["language"] == "ja") { echo __("Character encoding: "); } ?>
<?php		printEncodingSelect($text_encoding); ?> &nbsp;
<?php		printLineBreakSelect($line_break); ?>
		</td>
		<td style="vertical-align: top; text-align: <?php echo __("left"); ?>; width: 25%;">
			<span style="font-size: 90%;"><?php echo $savestatus; ?></span>
		</td>
	</tr>
	<tr>
		<td colspan="3" style="vertical-align: top; text-align: <?php echo __("left"); ?>;">
			<div style="margin-<?php echo __("left"); ?>: 0px; text-align: <?php echo __("left"); ?>;">
<?php /* ----- Plain textarea ----- */ ?>
<?php 		if ($textareaType == "" || $textareaType == "plain") { ?>
<?php // Do not use style="white-space: nowrap;" because then IE strips the carriage-return + linefeeds (tested on IE version 6.0) ?>
				<textarea name="text" class="edit" rows="33" style="width: 99%;" wrap="off" onkeydown="TabText()"><?php echo $text; ?></textarea>
<?php 		} 
	/* ----- FCKEditor ----- */
			elseif ($textareaType == "fckeditor") { ?>
				<div id="header_hidden"></div>
				<div id="header_shown" style="display: none;"><textarea name="text_splitted[top]" style="width: 100%; height: 200px;"><?php echo $text_splitted["top"]; ?></textarea></div>

				<div id="body_hidden"></div>
				<div id="body_shown" style="display: block;">
<?php				$oFCKeditor = new FCKeditor("text_splitted[middle]") ;
				$oFCKeditor->BasePath = $fckeditor_basepath;
				$oFCKeditor->Value    = $text_splitted["middle"];
				$oFCKeditor->Width    = "100%" ;
				$oFCKeditor->Height   = "450px";
				$oFCKeditor->Config['AutoDetectLanguage']	= false;
				$oFCKeditor->Config['DefaultLanguage']    = $fckeditor_language;
				$oFCKeditor->ToolbarSet = "Default";
				$oFCKeditor->Create() ; ?>
				</div>

				<div id="footer_hidden"></div>
				<div id="footer_shown" style="display: none;"><textarea name="text_splitted[bottom]" style="width: 100%; height: 200px;"><?php echo $text_splitted["bottom"]; ?></textarea></div>
<?php			}
	/* ----- TinyMCE ----- */
			elseif ($textareaType == "tinymce") { ?>
				<div id="header_hidden"></div>
				<div id="header_shown" style="display: none;"><textarea name="text_splitted[top]" style="width: 100%; height: 200px;"><?php echo $text_splitted["top"]; ?></textarea></div>

				<div id="body_hidden"></div>
				<div id="body_shown" style="display: block;"><textarea name="text_splitted[middle]" style="width: 100%; height: 450px;"><?php echo $text_splitted["middle"]; ?></textarea></div>

				<div id="footer_hidden"></div>
				<div id="footer_shown" style="display: none;"><textarea name="text_splitted[bottom]" style="width: 100%; height: 200px;"><?php echo $text_splitted["bottom"]; ?></textarea></div>
<?php 		}
	/* ----- CodePress ----- */
			elseif ($textareaType == "codepress") { ?>
				<iframe id="codepress" src="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/plugins/codepress/codepress.php?action=edit&amp;file=<?php echo $net2ftp_globals["entry_html"]; ?>" style="width: 100%; height: 450px;"></iframe><br />
				<input type="hidden" id="load-code" value="<?php echo $text; ?>" />
				<input type="hidden" name="text" id="text" value="" />
<?php 		} ?>
			</div>
		</td>
	</tr>
</table>
</form>

<?php if ($textareaType == "codepress") { ?>
<script type="text/javascript">

// get code from codepress
function getCode() {
	document.getElementById('text').value = document.getElementById('codepress').contentWindow.CodePress.getCode();
	document.getElementById('load-code').value = "";
	return true;
}

// get code from a textarea and put it inside codepress window
function setCode() {
	document.getElementById('codepress').contentWindow.CodePress.setCode('php',document.getElementById('load-code').value);
}
</script>
<?php } ?>


<!-- Template /skins/omega/edit.template.php end -->
