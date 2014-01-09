<VirtualHost {DOMAIN_IP}:443>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    Redirect / {FORWARD}

    SSLEngine On
    SSLCertificateFile {CERT}
    SSLCertificateChainFile {CERT}
</VirtualHost>
