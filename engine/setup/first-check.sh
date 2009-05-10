#!/bin/bash

echo "run from folder /var/www/ispcp/engine/setup"

# ispcp.conf
echo "Checking if ispcp.conf is ok"
cnf_check=$(php -r "include('../../gui/include/ispcp-config.php');")
if [ "$cnf_check" != "" ]; then
 echo "An error has occurred while reading /etc/ispcp/ispcp.conf, here comes the HTML code:"
 echo $cnf_check
 echo ""
 exit;
fi
echo "Everything fine until here"
