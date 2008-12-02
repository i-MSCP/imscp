    Alias /stats    {WWW_DIR}/{DMN_NAME}/{ALS_NAME}/statistics/

    <Directory "{WWW_DIR}/{DMN_NAME}/{ALS_NAME}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{ALS_NAME}.html
        Order allow,deny
        Allow from all
    </Directory>
