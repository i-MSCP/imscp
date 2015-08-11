<VirtualHost {DOMAIN_IP}:80>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    # SECTION hsts_disabled BEGIN.
    Redirect 301 / {FORWARD}
    # SECTION hsts_disabled END.
    # SECTION hsts_enabled BEGIN.
    Redirect 307 / https://{DOMAIN_NAME}/
    # SECTION hsts_enabled END.
</VirtualHost>
