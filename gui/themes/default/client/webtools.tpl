<!-- INCLUDE "../shared/layout/header.tpl" -->
        <div class="header">
            {MAIN_MENU}
            <div class="logo">
                <img src="{ISP_LOGO}" alt="i-MSCP logo" />
            </div>
        </div>
        <div class="location">
            <div class="location-area">
                <h1 class="webtools">{TR_MENU_WEBTOOLS}</h1>
            </div>
            <ul class="location-menu">
                <!-- <li><a class="help" href="#">Help</a></li> -->
                <!-- BDP: logged_from -->
                <li>
                    <a class="backadmin" href="change_user_interface.php?action=go_back">{YOU_ARE_LOGGED_AS}</a>
                </li>
                <!-- EDP: logged_from -->
                <li><a class="logout" href="../index.php?logout">{TR_MENU_LOGOUT}</a>
                </li>
            </ul>
            <ul class="path">
                <li><a href="webtools.php">{TR_MENU_WEBTOOLS}</a></li>
                <li><a href="webtools.php">{TR_LMENU_OVERVIEW}</a></li>
            </ul>
        </div>
        <div class="left_menu">
            {MENU}
        </div>
        <div class="body">
            <h2 class="tools"><span>{TR_TITLE_WEBTOOLS}</span></h2>

            <!-- BDP: page_message -->
            <div class="{MESSAGE_CLS}">{MESSAGE}</div>
            <!-- EDP: page_message -->

            <a href="protected_areas.php">{TR_HTACCESS}</a>
            <p>{TR_HTACCESS_TXT}</p>

            <a href="error_pages.php">{TR_ERROR_PAGES}</a>
            <p>{TR_ERROR_PAGES_TXT}</p>

			<!-- BDP: backup_feature -->
            <a href="backup.php">{TR_BACKUP}</a>
            <p>{TR_BACKUP_TXT}</p>
			<!-- EDP: backup_feature -->

            <!-- BDP: mail_feature -->
            <a href="{WEBMAIL_PATH}" target="{WEBMAIL_TARGET}">{TR_WEBMAIL}</a>
            <p>{TR_WEBMAIL_TXT}</p>
            <!-- EDP: mail_feature -->

			<!-- BDP: ftp_feature -->
            <a href="{FILEMANAGER_PATH}" target="{FILEMANAGER_TARGET}">{TR_FILEMANAGER}</a>
            <p>{TR_FILEMANAGER_TXT}</p>
			<!-- EDP: ftp_feature -->

            <!-- BDP: aps_feature -->
            <a href="software.php">{TR_APP_INSTALLER}</a>
            <p>{TR_APP_INSTALLER_TXT}</p>
            <!-- EDP: aps_feature -->

            <!-- BDP: awstats_feature -->
            <a href="{AWSTATS_PATH}" target="{AWSTATST_TARGET}">{TR_AWSTATS}</a>
            <p>{TR_AWSTATS_TXT}</p>
            <!-- EDP: awstats_feature -->
        </div>
<!-- INCLUDE "../shared/layout/footer.tpl" -->
