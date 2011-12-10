<!-- INCLUDE "header.tpl" -->
<style type="text/css">
/*<![CDATA[*/
	#header {
		background: transparent url('{THEME_COLOR_PATH}/images/{THEME_COLOR}/login/stripe.png') repeat-x top left;
	}

	#boxHeader {
		background: transparent url('{THEME_COLOR_PATH}/images/{THEME_COLOR}/box/message_top.jpg') no-repeat;
	}
 }
/*]]>*/
 </style>
<body>
<div id="wrapper">
	<div id="header">
		<div id="logo">
			<div id="logoInner">
				<img src="/themes/default/images/imscp_logo32.png" alt="{productLongName}"/>
				<span>{productLongName}</span>
			</div>
		</div>
		<div id="copyright">
			<div id="copyrightInner">
				<a href="{productLink}" target="blank" tabindex="8">{productCopyright}</a>
			</div>
		</div>
	</div>
	<div id="box">
		<div id="boxHeader"></div>
		<div id="content">
			<h1>{MESSAGE_TITLE}</h1>
			<p>{MESSAGE}</p>
			<!-- BDP: backlink_block -->
			<a href="{BACK_BUTTON_DESTINATION}" target="_self">{TR_BACK}</a>
			<!-- EDP: backlink_block -->
		</div>
	</div>
</div>
</body>
</html>
