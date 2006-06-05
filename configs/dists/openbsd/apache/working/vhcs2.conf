# httpd Data BEGIN.

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
    php_flag register_globals On
    php_admin_value open_basedir "/var/www/vhcs2/gui/tools/filemanager/:/tmp/:/usr/share/php/"
</Directory>

Alias /vhcs_images /var/www/vhcs2/gui/images
<Directory /var/www/vhcs2/gui/images>
    AllowOverride none 
    Options MultiViews IncludesNoExec FollowSymLinks
</Directory>

#
# Default GUI.
#

<VirtualHost _default_:*> 

    DocumentRoot /var/www/vhcs2/gui

    <Directory /var/www/vhcs2/gui>
        Options Indexes Includes FollowSymLinks MultiViews
        AllowOverride None
        Order allow,deny
        Allow from all
    </Directory>

</VirtualHost>

# httpd [{IP}] virtual host entry BEGIN.
# httpd [{IP}] virtual host entry END.

# httpd Data END.
