# httpd Data BEGIN.

#
# wget-hack prevention
#

<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteCond %{HTTP_USER_AGENT} ^LWP::Simple
    RewriteRule ^/.* http://%{REMOTE_ADDR}/ [L,E=nolog:1]
</IfModule>

#
# Web traffic accounting.
#

LogFormat "%B" traff

#
# GUI Location.
#

Alias /vhcs2 /var/www/vhcs2/gui
<Directory /var/www/vhcs2/gui>
    AllowOverride none
    Options MultiViews IncludesNoExec FollowSymLinks
    ErrorDocument 404 /vhcs2/errordocs/index.php
    DirectoryIndex index.html index.php
</Directory>

<Directory /var/www/vhcs2/gui/tools/filemanager>
    <IfModule mod_php4.c>
        php_flag register_globals On
        php_admin_value open_basedir "/var/www/vhcs2/gui/tools/filemanager/:/tmp/:/usr/share/php/"
    </IfModule>
</Directory>

Alias /vhcs_images /var/www/vhcs2/gui/images
<Directory /var/www/vhcs2/gui/images>
    AllowOverride none
    Options MultiViews IncludesNoExec FollowSymLinks
</Directory>

#
# Default GUI.
#

# Temporary changed; now it's working
# will be replaced in future
<VirtualHost {IP}:80>

    DocumentRoot /var/www/vhcs2/gui

    <IfModule mod_fastcgi.c>
        SuexecUserGroup vu2000 vu2000
    </IfModule>

    <Directory /var/www/vhcs2/gui>
        Options Indexes Includes FollowSymLinks MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </Directory>

    <IfModule mod_fastcgi.c>
    ScriptAlias /php4/ /var/www/fcgi/master/
        <Directory "/var/www/fcgi/master">
            AllowOverride None
            Options +ExecCGI MultiViews Indexes
            Order allow,deny
            Allow from all
        </Directory>
    </IfModule>

</VirtualHost>

#
# AWStats
#

Alias /awstatsclasses "/var/www/awstats/classes/"
Alias /awstatscss "/var/www/awstats/css/"
Alias /awstatsicons "/var/www/awstats/icon/"
Alias /awstatsjs "/var/www/awstats/js/"
Alias /stats "/usr/lib/cgi-bin/awstats/"

<Directory /usr/lib/cgi-bin/awstats>
    AllowOverride AuthConfig
    Options -Includes FollowSymLinks +ExecCGI MultiViews
    AddHandler cgi-script cgi pl
    DirectoryIndex awstats.pl
    Order deny,allow
    Allow from all
</Directory>

#
# Header End
#

# httpd [{IP}] virtual host entry BEGIN.
# httpd [{IP}] virtual host entry END.

# httpd Data END.
