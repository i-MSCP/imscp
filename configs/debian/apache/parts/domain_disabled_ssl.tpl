<VirtualHost {DOMAIN_IPS}>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot {USER_WEB_DIR}/domain_disabled_pages

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    <Directory {USER_WEB_DIR}/domain_disabled_pages>
        Options None
        AllowOverride None
        {AUTHZ_ALLOW_ALL}
    </Directory>

    SSLEngine On
    SSLCertificateFile {CERTIFICATE}
    SSLCertificateChainFile {CERTIFICATE}

    # SECTION hsts BEGIN.
    Header always set Strict-Transport-Security "max-age={HSTS_MAX_AGE}{HSTS_INCLUDE_SUBDOMAINS}"
    # SECTION hsts END.

    RewriteEngine on
    RewriteCond %{REQUEST_URI} !^/(?:images/(?:favicon\.ico|(?:imscp_logo32|stripe)\.png))?$ [NC]
    RewriteRule ^.*$ https://www.{DOMAIN_NAME}/ [R=303,L]
</VirtualHost>
