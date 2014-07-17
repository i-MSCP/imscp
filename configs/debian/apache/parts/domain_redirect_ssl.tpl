<VirtualHost {DOMAIN_IP}:443>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias www.{DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    LogLevel error
    ErrorLog {HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log

    Redirect / {FORWARD}

    SSLEngine On
    SSLCertificateFile {CERTIFICATE}
    SSLCertificateChainFile {CERTIFICATE}
</VirtualHost>
