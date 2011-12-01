<!-- INCLUDE "../shared/layout/header.tpl" -->
<body>
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function() {
		$('.frm-button').button();
		$('#languageTable').dataTable({"oLanguage": {DATATABLE_TRANSLATIONS}});
		$('.upload_help').iMSCPtooltips({msg:'{TR_UPLOAD_HELP}'});
	});
	/*]]>*/
</script>
<div class="header">
	{MAIN_MENU}
	<div class="logo">
		<img src="{ISP_LOGO}" alt="i-MSCP logo"/>
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

	<form name="i18nFrm" action="multilanguage.php" method="post" enctype="multipart/form-data">
		<table id="languageTable" class="datatable" cellpadding="0" cellspacing="0" style="vertical-align: top;">
			<thead>
				<tr>
					<th>{TR_LANGUAGE}</th>
					<th>{TR_MESSAGES}</th>
					<th>{TR_LANG_REV}</th>
					<th>{TR_LAST_TRANSLATOR}</th>
					<th><label for="defaultLanguage">{TR_DEFAULT}</label></th>
				</tr>
			</thead>
			<tbody>
				<!-- BDP: lang_row -->
				<tr>
					<td><span class="icon i_locale">{LANGUAGE}</span></td>
					<td>{MESSAGES}</td>
					<td>{LANGUAGE_REVISION}</td>
					<td>{LAST_TRANSLATOR}</td>
					<td>
						<input type="radio" name="defaultLanguage" id="defaultLanguage" value="{LANG_VALUE}" {LANG_VALUE_CHECKED}/>
					</td>
				</tr>
				<!-- EDP: lang_row -->
			</tbody>
			<tfoot>
			<tr>
				<td colspan="4" style="background: none;border: none;">
					<button name="rebuildIndex" type="submit" onclick="return sbmt(document.forms[0], 'rebuildIndex');" class="frm-button">{TR_REBUILD_INDEX}</button>
				</td>
				<td  style="background: none;border: none;text-align: right;">
					<button name="changeLanguage" type="submit" class="frm-button" onclick="return sbmt(document.forms[0], 'changeLanguage');">{TR_SAVE}</button>
				</td>
			</tr>
			</tfoot>
		</table>
		<table>
			<tr>
				<th colspan="2">{TR_INSTALL_NEW_LANGUAGE}</th>
			</tr>
			<tr>
				<td>{TR_LANGUAGE_FILE} <span class="upload_help icon i_help" style="vertical-align: middle;">{TR_HELP}</span></td>
				<td>
					<input type="file" name="languageFile" />
					<button name="uploadLanguage" type="submit" onclick="return sbmt(document.forms[0], 'uploadLanguage');" class="frm-button">{TR_INSTALL}</button>
				</td>
			</tr>

		</table>
		<div class="buttons">
			<input type="hidden" name="uaction" value="" />
			<button name="cancel" type="button" onclick="location.href='settings.php';" class="frm-button">{TR_CANCEL}</button>
		</div>
	</form>
</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
