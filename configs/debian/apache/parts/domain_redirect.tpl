<VirtualHost {DOMAIN_IPS}>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    Alias /errors/ {HOME_DIR}/errors/
    <Directory {HOME_DIR}/errors/>
        {AUTHZ_ALLOW_ALL}
    </Directory>

    # SECTION standard_redirect BEGIN.
    <LocationMatch "^/(?!.well-known/)">
        Redirect {FORWARD_TYPE} / {FORWARD}
    </LocationMatch>
    # SECTION standard_redirect END.
    # SECTION proxy_redirect BEGIN.
    # SECTION ssl_proxy BEGIN.
    SSLProxyEngine on
    # SECTION ssl_proxy END.
    RequestHeader set X-Forwarded-Proto "http"
    RequestHeader set X-Forwarded-Port 80
    ProxyPreserveHost {FORWARD_PRESERVE_HOST}
    ProxyPass /errors/ !
    ProxyPass /.well-known/ !
    ProxyPass / {FORWARD} retry=30 timeout=7200
    ProxyPassReverse / {FORWARD}
    # SECTION proxy_redirect END.
</VirtualHost>
