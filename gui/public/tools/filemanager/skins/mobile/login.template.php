<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/mobile/login.template.php begin -->
<h2>net2ftp - web based FTP</h2>
			<form id="LoginForm" action="<?php echo $net2ftp_globals["action_url"]; ?>" method="post" onsubmit="return CheckInput(this);">
			<table border="0" cellspacing="0" cellpadding="1">
				<tr>
					<td><?php echo __("FTP server"); ?></td>
					<td colspan="2">
<?php /* ----- FTP server ----- */ ?>
<?php 				if ($ftpserver["inputType"] == "text") { ?>
							<input type="text" name="ftpserver" class="input" size="10" value="<?php echo $ftpserver["list"][1]; ?>" />
<?php 				} elseif ($ftpserver["inputType"] == "select") { ?>
						<select name="ftpserver">
<?php 				for ($i=1; $i<=sizeof($ftpserver["list"]); $i=$i+1) { ?>
							<option value="<?php echo $ftpserver["list"][$i]; ?>" <?php echo $ftpserver["list"][$i]["selected"]; ?>><?php echo $ftpserver["list"][$i]; ?></option>
<?php					} // end for ?>
						</select>
<?php					} elseif ($ftpserver["inputType"] == "hidden") { ?>
							<input type="hidden" name="ftpserver" value="<?php echo $ftpserver["list"][1]; ?>">
							<b><?php echo $ftpserver["list"][1]; ?></b>
<?php 				} ?>
<?php /* ----- FTP server port ----- */ ?>
<?php					if ($ftpserverport["inputType"] == "text") { ?>
	 						<input type="text" class="input" size="3" maxlength="5" name="ftpserverport" value="<?php echo $ftpserverport["value"]; ?>" />
<?php 				} else { ?>
							<input type="hidden" name="ftpserverport" value="<?php echo $ftpserverport["value"]; ?>">
<?php 				} ?>
					</td>
				</tr>
				<tr style="vertical-align: middle;">
					<td><?php echo __("Username"); ?></td>
<?php /* ----- Username ----- */ ?>
					<td><input type="text" class="input" size="10" name="username" value="<?php echo $username; ?>" /></td>
<?php /* ----- Anonymous checkbox ----- */ ?>
					<td><span style="font-size: 80%;"><input type="checkbox" name="anonymous" value="1" onclick="do_anonymous(form);" /> <?php echo __("Anonymous"); ?></span></td>
				</tr>
				<tr style="vertical-align: middle;">
					<td><?php echo __("Password"); ?></td>
<?php /* ----- Password ----- */ ?>
					<td><input type="password" name="password" class="input" size="10" value="<?php echo $password; ?>" /></td>
<?php /* ----- Passive mode checkbox ----- */ ?>
					<td><span style="font-size: 80%;"><input type="checkbox" name="passivemode" value="yes" <?php echo $passivemode["checked"]; ?> /> <?php echo __("Passive mode"); ?></span></td>
				</tr>
				<tr style="vertical-align: middle;">
					<td><?php echo __("Directory"); ?></td>
<?php /* ----- Initial directory ----- */ ?>
					<td><input type="text" class="input" size="10" name="directory" value="<?php echo $directory; ?>" /></td>

<?php /* ----- SSL ----- */ ?>
<?php				if ($sslconnect["inputType"] == "checkbox") { ?>
						<td><span style="font-size: 80%;"><input type="checkbox" name="sslconnect" value="yes" <?php echo $sslconnect["checked"]; ?> /> SSL</span></td>
<?php 			} else { ?>
						<td></td>
<?php 			} ?>
				</tr>
				<tr style="vertical-align: middle;">
					<td><?php echo __("Language"); ?></td>
					<td>
<?php /* ----- Language ----- */ ?>
<?php						printLanguageSelect("language", $language_onchange, "", ""); ?>
					</td>
					<td></td>
				</tr>
				<tr style="vertical-align: middle;">
					<td><?php echo __("Skin"); ?></td>
					<td>
<?php /* ----- Skin ----- */ ?>
<?php						printSkinSelect("skin", $skin_onchange, "", ""); ?>
					</td>
					<td></td>
				</tr>
					<tr style="vertical-align: middle;">
						<td><?php echo __("FTP mode"); ?></td>
<?php /* ----- FTP mode radio button ----- */ ?>
						<td colspan="2"><input type="radio" name="ftpmode" value="automatic" <?php echo $ftpmode["automatic"]; ?> /> <?php echo __("Automatic"); ?> &nbsp; <input type="radio" name="ftpmode" value="binary" <?php echo $ftpmode["binary"]; ?> /> Binary</td>
					</tr>
				<tr style="vertical-align: middle;">
					<td colspan="2" style="text-align: center;">
						<input type="hidden" name="state"     value="browse">
						<input type="hidden" name="state2"    value="main">
						<input type="submit" class="button"   value="<?php echo __("Login"); ?>" title="<?php echo __("Login"); ?> (accesskey l)" accesskey="l"/>
					</td>
					<td></td>
				</tr>
			</table>
			</form>
			<script type="text/javascript"><!--
			document.forms['LoginForm'].<?php echo $focus; ?>.focus();
			//--></script>
<?php require_once($net2ftp_globals["application_skinsdir"] . "/" . $net2ftp_globals["skin"] . "/footer.template.php"); ?>
<!-- Template /skins/mobile/login.template.php end -->
