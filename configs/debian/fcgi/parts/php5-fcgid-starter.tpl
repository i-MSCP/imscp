#!/bin/sh

umask 022

PHPRC="{PHP_STARTER_DIR}/{DMN_NAME}/php5/"
export PHPRC

# This should *always* be 0 when running with mod_fcgid, because
# mod_fcgid is unable to send requests to the children
PHP_FCGI_CHILDREN=0
export PHP_FCGI_CHILDREN

# This directive should be made ineffective by setting a number
# higher than the FcgidMaxRequestsPerProcess defined in the file
# /etc/apache2/mods-enabled/fcgid_imscp.conf
PHP_FCGI_MAX_REQUESTS=1000
export PHP_FCGI_MAX_REQUESTS

TMPDIR="{HOME_DIR}/phptmp"
export TMPDIR

exec {PHP5_FASTCGI_BIN}
