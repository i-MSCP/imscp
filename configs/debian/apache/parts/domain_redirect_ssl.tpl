<VirtualHost {DMN_IP}:443>

    ServerAdmin	webmaster@{DMN_NAME}
    ServerName	{DMN_NAME}
    ServerAlias	*.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

    Redirect / {FORWARD}

    SSLEngine On
    SSLCertificateFile		{CERT}
    SSLCertificateKeyFile	{CERT}
    SSLCertificateChainFile	{CERT}

</VirtualHost>
