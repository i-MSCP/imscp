
<IfModule mod_cband.c>
    <CBandUser {DMN_GRP}>
        {BWLIMIT_DISABLED} CBandUserLimit {BWLIMIT}Mi
        {BWLIMIT_DISABLED} CBandUserScoreboard {SCOREBOARDS_DIR}/{DMN_GRP}
        {BWLIMIT_DISABLED} CBandUserPeriod 4W
        {BWLIMIT_DISABLED} CBandUserPeriodSlice 1W
        {BWLIMIT_DISABLED} CBandUserExceededURL http://{BASE_SERVER_VHOST}/errordocs/bw_exceeded.html
    </CBandUser>
</IfModule>


# httpd [{SUB_NAME}] sub entry BEGIN.
# httpd [{SUB_NAME}] sub entry END.

# httpd [{DMN_NAME}] dmn entry BEGIN.
# httpd [{DMN_NAME}] dmn entry END.

