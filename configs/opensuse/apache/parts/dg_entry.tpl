
#<IfModule mod_cband.c>
# ##TEMPLATE    <CBandUser {DMN_GRP}>
# ##TEMPLATE        {BWLIMIT_DISABLED} CBandUserLimit {BWLIMIT}Mi
# ##TEMPLATE        {BWLIMIT_DISABLED} CBandUserScoreboard {SCOREBOARDS_DIR}/{DMN_GRP}
# ##TEMPLATE        {BWLIMIT_DISABLED} CBandUserPeriod 4W
# ##TEMPLATE        {BWLIMIT_DISABLED} CBandUserPeriodSlice 1W
# ##TEMPLATE        {BWLIMIT_DISABLED} CBandUserExceededURL http://{BASE_SERVER_VHOST}/errors/bw_exceeded.html
# ##TEMPLATE    </CBandUser>
#</IfModule>


# httpd [{SUB_NAME}] sub entry BEGIN.
# httpd [{SUB_NAME}] sub entry END.

# httpd [{DMN_NAME}] dmn entry BEGIN.
# httpd [{DMN_NAME}] dmn entry END.

