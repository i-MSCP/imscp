    <IfModule mod_php4.c>
        php_admin_value open_basedir "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}:{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp/:{PEAR_DIR}/"
        php_admin_value upload_tmp_dir "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp/"
        php_admin_value session.save_path "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp/"
        php_admin_value sendmail_path '/usr/sbin/sendmail -f {SUEXEC_USER} -t -i'
    </IfModule>
    <IfModule mod_php5.c>
        php_admin_value open_basedir "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}:{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp/:{PEAR_DIR}/"
        php_admin_value upload_tmp_dir "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp/"
        php_admin_value session.save_path "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp/"
        php_admin_value sendmail_path '/usr/sbin/sendmail -f {SUEXEC_USER} -t -i'
    </IfModule>
    <IfModule mod_fastcgi.c>
        ScriptAlias /php4/ {STARTER_DIR}/{DMN_NAME}/
        ScriptAlias /php5/ {STARTER_DIR}/{DMN_NAME}/
        <Directory "{STARTER_DIR}/{DMN_NAME}">
            AllowOverride None
            Options +ExecCGI -MultiViews -Indexes
            Order allow,deny
            Allow from all
        </Directory>
    </IfModule>
	<IfModule mod_fcgid.c>
        ScriptAlias /php4/ {STARTER_DIR}/{DMN_NAME}/
        ScriptAlias /php5/ {STARTER_DIR}/{DMN_NAME}/
		<Directory "/var/www/virtual/{DMN_NAME}/htdocs">
			FCGIWrapper {STARTER_DIR}/{DMN_NAME}/php{PHP_VERSION}-fcgi-starter .php
			AddHandler fcgid-script .php
			Options +ExecCGI
		</Directory>
	</IfModule>
