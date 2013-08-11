#!/bin/sh

# Newly created files: 0640
# Newly created directories: 0750
umask 027

PHPRC="{PHP_STARTER_DIR}/{DOMAIN_NAME}/php{PHP_VERSION}/"
export PHPRC

PHP_FCGI_CHILDREN=2
export PHP_FCGI_CHILDREN

PHP_FCGI_MAX_REQUESTS=500
export PHP_FCGI_MAX_REQUESTS

TMPDIR="{WEB_DIR}/data/tmp"
export TMPDIR

exec {PHP5_FASTCGI_BIN}
