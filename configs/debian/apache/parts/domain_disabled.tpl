<VirtualHost {DOMAIN_IP}:80>

    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot {HOME_DIR}/domain_disable_page

    Alias /errors {HOME_DIR}/errors/

    <IfModule mod_cband.c>
    CBandUser {USER}
    </IfModule>

    <Directory {HOME_DIR}/domain_disable_page>
        Options -Indexes +Includes +FollowSymLinks +MultiViews
        AllowOverride None
        {AUTHZ_ALLOW_ALL}
    </Directory>

</VirtualHost>
