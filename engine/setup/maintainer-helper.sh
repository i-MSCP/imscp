#!/bin/sh

# ispCP helper functions for dits maintainers scripts
#
# ispCP ω (OMEGA) a Virtual Hosting Control Panel
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# author	Laurent Declercq <laurent.declercq@ispcp.net>
# version	1.1
#
# SVN: $Id$
#
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.
#
# The Original Code is "ispCP ω (OMEGA) a Virtual Hosting Control Panel".
#
# The Initial Developer of the Original Code is ispCP Team.
# Portions created by Initial Developer are Copyright (C) 2006-2010 by
# isp Control Panel. All Rights Reserved.
#
# The ispCP ω Home Page is:
#
#    http://isp-control.net
#

# Note to ispCP dists maintainers:
#
# This script provide a set of functions that can be used in your maintainers
# scripts (eg. preinst, postinst...).
#
# Currently, only a few helper functions to display the titles and error
# messages are provided.
#
# Also, when you include this file into your script, some ispCP configuration
# variables obtained from the 'ispcp.conf' file will be exported in your script.
#
# To use these functions, you must include this script at the beginning of your
# script like this:
#
# . $(dirname "$0")/maintainer-helper.sh
#
# See the docs/OpenSuse/postinst script for an usage example.
#

# Retrieve the isCP main configuration file path
if [ -f "/etc/ispcp/ispcp.conf" ] ; then
    ISPCP_CONF_FILE=/etc/ispcp/ispcp.conf
elif [ -f "/usr/local/etc/ispcp/ispcp.conf" ] ; then
    ISPCP_CONF_FILE=/usr/local/etc/ispcp/ispcp.conf
else
    printf "\033[1;31m[Error]\033[0m ispCP configuration file not found!\n"
    exit 1
fi

# Read needed entries from ispcp.conf
for a in `grep -E '(^Version|APACHE_|MTA_|ROOT_|^PHP_FASTCGI|^CMD_|^DEBUG)' \
$ISPCP_CONF_FILE | sed -e 's/ //g'`; do
    export $a
done

# Get ispCP version
ISPCP_VERS=`echo $Version | sed -e 's/[A-Z]//g'`

# Enable debugg mode (see ispcp.conf)
if [ $DEBUG -eq 1 ]; then
  echo "now debugging $0 $@"
  set -x
fi

# Global variables
ISPCP_LOGFILE=/tmp/ispcp-postinst.log
ISPCP_ERRMSG="\n\t  \033[1;34m[Notice]\033[0m See the $ISPCP_LOGFILE for the \
reason!\n\n"
ISPCP_STATE="\033[1;32mDone\033[0m"
ISPCP_MSG=""
ISPCP_PRINT=""
ISPCP_DYN_LENGTH=0
ISPCP_EXIT=0

# Print section title
#
# Param: string A title to be displayed
print_title() {
	ISPCP_PRINT=$1
	printf "\t $ISPCP_PRINT"
	printf "[$ISPCP_PRINT]\n" >> $ISPCP_LOGFILE
}

# Can be used in a loop to reflect the action progression
progress() {
    printf '.'
    ISPCP_DYN_LENGTH=$(($ISPCP_DYN_LENGTH+1))
}

# Set an error message
#
# Function that can be used to override the default error message that
# displayed when an error occur (after the call of the failed() function).
#
# Param: string Type of error. Can be 'notice', warning or 'error'.
# Param: string The error message
set_errmsg() {
	if [ "$1" = "notice" ] ; then
		ISPCP_ERRMSG="\n\t  \033[1;34m[Notice]\033[0m $2\n\n"
	elif [ "$1" = "warning" ] ; then
		ISPCP_ERRMSG="\n\t  \033[1;33m[Warning]\033[0m $2\n\n"
	elif [ "$1" = "error" ] ; then
		ISPCP_ERRMSG="\n\t  \033[1;31m[Error]\033[0m $2\n\n"
	else
		ISPCP_ERRMSG="\n\t $1\n\n"
	fi
}

# Sets the status string to 'Failed' and an error message when a command fail
#
# This function allow also to manage the action to be performed when a command
# fail. If an exit status is set, the program will end up with it according the
# following rules:
#
# If the exit status is set to 1, only the hook script will end up, otherwise,
# if the exit status is set to 2, the both maintainer script and master script
# (eg. ispcp-setup / ispcp-update) will end up.
#
# $1: Optional exit status
failed() {
	ISPCP_STATE="\033[1;31mFailed\033[0m"

	if [ ! -z $1 ] ; then
		ISPCP_EXIT=$1
	fi

	ISPCP_MSG="$ISPCP_ERRMSG"
}

# Display the status string and the error message
#
# If the exist status was set via the 'failed()' function, the script will end
# up with this exit status.
#
# See the failed function for more information about posibility.
print_status() {
	ISPCP_TERM_WIDTH=`stty size | cut -d' ' -f2`
	ISPCP_MSG_LENGTH=`echo "$ISPCP_PRINT" | $CMD_WC -c`

	ISPCP_MSG_LENGTH=$(($ISPCP_MSG_LENGTH+$ISPCP_DYN_LENGTH))
	ISPCP_DYN_LENGTH=0

	ISPCP_STRING=`printf "%$(($ISPCP_TERM_WIDTH-($ISPCP_MSG_LENGTH-8)))s" \
$ISPCP_STATE`

	printf "$ISPCP_STRING\n"
	printf "$ISPCP_MSG"

	if [ $ISPCP_EXIT != 0 ] ; then
		exit $ISPCP_EXIT
	fi

	# Reset the status string and the error message
	reset
}

# Reset the status string and the error message to they default values
reset() {
        ISPCP_STATE="\033[1;32mDone\033[0m"
        ISPCP_ERRMSG="\n\t  \033[1;34m[Notice]\033[0m See the $ISPCP_LOGFILE \
for the reason!\n\n"
	ISPCP_MSG=""
}
