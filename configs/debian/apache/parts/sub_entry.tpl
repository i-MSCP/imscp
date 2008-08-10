<VirtualHost {SUB_IP}:80>

    #
    #User {SUEXEC_USER}
    #Group {SUEXEC_GROUP}
    #

    <IfModule suexec_module>
           SuexecUserGroup {SUEXEC_USER} {SUEXEC_GROUP}
    </IfModule>

    ServerAdmin     webmaster@{DMN_NAME}
    DocumentRoot    {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/htdocs

    ServerName      {SUB_NAME}
    ServerAlias     www.{SUB_NAME} {SUB_NAME} *.{SUB_NAME}

    ErrorLog        {APACHE_USERS_LOG_DIR}/{SUB_NAME}-error.log
    TransferLog     {APACHE_USERS_LOG_DIR}/{SUB_NAME}-access.log

    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-traf.log traff
    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-combined.log combined

    Alias /errors {WWW_DIR}/{DMN_NAME}/errors/

    ErrorDocument 401 /errors/401.html
    ErrorDocument 403 /errors/403.html
    ErrorDocument 404 /errors/404.html
    ErrorDocument 500 /errors/500.html
    ErrorDocument 503 /errors/503.html

    <IfModule mod_cband.c>
        CBandUser {DMN_GRP}
    </IfModule>

    # httpd sub entry cgi support BEGIN.
    # httpd sub entry cgi support END.

    <Directory {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/htdocs>
        # httpd sub entry PHP support BEGIN.
        # httpd sub entry PHP support END.
        Options -Indexes Includes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

    # httpd sub entry PHP2 support BEGIN.
    # httpd sub entry PHP2 support END.

    Include {CUSTOM_SITES_CONFIG_DIR}/{SUB_NAME}.conf

</VirtualHost>
