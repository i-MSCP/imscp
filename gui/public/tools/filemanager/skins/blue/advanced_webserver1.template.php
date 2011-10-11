<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/advanced_webserver1.template.php begin -->

<ul>

	<li>
<?php	echo __("Checking if the FTP module of PHP is installed: "); ?>
<?php	if (function_exists("ftp_connect") == true) { ?> <span style="color: green; font-weight: bold;"><?php echo __("yes"); ?></span> <?php }
	else                                        { ?> <span style="color: red;   font-weight: bold;"><?php echo __("no - please install it!"); ?></span> <?php } ?>
	</li>
	<li><?php echo __("Checking the permissions of the directory on the web server: a small file will be written to the /temp folder and then deleted."); ?></li>
	<li><ul> 
		<li> 
		<?php echo __("Creating filename: "); ?>
		<?php 	if ($tempfilename == true) { ?> <span style="color: green; font-weight: bold;"><?php echo __("OK. Filename: %1\$s", $tempfilename); ?></span> <?php }
		else                       { ?> <span style="color: red;   font-weight: bold;"><?php echo __("not OK"); ?></span> <?php } ?>
		</li>

		<li> 
		<?php echo __("Opening the file in write mode: "); ?>
<?php 	if ($handle == true) { ?> <span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span> <?php }
		else                 { ?> <span style="color: red;   font-weight: bold;"><?php echo __("not OK. Check the permissions of the %1\$s directory"); ?></span> <?php } ?>
		</li>

		<li> 
		<?php echo __("Writing some text to the file: "); ?>
<?php 	if ($success1 == true) { ?> <span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span> <?php }
		else                   { ?> <span style="color: red;   font-weight: bold;"><?php echo __("not OK"); ?></span> <?php } ?>
		</li>

		<li> 
		<?php echo __("Closing the file: "); ?>
<?php 	if ($success2 == true) { ?> <span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span> <?php }
		else                   { ?> <span style="color: red;   font-weight: bold;"><?php echo __("not OK"); ?></span> <?php } ?>
		</li>

		<li> 
		<?php echo __("Deleting the file: "); ?>
<?php 	if ($success3 == true) { ?> <span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span> <?php }
		else                   { ?> <span style="color: red;   font-weight: bold;"><?php echo __("not OK"); ?></span> <?php } ?>
		</li>

	</ul></li>

	<li><?php echo __("Testing the FTP functions"); ?></li>
	<li><ul>
		<li> 
<?php		echo __("Connecting to a test FTP server: "); ?>
<?php		if ($conn_id == true) { ?>
			<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php 	} else { ?>
			<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php 	} // end if else ?>
		</li>

		<li>
<?php		echo __("Logging into the FTP server: "); ?>
<?php		if ($ftp_login_result == true) { ?>
			<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php 	} else { ?>
			<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php 	} // end if else ?>
		</li>

		<li>
<?php 	echo __("Setting the passive mode: "); ?>
<?php 	if ($ftp_pasv_result == true) { ?>
			<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php 	} else { ?>
			<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php 	} // end if else ?>
		</li>

		<li>
<?php 	echo __("Getting the FTP server system type: "); ?>
<?php 	if ($ftp_systype_result != false) { ?>
			<span style="color: green; font-weight: bold;"><?php echo $ftp_systype_result; ?></span>
<?php 	} else { ?>
			<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php 	} // end if else ?>
		</li>

		<li>
<?php 	echo __("Changing to the directory %1\$s: ", $troubleshoot_directory); ?>
<?php 	if ($ftp_chdir_result == true) { ?>
			<span style="color: green; font-weight: bold;"><?php echo __("OK"); ?></span>
<?php 	} else { ?>
			<span style="color: red; font-weight: bold;"><?php echo __("not OK"); ?></span>
<?php		} // end if else ?>
		</li>

		<li>
<?php 	echo __("Getting the raw list of directories and files: "); ?><br />
<?php 	print_r($ftp_rawlist_result); ?>
		</li>

		<li>
<?php 	echo __("Getting the raw list of directories and files: "); ?><br />
<?php 	for($i=0; $i<count($parsedlist); $i++) {
			echo "<u>Line $i</u><br />\n";
			print_r($parsedlist[$i]);
			echo "<br />";
		} // End for 
?>
		</li>
	</ul></li>

<!-- Template /skins/blue/advanced_webserver1.template.php end -->
