<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
	<head>
		<title>{TR_MAIN_INDEX_PAGE_TITLE}</title>
		<meta name="robots" content="nofollow, noindex">
		<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}">
		<meta http-equiv="Content-Style-Type" content="text/css">
		<meta http-equiv="Content-Script-Type" content="text/javascript">
		<!--<link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css">-->
        <link href="themes/default/css/login-imscp.css" rel="stylesheet" type="text/css">
        <!--[if IE 6]>
        <script type="text/javascript" src="../themes/default/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
	</head>
	<body onload="document.frm.uname.focus()">
		<div id="outer">
			<div id="middle">
				<div id="inner">
					<form name="lpwd_frm" action="lostpassword.php" method="post" >
						<fieldset style="top:140px;">
							<label>{TR_IMGCAPCODE}</label>
							<label>{TR_CAPCODE}:<input type="text" name="uname" id="uname" value="" maxlength="255" tabindex="1"></label>
							<label>{TR_USERNAME}:<input type="password" name="capcode" value="" maxlength="255" tabindex="2"></label>
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
