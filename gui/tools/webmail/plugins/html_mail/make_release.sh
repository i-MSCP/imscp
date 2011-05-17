#!/bin/sh


# Generic shell script for building SquirrelMail plugin release
#
# Copyright (c) 2004-2005 Paul Lesneiwski
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
# Note that you can also use this setting to move
# entire subdirectories away while creating the release
# package.
#
# CONFIG_FILES=( config.php )
#
CONFIG_FILES=( config.php fckeditor_orig htmlarea_cvs/htmlarea )



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
OLD_VERSION=`echo "<?php include_once('setup.php'); echo "$PACKAGE"_version(); ?>" | php -q`



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
echo
read -p "Enter Version Number [$OLD_VERSION]: " VERSION
if [ -z "$VERSION" ] ; then
   VERSION=$OLD_VERSION;
fi



# remove tarball we are building if present
#
echo
echo "Removing $PACKAGE-$VERSION.tar.gz"
rm -f $PACKAGE-$VERSION.tar.gz



# replace version number in setup.php
# NOTE that this requires specific syntax in setup.php
# for the <package>_version() function which should be
# a line that looks like:
#    return '<version>';
#
if test -e setup.php; then
   echo "Replacing version in setup.php"
   sed -e "s/return '$OLD_VERSION'/return '$VERSION'/" setup.php > setup.php.tmp
   mv setup.php.tmp setup.php
fi



# update version number in version file too
#
echo "Replacing version in version file"
echo "$PRETTY_NAME" > version
echo $VERSION >> version



# create temp working directory one level up
#
WORKING_DIR="../.delete_me.$PACKAGE.temp.$$"
echo "Creating temporary working directory: $WORKING_DIR"
if [ -e "$WORKING_DIR" ] ; then
  rm -rf "$WORKING_DIR"
fi
mkdir "$WORKING_DIR"



# move config files out of directory
#
J=0
while [ "$J" -lt ${#CONFIG_FILES[@]} ]; do

   echo "Excluding ${CONFIG_FILES[$J]}"
   CONFIG_FILE_NAME=`echo "${CONFIG_FILES[$J]}" | sed s/.*\\\///`
   if test `echo "${CONFIG_FILES[$J]}" | grep -ce "/"` -gt 0; then
      CONFIG_FILE_PATH=`echo "${CONFIG_FILES[$J]}" | sed s/\\\/[^/]\\\+$//`
      mkdir -p "$WORKING_DIR/$CONFIG_FILE_PATH"
   else
      CONFIG_FILE_PATH=""
   fi
   
   echo "Moving ${CONFIG_FILES[$J]} to $WORKING_DIR/$CONFIG_FILE_PATH/$CONFIG_FILE_NAME"
   mv "${CONFIG_FILES[$J]}" "$WORKING_DIR/$CONFIG_FILE_PATH/$CONFIG_FILE_NAME"

   J=`expr $J + 1`
done



# make tarball
#
echo "Creating $PACKAGE-$VERSION.tar.gz"
cd ../
tar czvf $PACKAGE-$VERSION.tar.gz $PACKAGE
mv $PACKAGE-$VERSION.tar.gz $PACKAGE
cd $PACKAGE



# moving config files back in place
#
J=0
while [ "$J" -lt ${#CONFIG_FILES[@]} ]; do

   echo "Moving ${CONFIG_FILES[$J]} back into place"
   CONFIG_FILE_NAME=`echo "${CONFIG_FILES[$J]}" | sed s/.*\\\///`
   if test `echo "${CONFIG_FILES[$J]}" | grep -ce "/"` -gt 0; then
      CONFIG_FILE_PATH=`echo "${CONFIG_FILES[$J]}" | sed s/\\\/[^/]\\\+$//`
      mkdir -p "$WORKING_DIR/$CONFIG_FILE_PATH"
   else
      CONFIG_FILE_PATH=""
   fi

   mv "$WORKING_DIR/$CONFIG_FILE_PATH/$CONFIG_FILE_NAME" "${CONFIG_FILES[$J]}"

   J=`expr $J + 1`
done



# delete temp working directory 
#
echo "Removing temporary working directory: $WORKING_DIR"
rm -rf "$WORKING_DIR"


echo 
echo "Finished"
echo

