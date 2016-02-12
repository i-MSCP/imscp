
<table class="firstColFixed">
    <thead>
    <tr>
        <th colspan="2">{TR_DOMAIN_DETAILS}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{TR_DOMAIN_NAME}</td>
        <td>{VL_DOMAIN_NAME}</td>
    </tr>
    <tr>
        <td>{TR_DOMAIN_IP}</td>
        <td>{VL_DOMAIN_IP}</td>
    </tr>
    <tr>
        <td>{TR_STATUS}</td>
        <td>{VL_STATUS}</td>
    </tr>
    <tr>
        <td>{TR_PHP_SUPP}</td>
        <td>{VL_PHP_SUPP}</td>
    </tr>
    <tr>
        <td>{TR_PHP_EDITOR_SUPP}</td>
        <td>{VL_PHP_EDITOR_SUPP}</td>
    </tr>
    <tr>
        <td>{TR_CGI_SUPP}</td>
        <td>{VL_CGI_SUPP}</td>
    </tr>
    <tr>
        <td>{TR_DNS_SUPP}</td>
        <td>{VL_DNS_SUPP}</td>
    </tr>
    <tr>
        <td>{TR_EXT_MAIL_SUPP}</td>
        <td>{VL_EXT_MAIL_SUPP}</td>
    </tr>
    <tr>
        <td>{TR_SOFTWARE_SUPP}</td>
        <td>{VL_SOFTWARE_SUPP}</td>
    </tr>
    <tr>
        <td>{TR_BACKUP_SUPP}</td>
        <td>{VL_BACKUP_SUP}</td>
    </tr>
    <tr>
        <td>{TR_TRAFFIC}</td>
        <td>
            <div class="graph"><span style="width:{VL_TRAFFIC_PERCENT}%">&nbsp;</span></div>
            {VL_TRAFFIC_USED} / {VL_TRAFFIC_LIMIT}
        </td>
    </tr>
    <tr>
        <td>{TR_DISK}</td>
        <td>
            <div class="graph"><span style="width:{VL_DISK_PERCENT}%">&nbsp;</span></div>
            {VL_DISK_USED} / {VL_DISK_LIMIT}
        </td>
    </tr>
    </tbody>
</table>
<table class="firstColFixed">
    <thead>
    <tr>
        <th>{TR_FEATURE}</th>
        <th>{TR_USED}</th>
        <th>{TR_LIMIT}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{TR_SUBDOM_ACCOUNTS}</td>
        <td>{VL_SUBDOM_ACCOUNTS_USED}</td>
        <td>{VL_SUBDOM_ACCOUNTS_LIMIT}</td>
    </tr>
    <tr>
        <td>{TR_DOMALIAS_ACCOUNTS}</td>
        <td>{VL_DOMALIAS_ACCOUNTS_USED}</td>
        <td>{VL_DOMALIAS_ACCOUNTS_LIMIT}</td>
    </tr>
    <tr>
        <td>{TR_MAIL_ACCOUNTS}</td>
        <td>{VL_MAIL_ACCOUNTS_USED}</td>
        <td>{VL_MAIL_ACCOUNTS_LIMIT}</td>
    </tr>
    <tr>
        <td>{TR_MAIL_QUOTA}</td>
        <td>{VL_MAIL_QUOTA_USED}</td>
        <td>{VL_MAIL_QUOTA_LIMIT}</td>
    </tr>
    <tr>
        <td>{TR_FTP_ACCOUNTS}</td>
        <td>{VL_FTP_ACCOUNTS_USED}</td>
        <td>{VL_FTP_ACCOUNTS_LIMIT}</td>
    </tr>
    <tr>
        <td>{TR_SQL_DB_ACCOUNTS}</td>
        <td>{VL_SQL_DB_ACCOUNTS_USED}</td>
        <td>{VL_SQL_DB_ACCOUNTS_LIMIT}</td>
    </tr>
    <tr>
        <td>{TR_SQL_USER_ACCOUNTS}</td>
        <td>{VL_SQL_USER_ACCOUNTS_USED}</td>
        <td>{VL_SQL_USER_ACCOUNTS_LIMIT}</td>
    </tr>
    </tbody>
</table>
<div class="buttons">
    <a class="link_as_button" href="domain_edit.php?edit_id={DOMAIN_ID}">{TR_EDIT}</a>
    <a class="link_as_button" href="users.php">{TR_BACK}</a>
</div>
