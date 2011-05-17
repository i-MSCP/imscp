			<form id="LoginForm" action="<?php echo $net2ftp_globals["action_url"]; ?>" method="post" onsubmit="return CheckInput(this);">
				<table style="background-color: #bbd2e0; border: 1px solid #003250;" border="0" cellpadding="2" cellspacing="0">
					<tbody>
<?php /* ----- FTP server ----- */ ?>
						<tr>
							<td style="padding-<?php echo __("left"); ?>: 10px; vertical-align: top; width: 20%; padding-top:5px;"><?php echo __("FTP server"); ?></td>
							<td colspan="2" style="vertical-align: top; padding-top:5px;">
<?php								if ($ftpserver["inputType"] == "text") { ?>
									<input type="text" name="ftpserver" value="<?php echo $ftpserver["list"][1]; ?>" style="width:230px;" />
<?php								} elseif ($ftpserver["inputType"] == "select") { ?>
									<select name="ftpserver">
<?php									for ($i=1; $i<=sizeof($ftpserver["list"]); $i=$i+1) { ?>
										<option value="<?php echo $ftpserver["list"][$i]; ?>" <?php echo $ftpserver["list"][$i]["selected"]; ?>><?php echo $ftpserver["list"][$i]; ?></option>
<?php									} // end for ?>
									</select>
<?php								} elseif ($ftpserver["inputType"] == "hidden") { ?>
									<input type="hidden" name="ftpserver" value="<?php echo $ftpserver["list"][1]; ?>" />
									<b><?php echo $ftpserver["list"][1]; ?></b>
<?php								} ?>
<?php /* ----- FTP server port ----- */ ?>  
<?php								if ($ftpserverport["inputType"] == "text") { ?>
									&nbsp; port <input size="3" maxlength="5" name="ftpserverport" value="<?php echo $ftpserverport["value"]; ?>" type="text" /> 
<?php								} else { ?>
									<input type="hidden" name="ftpserverport" value="<?php echo $ftpserverport["value"]; ?>" />
<?php								} ?>
<?php /* ----- Example ----- */ ?>  
<?php								if ($ftpserver["inputType"] == "text") { ?>
									<div style="font-size: 0.9em;"><?php echo __("Example"); ?>: ftp.server.com, 192.123.45.67</div>
<?php								} ?>
							</td>
						</tr>
<?php /* ----- Username ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("Username"); ?></td>
							<td style="width: 46%;"><input name="username" value="<?php echo $username; ?>" type="text" style="width:230px;" /></td>
<?php /* ----- Anonymous checkbox ----- */ ?>
							<td style="width: 27%;"><input name="anonymous" value="1" onclick="do_anonymous(form);" type="checkbox" />&nbsp; <?php echo __("Anonymous"); ?></td>
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
<?php /* ----- Skin ----- */ ?>
						<tr style="vertical-align: middle;"> 
							<td style="padding-<?php echo __("left"); ?>: 10px;"><?php echo __("Skin"); ?></td>
							<td>
<?php								printSkinSelect("skin", $skin_onchange, "width:120px;", "input_select"); ?>
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
								<a href="<?php echo $admin_url; ?>"><?php echo __("Admin"); ?></a>
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
