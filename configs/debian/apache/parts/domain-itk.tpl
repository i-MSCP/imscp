<VirtualHost {DMN_IP}:80>

	<IfModule mpm_itk_module>
		AssignUserID {USER} {GROUP}
	</IfModule>

	ServerAdmin	webmaster@{DMN_NAME}
	DocumentRoot	{HOME_DIR}/htdocs

	ServerName	{DMN_NAME}
	ServerAlias	www.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

	Alias /errors {WWW_DIR}/{ROOT_DMN_NAME}/errors/

	RedirectMatch permanent ^/ftp[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/ftp/
	RedirectMatch permanent ^/pma[\/]?$		{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/pma/
	RedirectMatch permanent ^/webmail[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/webmail/
	RedirectMatch permanent ^/imscp[\/]?$	{BASE_SERVER_VHOST_PREFIX}{BASE_SERVER_VHOST}/

	ErrorDocument 401 /errors/401.html
	ErrorDocument 403 /errors/403.html
	ErrorDocument 404 /errors/404.html
	ErrorDocument 500 /errors/500.html
	ErrorDocument 503 /errors/503.html

	<IfModule mod_cband.c>
		CBandUser {USER}
	</IfModule>

	# SECTION awstats support BEGIN.

	# SECTION awstats dynamic BEGIN.
		ProxyRequests Off
		<Proxy *>
			Order deny,allow
			Allow from all
		</Proxy>
		ProxyPass			/stats	http://localhost/stats/{DMN_NAME}
		ProxyPassReverse	/stats	http://localhost/stats/{DMN_NAME}
		<Location /stats>
			<IfModule mod_rewrite.c>
				RewriteEngine on
				RewriteRule ^(.+)\?config=([^\?\&]+)(.*) $1\?config={DMN_NAME}&$3 [NC,L]
			</IfModule>
			AuthType Basic
			AuthName "Statistics for domain {DMN_NAME}"
			AuthUserFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
			AuthGroupFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
			Require group {AWSTATS_GROUP_AUTH}
		</Location>
	# SECTION awstats dynamic END.

	# SECTION awstats static BEGIN.
		Alias /awstatsicons 	"{AWSTATS_WEB_DIR}/icon/"
		Alias /stats			"{HOME_DIR}/statistics/"
		<Directory "{HOME_DIR}/statistics">
			AllowOverride AuthConfig
			DirectoryIndex awstats.{DMN_NAME}.html
			Order allow,deny
			Allow from all
		</Directory>
		<Location /stats>
			AuthType Basic
			AuthName "Statistics for domain {DMN_NAME}"
			AuthUserFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_USERS_FILE_NAME}
			AuthGroupFile {WWW_DIR}/{ROOT_DMN_NAME}/{HTACCESS_GROUPS_FILE_NAME}
			Require group {AWSTATS_GROUP_AUTH}
		</Location>
	# SECTION awstats static END.

	# SECTION awstats support END.

	# SECTION cgi support BEGIN.
		ScriptAlias /cgi-bin/ {HOME_DIR}/cgi-bin/
		<Directory {HOME_DIR}/cgi-bin>
			AllowOverride AuthConfig
			#Options ExecCGI
			Order allow,deny
			Allow from all
		</Directory>
	# SECTION cgi support END.

	<Directory {HOME_DIR}/htdocs>
		# httpd dmn entry PHP support BEGIN.
		# httpd dmn entry PHP support END.
		Options -Indexes Includes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		Allow from all
	</Directory>

	# SECTION php enabled BEGIN.
		<IfModule mod_php5.c>
			php_admin_value open_basedir "{HOME_DIR}/:{HOME_DIR}/phptmp/:{PEAR_DIR}/{PHPINI_OPEN_BASEDIR}"
			php_admin_value upload_tmp_dir "{HOME_DIR}/phptmp/"
			php_admin_value session.save_path "{HOME_DIR}/phptmp/"
			php_admin_value sendmail_path '/usr/sbin/sendmail -f webmaster@{DMN_NAME} -t -i'

			#Custom values
			php_admin_value max_execution_time "{MAX_EXECUTION_TIME}"
			php_admin_value max_input_time "{MAX_INPUT_TIME}"
			php_admin_value memory_limit "{MEMORY_LIMIT}M"
			php_value error_reporting "{ERROR_REPORTING}"
			php_value display_errors "{DISPLAY_ERRORS}"
			php_admin_value register_globals "{REGISTER_GLOBALS}"
			php_admin_value post_max_size "{POST_MAX_SIZE}M"
			php_admin_value upload_max_filesize "{UPLOAD_MAX_FILESIZE}M"
			php_admin_value allow_url_fopen "{ALLOW_URL_FOPEN}"
		</IfModule>
	# SECTION php enabled END.

	# SECTION php disabled BEGIN.
		<IfModule mod_php5.c>
			php_admin_flag engine off
		</IfModule>
		<IfModule mod_fastcgi.c>
			RemoveHandler .php
			RemoveType .php
		</IfModule>
		<IfModule mod_fcgid.c>
			RemoveHandler .php
			RemoveType .php
		</IfModule>
	# SECTION php disabled END.

	Include {APACHE_CUSTOM_SITES_CONFIG_DIR}/{DMN_NAME}.conf

</VirtualHost>
