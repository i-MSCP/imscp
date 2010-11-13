<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_ADMIN_I18N_PAGE_TITLE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/ispcp.js"></script>
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
        <script type="text/javascript">
            <!--
            function action_delete(url, language) {
                if (!confirm(sprintf("{TR_MESSAGE_DELETE}", language)))
                    return false;
                location = url;
            }
            //-->
        </script>
    </head>

    <body>
    <div class="header">
        {MAIN_MENU}

        <div class="logo">
            <img src="{THEME_COLOR_PATH}/images/ispcp_logo.png" alt="IspCP logo" />
            <img src="{THEME_COLOR_PATH}/images/ispcp_webhosting.png" alt="IspCP omega" />
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

        <!-- BDP: page_message -->
        <div class="warning">{MESSAGE}</div>
        <!-- EDP: page_message -->

        <h2 class="multilanguage"><span>{TR_MULTILANGUAGE}</span></h2>
        <form action="multilanguage.php" method="post" enctype="multipart/form-data" name="set_layout" id="set_layout">
            <fieldset>
                <legend>{TR_INSTALLED_LANGUAGES}</legend>
                <table>
                    <tr>
                        <th>{TR_LANGUAGE}</th>
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
                        <td><!-- BDP: lang_def -->
                            {DEFAULT}
                            <!-- EDP: lang_def -->
                                        <!-- BDP: lang_radio -->
                            <input type="radio" name="default_language" value="{LANG_VALUE}" />
                            <!-- EDP: lang_radio -->
                        </td>
                        <td><a class="icon i_details" href="{URL_EXPORT}" target="_blank">{TR_EXPORT}</a>
                            <!-- BDP: lang_delete_show -->
                <!-- EDP: lang_delete_show -->
                <!-- BDP: lang_delete_link -->
                            <a class="icon i_delete" href="#" onclick="action_delete('{URL_DELETE}', '{LANGUAGE}')">{TR_UNINSTALL}</a>
                            <!-- EDP: lang_delete_link -->
                        </td>
                    </tr>
                    <!-- EDP: lang_row -->
                </table>
            </fieldset>

            <div class="buttons">
                <input name="Button" type="button" value="{TR_SAVE}" onclick="return sbmt(document.forms[0],'change_language');" />
            </div>

            <fieldset>
                <legend>{TR_INSTALL_NEW_LANGUAGE}</legend>
                <table>
                    <tr>
                        <td>{TR_LANGUAGE_FILE}</td>
                        <td><input type="file" name="lang_file" /></td>
                    </tr>
                </table>
            </fieldset>

            <div class="buttons">
                <input name="Button" type="button" value="{TR_INSTALL}" onclick="return sbmt(document.forms[0],'upload_language');" />
                <input type="hidden" name="uaction" value="" />
            </div>

        </form>
    </div>
    <div class="footer">
        ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
    </div>
    </body>
</html>