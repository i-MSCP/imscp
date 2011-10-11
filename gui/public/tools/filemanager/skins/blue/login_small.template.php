<?php defined("NET2FTP") or die("Direct access to this location is not allowed."); ?>
<!-- Template /skins/blue/login_small.template.php begin -->
<form id="<?php echo $formname; ?>" action="<?php echo $net2ftp_globals["action_url"]; ?>" <?php echo $enctype; ?> method="post">
<?php	printLoginInfo(); ?>
<input type="hidden" name="state"          value="<?php echo $go_to_state;  ?>" />
<input type="hidden" name="state2"         value="<?php echo $go_to_state2; ?>" />
<input type="hidden" name="directory"      value="<?php echo $net2ftp_globals["directory_html"]; ?>" />
<input type="hidden" name="entry"          value="<?php echo $net2ftp_globals["entry_html"]; ?>" />
<input type="hidden" name="screen"         value="<?php echo $net2ftp_globals["screen_html"]; ?>" />

<input type="hidden" name="command"        value="<?php echo htmlEncode2($command); ?>" />
<input type="hidden" name="ftpserver2"     value="<?php echo htmlEncode2($ftpserver2); ?>" />
<input type="hidden" name="ftpserverport2" value="<?php echo htmlEncode2($ftpserverport2); ?>" />
<input type="hidden" name="username2"      value="<?php echo htmlEncode2($username2); ?>" />
<input type="hidden" name="password2"      value="<?php echo htmlEncode2($password2); ?>" />
<input type="hidden" name="newNames"       value="<?php echo htmlEncode2($newNames); ?>" />
<input type="hidden" name="textareaType"   value="<?php echo htmlEncode2($textareaType);  ?>" />
<input type="hidden" name="text"           value="<?php echo htmlEncode2($text); ?>" />
<input type="hidden" name="text_splitted"  value="<?php echo htmlEncode2($text_splitted); ?>" />
<input type="hidden" name="url"            value="<?php echo htmlEncode2($url); ?>" />

<?php if (is_array($searchoptions) == true) { ?>
<?php		while (list($key, $value) = each($searchoptions)) { ?>
<input type="hidden" name="searchoptions[<?php echo htmlEncode2($key); ?>]" value="<?php echo htmlEncode2($value); ?>" />
<?php		} // end while ?>
<?php	} // end if ?>

<?php if (is_array($text_splitted) == true) { ?>
<?php		while (list($key, $value) = each($text_splitted)) { ?>
<input type="hidden" name="text_splitted[<?php echo htmlEncode2($key); ?>]" value="<?php echo htmlEncode2($value); ?>" />
<?php		} // end while ?>
<?php	} // end if ?>

<?php if (is_array($zipactions) == true) { ?>
<?php		while (list($key, $value) = each($zipactions)) { ?>
<input type="hidden" name="zipactions[<?php echo htmlEncode2($key); ?>]" value="<?php echo htmlEncode2($value); ?>" />
<?php		} // end while ?>
<?php	} // end if ?>

<?php if (is_array($zipactions) == true) { ?>
<?php 	for ($i=1; $i<=sizeof($list["all"]); $i++) { ?>
<?php 		while (list($key, $value) = each($list["all"][$i])) { ?>
<input type="hidden" name="list[<?php echo $i; ?>][<?php echo htmlEncode2($key); ?>]" value="<?php echo htmlEncode2($value); ?>" />
<?php			} // end while ?>
<?php 	} // end for ?>
<?php	} // end if ?>

<div style="border: 1px solid black; background-color: #DDDDDD; width: 50%; margin-<?php echo __("left"); ?>: auto; margin-<?php echo __("right"); ?>: auto; margin-top:50px; padding: 10px;">

<?php echo $message; ?><br />

<?php if ($errormessage != "") { ?>
<span style="color: red;"><?php echo $errormessage; ?></span><br />
<?php } // end if ?>

<br />

<table border="0" cellspacing="0" cellpadding="0" style="width: 90%; margin-left: auto; margin-right: auto;">
	<tr style="vertical-align: middle;">
		<td rowspan="2" style="width: 20%;"><?php printTitleIcon(); ?></td>
		<td><?php echo __("Username"); ?></td>
		<td><input type="text"     class="input" name="<?php echo $username_fieldname; ?>" value="<?php echo $username_value; ?>" /></td>
	</tr>
	<tr style="vertical-align: middle;">
		<td><?php echo __("Password"); ?></td>
		<td><input type="password" class="input" name="<?php echo $password_fieldname; ?>" value="<?php echo $password_value; ?>" /></td>
	</tr>
	<tr style="vertical-align: middle;">
		<td></td>
		<td colspan="2" style="text-align: center;">
			<input type="submit" class="button" style="margin-top: 10px;" value="<?php echo $button_text; ?>" title="<?php echo $button_text; ?> (accesskey l)" accesskey="l"/>
		</td>
	</tr>

</table>
</div>
</form>
<script type="text/javascript"><!--
  document.forms['LoginForm'].<?php echo $focus; ?>.focus();
//--></script>
<!-- Template /skins/blue/login_small.template.php end -->
