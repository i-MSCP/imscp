#!/bin/sh

# Newly created files: 0640
# Newly created directories: 0750
umask 027

PHPRC="{PHP_STARTER_DIR}/{DOMAIN_NAME}/php5/"
export PHPRC

TMPDIR="{WEB_DIR}/phptmp"
export TMPDIR

PHP_FCGI_CHILDREN=2
export PHP_FCGI_CHILDREN

exec {PHP5_FASTCGI_BIN}
