<VirtualHost {DOMAIN_IP}:443>

    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias *.{DOMAIN_NAME} {DOMAIN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot {PARENT_DIR}/domain_disable_page

    Alias /errors {WWW_DIR}/{ROOT_DOMAIN_NAME}/errors/

    <IfModule mod_cband.c>
        CBandUser {USER}
    </IfModule>

    <Directory {PARENT_DIR}/domain_disable_page>
        Options -Indexes Includes FollowSymLinks MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </Directory>

    SSLEngine On
    SSLCertificateFile {CERT}
    SSLCertificateChainFile {CERT}

</VirtualHost>
