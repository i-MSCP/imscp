<VirtualHost {DOMAIN_IPS}>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias {SERVER_ALIASES}

    DocumentRoot "{DOCUMENT_ROOT}"

    # Reset list of resources to look for when the client requests a directory
    DirectoryIndex disabled
    
    LogLevel error
    ErrorLog "{HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log"

    Alias "/errors/" "{HOME_DIR}/errors/"

    # SECTION ssl BEGIN.
    SSLEngine On
    SSLCertificateFile      "{CERTIFICATE}"

    Header always set Strict-Transport-Security "max-age={HSTS_MAX_AGE}{HSTS_INCLUDE_SUBDOMAINS}"
    # SECTION ssl END.

    # SECTION dmn BEGIN.
    # SECTION itk BEGIN.
    AssignUserID {USER} {GROUP}
    # SECTION itk END.

    # SECTION suexec BEGIN.
    SuexecUserGroup {USER} {GROUP}
    # SECTION suexec END.

    # SECTION php_on BEGIN.
    # SECTION php_fpm BEGIN.
    <Proxy "{PROXY_FCGI_PATH}{PROXY_FCGI_URL}" retry=0>
        ProxySet connectiontimeout=5 timeout=7200
    </Proxy>
    # SECTION php_fpm END.
    # SECTION php_on END.

    <Directory "{DOCUMENT_ROOT}">
        Options FollowSymLinks
        # SECTION php_on BEGIN.
        DirectoryIndex index.php
        AllowOverride All
        # SECTION fcgid BEGIN.
        Options +ExecCGI
        FCGIWrapper "{PHP_FCGI_STARTER_DIR}/{FCGID_NAME}/php-fcgi-starter"
        # SECTION fcgid END.
        # SECTION itk BEGIN.
        php_admin_value open_basedir "{HOME_DIR}/:{PEAR_DIR}/:dev/random:/dev/urandom"
        php_admin_value upload_tmp_dir "{TMPDIR}"
        php_admin_value session.save_path "{TMPDIR}"
        php_admin_value soap.wsdl_cache_dir "{TMPDIR}"
        php_admin_value sendmail_path "/usr/sbin/sendmail -t -i -f webmaster@{EMAIL_DOMAIN}"
        php_admin_value max_execution_time {MAX_EXECUTION_TIME}
        php_admin_value max_input_time {MAX_INPUT_TIME}
        php_admin_value memory_limit "{MEMORY_LIMIT}M"
        php_flag display_errors {DISPLAY_ERRORS}
        php_admin_value post_max_size "{POST_MAX_SIZE}M"
        php_admin_value upload_max_filesize "{UPLOAD_MAX_FILESIZE}M"
        php_admin_flag allow_url_fopen {ALLOW_URL_FOPEN}
        # SECTION itk END.
        # SECTION php_fpm BEGIN.
        <If "%{REQUEST_FILENAME} =~ /\.ph(?:p[3457]?|t|tml)$/ && -f %{REQUEST_FILENAME}">
            SetEnvIfNoCase ^Authorization$ "(.+)" HTTP_AUTHORIZATION=$1
            SetHandler proxy:{PROXY_FCGI_URL}
        </If>
        # SECTION php_fpm END.
        # SECTION php_on END.
        # SECTION php_off BEGIN.
        AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
            Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
        # SECTION php_off END.
        DirectoryIndex index.html index.xhtml index.htm
        Require all granted
    </Directory>

    # SECTION cgi BEGIN.
    Alias "/cgi-bin/" "{WEB_DIR}/cgi-bin/"
    <Directory "{WEB_DIR}/cgi-bin">
        AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
            Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
        DirectoryIndex index.cgi index.pl index.py index.rb
        Options FollowSymLinks ExecCGI
        AddHandler cgi-script .cgi .pl .py .rb
        Require all granted
    </Directory>
    # SECTION cgi END.

    # SECTION php_off BEGIN.
    # SECTION itk BEGIN.
    php_admin_flag engine off
    # SECTION itk END.
    # SECTION fcgid BEGIN.
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    # SECTION fcgid END.
    # SECTION php_fpm BEGIN.
    RemoveHandler .php .php3 .php4 .php5 .php7 .pht .phtml
    # SECTION php_fpm END.
    # SECTION php_off END.
    # SECTION dmn END.

    # SECTION fwd BEGIN.
    <Directory "{DOCUMENT_ROOT}">
        Options FollowSymLinks
        AllowOverride AuthConfig Indexes Limit Options=Indexes,MultiViews \
            Fileinfo=RewriteEngine,RewriteOptions,RewriteBase,RewriteCond,RewriteRule Nonfatal=Override
        Require all granted
    </Directory>

    # SECTION std_fwd BEGIN.
    RedirectMatch {FORWARD_TYPE} "^/((?!(?:errors|\.well-known)/acme-challenge/).*)" "{FORWARD}$1"
    # SECTION std_fwd END.
    # SECTION proxy_fwd BEGIN.
    # SECTION ssl_proxy BEGIN.
    SSLProxyEngine on
    # SECTION ssl_proxy END.
    RequestHeader set X-Forwarded-Proto "{X_FORWARDED_PROTOCOL}"
    RequestHeader set X-Forwarded-Port {X_FORWARDED_PORT}
    ProxyPreserveHost {FORWARD_PRESERVE_HOST}
    ProxyPassMatch "^/((?!(?:errors|\.well-known)/acme-challenge/).*)" "{FORWARD}$1" retry=30 timeout=7200
    ProxyPassReverse "/" "{FORWARD}"
    # SECTION proxy_fwd END.
    # SECTION fwd END.

    # SECTION addons BEGIN.
    # SECTION addons END.

    Include "{HTTPD_CUSTOM_SITES_DIR}/{DOMAIN_NAME}.conf"
</VirtualHost>
