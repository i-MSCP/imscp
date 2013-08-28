<!DOCTYPE html>
<html>
<head>
<title>{TR_PAGE_TITLE}</title>
<meta http-equiv="Content-Script-Type" content="text/javascript"/>
<meta http-equiv="Content-Style-Type" content="text/css"/>
<meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}"/>
<meta name="copyright" content="i-MSCP"/>
<meta name="owner" content="i-MSCP"/>
<meta name="publisher" content="i-MSCP"/>
<meta name="robots" content="nofollow, noindex"/>
<meta name="title" content="{TR_PAGE_TITLE}"/>
<link href="{THEME_ASSETS_PATH}/css/imscp.css?v={THEME_ASSETS_VERSION}" rel="stylesheet" type="text/css"/>
<link href="{THEME_ASSETS_PATH}/css/{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}" rel="stylesheet" type="text/css"/>
<link href="{THEME_ASSETS_PATH}/css/jquery-ui-{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}" rel="stylesheet" type="text/css"/>
<script type="text/javascript" src="{THEME_ASSETS_PATH}/js/jquery.js?v={THEME_ASSETS_VERSION}"></script>
<script type="text/javascript" src="{THEME_ASSETS_PATH}/js/jquery.ui.js?v={THEME_ASSETS_VERSION}"></script>
<script type="text/javascript" src="{THEME_ASSETS_PATH}/js/jquery.dataTables.min.js?v={THEME_ASSETS_VERSION}"></script>
<script type="text/javascript" src="{THEME_ASSETS_PATH}/js/jquery.imscpTooltip-min.js?v={THEME_ASSETS_VERSION}"></script>
<script type="text/javascript" src="{THEME_ASSETS_PATH}/js/imscp.js?v={THEME_ASSETS_VERSION}"></script>
<!--[if IE]>
<script type="text/javascript">
	/*<![CDATA[*/
	// css adjustement for IE browsers
	$(window).load(function(){
		$('tr').each(function(index) {
			if(index % 2) {
				$(this).css('background-color', 'rgb(255, 255, 255)');
			} else {
				$(this).css('background-color', 'rgb(237, 237, 237)');
			}
		});
			$("td:last-child").css('border-right','1px solid rgb(223, 223, 223)');
		$("th:last-child, tfoot td:last-child").css('border-right','1px solid rgb(0, 0, 0)');
		$(".datatable tfoot td:last-child").css('border-right','1px solid rgb(223, 223, 223)');
	});
/*]]>*/
</script>
<![endif]-->
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
		$.fx.speeds._default = 500;
		setTimeout(function () {
			$('.timeout').fadeOut(1000);
		}, 5000);
		$('.main_menu a').imscpTooltip();
		$('.body a, .body span, .body input').imscpTooltip({extraClass:"tooltip_icon tooltip_notice"});
			// Setup buttons
		$("input:submit, input:button, button").button();
		$(".radio, .checkbox").buttonset();
		$(":radio, :checkbox").change(function () {
			$(this).blur();
		});
	});
	/*]]>*/
</script>
</head>
<body>
<div id="wrapper">
	<div class="header">
		<!-- INCLUDE "../partials/navigation/main_menu.tpl" -->
		<div class="logo"><img src="{ISP_LOGO}" alt="i-MSCP logo"/></div>
	</div>
	<div class="location">
		<div class="location-area">
			<h1 class="{SECTION_TITLE_CLASS}">{TR_SECTION_TITLE}</h1>
		</div>
		<ul class="location-menu">
		<!-- BDP: logged_from -->
		<li>
			<a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
		</li>
		<!-- EDP: logged_from -->
		<li>
			<a class="logout" href="../index.php?action=logout">{TR_MENU_LOGOUT}</a>
		</li>
		</ul>
		<!-- INCLUDE "../partials/navigation/breadcrumbs.tpl" -->
	</div>
	<!-- INCLUDE "../partials/navigation/left_menu.tpl" -->
	<div class="body">
		<h2 class="{TITLE_CLASS}"><span>{TR_TITLE}</span></h2>
		<!-- BDP: page_message -->
		<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->
			{LAYOUT_CONTENT}
		</div>
	</div>
	<div class="footer">
		i-MSCP {VERSION}<br/>
		Build: {BUILDDATE}<br/>
		Codename: {CODENAME}
	</div>
</body>
</html>
