<VirtualHost {DMN_IP}:443>

	#
	# SSL Start
	#

	SSLEngine On
	SSLCertificateFile		{CERT}
	SSLCertificateKeyFile	{CERT}
	SSLCertificateChainFile	{CERT}

	#
	# SSL End
	#

	ServerAdmin	webmaster@{DMN_NAME}
	DocumentRoot	{PARENT_DIR}/domain_disable_page

	ServerName	{DMN_NAME}
	ServerAlias	*.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

	Alias /errors {WWW_DIR}/{ROOT_DMN_NAME}/errors/

	RedirectMatch permanent ^/ftp[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/ftp/
	RedirectMatch permanent ^/pma[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/pma/
	RedirectMatch permanent ^/webmail[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/webmail/
	RedirectMatch permanent ^/imscp[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/

	<IfModule mod_cband.c>
		CBandUser {USER}
	</IfModule>

	<Directory {PARENT_DIR}/domain_disable_page>
		Options -Indexes Includes FollowSymLinks MultiViews
		AllowOverride None
		Order allow,deny
		Allow from all
	</Directory>

</VirtualHost>
