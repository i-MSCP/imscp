   ProxyRequests Off

   <Proxy *>
      Order deny,allow
      Allow from all
   </Proxy>

   ProxyPass 			/stats 	http://localhost/stats/{DMN_NAME}
   ProxyPassReverse 	/stats	http://localhost/stats/{DMN_NAME}