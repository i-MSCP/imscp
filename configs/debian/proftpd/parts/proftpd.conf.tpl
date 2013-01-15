<Directory {PATH}>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/*>
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

<Directory {PATH}/phptmp>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
  DenyAll
 </Limit>
</Directory>
<Directory {PATH}/phptmp/*>
 <Limit RMD XRMD SITE_RMDIR DELE RNFR SITE_CHMOD SITE_CHGRP>
    AllowAll
 </Limit>
</Directory>
