#!/bin/sh

PHPRC="{PHP_STARTER_DIR}/{DOMAIN_NAME}/"
export PHPRC
#PHP_FCGI_CHILDREN=4
#export PHP_FCGI_CHILDREN
exec /usr/bin/php5-cgi 
