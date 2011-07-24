<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/findstring1.template.php begin -->
<?php /* ----- List of selected entries ----- */ ?>
<?php	for ($i=1; $i<=sizeof($list["all"]); $i++) { ?>
<?php		printDirFileProperties($i, $list["all"][$i], "hidden", ""); ?>
<?php	} // end for ?>
<table border="0" cellspacing="2" cellpadding="2">
	<tr>
		<td style="vertical-align: top;"><?php echo __("Search for a word or phrase"); ?></td>
		<td style="vertical-align: top;">
			<input type="text" class="input" name="searchoptions[string]" value="" /><br />
			<input type="checkbox" name="searchoptions[case_sensitive]" value="yes" /> <?php echo __("Case sensitive search"); ?>
		</td>
	</tr>
	<tr>
		<td colspan="2"><?php echo __("Restrict the search to:"); ?></td>
	</tr>
	<tr>
		<td> &nbsp; <?php echo __("files with a filename like"); ?></td>
		<td><input type="text" class="input" name="searchoptions[filename]" value="*.*" /> (wildcard character is *) </td>
	</tr>
	<tr>
		<td> &nbsp; <?php echo __("files with a size"); ?></td>
		<td><?php echo __("from"); ?> <input type="text" class="input" name="searchoptions[size_from]" value="0" maxlength="7" /> <?php echo __("to"); ?> <input type="text" class="input" name="searchoptions[size_to]" value="500000" maxlength="7" /> Bytes</td>
	</tr>
	<tr>
		<td> &nbsp; <?php echo __("files which were last modified"); ?></td>
		<td><?php echo __("from"); ?> <input type="text" class="input" name="searchoptions[modified_from]" id="modified_from" value="<?php echo $modified_from; ?>" /><input type="button" id="from_trigger" class="microbutton" value="..."> <?php echo __("to"); ?> <input type="text" class="input" name="searchoptions[modified_to]" id="modified_to" value="<?php echo $modified_to; ?>" /><input type="button" id="to_trigger" class="microbutton" value="..."> </td>
	</tr>
</table>

<script type="text/javascript">
    Calendar.setup({
        inputField     :    "modified_from", // id of the input field
        ifFormat       :    "%Y-%m-%d",      // format of the input field
        showsTime      :    false,           // will display a time selector
        button         :    "from_trigger",  // trigger for the calendar (button ID)
        singleClick    :    true,            // double-click mode
        step           :    1                // show all years in drop-down boxes (instead of every other year as default)
    });
    Calendar.setup({
        inputField     :    "modified_to",   // id of the input field
        ifFormat       :    "%Y-%m-%d",      // format of the input field
        showsTime      :    false,           // will display a time selector
        button         :    "to_trigger",    // trigger for the calendar (button ID)
        singleClick    :    true,            // double-click mode
        step           :    1                // show all years in drop-down boxes (instead of every other year as default)
    });
</script>

<!-- Template /skins/blue/findstring1.template.php end -->
