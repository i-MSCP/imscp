<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/error.template.php begin -->
<table class="error-table" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td class="error-header">
		<?php echo __("An error has occured"); ?>:
		</td>
	</tr>
	<tr>
		<td class="error-text">
			<?php echo $net2ftp_result["errormessage"]; ?><br /><br />
			<a href="javascript:top.history.back()" style="font-size: 130%; font-weight: bold;"><?php echo __("Go back"); ?></a> or 
			<a href="index.php?state=clearcookies"  style="font-size: 130%; font-weight: bold;"><?php echo __("Go to the login page"); ?></a><br /><br />
		</td>
	</tr>
	<tr>
		<td class="error-text" style="font-size: 80%;">
			<b><u>Technical information for developers:</u></b><br /><br />
			The error occured in file <b><?php echo $net2ftp_result["file"]; ?></b> on line <b><?php echo $net2ftp_result["line"]; ?></b>.<br />
			<ul>
<?php				for ($i=0; $i<sizeof($net2ftp_result["debug_backtrace"]); $i++) { ?>
					<li> function <?php echo $net2ftp_result["debug_backtrace"][$i]["function"]; ?> <span style=\"font-size: 75%\">(<?php echo $net2ftp_result["debug_backtrace"][$i]["file"]; ?> on line <?php echo $net2ftp_result["debug_backtrace"][$i]["line"]; ?>)</span></li>
					<ul>
<?php					if (isset($net2ftp_result["debug_backtrace"][$i]["args"]) == true) { ?>
<?php						for ($j=0; $j<count($net2ftp_result["debug_backtrace"][$i]["args"]); $j++) { ?>
						<li> argument <?php echo $j; ?>: <?php echo $net2ftp_result["debug_backtrace"][$i]["args"][$j]; ?></li>
<?php						} // end for ?>
<?php					} // end if ?>
					</ul>
<?php				} // end for ?>
			</ul>
		</td>
	</tr>
</table><br /><br />
<!-- Template /skins/blue/error.template.php end -->
