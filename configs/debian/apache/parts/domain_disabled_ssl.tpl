<VirtualHost {DOMAIN_IP}:443>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot {HOME_DIR}/domain_disable_page

    Alias /errors {HOME_DIR}/errors/

    <Directory {HOME_DIR}/domain_disable_page>
        {AUTHZ_ALLOW_ALL}
    </Directory>

    SSLEngine On
    SSLCertificateFile {CERT}
    SSLCertificateChainFile {CERT}
</VirtualHost>
