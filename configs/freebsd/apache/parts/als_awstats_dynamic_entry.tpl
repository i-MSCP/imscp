   ProxyRequests Off

   <Proxy *>
      Order deny,allow
      Allow from all
   </Proxy>

   ProxyPass		/stats	http://localhost/stats/{ALS_NAME}
   ProxyPassReverse	/stats	http://localhost/stats/{ALS_NAME}