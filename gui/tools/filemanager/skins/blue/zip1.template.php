<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/zip1.template.php begin -->
<div style="border-color: #000000; border-style: solid; border-width: 1px; padding: 10px; margin-<?php echo __("right"); ?>: 100px; margin-bottom: 10px;">
<input type="checkbox" name="zipactions[save]" value="yes" /> <?php echo __("Save the zip file on the FTP server as:"); ?> <input type=text class="input" name="zipactions[save_filename]" value="<?php echo $zipfilename; ?>" />
</div> &nbsp; 

<div style="border-color: #000000; border-style: solid; border-width: 1px; padding: 10px; margin-<?php echo __("right"); ?>: 100px; margin-bottom: 10px;">
<input type="checkbox" name="zipactions[email]" value="yes" /> <?php echo __("Email the zip file in attachment to:"); ?> <input type="text" class="input" name="zipactions[email_to]" value="" /><br /><br />
<?php echo __("Note that sending files is not anonymous: your IP address as well as the time of the sending will be added to the email."); ?><br /><br />
<?php echo __("Some additional comments to add in the email:"); ?><br />
<textarea name="zipactions[message]" class="edit" rows="5" cols="60" wrap="off"></textarea>
</div>

<?php for ($i=1; $i<=sizeof($list["all"]); $i++) { ?>
<?php		printDirFileProperties($i, $list["all"][$i], "hidden", ""); ?>
<?php	} // end for ?>

<!-- Template /skins/blue/zip1.template.php end -->
