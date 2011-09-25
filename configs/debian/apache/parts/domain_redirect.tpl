<VirtualHost {DMN_IP}:80>

	ServerAdmin	webmaster@{DMN_NAME}

	ServerName	{DMN_NAME}
	ServerAlias	*.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

	<IfModule mod_cband.c>
		CBandUser {USER}
	</IfModule>

	Redirect / {FORWARD}

</VirtualHost>
