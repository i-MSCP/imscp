<!DOCTYPE html>
<html>
<head>
    <title>{TR_PAGE_TITLE}</title>
    <meta charset="{THEME_CHARSET}">
    <meta name="robots" content="nofollow, noindex">
    <link rel="shortcut icon" href="{THEME_ASSETS_PATH}/images/favicon.ico">
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
    <script src="{THEME_ASSETS_PATH}/js/imscp.min.js?v={THEME_ASSETS_VERSION}"></script>
</head>
<body class="{THEME_COLOR} simple">
<div class="wrapper">
    <!-- BDP: header_block -->
    <div id="header">
        <div id="logo"><span>internet Multi Server Control Panel</span></div>
        <div id="copyright">
            <a href="https://www.i-mscp.net" tabindex="8" title="i-MSCP">
                © 2010-<script>document.write(String((new Date()).getFullYear()))</script> i-MSCP Team<br>All Rights Reserved
            </a>
        </div>
    </div>
    <!-- EDP: header_block -->
    <div id="content">
        <!-- BDP: page_message -->
        <div id="notice" class="{MESSAGE_CLS}">{MESSAGE}</div>
        <!-- EDP: page_message -->
        {LAYOUT_CONTENT}
    </div>
</div>
</body>
</html>
