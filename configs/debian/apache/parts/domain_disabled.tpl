<VirtualHost {DOMAIN_IP}:80>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot {USER_WEB_DIR}/domain_disabled_pages

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    Alias /errors {HOME_DIR}/errors/

    <Directory {HOME_DIR}/errors>
        {AUTHZ_ALLOW_ALL}
    </Directory>

    <Directory {USER_WEB_DIR}/domain_disabled_pages>
        {AUTHZ_ALLOW_ALL}
    </Directory>
</VirtualHost>
