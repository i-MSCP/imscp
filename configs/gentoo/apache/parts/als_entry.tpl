<VirtualHost {ALS_IP}:80>

    #
    #User {SUEXEC_USER}
    #Group {SUEXEC_GROUP}
    #

    <IfModule suexec_module>
           SuexecUserGroup {SUEXEC_USER} {SUEXEC_GROUP}
    </IfModule>

    ServerAdmin     webmaster@{ALS_NAME}
    DocumentRoot    {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/htdocs

    ServerName      {ALS_NAME}
    ServerAlias     www.{ALS_NAME} {ALS_NAME} *.{ALS_NAME}

    Alias /errors   {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/errors/

    ErrorDocument 401 /errors/401.html
    ErrorDocument 403 /errors/403.html
    ErrorDocument 404 /errors/404.html
    ErrorDocument 500 /errors/500.html
    ErrorDocument 503 /errors/503.html

    <IfModule mod_cband.c>
        CBandUser {DMN_GRP}
    </IfModule>

    # httpd awstats support BEGIN.
    # httpd awstats support END.

    <Location /stats>
        AuthType Basic
        AuthName "Statistics for domain {ALS_NAME}"
        AuthUserFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {WWW_DIR}/{DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {AWSTATS_GROUP_AUTH}
    </Location>

    # httpd als entry redirect entry BEGIN.
    # httpd als entry redirect entry END.

    # httpd als entry cgi support BEGIN.
    # httpd als entry cgi support END.

    <Directory {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/htdocs>
        # httpd als entry PHP support BEGIN.
        # httpd als entry PHP support END.
        Options -Indexes Includes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

    # httpd als entry PHP2 support BEGIN.
    # httpd als entry PHP2 support END.

    Include {CUSTOM_SITES_CONFIG_DIR}/{ALS_NAME}.conf

</VirtualHost>
