<VirtualHost {DMN_IP}:80>

    SuexecUserGroup {SUEXEC_USER} {SUEXEC_GROUP}

    ServerAdmin     webmaster@{DMN_NAME}
    DocumentRoot    {WWW_DIR}/{DMN_NAME}/htdocs

    ServerName      {DMN_NAME}
    ServerAlias     www.{DMN_NAME} {DMN_NAME} *.{DMN_NAME}

    ErrorLog        {APACHE_USERS_LOG_DIR}/{DMN_NAME}-error.log
    TransferLog     {APACHE_USERS_LOG_DIR}/{DMN_NAME}-access.log

    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-traf.log traff
    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-combined.log combined

    Alias /errors   {WWW_DIR}/{DMN_NAME}/errors/

    ErrorDocument 401 /errors/401.html
    ErrorDocument 403 /errors/403.html
    ErrorDocument 404 /errors/404.html
    ErrorDocument 500 /errors/500.html
    ErrorDocument 503 /errors/503.html

    Redirect /ispcp http://{BASE_SERVER_VHOST}

    <IfModule mod_cband.c>
        CBandUser {DMN_GRP}
    </IfModule>

    # httpd dmn entry cgi support BEGIN.
    # httpd dmn entry cgi support END.

    # httpd dmn entry PHP2 support BEGIN.
    # httpd dmn entry PHP2 support END.

    <Directory {WWW_DIR}/{DMN_NAME}/htdocs>
        # httpd dmn entry PHP support BEGIN.
        # httpd dmn entry PHP support END.
        Options -Indexes Includes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

</VirtualHost>
