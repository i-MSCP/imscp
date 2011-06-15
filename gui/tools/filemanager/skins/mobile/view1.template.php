<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/mobile/view1.template.php begin -->

<?php /* ----- Text ----- */ ?>
<?php if ($filetype == "text") { ?>
		<?php echo $geshi_text; ?>

		<div style="margin-top: 10px; text-align: center; font-size: 80%;">
		<?php echo __("Syntax highlighting powered by %1\$s", $powered_by); ?>
		</div>
<?php } // end if

	/* ----- Image ----- */
	elseif ($filetype == "image") { ?>
	<div style="font-size: 80%; text-align: center;">
	<img src="<?php echo $image_url; ?>" alt="<?php echo $image_alt; ?>" style="border: 1px solid black;" /><br /><br />
	<?php echo __("To save the image, right-click on it and choose 'Save picture as...'"); ?><br />
	</div>
<?php } // end elseif

	/* ----- Flash ----- */
	elseif ($filetype == "flash") { ?>
		<div style="font-size: 80%; text-align: center;">
		<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" id="flashfile">
		<param name="movie" value="">
		<param name="quality" value="high">
		<param name="bgcolor" value="#FFFFFF">
		<embed src="<?php echo $flash_url; ?>" quality="high" bgcolor="#FFFFFF" NAME="flashfile" ALIGN TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer">
		</object>
		</div>


<?php	} // end else?>

<!-- Template /skins/mobile/view1.template.php end -->
