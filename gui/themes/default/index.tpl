<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
<head>
	<title>{TR_PAGE_TITLE}</title>
	<meta name="robots" content="nofollow, noindex" />
	<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
	<link href="{THEME_COLOR_PATH}/css/{THEME_COLOR}.css" rel="stylesheet" type="text/css" />
	<link href="{THEME_COLOR_PATH}/css/jquery-ui-{THEME_COLOR}.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.ui.js"></script>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltips.js"></script>
	<!--[if IE 6]>
	<script type="text/javascript" src="/themes/default/js/DD_belatedPNG_0.0.8a-min.js"></script>
	<script type="text/javascript">
		DD_belatedPNG.fix('.login #logo, #loginBox, .webmail, .pma, .filemanager, .i_lock, .i_unlock, .error');
	</script>
	<![endif]-->
	<script type="text/javascript">
	/*<![CDATA[*/
		$(document).ready(function() {
			setTimeout(function(){$('.error').fadeOut(2000);},3000);
			$('a').iMSCPtooltips();
			$('button').button({icons: {secondary: "ui-icon-triangle-1-e"}});
			$('input[name="uname"]').focus();
		});
	/*]]>*/
	</script>
</head>
<body class="login no_menu no_footer">
	<div id="header">
		<div id="logo"><span>{productLongName}</span></div>
		<div id="copyright"><span><a href="{productLink}" target="blank">{productCopyright}</a></span></div>
	</div>
	<div id="messageContainer">
	<!-- BDP: page_message -->
		<div id="message" class="{MESSAGE_CLS}">{MESSAGE}</div>
	<!-- EDP: page_message -->
	</div>
	<div id="body">
		<div class="clearfix">
			<div id="loginBox">
				<form name="loginFrm" action="index.php" method="post">
					<label for="uname"><span>{TR_USERNAME}</span><input type="text" name="uname" id="uname" tabindex="1"/></label>
					<label for="upass"><span>{TR_PASSWORD}</span><input type="password" name="upass" id="upass" tabindex="2"/></label>
					<div class="button">
						<!-- BDP: lostpwd_button -->
						<button name="lostpwd" type="button" tabindex="4" onclick="location.href='lostpassword.php'">{TR_LOSTPW}</button>
						<!-- EDP: lostpwd_button -->
						<button  name="login" type="submit" tabindex="3">{TR_LOGIN}</button>
					</div>
					<!-- BDP: ssl_support -->
					<a style="float:none;" class="icon {SSL_IMAGE_CLASS}" href="{SSL_LINK}" title="{TR_SSL_DESCRIPTION}">{TR_SSL}</a>
					<!-- EDP: ssl_support -->
				</form>
			</div>
		</div>
		<div id="toolbox">
			<ul>
				<li><a class="icon_big pma" href="{TR_PMA_LINK}" target="blank" title="Login into PhpMyAdmin">{TR_PHPMYADMIN}</a></li>
				<li><a class="icon_big filemanager" href="{TR_FTP_LINK}" target="blank" title="Login into the File manager">FileManager</a></li>
				<li><a class="icon_big webmail" href="{TR_WEBMAIL_LINK}" target="blank" title="Login into the Webmail">{TR_WEBMAIL}</a></li>
			</ul>
		</div>
	</div>
</body>
</html>
