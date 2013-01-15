<Directory {PATH}>
	HideFiles (\.htpasswd|\.htgroup)$
</Directory>

<Directory {PATH}/backups>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/backups/*>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/cgi-bin>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/cgi-bin/*>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/htdocs>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/htdocs/*>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/errors>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/errors/*>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  AllowAll
 </Limit>
</Directory>

<Directory {PATH}/logs>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/logs/*>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  AllowAll
 </Limit>
</Directory>

<Directory {PATH}/phptmp>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/phptmp/*>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
    AllowAll
 </Limit>
</Directory>

<Directory {PATH}/statistics>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/statistics/*>
 <Limit RMD RNFR DELE XRMD SITE_RMDIR>
  AllowAll
 </Limit>
</Directory>
