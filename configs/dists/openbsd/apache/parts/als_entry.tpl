<VirtualHost {ALS_IP}:80>

    #
    #User {SUEXEC_USER}
    #Group {SUEXEC_GROUP}
    #

    <IfModule mod_suexec.c>
           SuexecUserGroup {SUEXEC_USER} {SUEXEC_GROUP}
    </IfModule>

    ServerAdmin     webmaster@{ALS_NAME}
    DocumentRoot    {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/htdocs

    ServerName      {ALS_NAME}
    ServerAlias     www.{ALS_NAME} {ALS_NAME} *.{ALS_NAME}

    ErrorLog        {APACHE_USERS_LOG_DIR}/{ALS_NAME}-error.log
    TransferLog     {APACHE_USERS_LOG_DIR}/{ALS_NAME}-access.log

    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-traf.log traff
    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-combined.log combined

    Alias /errors   {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/errors/

    ErrorDocument 401 /errors/401.html
    ErrorDocument 403 /errors/403.html
    ErrorDocument 404 /errors/404.html
    ErrorDocument 500 /errors/500.html
    ErrorDocument 503 /errors/503.html

    <IfModule mod_cband.c>
        CBandUser {DMN_GRP}
    </IfModule>

    # httpd als entry redirect entry BEGIN.
    # httpd als entry redirect entry END.

    # httpd als entry cgi support BEGIN.
    # httpd als entry cgi support END.

    # httpd als entry PHP2 support BEGIN.
    # httpd als entry PHP2 support END.

    <Directory {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/htdocs>
        # httpd als entry PHP support BEGIN.
        # httpd als entry PHP support END.
        Options -Indexes Includes FollowSymLinks MultiViews
        AllowOverride AuthConfig FileInfo
        Order allow,deny
        Allow from all
    </Directory>

    Include {CUSTOM_SITES_CONFIG_DIR}/{ALS_NAME}.conf

</VirtualHost>
