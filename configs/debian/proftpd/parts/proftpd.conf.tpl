<Directory {PATH}/cgi-bin>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/cgi-bin/*>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/htdocs>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/htdocs/*>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/phptmp>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/phptmp/*>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
    AllowAll
 </Limit>
</Directory>
