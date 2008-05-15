    <IfModule mod_php4.c>
        php_admin_value open_basedir "{WWW_DIR}/{DMN_NAME}/:{WWW_DIR}/{DMN_NAME}/phptmp/:{PEAR_DIR}/"
        php_admin_value upload_tmp_dir "{WWW_DIR}/{DMN_NAME}/phptmp/"
        php_admin_value session.save_path "{WWW_DIR}/{DMN_NAME}/phptmp/"
        php_admin_value sendmail_path '/usr/sbin/sendmail -f {SUEXEC_USER} -t -i'
    </IfModule>
    <IfModule mod_php5.c>
        php_admin_value open_basedir "{WWW_DIR}/{DMN_NAME}/:{WWW_DIR}/{DMN_NAME}/phptmp/:{PEAR_DIR}/"
        php_admin_value upload_tmp_dir "{WWW_DIR}/{DMN_NAME}/phptmp/"
        php_admin_value session.save_path "{WWW_DIR}/{DMN_NAME}/phptmp/"
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
