<VirtualHost {DOMAIN_IPS}>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot {USER_WEB_DIR}/domain_disabled_pages

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    # SECTION ssl BEGIN.
    SSLEngine On
    SSLCertificateFile      {CERTIFICATE}
    SSLCertificateChainFile {CERTIFICATE}

    Header always set Strict-Transport-Security "max-age={HSTS_MAX_AGE}{HSTS_INCLUDE_SUBDOMAINS}"
    # SECTION ssl END.

    DirectoryIndex index.html

    <Directory {USER_WEB_DIR}/domain_disabled_pages>
        Options None
        AllowOverride None
        Require all granted
    </Directory>

    # SECTION forward BEGIN.
    RedirectMatch {FORWARD_TYPE} ^/((?!(?:\.well-known|errors)/).*) {FORWARD}$1
    # SECTION forward END.

    RedirectMatch 303 ^/(?!(?:images/.+|index\.html|$)) {HTTP_URI_SCHEME}www.{DOMAIN_NAME}/
</VirtualHost>
