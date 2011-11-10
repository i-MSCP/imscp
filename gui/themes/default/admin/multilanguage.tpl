<!-- INCLUDE "../shared/layout/header.tpl" -->
	<body>
		<script type="text/javascript">
			/*<![CDATA[*/
			// Overrides exportation url to enable/disable gzip compression
			function override_export_url(ob) {
				regexp = new RegExp('[a-z_]*([0-9]+)');
				link = document.getElementById('url_export' + regexp.exec(ob.id)[1]);

				if(ob.checked) {
					link.href = link.href + '&compress=1';
				} else {
					link.href = link.href. substring(0, link.href.indexOf('&compress'));
				}
			}
			/*]]>*/
		</script>
		<div class="header">
			{MAIN_MENU}
			<div class="logo">
				<img src="{ISP_LOGO}" alt="i-MSCP logo" />
			</div>
		</div>
		<div class="location">
			<div class="location-area">
				<h1 class="settings">{TR_GENERAL_SETTINGS}</h1>
			</div>
			<ul class="location-menu">
				<!-- <li><a class="help" href="#">Help</a></li> -->
				<li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
				</li>
			</ul>
			<ul class="path">
				<li><a href="settings.php">{TR_GENERAL_SETTINGS}</a></li>
				<li><a href="multilanguage.php">{TR_MULTILANGUAGE}</a></li>
			</ul>
		</div>
		<div class="left_menu">
			{MENU}
		</div>
		<div class="body">
			<h2 class="multilanguage"><span>{TR_MULTILANGUAGE}</span></h2>
			<!-- BDP: page_message -->
			<div class="{MESSAGE_CLS}">{MESSAGE}</div>
			<!-- EDP: page_message -->
			<form action="multilanguage.php{PSI}" method="post" enctype="multipart/form-data" name="set_layout" id="set_layout">
				<fieldset>
					<legend style="display: inline">{TR_INSTALLED_LANGUAGES}</legend>
					<table>
						<tr>
							<th style="width:300px;">{TR_LANGUAGE}</th>
							<th>{TR_MESSAGES}</th>
							<th>{TR_LANG_REV}</th>
							<th>{TR_LAST_TRANSLATOR}</th>
							<th>{TR_DEFAULT}</th>
							<!--<th>{TR_ACTION}</th>-->
						</tr>
						<!-- BDP: lang_row -->
						<tr>
							<td><span class="icon i_locale">{LANGUAGE}</span></td>
							<td>{MESSAGES}</td>
							<td>{LANGUAGE_REVISION}</td>
							<td>{LAST_TRANSLATOR}</td>
							<td>
								<input type="radio" name="defaultLanguage" value="{LANG_VALUE}" {LANG_VALUE_CHECKED}/>
							</td>
						</tr>
						<!-- EDP: lang_row -->
						<tr>
							<td colspan="5">
								<div class="buttons">
									<input style="float:left;" name="button" type="button" value="{TR_REBUILD_INDEX}" onclick="return sbmt(document.forms[0], 'rebuildIndex');" />
								</div>
							</td>
						</tr>
					</table>
					<div class="paginator">
						<!-- BDP: scroll_next_gray -->
						<a class="icon i_next_gray" href="#">&nbsp;</a>
						<!-- EDP: scroll_next_gray -->
						<!-- BDP: scroll_next -->
						<a class="icon i_next" href="multilanguage.php?psi={NEXT_PSI}" title="next">next</a>
						<!-- EDP: scroll_next -->
						<!-- BDP: scroll_prev -->
						<a class="icon i_prev" href="multilanguage.php?psi={PREV_PSI}" title="previous">previous</a>
						<!-- EDP: scroll_prev -->
						<!-- BDP: scroll_prev_gray -->
						<a class="icon i_prev_gray" href="#">&nbsp;</a>
						<!-- EDP: scroll_prev_gray -->
					</div>
				</fieldset>
				<div class="buttons">
					<input name="button" type="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0], 'changeLanguage');" />
				</div>
				<fieldset>
					<legend>{TR_INSTALL_NEW_LANGUAGE}</legend>
					<table>
						<tr>
							<td style="width:300px;">{TR_LANGUAGE_FILE}</td>
							<td><input type="file" name="languageFile" /></td>
						</tr>
					</table>
				</fieldset>
				<div class="buttons">
					<input name="button" type="button" value="{TR_INSTALL}" onclick="return sbmt(document.forms[0],'uploadLanguage');" />
				</div>
				<input type="hidden" name="uaction" value="" />
			</form>
		</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
