<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<title>{TR_MAIN_INDEX_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex">
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
		<meta http-equiv="Content-Style-Type" content="text/css">
		<meta http-equiv="Content-Script-Type" content="text/javascript">
		<link href="{THEME_COLOR_PATH}/css/login-imscp.css" rel="stylesheet" type="text/css">
		<!--[if IE 6]>
			<script type="text/javascript" src="themes/default/js/DD_belatedPNG_0.0.8a-min.js"></script>
			<script type="text/javascript">
				DD_belatedPNG.fix('*');
			</script>
		<![endif]-->
	</head>
	<body onload="document.login_frm.uname.focus()" class="body">
		<div class="header">
			<div id="logo">
				<div id="logoInner">
					<img src="themes/default/images/imscp_logo32.png" alt="{productLongName}" />
					<span>{productLongName}</span>
				</div>
			</div>
			<div id="copyright">
				<div id="copyrightInner">
					<a href="{productLink}" target="blank">{productCopyright}</a>
				</div>
			</div>
		</div>
		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS} message">{MESSAGE}</div>
		<!-- EDP: page_message -->
		<div id="outer">

			<div id="middle">
				<div id="inner">
					<form name="login_frm" action="index.php" method="post">
						<fieldset>
							<label>{TR_USERNAME}:<input type="text" name="uname" id="uname" value="" maxlength="255" tabindex="1"></label>
							<label>{TR_PASSWORD}:<input type="password" name="upass" id="upass" value="" maxlength="255" tabindex="2"></label>
							<div class="buttons">
							<!-- BDP: lostpwd_button -->
								<input style="text-align:left;padding-left:15px;" type="button" name="lostpwd" value="{TR_LOSTPW}" tabindex="2" onclick="location.href='lostpassword.php';return false"/>&nbsp;&nbsp;&nbsp;
							<!-- EDP: lostpwd_button -->
								<input type="submit" name="Submit" value="{TR_LOGIN}" tabindex="3" />
							</div>
							<!-- /* Uncomment this to show the ssl switch */
							<div style="margin-top:15px;">
								<a class="icon i_lock" href="{TR_SSL_LINK}" title="{TR_SSL_DESCRIPTION}">{TR_SSL_DESCRIPTION}</a>
							</div> -->
						</fieldset>
					</form>
					<div class="toolsbox">
    					<ul class="icons">
       						<li><a class="pma" href="{TR_PMA_LINK}">PhpMyAdmin</a></li>
       						<li><a class="filemanager" href="{TR_FTP_LINK}">FileManager</a></li>
       						<li><a class="webmail" href="{TR_WEBMAIL_LINK}">Webmail</a></li>
   						</ul>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
