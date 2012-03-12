<Directory {PATH}/cgi-bin>
 <Limit RMD RNFR DELE XRMD>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/cgi-bin/*>
 <Limit RMD RNFR DELE XRMD>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/htdocs>
 <Limit RMD RNFR DELE XRMD>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/htdocs/*>
 <Limit RMD RNFR DELE XRMD>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/phptmp>
 <Limit RMD RNFR DELE XRMD>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/phptmp/*>
 <Limit RMD RNFR DELE XRMD>
    AllowAll
 </Limit>
</Directory>
