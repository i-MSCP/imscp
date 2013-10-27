<VirtualHost {DOMAIN_IP}:80>

    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    Redirect / {FORWARD}

</VirtualHost>
