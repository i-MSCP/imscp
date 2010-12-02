<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
	<head>
		<title>{TR_MAIN_INDEX_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex" />
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta http-equiv="Content-Script-Type" content="text/javascript" />
        <link href="themes/default/css/login-imscp.css" rel="stylesheet" type="text/css" />
        <!--[if IE 6]>
        <script type="text/javascript" src="themes/default/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
	</head>
	<body onload="document.lostpwd_frm.uname.focus()" class="body">
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
					<form name="lostpwd_frm" action="lostpassword.php" method="post" >
						<fieldset style="top:140px;">
							<label>{TR_IMGCAPCODE}</label>
							<label>{TR_CAPCODE}:<input type="text" name="uname" id="uname" value="" maxlength="255" tabindex="1" /></label>
							<label>{TR_USERNAME}:<input type="text" name="capcode" value="" maxlength="255" tabindex="2" /></label>
							<div class="buttons" style="margin-top:10px;">
								<input type="submit" name="back" value="{TR_BACK}" tabindex="2" onclick="location.href='index.php';return false"/>&nbsp;&nbsp;&nbsp;
								<input style="padding-left:3px;" type="submit" name="Submit" value="{TR_SEND}" tabindex="3" />
							</div>
						</fieldset>
					</form>
				</div>
				<div class="info" style="width:450px;position: relative;top:-50%;margin-left:auto;margin-right: auto;">{TR_IMGCAPCODE_DESCRIPTION}</div>
			</div>
		</div>
	</body>
</html>
