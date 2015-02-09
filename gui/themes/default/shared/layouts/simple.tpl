<!DOCTYPE html>
<html>
<head>
<title>{TR_PAGE_TITLE}</title>
<meta charset="{THEME_CHARSET}">
<meta name="robots" content="nofollow, noindex">
<link rel="icon" href="{THEME_ASSETS_PATH}/images/favicon.ico">
<link rel="stylesheet" href="{THEME_ASSETS_PATH}/css/jquery-ui-{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}">
<link rel="stylesheet" href="{THEME_ASSETS_PATH}/css/simple.css?v={THEME_ASSETS_VERSION}">
<!--[if (IE 7)|(IE 8)]>
	<link href="{THEME_ASSETS_PATH}/css/ie78overrides.css?v={THEME_ASSETS_VERSION}" rel="stylesheet">
<![endif]-->
<script>
imscp_i18n = {JS_TRANSLATIONS};
</script>
<script src="{THEME_ASSETS_PATH}/js/jquery/jquery.js?v={THEME_ASSETS_VERSION}"></script>
<script src="{THEME_ASSETS_PATH}/js/jquery/jquery-ui.js?v={THEME_ASSETS_VERSION}"></script>
<script src="{THEME_ASSETS_PATH}/js/imscp.js?v={THEME_ASSETS_VERSION}"></script>
<script>
	$(document).ready(function () {
		iMSCP.initApplication('simple');
	});
</script>
</head>
<body class="{THEME_COLOR}">
<div class="wrapper{CONTEXT_CLASS}">
	<div id="header">
		<div id="logo"><span>{productLongName}</span></div>
		<div id="copyright"><span><a href="{productLink}" target="blank">{productCopyright}</a></span></div>
	</div>
	<div id="content">
		<!-- BDP: page_message -->
		<div id="notice" class="{MESSAGE_CLS}">{MESSAGE}</div>
		<!-- EDP: page_message -->
		{LAYOUT_CONTENT}
	</div>
</div>
</body>
</html>
