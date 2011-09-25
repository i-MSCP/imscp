   ProxyRequests Off

   <Proxy *>
      Order deny,allow
      Allow from all
   </Proxy>

   ProxyPass			/stats	http://localhost/stats/{DMN_NAME}
   ProxyPassReverse		/stats	http://localhost/stats/{DMN_NAME}

    <Location /stats>
        <IfModule mod_rewrite.c>
            RewriteEngine on
            RewriteRule ^(.+)\?config=([^\?\&]+)(.*) $1\?config={DMN_NAME}&$3 [NC,L]
        </IfModule>
        AuthType Basic
        AuthName "Statistics for domain {DMN_NAME}"
        AuthUserFile {HOME_DIR}/{HTACCESS_USERS_FILE_NAME}
        AuthGroupFile {HOME_DIR}/{HTACCESS_GROUPS_FILE_NAME}
        Require group {AWSTATS_GROUP_AUTH}
    </Location>
