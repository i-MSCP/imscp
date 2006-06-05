    php_admin_value open_basedir "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}:{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp:{PEAR_DIR}"
    php_admin_value upload_tmp_dir "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp"
    php_admin_value session.save_path "{WWW_DIR}/{DMN_NAME}{MOUNT_POINT}/phptmp"
    php_admin_value sendmail_path '/usr/sbin/sendmail -f{SUEXEC_USER} -t -i'

