    Alias /awstatsicons 	"{AWSTATS_WEB_DIR}/icon/"
    Alias /stats    		"{WWW_DIR}/{DMN_NAME}/statistics/"

    <Directory "{WWW_DIR}/{DMN_NAME}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{DMN_NAME}.html
        Order allow,deny
        Allow from all
    </Directory>

    <Location /stats>
        AuthType Basic
        AuthName "Statistics for domain {DMN_NAME}"
        AuthUserFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {AWSTATS_GROUP_AUTH}
    </Location>
