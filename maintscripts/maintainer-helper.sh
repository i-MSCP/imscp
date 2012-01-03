#!/bin/sh

# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2006-2010 by isp Control Panel - http://ispcp.net
# Copyright (C) 2010-2012 by internet Multi Server Control Panel - http://i-mscp.net
#
# Author: Laurent Declercq <laurent.declercq@i-mscp.net>
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
# Portions created by the i-MSCP Team are Copyright (C) 2010-2012 by
# internet Multi Server Control Panel. All Rights Reserved.
#
# The i-MSCP Home Page is:
#
#    http://i-mscp.net
#

################################################################################
# Note to i-MSCP distributions. maintainers:
#
# This library provide a set of functions that can be used in your maintenance
# scripts.
#
# Currently, only a few helper functions to display the titles and error
# messages are provided.
#
# Also, when you include this file into your script, some i-MSCP configuration
# parameters obtained from the 'imscp.conf' file are exported in your script.
#
# To use library, you must include it at the beginning of your
# script like this:
#
# . $(dirname "$0")/maintainer-helper.sh
#

################################################################################
#                      i-MSCP configuration variables                          #
################################################################################

# Retrieving the main i-MSCP configuration file path
if [ -f "/etc/imscp/imscp.conf" ] ; then
    CONF_FILE=/etc/imscp/imscp.conf
	CMD_SED=`which sed`
elif [ -f "/usr/local/etc/imscp/imscp.conf" ] ; then
    CONF_FILE=/usr/local/etc/imscp/imscp.conf
	    if [ -f "$(which gsed)" ]; then
           CMD_SED=`which gsed`
        else
          printf "\033[1;31m[Error]\033[0m gsed not found!\n"
        fi
else
    printf "\033[1;31m[Error]\033[0m i-MSCP configuration file not found!\n"
    exit 1
fi

OLD_IFS=$IFS
IFS=$

# Reading needed entries from imscp.conf
for a in $(grep -E '^(AMAVIS|APACHE_|BASE_SERVER_IP|CMD_|DEBUG|DATABASE_HOST|DEFAULT_ADMIN_ADDRESS|ETC_|LOG_DIR|MTA_|ROOT_|PHP_FASTCGI|SPAMASSASSIN|Version)' \
${CONF_FILE} | $CMD_SED 's/\s*=\s*\(.*\)/="\1"/') ; do
	 eval $a
done

IFS=$OLD_IFS

# Enable DEBUG mode if needed
if [ $DEBUG -eq 1 ]; then
  echo "now debugging $0 $@"
  set -x
fi

# i-MSCP version
IMSCP_VERSION=$(echo $Version | $CMD_SED -e 's/\s\+\|[a-z]//gi')

################################################################################
#                                   Logging                                    #
################################################################################

# Log file path
LOGFILE="$LOG_DIR/setup/imscp-$1.log"

# Make sure that the log directory exists
/usr/bin/install -d $LOG_DIR/setup -m 0755 -o $ROOT_USER -g $ROOT_GROUP

# Removing old log file if it exists
$CMD_RM -f $LOGFILE

################################################################################
#                                Utils functions                               #
################################################################################

# Register shutdown function
trap "shutdown" EXIT

# Default Error message
ERROR_MESSAGE="See the $LOGFILE logfile for the reason!"

# TAB+SP+*+SP (11 bytes) + TITLE length + 1 byte
TITLE_LENGTH=12
PROGRESS_LENGTH=0

################################################################################
# Print a title
#
# Param: string Title to be printed
#
print_title() {
	TITLE_LENGTH=$(($TITLE_LENGTH+$(printf "$1" | wc -c)))
	TITLE="\t \033[1;32m*\033[0m $1"

	printf "[$1]\n" >> $LOGFILE
	printf "$TITLE";
}

################################################################################
# Print status
#
print_status() {
	if [ "$?" -eq 0 ] ; then
		STATUS="\033[1;35m[ \033[1;32mDone \033[1;35m]\033[0m"
		STATUS_LENGTH=8
	else
		STATUS="\033[1;35m[ \033[1;31mFailed \033[1;35m]\033[0m"
		STATUS_LENGTH=10
	fi

	# Getting terminal width
	TERM_WIDTH=$(stty size | cut -d' ' -f2)

	# Calculating separator size
	SEP=$(($TERM_WIDTH-($TITLE_LENGTH+$STATUS_LENGTH+$PROGRESS_LENGTH)))

	printf "%$(($SEP))s$STATUS\n"

	# Reset default length
	TITLE_LENGTH=12
	PROGRESS_LENGTH=0
}

################################################################################
# Print progress
#
progress() {
    printf '.'
	PROGRESS_LENGTH=$(($PROGRESS_LENGTH+1))
}

################################################################################
# Exit with an error message
#
# [param: string Error message that override the default one]
#
failed() {
	print_status

	if ! test -z "$1" ; then
		ERROR_MESSAGE=$1
	fi

	printf "\n\t \033[1;31m[ERROR]\033[0m $ERROR_MESSAGE\n"

	exit 1
}

################################################################################
# Shutdown function
#
shutdown() {
	if test -z "$SEP" ; then
		print_title "Nothing to do..."
		print_status
	fi
}
