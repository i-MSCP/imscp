#!/bin/sh


# Generic shell script for building SquirrelMail plugin release
#
# Copyright (c) 2004-2009 Paul Lesniewski <paul@squirrelmail.org>
# Licensed under the GNU GPL. For full terms see the file COPYING.
#



#######################################################
#
# CONFIGURATION
#


# Relative paths to any and all configuration files
# for this plugin:  these files will NOT be included
# in the release package built by this script; they
# should be given as relative paths and filenames from
# the plugin's own directory - for example, if you 
# have a config.php file in the main plugin directory
# and a special_config.php file in a "data" subdirectory,
# this should be set as follows:
#
# CONFIG_FILES=( config.php data/special_config.php )
#
# Note that you can also use this setting to exclude
# entire subdirectories while creating the release
# package.  Here is an example that skips any files 
# inside a subdirectory called "cache_files" and 
# completely removes a subdirectory called "tmp", as
# well as the standard config.php file:
#
# CONFIG_FILES=( config.php tmp cache_files/* )
#
#
CONFIG_FILES=( )



#
# END CONFIGURATION
#
#######################################################



# avoid all kinds of potential problems; only allow
# this to be run from directory where it resides
#
if [ "$0" != "./make_release.sh" ]; then

   echo 
   echo "Please do not run from remote directory"
   echo 
   exit 1
 
fi



# grab name of package being built from directory name
#
#
PACKAGE=`echo "$PWD" | sed s/.*\\\///`



# get "pretty name" from version file
#
if [ ! -e version ]; then
   echo 
   echo "No version file found.  Please create before making release"
   echo
   exit 2
fi
PRETTY_NAME=`head -1 version`



# announce ourselves
#
echo 
echo "Creating Release Package for $PRETTY_NAME"
echo



# grab old version number straight from the php code
#
OLD_VERSION=`echo "<?php define('SQ_INCOMPATIBLE', 'INCOMPATIBLE'); include_once('setup.php'); echo "$PACKAGE"_version(); ?>" | php -q`
REQ_SM_VERSION=`echo "<?php define('SQ_INCOMPATIBLE', 'INCOMPATIBLE'); include_once('setup.php'); \\$info = "$PACKAGE"_info(); echo \\$info['required_sm_version']; ?>" | php -q`



# check for the standard files...
#
if [ ! -e README ]; then
   echo 
   echo "No README file found.  Please create before making release"
   echo
   exit 3
fi
if [ ! -e INSTALL ]; then
   echo 
   echo "No INSTALL file found.  Please create before making release"
   echo
   exit 4
fi
if [ ! -e getpot ]; then
   echo 
   echo "No getpot file found.  Please create before making release"
   echo
   exit 5
fi
if [ ! -e $PACKAGE.pot ]; then
   echo
   echo "No $PACKAGE.pot file found.  Please create before making release"
   echo
   exit 5
fi



# just copy index.php and COPYING automatically if not found
#
if [ ! -e COPYING ]; then
   echo "No COPYING file found.  Grabbing one from ../../"
   cp ../../COPYING .
fi
if [ ! -e index.php ]; then
   echo "No index.php file found.  Grabbing one from ../"
   cp ../index.php .
fi



# remove any previous tarballs
#
while test 1; do
   echo
   echo -n "Remove all .tar.gz files? (y/[n]): "
   read REPLY
   if test -z $REPLY; then
      REPLY="n"
      break
   fi
   if test $REPLY = "y"; then
      break
   fi
   if test $REPLY = "n"; then
      break
   fi
done
if [ "$REPLY" = "y" ]; then
   rm -f *.tar.gz
fi



# get new version number if needed
#
if [ ! -z "$REQ_SM_VERSION" ] ; then
   OLD_FULL_VERSION=$OLD_VERSION-$REQ_SM_VERSION
else
   OLD_FULL_VERSION=$OLD_VERSION
fi
echo
read -p "Enter Version Number [$OLD_VERSION]: " VERSION
if [ -z "$VERSION" ] ; then
   VERSION=$OLD_VERSION;
#   VERSION=$OLD_FULL_VERSION;
fi
PURE_VERSION=`echo "$VERSION" | sed 's/-.*//'`

if [ ! -z "$REQ_SM_VERSION" ] ; then
   FINAL_VERSION="$PURE_VERSION-$REQ_SM_VERSION"
else
   FINAL_VERSION="$PURE_VERSION"
fi



# remove tarball we are building if present
#
echo
echo "Removing $PACKAGE-$FINAL_VERSION.tar.gz"
rm -f $PACKAGE-$FINAL_VERSION.tar.gz



# replace version number in info function in setup.php
# NOTE that this requires specific syntax in setup.php
# for the <package>_info() function which should be
# a line that looks like:
#                  'version' => '<version>',
#
if test -e setup.php; then
   echo "Replacing version in setup.php (info function)"
   sed -e "s/'version' => '$OLD_VERSION',/'version' => '$PURE_VERSION',/" setup.php > setup.php.tmp
   mv setup.php.tmp setup.php
fi



# update version number in version file too
#
echo "Replacing version in version file"
echo "$PRETTY_NAME" > version
echo $PURE_VERSION >> version



# Build tar command; exclude config and other irrelevant files 
#
TAR_COMMAND="tar -c -z -v --exclude CVS"
J=0
while [ "$J" -lt ${#CONFIG_FILES[@]} ]; do

   echo "Excluding ${CONFIG_FILES[$J]}"
   TAR_COMMAND="$TAR_COMMAND --exclude ${CONFIG_FILES[$J]}"

   J=`expr $J + 1`
done
TAR_COMMAND="$TAR_COMMAND -f $PACKAGE-$FINAL_VERSION.tar.gz $PACKAGE"



# make tarball
#
echo "Creating $PACKAGE-$FINAL_VERSION.tar.gz"
cd ../
$TAR_COMMAND
mv $PACKAGE-$FINAL_VERSION.tar.gz $PACKAGE
cd $PACKAGE



echo 
echo "Finished"
echo

