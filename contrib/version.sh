#!/bin/sh

# If file VERSION exists, we don't need to run
if [ -e VERSION ]; then
  exit
fi

# Get version from SVN information
VERSION=SVN-r`svn info 2> /dev/null | grep Revision | cut -d' ' -f2`

# If We didn't get the version, then it's unknown.
if [ $VERSION = "SVN-r" ]; then
  VERSION="UNKNOWN"
fi

echo "$VERSION" > VERSION
