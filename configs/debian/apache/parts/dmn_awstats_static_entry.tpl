    Alias /awstatsicons 	"{AWSTATS_WEB_DIR}/icon/"
    Alias /stats    		"{HOME_DIR}/statistics/"

    <Directory "{HOME_DIR}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{DMN_NAME}.html
        Order allow,deny
        Allow from all
    </Directory>

    <Location /stats>
        AuthType Basic
        AuthName "Statistics for domain {DMN_NAME}"
        AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {AWSTATS_GROUP_AUTH}
    </Location>
