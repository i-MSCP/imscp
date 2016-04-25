<IfModule fastcgi_module>
    Action php{PHP_VERSION}-fcgi /php{PHP_VERSION}-fcgi
    AddHandler php5-fcgi .php .php3 .php4 .php5 .php7 .pht .phtml

    <Directory /var/lib/apache2/fastcgi>
        Options None
        AllowOverride None
        {AUTHZ_ALLOW_ALL}
    </Directory>

    # SECTION custom BEGIN.
    # SECTION custom END.
</IfModule>
