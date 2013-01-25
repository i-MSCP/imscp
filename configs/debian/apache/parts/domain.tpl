<VirtualHost {DMN_IP}:80>

    ServerAdmin webmaster@{DMN_NAME}
    ServerName  {DMN_NAME}
    ServerAlias www.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot    {HOME_DIR}/htdocs

    # SECTION itk BEGIN.
    <IfModule mpm_itk_module>
        AssignUserID {USER} {GROUP}
    </IfModule>
    # SECTION itk END.

    # SECTION suexec BEGIN.
    <IfModule suexec_module>
        SuexecUserGroup {USER} {GROUP}
    </IfModule>
    # SECTION suexec END.

    Alias /errors {WWW_DIR}/{ROOT_DMN_NAME}/errors/

    RedirectMatch permanent ^/ftp[\/]?$     {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/ftp/
    RedirectMatch permanent ^/pma[\/]?$     {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/pma/
    RedirectMatch permanent ^/webmail[\/]?$ {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/webmail/
    RedirectMatch permanent ^/imscp[\/]?$   {BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/

    ErrorDocument 401 /errors/401.html
    ErrorDocument 403 /errors/403.html
    ErrorDocument 404 /errors/404.html
    ErrorDocument 500 /errors/500.html
    ErrorDocument 503 /errors/503.html

    <IfModule mod_cband.c>
        CBandUser {USER}
    </IfModule>

    # SECTION awstats_support BEGIN.
    # SECTION awstats_dynamic BEGIN.
    ProxyRequests Off
    <Proxy *>
        Order deny,allow
        Allow from all
    </Proxy>
        ProxyPass           /stats  http://localhost/stats/{DMN_NAME}
        ProxyPassReverse    /stats  http://localhost/stats/{DMN_NAME}
    <Location /stats>
        <IfModule mod_rewrite.c>
            RewriteEngine on
            RewriteRule ^(.+)\?config=([^\?\&]+)(.*) $1\?config={DMN_NAME}&$3 [NC,L]
        </IfModule>
        AuthType Basic
        AuthName "Statistics for domain {DMN_NAME}"
        AuthUserFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {AWSTATS_GROUP_AUTH}
    </Location>
    # SECTION awstats_dynamic END.

    # SECTION awstats_static BEGIN.
    Alias /awstatsicons     "{AWSTATS_WEB_DIR}/icon/"
    Alias /stats            "{HOME_DIR}/statistics/"
    <Directory "{HOME_DIR}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{DMN_NAME}.html
        Order allow,deny
        Allow from all
    </Directory>
    <Location /stats>
        AuthType Basic
        AuthName "Statistics for domain {DMN_NAME}"
        AuthUserFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {AWSTATS_GROUP_AUTH}
    </Location>
    # SECTION awstats_static END.
    # SECTION awstats_support END.

    # SECTION cgi_support BEGIN.
    ScriptAlias /cgi-bin/ {HOME_DIR}/cgi-bin/
    <Directory {HOME_DIR}/cgi-bin>
        AllowOverride AuthConfig
        #Options ExecCGI
        Order allow,deny
        Allow from all
    </Directory>
    # SECTION cgi_support END.

    <Directory {HOME_DIR}/htdocs>
        Options -Indexes Includes FollowSymLinks MultiViews
        AllowOverride All
        Order allow,deny
        Allow from all
    </Directory>

    # SECTION php_enabled BEGIN.
    # SECTION fcgid BEGIN.
    <IfModule fcgid_module>
        <Directory {HOME_DIR}/htdocs>
            FCGIWrapper {PHP_STARTER_DIR}/{FCGID_NAME}/php{PHP_VERSION}-fcgid-starter .php
            Options +ExecCGI
        </Directory>
        <Directory "{PHP_STARTER_DIR}/{FCGID_NAME}">
            AllowOverride None
            Options +ExecCGI MultiViews -Indexes
            Order allow,deny
            Allow from all
        </Directory>
    </IfModule>
    # SECTION fcgid END.

    # SECTION fastcgi BEGIN.
    <IfModule fastcgi_module>
        ScriptAlias /php5/ {PHP_STARTER_DIR}/{FCGID_NAME}/
        <Directory "{PHP_STARTER_DIR}/{FCGID_NAME}">
            AllowOverride None
            Options +ExecCGI -MultiViews -Indexes
            Order allow,deny
            Allow from all
        </Directory>
    </IfModule>
    # SECTION fastcgi END.

    # SECTION php_fpm BEGIN.
    <IfModule fastcgi_module>
        Alias /php{PHP_VERSION}.{DMN_NAME}.fcgi /var/lib/apache2/fastcgi/php{PHP_VERSION}.{DMN_NAME}.fcgi
        FastCGIExternalServer /var/lib/apache2/fastcgi/php{PHP_VERSION}.{DMN_NAME}.fcgi \
        -socket /var/run/php{PHP_VERSION}-fpm.{POOL_NAME}.socket \
        -pass-header Authorization \
        -idle-timeout 300
        Action php-script /php{PHP_VERSION}.{DMN_NAME}.fcgi virtual
        <Directory /var/lib/apache2/fastcgi>
            <Files php{PHP_VERSION}.{DMN_NAME}.fcgi>
                Order deny,allow
                Allow from all
            </Files>
        </Directory>
    </IfModule>
    # SECTION php_fpm END.

    # SECTION itk BEGIN.
    <IfModule php5_module>
        php_admin_value open_basedir "{HOME_DIR}/:{HOME_DIR}/phptmp/:{PEAR_DIR}/{PHPINI_OPEN_BASEDIR}"
        php_admin_value upload_tmp_dir "{HOME_DIR}/phptmp/"
        php_admin_value session.save_path "{HOME_DIR}/phptmp/"
        php_admin_value soap.wsdl_cache_dir "{HOME_DIR}/phptmp/"
        php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f webmaster@{DMN_NAME}"

        # Custom values
        php_admin_value max_execution_time {MAX_EXECUTION_TIME}
        php_admin_value max_input_time {MAX_INPUT_TIME}
        php_admin_value memory_limit "{MEMORY_LIMIT}M"
        php_value error_reporting {ERROR_REPORTING}
        php_flag display_errors {DISPLAY_ERRORS}
        php_admin_value post_max_size "{POST_MAX_SIZE}M"
        php_admin_value upload_max_filesize "{UPLOAD_MAX_FILESIZE}M"
        php_admin_flag allow_url_fopen {ALLOW_URL_FOPEN}
    </IfModule>
    # SECTION itk END.
    # SECTION php_enabled END.

    # SECTION php_disabled BEGIN.
    # SECTION fcgid BEGIN.
    RemoveHandler .php php{PHP_VERSION}
    RemoveType .php php{PHP_VERSION}
    # SECTION fcgid END.

    # SECTION fastcgi BEGIN.
    RemoveHandler .php php{PHP_VERSION}
    RemoveType .php php{PHP_VERSION}
    # SECTION fastcgi END.

    # SECTION php_fpm BEGIN.
    RemoveHandler .php php{PHP_VERSION}
    RemoveType .php php{PHP_VERSION}
    # SECTION php_fpm END.

    # SECTION itk BEGIN.
    <IfModule php5_module>
        php_admin_flag engine off
    </IfModule>
    # SECTION itk END.
    # SECTION php_disabled END.

    Include {APACHE_CUSTOM_SITES_CONFIG_DIR}/{DMN_NAME}.conf

</VirtualHost>
