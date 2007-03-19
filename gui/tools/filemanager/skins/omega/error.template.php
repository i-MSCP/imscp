<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/omega/error.template.php begin -->
<div align="center" style="text-align:left; padding-left:20px; background-color:#FFFFFF;">
<script type="text/javascript" src="<?php echo $net2ftp_globals["application_rootdir_url"]; ?>/modules/edit/edit.js"></script>
		<h1><?php echo __("An error has occured"); ?></h1> 
		<p><?php echo $net2ftp_result["errormessage"]; ?><br /><br />
		<a href="javascript:top.history.back();"><?php echo __("Go back"); ?></a> or <a href="index.php?state=clearcookies"><?php echo __("Go to the login page"); ?></a></p><br />
		<div id="error_hidden">
			<a href="javascript:toggleTextarea('error');">View technical details</a>
		</div>
		<div id="error_shown" style="display: none;">
			<a href="javascript:toggleTextarea('error');">Hide technical details</a><br /><br />
			The error occured in file <b><?php echo $net2ftp_result["file"]; ?></b> on line <b><?php echo $net2ftp_result["line"]; ?></b>.<br />
			<ul>
<?php			for ($i=0; $i<sizeof($net2ftp_result["debug_backtrace"]); $i++) { ?>
				<li> function <?php echo $net2ftp_result["debug_backtrace"][$i]["function"]; ?> <span style="font-size: 75%">(<?php echo $net2ftp_result["debug_backtrace"][$i]["file"]; ?> on line <?php echo $net2ftp_result["debug_backtrace"][$i]["line"]; ?>)</span>
<?php				if (isset($net2ftp_result["debug_backtrace"][$i]["args"]) == true && sizeof($net2ftp_result["debug_backtrace"][$i]["args"]) > 0) { ?>
				<ul>
<?php					for ($j=0; $j<sizeof($net2ftp_result["debug_backtrace"][$i]["args"]); $j++) { ?>
					<li> argument <?php echo $j; ?>: <?php echo $net2ftp_result["debug_backtrace"][$i]["args"][$j]; ?></li>
<?php					} // end for ?>
				</ul>
<?php				} // end if ?>
				</li>
<?php			} // end for ?>
			</ul>
		</div>
	</div>
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/omega/error.template.php end -->
</div>