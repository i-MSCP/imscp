<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/chmod1.template.php begin -->
<table style="border-color: #000000; border-style: solid; border-width: 1px; padding: 5px;">
	<tr><td>
		<table border="0" cellspacing="2" cellpadding="2" style="margin-<?php echo __("left"); ?>: 5px;">
			<tr>
				<td rowspan="4" style="vertical-align: top;"><input type="button" class="extralongbutton" value="<?php echo __("Set all permissions"); ?>" onclick="CopyCheckboxToAll(document.forms['ChmodForm'], 'header[owner_read]', 'owner_read'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[owner_write]', 'owner_write'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[owner_execute]', 'owner_execute'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[group_read]', 'group_read'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[group_write]', 'group_write'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[group_execute]', 'group_execute'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[other_read]', 'other_read'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[other_write]', 'other_write'); CopyCheckboxToAll(document.forms['ChmodForm'], 'header[other_execute]', 'other_execute'); update_input(); " /></td>
			</tr>
			<tr>
				<td>Owner:</td>
				<td><input type="checkbox" name="header[owner_read]"    value="4">Read</td>
				<td><input type="checkbox" name="header[owner_write]"   value="2">Write</td>
				<td><input type="checkbox" name="header[owner_execute]" value="1">Execute</td>
			</tr>
			<tr>
				<td>Group:</td>
				<td><input type="checkbox" name="header[group_read]"    value="4">Read</td>
				<td><input type="checkbox" name="header[group_write]"   value="2">Write</td>
				<td><input type="checkbox" name="header[group_execute]" value="1">Execute</td>
			</tr>
			<tr>
				<td>Everyone:</td>
				<td><input type="checkbox" name="header[other_read]"    value="4">Read</td>
				<td><input type="checkbox" name="header[other_write]"   value="2">Write</td>
				<td><input type="checkbox" name="header[other_execute]" value="1">Execute</td>
			</tr>
		</table>
		<div style="font-size: 90%"><?php echo __("To set all permissions to the same values, enter those permissions and click on the button \"Set all permissions\""); ?>.</div>
	</td></tr>
</table><br />

<?php for ($i=1; $i<=sizeof($list["all"]); $i++) { ?>
<?php		printDirFileProperties($i, $list["all"][$i], "hidden", ""); ?>
<?php 	echo $list["all"][$i]["message"]; ?> <br />

<table border="0" cellspacing="2" cellpadding="2" style="margin-<?php echo __("left"); ?>: 20px;">
	<tr>
		<td>Owner:</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][owner_read]"    value="4" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["owner_read"]; ?> />Read</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][owner_write]"   value="2" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["owner_write"]; ?> />Write</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][owner_execute]" value="1" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["owner_execute"]; ?> />Execute</td>
	</tr>
	<tr>
		<td>Group:</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][group_read]"    value="4" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["group_read"]; ?> />Read</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][group_write]"   value="2" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["group_write"]; ?> />Write</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][group_execute]" value="1" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["group_execute"]; ?> />Execute</td>
	</tr>
	<tr>
		<td>Everyone:</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][other_read]"    value="4" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["other_read"]; ?> />Read</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][other_write]"   value="2" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["other_write"]; ?> />Write</td>
		<td><input type="checkbox" name="list[<?php echo $i; ?>][other_execute]" value="1" onclick="update_input(<?php echo $i; ?>);" <?php echo $list["all"][$i]["other_execute"]; ?> />Execute</td>
	</tr>
	<tr>
		<td colspan=4>Chmod value: <input class="smallinput" value="<?php echo $list["all"][$i]["chmodvalue"]; ?>" name="chmod<?php echo $i; ?>" id="chmod<?php echo $i; ?>" onchange="update_checkbox(<?php echo $i; ?>);" /></td>
	</tr>
</table>
<?php if     ($list["all"][$i]["dirorfile"] == "d") { ?>
	<input type="checkbox" name="list[<?php echo $i; ?>][chmod_subdirectories]" value="yes" /> <?php echo __("Chmod also the subdirectories within this directory"); ?><br />
	<input type="checkbox" name="list[<?php echo $i; ?>][chmod_subfiles]"       value="yes" /> <?php echo __("Chmod also the files within this directory"); ?><br />
<?php } ?>
<br /><br />

<?php } // end for ?>

<!-- Template /skins/blue/chmod1.template.php end -->
