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
# Log processing
#

LogFormat "%v %h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %I %O" imscplog

CustomLog "{PIPE}{ROOT_DIR}/engine/imscp-apache-logger" imscplog
ErrorLog "{PIPE}{ROOT_DIR}/engine/imscp-apache-logger -t error"

#
# mod_cband configuration
#

<IfModule mod_cband.c>
    CBandScoreFlushPeriod 10
    CBandRandomPulse On
</IfModule>

#
# let the customer decide what charset he likes to use
#

AddDefaultCharset Off

#
# Access for errors directory
#

<Directory {APACHE_WWW_DIR}/*/errors>
	Order allow,deny
	Allow from all
</Directory>

#
# Header End
#

# httpd [{IP}] virtual host entry BEGIN.
# httpd [{IP}] virtual host entry END.

# httpd Data END.
