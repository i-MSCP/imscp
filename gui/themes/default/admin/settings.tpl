
<script>
    $(function () {
        $("#lostpassword,#passwd_strong,#bruteforce").change(function () {
            if ($(this).val() == '1') {
                $(this).parents().nextAll(".display").show();
            } else {
                $(this).parents().nextAll(".display").hide();
            }
        }).trigger('change');

        $(".accordion").accordion({
            heightStyle: "content",
            collapsible: true,
            animated: 'slide',
            active: typeof(Storage) !== "undefined" ? parseInt(sessionStorage.getItem("/admin/settings.php")) || 0 : 0,
            activate: function () {
                if ((typeof(Storage) !== "undefined")) {
                    sessionStorage.setItem("/admin/settings.php", parseInt($(this).accordion('option', 'active')));
                }
            }
        });
    });
</script>
<form action="settings.php" method="post" name="frmsettings" id="frmsettings">
    <div class="accordion">
        <h1><strong>{TR_UPDATES}</strong></h1>
        <div>
            <div class="odd">
                <div class="left">
                    <label for="checkforupdate">{TR_CHECK_FOR_UPDATES}</label>
                </div>
                <div class="right">
                    <select name="checkforupdate" id="checkforupdate">
                        <option value="0"{CHECK_FOR_UPDATES_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{CHECK_FOR_UPDATES_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
        </div>
        <h1><strong>{TR_LOSTPASSWORD}</strong></h1>
        <div>
            <div class="odd">
                <div class="left">
                    <label for="lostpassword">{TR_LOSTPASSWORD}</label>
                </div>
                <div class="right">
                    <select name="lostpassword" id="lostpassword">
                        <option value="0"{LOSTPASSWORD_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{LOSTPASSWORD_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="display">
                <div class="even">
                    <div class="left">
                        <label for="lostpassword_timeout">{TR_LOSTPASSWORD_TIMEOUT}</label>
                    </div>
                    <div class="right">
                        <input type="number" name="lostpassword_timeout" id="lostpassword_timeout" min="1" max="1440" value="{LOSTPASSWORD_TIMEOUT_VALUE}">
                    </div>
                </div>
            </div>
        </div>
        <h1><strong>{TR_PASSWORD_SETTINGS}</strong></h1>
        <div>
            <div class="odd">
                <div class="left">
                    <label for="passwd_strong">{TR_PASSWD_STRONG}</label>
                </div>
                <div class="right">
                    <select name="passwd_strong" id="passwd_strong">
                        <option value="0"{PASSWD_STRONG_OFF}>{TR_DISABLED}</option>
                        <option value="1"{PASSWD_STRONG_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="display">
                <div class="even" style="width: 100%">
                    <div class="left">
                        <label for="passwd_chars">{TR_PASSWD_CHARS}</label>
                    </div>
                    <div class="right">
                        <input type="number" name="passwd_chars" id="passwd_chars" min="6" max="32" value="{PASSWD_CHARS}">
                    </div>
                </div>
            </div>
        </div>
        <h1><strong>{TR_BRUTEFORCE}</strong></h1>
        <div>
            <div class="odd">
                <div class="left">
                    <label for="bruteforce">{TR_BRUTEFORCE}</label>
                </div>
                <div class="right">
                    <select name="bruteforce" id="bruteforce">
                        <option value="0"{BRUTEFORCE_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{BRUTEFORCE_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="display">
                <div class="even">
                    <div class="left">
                        <label for="bruteforce_between">{TR_BRUTEFORCE_BETWEEN}</label>
                    </div>
                    <div class="right">
                        <select name="bruteforce_between" id="bruteforce_between">
                            <option value="0"{BRUTEFORCE_BETWEEN_SELECTED_OFF}>{TR_DISABLED}</option>
                            <option value="1"{BRUTEFORCE_BETWEEN_SELECTED_ON}>{TR_ENABLED}</option>
                        </select>
                    </div>
                </div>
                <div class="odd">
                    <div class="left">
                        <label for="bruteforce_max_login">{TR_BRUTEFORCE_MAX_LOGIN}</label>
                    </div>
                    <div class="right">
                        <input type="text" name="bruteforce_max_login" id="bruteforce_max_login" value="{BRUTEFORCE_MAX_LOGIN_VALUE}" maxlength="3">
                    </div>
                </div>
                <div class="even">
                    <div class="left">
                        <label for="bruteforce_block_time">{TR_BRUTEFORCE_BLOCK_TIME}</label>
                    </div>
                    <div class="right">
                        <input name="bruteforce_block_time" id="bruteforce_block_time" type="text" value="{BRUTEFORCE_BLOCK_TIME_VALUE}" maxlength="3">
                    </div>
                </div>
                <div class="odd">
                    <div class="left">
                        <label for="bruteforce_between_time">{TR_BRUTEFORCE_BETWEEN_TIME}</label>
                    </div>
                    <div class="right">
                        <input name="bruteforce_between_time" id="bruteforce_between_time" type="text" value="{BRUTEFORCE_BETWEEN_TIME_VALUE}" maxlength="3">
                    </div>
                </div>
                <div class="even">
                    <div class="left"><label for="bruteforce_max_attempts_before_wait">{TR_BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT}</label>
                    </div>
                    <div class="right">
                        <input name="bruteforce_max_attempts_before_wait" id="bruteforce_max_attempts_before_wait" type="text" value="{BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT}" maxlength="3">
                    </div>
                </div>
                <div class="odd">
                    <div class="left">
                        <label for="bruteforce_max_capcha">{TR_BRUTEFORCE_MAX_CAPTCHA}</label>
                    </div>
                    <div class="right">
                        <input name="bruteforce_max_capcha" id="bruteforce_max_capcha" type="text" value="{BRUTEFORCE_MAX_CAPTCHA}" maxlength="3">
                    </div>
                </div>
            </div>
        </div>
        <h1><strong>{TR_MAIL_SETTINGS}</strong></h1>
        <div>
            <div class="odd">
                <div class="left">
                    <label for="create_default_email_addresses">{TR_CREATE_DEFAULT_EMAIL_ADDRESSES}</label>
                </div>
                <div class="right">
                    <select name="create_default_email_addresses" id="create_default_email_addresses">
                        <option value="0"{CREATE_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
                        <option value="1"{CREATE_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="even">
                <div class="left">
                    <label for="count_default_email_addresses">{TR_COUNT_DEFAULT_EMAIL_ADDRESSES}</label>
                </div>
                <div class="right">
                    <select name="count_default_email_addresses" id="count_default_email_addresses">
                        <option value="0"{COUNT_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
                        <option value="1"{COUNT_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="odd">
                <div class="left">
                    <label for="protect_default_email_addresses">{PROTECT_DEFAULT_EMAIL_ADDRESSES}</label>
                </div>
                <div class="right">
                    <select name="protect_default_email_addresses" id="protect_default_email_addresses">
                        <option value="0"{PROTECT_DEFAULT_EMAIL_ADDRESSES_OFF}>{TR_DISABLED}</option>
                        <option value="1"{PROTECT_DEFAULT_EMAIL_ADDRESSES_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="even">
                <div class="left">
                    <label for="hard_mail_suspension">{TR_HARD_MAIL_SUSPENSION}</label>
                </div>
                <div class="right">
                    <select name="hard_mail_suspension" id="hard_mail_suspension">
                        <option value="0"{HARD_MAIL_SUSPENSION_OFF}>{TR_DISABLED}</option>
                        <option value="1"{HARD_MAIL_SUSPENSION_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="odd">
                <div class="left">
                    <label for="email_quota_sync_mode">{TR_EMAIL_QUOTA_SYNC_MODE}</label>
                </div>
                <div class="right">
                    <select name="email_quota_sync_mode" id="email_quota_sync_mode">
                        <option value="0"{REDISTRIBUTE_EMAIl_QUOTA_NO}>{TR_NO}</option>
                        <option value="1"{REDISTRIBUTE_EMAIl_QUOTA_YES}>{TR_YES}</option>
                    </select>
                </div>
            </div>
        </div>
        <h1><strong>{TR_OTHER_SETTINGS}</strong></h1>
        <div>
            <div class="odd">
                <div class="left">
                    <label for="def_language">{TR_USER_INITIAL_LANG}</label>
                </div>
                <div class="right">
                    <select name="def_language" id="def_language">
                        <!-- BDP: def_language -->
                        <option value="{LANG_VALUE}"{LANG_SELECTED}>{LANG_NAME}</option>
                        <!-- EDP: def_language -->
                    </select>
                </div>
            </div>
            <div class="even">
                <div class="left">
                    <label for="support_system">{TR_SUPPORT_SYSTEM}</label>
                </div>
                <div class="right">
                    <select name="support_system" id="support_system">
                        <option value="0"{SUPPORT_SYSTEM_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{SUPPORT_SYSTEM_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="odd">
                <div class="left">
                    <label for="domain_rows_per_page">{TR_DOMAIN_ROWS_PER_PAGE}</label>
                </div>
                <div class="right">
                    <input name="domain_rows_per_page" id="domain_rows_per_page" type="number" min="10" max="100" value="{DOMAIN_ROWS_PER_PAGE}" maxlength="5">
                </div>
            </div>
            <div class="even">
                <div class="left">
                    <label for="log_level">{TR_LOG_LEVEL}</label>
                </div>
                <div class="right">
                    <select name="log_level" id="log_level">
                        <option value="0"{LOG_LEVEL_SELECTED_OFF}>{TR_E_USER_OFF}</option>
                        <option value="E_USER_ERROR"{LOG_LEVEL_SELECTED_ERROR}>{TR_E_USER_ERROR}</option>
                        <option value="E_USER_WARNING"{LOG_LEVEL_SELECTED_WARNING}>{TR_E_USER_WARNING}</option>
                        <option value="E_USER_NOTICE"{LOG_LEVEL_SELECTED_NOTICE}>{TR_E_USER_NOTICE}</option>
                    </select>
                </div>
            </div>
            <div class="odd">
                <div class="left">
                    <label for="prevent_external_login_admin">{TR_PREVENT_EXTERNAL_LOGIN_ADMIN}</label>
                </div>
                <div class="right">
                    <select name="prevent_external_login_admin" id="prevent_external_login_admin">
                        <option value="0"{PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{PREVENT_EXTERNAL_LOGIN_ADMIN_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="even">
                <div class="left">
                    <label for="prevent_external_login_reseller">{TR_PREVENT_EXTERNAL_LOGIN_RESELLER}</label>
                </div>
                <div class="right">
                    <select name="prevent_external_login_reseller" id="prevent_external_login_reseller">
                        <option value="0"{PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{PREVENT_EXTERNAL_LOGIN_RESELLER_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="odd">
                <div class="left">
                    <label for="prevent_external_login_client">{TR_PREVENT_EXTERNAL_LOGIN_CLIENT}</label>
                </div>
                <div class="right">
                    <select name="prevent_external_login_client" id="prevent_external_login_client">
                        <option value="0"{PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_OFF}>{TR_DISABLED}</option>
                        <option value="1"{PREVENT_EXTERNAL_LOGIN_CLIENT_SELECTED_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
            <div class="even">
                <div class="left">
                    <label for="enableSSL">{TR_ENABLE_SSL}</label>
                </div>
                <div class="right">
                    <select name="enableSSL" id="enableSSL">
                        <option value="0"{ENABLE_SSL_OFF}>{TR_DISABLED}</option>
                        <option value="1"{ENABLE_SSL_ON}>{TR_ENABLED}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="buttons">
        <input name="Submit" type="submit" value="{TR_UPDATE}">
    </div>
</form>
