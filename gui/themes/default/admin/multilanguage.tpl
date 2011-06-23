<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_I18N_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/imscp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/imscp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
		/*<![CDATA[*/
			function action_delete(url, language) {
				if (!confirm(sprintf("{TR_MESSAGE_DELETE}", language))) {
					return false;
                }
		
				location = url;
			}
		
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
    </head>
    <body>
    <div class="header">
        {MAIN_MENU}

        <div class="logo">
            <img src="{THEME_COLOR_PATH}/images/imscp_logo.png" alt="i-MSCP logo" />
        </div>
    </div>
    <div class="location">
        <div class="location-area icons-left">
            <h1 class="settings">{TR_GENERAL_SETTINGS}</h1>
        </div>
        <ul class="location-menu">
            <!-- <li><a class="help" href="#">Help</a></li> -->
            <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
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

        <form action="multilanguage.php" method="post" enctype="multipart/form-data" name="set_layout" id="set_layout">
            <fieldset>
                <legend>{TR_INSTALLED_LANGUAGES}</legend>
                <table>
                    <tr>
                        <th style="width:300px;">{TR_LANGUAGE}</th>
                        <th>{TR_MESSAGES}</th>
                        <th>{TR_LANG_REV}</th>
                        <th>{TR_DEFAULT}</th>
                        <th>{TR_ACTION}</th>
                    </tr>
                    <!-- BDP: lang_row -->
                    <tr>
                        <td><span class="icon i_locale">{LANGUAGE}</span></td>
                        <td>{MESSAGES}</td>
                        <td>{LANGUAGE_REVISION}</td>
                        <td>
                            <input type="radio" name="default_language" value="{LANG_VALUE}" {LANG_VALUE_CHECKED}/>
                        </td>
                        <td>
                            <!--
                            <a class="icon i_details" href="{URL_EXPORT}" id="url_export{INDEX}" target="_blank">{TR_EXPORT}</a>
                            <input id="gz_export{INDEX}" type="checkbox" onClick="override_export_url(this)" style="vertical-align:middle;margin-bottom:3px;" />
                            <span style="font-size:8px;vertical-align:middle;">{TR_GZIPPED}</span>
                            -->

                            <!-- BDP: lang_show -->
                            <a class="icon i_delete" href="#">{TR_UNINSTALL}</a>
                            <!-- EDP: lang_show -->

                            <!-- BDP: lang_delete_link -->
                            <a class="icon i_delete" href="#" onclick="action_delete('{URL_DELETE}', '{LANGUAGE}');return false;">{TR_UNINSTALL}</a>
                            <!-- EDP: lang_delete_link -->
                        </td>
                    </tr>
                    <!-- EDP: lang_row -->
                </table>
                <p style="margin: 10px;">{TR_NOTE_DELETION}</p>
            </fieldset>
            <div class="buttons">
                <input name="Button" type="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0],'changeLanguage');" />
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
                <input type="hidden" name="uaction" value="" />
            </div>
        </form>
    </div>
    <div class="footer">
        i-MSCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
    </div>
    </body>
</html>
