    ScriptAlias /cgi-bin/ {WWW_DIR}/{DMN_NAME}/cgi-bin/
    <Directory {WWW_DIR}/{DMN_NAME}/cgi-bin>
        AllowOverride None
        #Options ExecCGI
        Order allow,deny
        Allow from all
    </Directory>
