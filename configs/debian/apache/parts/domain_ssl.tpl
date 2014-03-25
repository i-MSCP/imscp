<VirtualHost {DOMAIN_IP}:443>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    LogLevel error
    ErrorLog {APACHE_LOG_DIR}/{DOMAIN_NAME}/error.log

    DocumentRoot {WEB_DIR}/htdocs

    Alias /errors {HOME_DIR}/errors/

    # SECTION itk BEGIN.
    AssignUserID {USER} {GROUP}
    # SECTION itk END.
    # SECTION suexec BEGIN.
    SuexecUserGroup {USER} {GROUP}
    # SECTION suexec END.

    # SECTION php_enabled BEGIN.
    # SECTION php_fpm BEGIN.
    Alias /php5-fcgi /var/lib/apache2/fastcgi/php5-fcgi-{DOMAIN_NAME}-ssl

    FastCGIExternalServer /var/lib/apache2/fastcgi/php5-fcgi-{DOMAIN_NAME}-ssl \
      -socket /var/run/php5-fpm-{POOL_NAME}.socket \
      -idle-timeout 300 \
      -pass-header Authorization
    # SECTION php_fpm END.
    # SECTION php_enabled END.

    <Directory {WEB_DIR}/htdocs>
        Options +FollowSymlinks
        # SECTION php_disabled BEGIN.
        AllowOverride AuthConfig Indexes Limit Options=Indexes \
            Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule
        # SECTION php_disabled END.
        # SECTION php_enabled BEGIN.
        AllowOverride All
        # SECTION fcgid BEGIN.
        Options +ExecCGI
        FCGIWrapper {PHP_STARTER_DIR}/{FCGID_NAME}/php5-fcgid-starter
        # SECTION fcgid END.
        # SECTION itk BEGIN.
        php_admin_value open_basedir "{HOME_DIR}/:{PEAR_DIR}/{PHPINI_OPEN_BASEDIR}"
        php_admin_value upload_tmp_dir "{WEB_DIR}/phptmp"
        php_admin_value session.save_path "{WEB_DIR}/phptmp"
        php_admin_value soap.wsdl_cache_dir "{WEB_DIR}/phptmp"
        php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f webmaster@{DOMAIN_NAME}"
        # Custom values
        php_admin_value max_execution_time {MAX_EXECUTION_TIME}
        php_admin_value max_input_time {MAX_INPUT_TIME}
        php_admin_value memory_limit "{MEMORY_LIMIT}M"
        php_value error_reporting {ERROR_REPORTING}
        php_flag display_errors {DISPLAY_ERRORS}
        php_admin_value post_max_size "{POST_MAX_SIZE}M"
        php_admin_value upload_max_filesize "{UPLOAD_MAX_FILESIZE}M"
        php_admin_flag allow_url_fopen {ALLOW_URL_FOPEN}
        # SECTION itk END.
        # SECTION php_enabled END.
        {AUTHZ_ALLOW_ALL}
     </Directory>

    # SECTION cgi_support BEGIN.
    ScriptAlias /cgi-bin/ {WEB_DIR}/cgi-bin/

    <Directory {WEB_DIR}/cgi-bin>
        AllowOverride AuthConfig Indexes Limit
        {AUTHZ_ALLOW_ALL}
    </Directory>
    # SECTION cgi_support END.

    # SECTION php_disabled BEGIN.
    # SECTION itk BEGIN.
    php_admin_flag engine off
    # SECTION itk END.
    # SECTION fcgid BEGIN.
    RemoveHandler .php php5
    # SECTION fcgid END.
    # SECTION php_fpm BEGIN.
    RemoveHandler .php php5
    # SECTION php_fpm END.
    # SECTION php_disabled END.

    # SECTION addons BEGIN.
    # SECTION addons END.

    SSLEngine On
    SSLCertificateFile {CERT}
    SSLCertificateChainFile {CERT}

    Include {APACHE_CUSTOM_SITES_CONFIG_DIR}/{DOMAIN_NAME}.conf
</VirtualHost>
