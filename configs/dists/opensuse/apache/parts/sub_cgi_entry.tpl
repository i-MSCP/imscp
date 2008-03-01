    ScriptAlias /cgi-bin/ {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/cgi-bin/
    <Directory {WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/cgi-bin>
        AllowOverride None
        #Options ExecCGI
        Order allow,deny
        Allow from all
    </Directory>
