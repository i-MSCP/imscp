			<form id="LoginForm" action="<?php echo $net2ftp_globals["action_url"]; ?>" method="post" onsubmit="return CheckInput(this);">
				<table style="background-color: #EEEEEE; border: 1px solid #666666;" border="0" cellpadding="2" cellspacing="0">
					<tbody>
<?php /* ----- FTP server ----- */ ?>
						<tr>
							<td style="padding-<?php echo __("left"); ?>: 10px; vertical-align: top; width: 20%; padding-top:5px;">
							</td>
						</tr>
<?php /* ----- Username ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("Username"); ?></td>
							<td style="width: 46%;"><input name="username" value="<?php echo $username; ?>" type="text" style="width:230px;" /></td>
<?php /* ----- Anonymous checkbox ----- */ ?>
							<td style="width: 27%;"></td>
						</tr>
<?php /* ----- Password ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("Password"); ?></td>
							<td><input name="password" value="<?php echo $password; ?>" type="password" style="width:230px;" /></td>
<?php /* ----- Passive mode ----- */ ?>
							<td><span><input name="passivemode" value="yes" type="checkbox" <?php echo $passivemode["checked"]; ?> />&nbsp; <?php echo __("Passive mode"); ?></span></td>
						</tr>
<?php /* ----- Initial directory ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("Initial directory"); ?></td>
							<td><input name="directory" value="<?php echo $directory; ?>" type="text" style="width:230px;" /></td>
<?php /* ----- SSL ----- */ ?>
<?php							if ($sslconnect["inputType"] == "checkbox") { ?>
								<td><span><input name="sslconnect" value="yes" type="checkbox" <?php echo $sslconnect["checked"]; ?> />&nbsp; SSL</span></td>
<?php							} else { ?>
								<td></td>
<?php							} ?>
						</tr>
<?php /* ----- Language ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("Language"); ?></td>
							<td>
<?php								printLanguageSelect("language", $language_onchange, "width:120px;", "input_select"); ?>
							</td>
							<td></td>
						</tr>
<?php /* ----- FTP mode radio button ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("FTP mode"); ?></td>
							<td colspan="2"> 
								<input name="ftpmode" value="binary"    <?php echo $ftpmode["binary"]; ?>    type="radio" />&nbsp; Binary &nbsp; &nbsp;
								<input name="ftpmode" value="automatic" <?php echo $ftpmode["automatic"]; ?> type="radio" />&nbsp; <?php echo __("Automatic"); ?>
							</td>
						</tr>
<?php /* ----- Login button ----- */ ?>
						<tr>
							<td></td>
							<td colspan="2" style="padding-<?php echo __("left"); ?>: 40px;"> 
								<input type="submit" class="button" name="Login" value="<?php echo __("Login"); ?>" alt="<?php echo __("Login"); ?>" />
							</td>
						</tr>
<?php /* ----- Clear cookies and Admin link ----- */ ?>
						<tr style="vertical-align: middle; text-align: left;"> 
							<td colspan="1" style="text-align: <?php echo __("left"); ?>; padding-<?php echo __("left"); ?>: 20px; padding-bottom: 5px;"></td>
							<td colspan="2" style="text-align: <?php echo __("right"); ?>; padding-<?php echo __("right"); ?>: 20px; padding-bottom: 5px;">
								<a href="javascript:ClearCookies();"><?php echo __("Clear cookies"); ?></a> &nbsp;
							</td>
						</tr>
					</tbody>
				</table>
				<input type="hidden" name="state"     value="browse" />
				<input type="hidden" name="state2"    value="main" />
			</form>
<script type="text/javascript"><!--
	document.forms['LoginForm'].<?php echo $focus; ?>.focus();
//--></script>
