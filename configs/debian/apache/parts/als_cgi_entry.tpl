    ScriptAlias /cgi-bin/ {HOME_DIR}{MOUNT_POINT}/cgi-bin/
    <Directory {HOME_DIR}{MOUNT_POINT}/cgi-bin>
        AllowOverride AuthConfig
        #Options ExecCGI
        Order allow,deny
        Allow from all
    </Directory>
