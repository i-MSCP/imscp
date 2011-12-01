<!-- INCLUDE "../shared/layout/header.tpl" -->
<body>
<script type="text/javascript">
	/*<![CDATA[*/
	$(document).ready(function () {
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
		<!-- BDP: languages_block -->
		<table id="languageTable" class="datatable" cellpadding="0" cellspacing="0" style="vertical-align: top;">
			<thead>
				<tr>
					<th>{TR_LANGUAGE_NAME}</th>
					<th>{TR_NUMBER_TRANSLATED_STRINGS}</th>
					<th>{TR_LANGUAGE_REVISION}</th>
					<th>{TR_LAST_TRANSLATOR}</th>
					<th>{TR_DEFAULT_LANGUAGE}</th>
				</tr>
				</thead>
				<tbody>
				<!-- BDP: language_block -->
				<tr>
					<td><label for="defaultLanguage_{LOCALE}"><span class="icon i_locale">{LANGUAGE_NAME}</span></label></td>
					<td>{NUMBER_TRANSLATED_STRINGS}</td>
					<td>{LANGUAGE_REVISION}</td>
					<td>{LAST_TRANSLATOR}</td>
					<td>
						<input type="radio" name="defaultLanguage" id="defaultLanguage_{LOCALE}" value="{LOCALE}" {LOCALE_CHECKED}/>
					</td>
				</tr>
				<!-- EDP: language_block -->
			</tbody>
			<tfoot>
				<tr>
					<td colspan="5" style="background: none;border: none;text-align: right;">
						<button name="rebuildIndex" type="submit" onclick="$('#uaction').val('rebuildIndex')" class="frm-button">{TR_REBUILD_INDEX}</button>
						<button name="changeLanguage" type="submit" class="frm-button" onclick="$('#uaction').val('changeLanguage');">{TR_SAVE}</button>
					</td>
				</tr>
			</tfoot>
		</table>
		<!-- EDP: languages_block -->
		<table>
			<tr>
				<th colspan="2">{TR_INSTALL_NEW_LANGUAGE}</th>
			</tr>
			<tr>
				<td>{TR_LANGUAGE_FILE} <span class="upload_help icon i_help" style="vertical-align: middle;">{TR_HELP}</span></td>
				<td>
					<input type="file" name="languageFile"/>
					<button name="uploadLanguage" type="submit" onclick="$('#uaction').val('uploadLanguage')" class="frm-button">{TR_INSTALL}</button>
				</td>
			</tr>
		</table>
		<div class="buttons">
			<input type="hidden" name="uaction" id="uaction" value=""/>
			<button name="cancel" type="button" onclick="location.href='settings.php'" class="frm-button">{TR_CANCEL}</button>
		</div>
	</form>
</div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
