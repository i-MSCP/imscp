#!/bin/sh
umask 027
export PHPRC={FCGI_DIR}/php5/
export PHP_FCGI_MAX_REQUESTS=1000
export PHP_FCGI_CHILDREN=0
export TMPDIR={WEB_DIR}/phptmp
exec {PHP_CGI_BIN} "$@"
