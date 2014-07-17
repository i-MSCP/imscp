#!/bin/sh
umask 027
export PHPRC={PHP_STARTER_DIR}/{DOMAIN_NAME}/php5/
export PHP_FCGI_MAX_REQUESTS=600
export PHP_FCGI_CHILDREN=0
export TMPDIR={WEB_DIR}/phptmp
exec {PHP_CGI_BIN} "$@"
