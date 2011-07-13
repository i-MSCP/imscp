<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/upload2.template.php begin -->
<table style="border-color: #000000; border-style: solid; border-width: 1px; padding: 10px; margin-<?php echo __("right"); ?>: 100px; margin-bottom: 30px;">
	<tr>
		<td>
			<b><u>Checking files:</u></b> <br />
			<ul>
<?php			for ($i=0; $i<sizeof($net2ftp_output["acceptFiles"]); $i++) { ?>
				<li> <?php echo $net2ftp_output["acceptFiles"][$i]; ?> </li>
<?php 		} // end for ?>
			</ul>
			<b><u>Transferring files to the FTP server:</u></b> <br />
			<ul>
<?php			if (isset($net2ftp_output["ftp_transferfiles"]) == true) {
				for ($i=0; $i<sizeof($net2ftp_output["ftp_transferfiles"]); $i++) {
					echo "<li>" . $net2ftp_output["ftp_transferfiles"][$i] . "</li>\n";
	 			} // end for
			} // end if
			if (isset($net2ftp_output["ftp_unziptransferfiles"]) == true) {
				for ($i=0; $i<sizeof($net2ftp_output["ftp_unziptransferfiles"]); $i++) {
					if ($net2ftp_output["ftp_unziptransferfiles"][$i] == "<ul>" || $net2ftp_output["ftp_unziptransferfiles"][$i] == "</ul>") { echo $net2ftp_output["ftp_unziptransferfiles"][$i]; }
					else { echo "<li>" . $net2ftp_output["ftp_unziptransferfiles"][$i] . "</li>\n"; }
				} // end for
			} // end if 
?>
			</ul>
		</td>
	</tr>
</table>
<?php	require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/upload1.template.php"); ?>
<!-- Template /skins/blue/upload2.template.php end -->
