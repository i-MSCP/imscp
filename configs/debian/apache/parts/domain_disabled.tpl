<VirtualHost {DOMAIN_IPS}>
    ServerAdmin webmaster@{DOMAIN_NAME}
    ServerName {DOMAIN_NAME}
    ServerAlias {SERVER_ALIASES}

    LogLevel error
    ErrorLog "{HTTPD_LOG_DIR}/{DOMAIN_NAME}/error.log"

    # SECTION dmn BEGIN.
    DocumentRoot "{USER_WEB_DIR}/domain_disabled_pages"

    # SECTION ssl BEGIN.
    SSLEngine On
    SSLCertificateFile      "{CERTIFICATE}"
    SSLCertificateChainFile "{CERTIFICATE}"

    Header always set Strict-Transport-Security "max-age={HSTS_MAX_AGE}{HSTS_INCLUDE_SUBDOMAINS}"
    # SECTION ssl END.

    DirectoryIndex index.html

    <Directory "{USER_WEB_DIR}/domain_disabled_pages">
        AllowOverride None
        Options FollowSymLinks 
        Require all granted
    </Directory>

    RedirectMatch 303 "^/(?!(?:images/.+|index\.html|$))" "{HTTP_URI_SCHEME}www.{DOMAIN_NAME}/"
    # SECTION dmn END.

    # SECTION fwd BEGIN.
    RedirectMatch {FORWARD_TYPE} "^/((?!\.well-known/).*)" "{FORWARD}$1"
    # SECTION fwd END.
</VirtualHost>
