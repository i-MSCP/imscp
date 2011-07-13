#!/bin/sh

PONAME=gpg.po
MONAME=gpg.mo

# **
# ** This script compiles locale PO files
# **
# ** Usage:   compilepo <locale id>
# ** Example: compilepo es
# **
# ** Philipe Mingo <mingo@rotedic.com>
# ** Konstantin Riabitsev <icon@duke.edu>
# **
# **  $Id$

if [ -z "$1" ]; then
 echo "USAGE: compilepo [localename]"
 exit 1
fi

WORKDIR=../locale
LOCALEDIR=$WORKDIR/$1

if [ ! -d $LOCALEDIR ]; then
 # lessee if it's been renamed.
 DCOUNT=`find $WORKDIR/ -name $1* | wc -l` 
 if [ $DCOUNT -eq 1 ]; then 
  # aha
  LOCALEDIR=`find $WORKDIR/ -name $1*`
 elif [ $DCOUNT -gt 1 ]; then
  # err out
  echo "More than one locale matching this name found:"
  find $WORKDIR/ -name $1*
  echo "You have to be more specific."
  exit 1
 fi
fi

POFILE=$LOCALEDIR/LC_MESSAGES/$PONAME
MOFILE=$LOCALEDIR/LC_MESSAGES/$MONAME

echo "Compiling $POFILE"
msgfmt -vvv -o $MOFILE $POFILE
