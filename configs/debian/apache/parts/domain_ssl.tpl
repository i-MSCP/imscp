<VirtualHost {DMN_IP}:443>

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
        # SECTION php_enabled BEGIN.
        AllowOverride All
        # SECTION php_enabled END.
        # SECTION php_disabled BEGIN.
        AllowOverride AuthConfig Indexes Limit Options
        # SECTION php_disabled END.
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
        Alias /php{PHP_VERSION}.{DMN_NAME}.ssl.fcgi /var/lib/apache2/fastcgi/php{PHP_VERSION}.{DMN_NAME}.ssl.fcgi
        FastCGIExternalServer /var/lib/apache2/fastcgi/php{PHP_VERSION}.{DMN_NAME}.ssl.fcgi \
        -socket /var/run/php5-fpm.{POOL_NAME}.socket \
        -pass-header Authorization \
        -idle-timeout 300
        Action php-script /php{PHP_VERSION}.{DMN_NAME}.ssl.fcgi virtual
        <Directory /var/lib/apache2/fastcgi>
            <Files php{PHP_VERSION}.{DMN_NAME}.ssl.fcgi>
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

    # SECTION addons BEGIN.
    # SECTION addons END.

    SSLEngine On
    SSLCertificateFile      {CERT}
    SSLCertificateKeyFile   {CERT}
    SSLCertificateChainFile {CERT}

    Include {APACHE_CUSTOM_SITES_CONFIG_DIR}/{DMN_NAME}.conf

</VirtualHost>
