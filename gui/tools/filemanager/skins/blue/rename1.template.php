<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/rename1.template.php begin -->
<?php for ($i=1; $i<=sizeof($list["all"]); $i++) { ?>
<?php		printDirFileProperties($i, $list["all"][$i], "hidden", ""); ?>
	<?php echo __("Old name: "); ?><b><?php echo $list["all"][$i]["dirfilename"]; ?></b><br />
	<?php echo __("New name: "); ?><input type="text" class="input" name="newNames[<?php echo $i; ?>]" value="<?php echo $list["all"][$i]["dirfilename_html"]; ?>" /><br /><br />
<?php } // end for ?>
<!-- Template /skins/blue/rename1.template.php end -->
