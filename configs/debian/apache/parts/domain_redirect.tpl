<VirtualHost {DOMAIN_IP}:80>

    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias *.{DOMAIN_NAME} {DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    <IfModule mod_cband.c>
        CBandUser {USER}
    </IfModule>

    Redirect / {FORWARD}

</VirtualHost>
