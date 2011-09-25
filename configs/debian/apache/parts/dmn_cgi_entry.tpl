    ScriptAlias /cgi-bin/ {HOME_DIR}/cgi-bin/
    <Directory {HOME_DIR}/cgi-bin>
        AllowOverride AuthConfig
        #Options ExecCGI
        Order allow,deny
        Allow from all
    </Directory>
