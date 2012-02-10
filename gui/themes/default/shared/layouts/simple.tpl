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
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.imscpTooltip-min.js"></script>
	<!--[if IE 6]>
	<script type="text/javascript" src="{THEME_COLOR_PATH}/js/jquery.bgiframe-2.1.2.js"></script>
	<script type="text/javascript" src="/themes/default/js/DD_belatedPNG_0.0.8a-min.js"></script>
	<script type="text/javascript">
		DD_belatedPNG.fix('.login #logo, #loginBox, .webmail, .pma, .filemanager, .i_lock, .i_unlock, .error');
	</script>
	<![endif]-->
	<script type="text/javascript">
	/*<![CDATA[*/
		$(document).ready(function() {
			setTimeout(function(){$('.error, .success').fadeOut(2000);},3000);
			$('.body a').imscpTooltip();
			$('button').button({icons: {secondary: "ui-icon-triangle-1-e"}});
			$('input').first().focus();
		});
	/*]]>*/
	</script>
</head>
<body class="{CONTEXT_CLASS} no_menu">
	<div id="header">
		<div id="logo"><span>{productLongName}</span></div>
		<div id="copyright"><span><a href="{productLink}" target="blank">{productCopyright}</a></span></div>
	</div>
	<div id="messageContainer">
	<!-- BDP: page_message -->
		<div id="message" class="{MESSAGE_CLS}">{MESSAGE}</div>
	<!-- EDP: page_message -->
	</div>
	<div class="body">
		{LAYOUT_CONTENT}
	</div>
</body>
</html>
