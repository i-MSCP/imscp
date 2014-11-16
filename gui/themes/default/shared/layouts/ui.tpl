<!DOCTYPE html>
<html>
<head>
<title>{TR_PAGE_TITLE}</title>
<meta charset="{THEME_CHARSET}">
<meta name="copyright" content="i-MSCP">
<meta name="owner" content="i-MSCP">
<meta name="publisher" content="i-MSCP">
<meta name="robots" content="nofollow, noindex">
<meta name="title" content="{TR_PAGE_TITLE}">
<link href="{THEME_ASSETS_PATH}/css/ui.css?v={THEME_ASSETS_VERSION}" rel="stylesheet">
<link href="{THEME_ASSETS_PATH}/css/{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}" rel="stylesheet">
<link href="{THEME_ASSETS_PATH}/css/jquery-ui-{THEME_COLOR}.css?v={THEME_ASSETS_VERSION}" rel="stylesheet">
<script src="{THEME_ASSETS_PATH}/js/jquery.min.js?v={THEME_ASSETS_VERSION}"></script>
<script src="{THEME_ASSETS_PATH}/js/jquery-ui.min.js?v={THEME_ASSETS_VERSION}"></script>
<script src="{THEME_ASSETS_PATH}/js/jquery.dataTables.min.js?v={THEME_ASSETS_VERSION}"></script>
<script src="{THEME_ASSETS_PATH}/js/jquery.imscpTooltip-min.js?v={THEME_ASSETS_VERSION}"></script>
<script src="{THEME_ASSETS_PATH}/js/imscp.js?v={THEME_ASSETS_VERSION}"></script>
<script>
$(document).ready(function () {
	$.fx.speeds._default = 500;

	$(".body").on("message_timeout", ".success,.info,.warning,.error", function() {
		$(this).hide().slideDown('fast').delay(5000).slideUp("normal", function () { $(this).remove(); });
	});

	$(".success,.info,.warning,.error").trigger("message_timeout");

	$(".main_menu a").imscpTooltip();
	$(".body a, .body span, .body input, .dataTables_paginate div").imscpTooltip({ extraClass: "tooltip_icon tooltip_notice" });
	$("input:submit, input:button, input:reset, button, .link_as_button").button();
	$(".radio, .checkbox").buttonset();
	$("body").on("updateTable", "tbody", function() {
		$(this).find("tr:visible:odd").removeClass("odd").addClass("even");
		$(this).find("tr:visible:even").removeClass("even").addClass("odd");
		$(this).find('th').parent().removeClass("even odd");
	});
	$("tbody").trigger('updateTable');

	// Dirty fix for http://bugs.jqueryui.com/ticket/7856
	$('[type=checkbox]').on("change", function() {
		if(!$(this).is(":checked")) {
			$(this).blur();
		}
	});
	$(document).on("click", "button", function() {
		$(this).removeClass("ui-state-focus ui-state-hover");
	});
});
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
			<li><a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a></li>
			<!-- EDP: logged_from -->
			<li><a class="logout" href="/index.php?action=logout">{TR_MENU_LOGOUT}</a></li>
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
 i-MSCP {VERSION}<br>
 Build: {BUILDDATE}<br>
 Codename: {CODENAME}
</div>
</body>
</html>
