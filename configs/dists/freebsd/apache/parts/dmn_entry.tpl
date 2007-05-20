<VirtualHost {DMN_IP}:80>

    <IfModule mod_fastcgi.c>
        SuexecUserGroup {SUEXEC_USER} {SUEXEC_GROUP}
    </IfModule>

    ServerAdmin     root@{DMN_NAME}
    DocumentRoot    {WWW_DIR}/{DMN_NAME}/htdocs

    ServerName      {DMN_NAME}
    ServerAlias     www.{DMN_NAME} {DMN_NAME} *.{DMN_NAME}

    ErrorLog        {APACHE_USERS_LOG_DIR}/{DMN_NAME}-error.log
    TransferLog     {APACHE_USERS_LOG_DIR}/{DMN_NAME}-access.log

    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-traf.log traff
    CustomLog       {APACHE_LOG_DIR}/{DMN_NAME}-combined.log combined

    Alias /errors   {WWW_DIR}/{DMN_NAME}/errors/

    ErrorDocument 401 /errors/401/index.php
    ErrorDocument 403 /errors/403/index.php
    ErrorDocument 404 /errors/404/index.php
    ErrorDocument 500 /errors/500/index.php

    Redirect /stats http://{DMN_NAME}/awstats/awstats.pl
    Redirect /ispcp http://{BASE_SERVER_VHOST}

    # httpd dmn entry cgi support BEGIN.
    # httpd dmn entry cgi support END.

    <IfModule mod_fastcgi.c>
        ScriptAlias /php4/ {STARTER_DIR}/{DMN_NAME}/
        <Directory "{STARTER_DIR}/{DMN_NAME}">
            AllowOverride None
            Options +ExecCGI -MultiViews -Indexes
            Order allow,deny
            Allow from all
        </Directory>
    </IfModule>

    <IfModule mod_php4.c>
        <Directory {GUI_ROOT_DIR}>
            php_admin_value open_basedir "{GUI_ROOT_DIR}/:/etc/ispcp/:/proc/:{WWW_DIR}/:/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin"
            php_admin_value session.save_path "{GUI_ROOT_DIR}/phptmp/"
        </Directory>
    </IfModule>

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
