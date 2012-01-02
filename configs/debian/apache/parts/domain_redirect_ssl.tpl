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

	ServerName	{DMN_NAME}
	ServerAlias	*.{DMN_NAME} {DMN_NAME} {ALIAS}.{BASE_SERVER_VHOST}

	Redirect / {FORWARD}

</VirtualHost>
