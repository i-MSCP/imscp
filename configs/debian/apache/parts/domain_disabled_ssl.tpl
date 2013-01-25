<VirtualHost {DMN_IP}:443>

    ServerAdmin	webmaster@{DMN_NAME}
    ServerName	{DMN_NAME}
    ServerAlias	*.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    DocumentRoot	{PARENT_DIR}/domain_disable_page

    Alias /errors {WWW_DIR}/{ROOT_DMN_NAME}/errors/

    #RedirectMatch permanent ^/ftp[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/ftp/
    #RedirectMatch permanent ^/pma[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/pma/
    #RedirectMatch permanent ^/webmail[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/webmail/
    #RedirectMatch permanent ^/imscp[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/

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
    SSLCertificateFile		{CERT}
    SSLCertificateKeyFile	{CERT}
    SSLCertificateChainFile	{CERT}

</VirtualHost>
