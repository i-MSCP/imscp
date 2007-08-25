	ProxyRequests Off

	<Proxy *>
		Order deny,allow
		Allow from all
	</Proxy>

	ProxyPass 			/stats 	http://localhost/stats
	ProxyPassReverse 	/stats	http://localhost/stats