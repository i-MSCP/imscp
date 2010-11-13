<?xml version="1.0" encoding="{THEME_CHARSET}" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset={THEME_CHARSET}" />
        <meta http-equiv="X-UA-Compatible" content="IE=8" />
        <title>{TR_CHOOSE_DEFAULT_LANGUAGE}</title>
        <meta name="robots" content="nofollow, noindex" />
        <link href="{THEME_COLOR_PATH}/css/ispcp.css" rel="stylesheet" type="text/css" />
        <!--[if IE 6]>
        <script type="text/javascript" src="{THEME_COLOR_PATH}/js/DD_belatedPNG_0.0.8a-min.js"></script>
        <script type="text/javascript">
            DD_belatedPNG.fix('*');
        </script>
        <![endif]-->
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
                <h1 class="general">{TR_MENU_GENERAL_INFORMATION}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a></li>
            </ul>
            <ul class="path">
                <li><a href="index.php">{TR_MENU_GENERAL_INFORMATION}</a></li>
                <li><a href="password_change.php">{TR_CHOOSE_DEFAULT_LANGUAGE}</a></li>
            </ul>
        </div>
                 
        <div class="left_menu">
            {MENU}
        </div>

        <div class="body">

            <!-- BDP: page_message -->
            <div class="warning">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <h2 class="multilanguage"><span>{TR_CHOOSE_DEFAULT_LANGUAGE}</span></h2>
            <form name="client_change_language" method="post" action="language.php">
                <table>
                    <tr>
                        <td><label for="def_language">{TR_CHOOSE_DEFAULT_LANGUAGE}</label></td>
                        <td>
                            <select name="def_language" id="def_language">
                                <!-- BDP: def_language -->
                                <option value="{LANG_VALUE}" {LANG_SELECTED}>{LANG_NAME}</option>
                                <!-- EDP: def_language -->
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="buttons">
                    <input name="Submit" type="submit" class="button" value="{TR_SAVE}" />
                    <input type="hidden" name="uaction" value="save_lang" />
                </div>
            </form>

        </div>

        <div class="footer">
            ispCP {VERSION}<br />build: {BUILDDATE}<br />Codename: {CODENAME}
        </div>
    
    </body>
</html>
