            <IfModule mod_php4.c>
                php_admin_flag engine off
            </IfModule>
            <IfModule mod_php5.c>
                php_admin_flag engine off
            </IfModule>
            <IfModule mod_fastcgi.c>
                RemoveHandler .php
                RemoveType .php
            </IfModule>
