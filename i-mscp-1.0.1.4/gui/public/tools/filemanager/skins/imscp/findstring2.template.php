<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/findstring2.template.php begin -->

<input type="hidden" name="entry"     value="">

<?php /* ----- No results are found ----- */ ?>
<?php	if (count($result) == 0) { ?>
<?php		for ($i=0; $i<sizeof($net2ftp_output["findstring"]); $i++) { ?>
			<?php echo $net2ftp_output["findstring"][$i]; ?><br />
<?php		} // end for
	} // end if

	/* ----- There are results ----- */
	else { ?>
<?php		for ($i=0; $i<sizeof($net2ftp_output["findstring"]); $i++) { ?>
			<?php echo $net2ftp_output["findstring"][$i]; ?><br />
<?php		} // end for ?>
		<br />
		<table cellpadding="2" style="border: 2px solid #CCCCFF;">
			<tr class="browse_rows_heading">
				<td><?php echo __("Directory"); ?></td>
				<td><?php echo __("File"); ?></td>
				<td><?php echo __("Line"); ?></td>
				<td colspan="2"><?php echo __("Action"); ?></td>
			</tr>
<?php			for ($i=0; $i<sizeof($result); $i++) { ?>
<?php				if ($i % 2 == 1) { $odd_even = "odd"; }
				else             { $odd_even = "even"; } ?>
				<tr class="browse_rows_<?php echo $odd_even; ?>">
					<td><?php echo $result[$i]["directory"]; ?></td>
					<td><?php echo $result[$i]["dirfilename"]; ?></td>
					<td><?php echo $result[$i]["line"]; ?></td>
					<td onclick="submitFindstringForm('<?php echo $result[$i]["directory_js"]; ?>', '<?php echo $result[$i]["dirfilename_js"]; ?>', 'view', '', 1);" title="<?php echo __("View the highlighted source code of file %1\$s", $result[$i]["dirfilename_html"]); ?>" style="cursor: pointer; cursor: hand;"><?php echo __("View"); ?></td>
					<td onclick="submitFindstringForm('<?php echo $result[$i]["directory_js"]; ?>', '<?php echo $result[$i]["dirfilename_js"]; ?>', 'edit', '', 1);" title="<?php echo __("Edit the source code of file %1\$s", $result[$i]["dirfilename_html"]); ?>"             style="cursor: pointer; cursor: hand;"><?php echo __("Edit"); ?></td>
				</tr>
<?php		} // end for ?>
		</table>
<?php	} // end else ?>
<!-- Template /skins/blue/findstring2.template.php end -->
