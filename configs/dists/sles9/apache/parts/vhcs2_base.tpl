# httpd Data BEGIN.

#
# Web traffic accounting.
#

LogFormat "%B" traff

#
# GUI Location.
#

Alias /vhcs2 /srv/www/vhcs2/gui
<Directory /srv/www/vhcs2/gui>
    AllowOverride none
    Options MultiViews IncludesNoExec FollowSymLinks
    ErrorDocument 404 /vhcs2/errordocs/index.php
    DirectoryIndex index.html index.php
</Directory>

<Directory /srv/www/vhcs2/gui/tools/filemanager>
    php_flag register_globals On
    php_admin_value open_basedir "/srv/www/vhcs2/gui/tools/filemanager/:/tmp/:/usr/share/php/"
</Directory>

Alias /vhcs_images /srv/www/vhcs2/gui/images
<Directory /srv/www/vhcs2/gui/images>
    AllowOverride none 
    Options MultiViews IncludesNoExec FollowSymLinks
</Directory>

#
# Default GUI.
#

<VirtualHost _default_:*> 

    DocumentRoot /srv/www/vhcs2/gui

    <Directory /srv/www/vhcs2/gui>
        Options Indexes Includes FollowSymLinks MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </Directory>

</VirtualHost>

# httpd [{IP}] virtual host entry BEGIN.
# httpd [{IP}] virtual host entry END.

# httpd Data END.
