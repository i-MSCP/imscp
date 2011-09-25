    Alias /stats    {HOME_DIR}{MOUNT_POINT}/statistics/

    <Directory "{HOME_DIR}{MOUNT_POINT}/statistics">
        AllowOverride AuthConfig
        DirectoryIndex awstats.{ALS_NAME}.html
        Order allow,deny
        Allow from all
    </Directory>

   <Location /stats>
       AuthType Basic
       AuthName "Statistics for domain {ALS_NAME}"
       AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILE_NAME}
       AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILE_NAME}
       Require group {AWSTATS_GROUP_AUTH}
   </Location>
