    <IfModule mod_php.c>
        php_admin_value open_basedir "{WWW_DIR}/{SUB_NAME_PHP2}:{WWW_DIR}/{SUB_NAME_PHP2}/phptmp:{PEAR_DIR}"
        php_admin_value upload_tmp_dir "{WWW_DIR}/{SUB_NAME_PHP2}/phptmp"
        php_admin_value session.save_path "{WWW_DIR}/{SUB_NAME_PHP2}/phptmp"
        php_admin_value sendmail_path '/usr/sbin/sendmail -f{SUEXEC_USER} -t -i'
    </IfModule>
