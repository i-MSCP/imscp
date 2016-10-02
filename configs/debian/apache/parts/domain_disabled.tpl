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

    RewriteEngine on
    RewriteCond %{REQUEST_URI} !^/(?:images/(?:favicon\.ico|(?:imscp_logo32|stripe)\.png))?$ [NC]
    RewriteRule ^.*$ http://www.{DOMAIN_NAME}/ [R=303,L]
</VirtualHost>
