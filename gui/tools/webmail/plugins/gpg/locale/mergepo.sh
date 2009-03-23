#!/bin/sh

PONAME=gpg.po
POTNAME=gpg.pot

# **
# ** This script merges global PO to locale PO files.
# ** It creates a backup of the old PO file as $PONAME.bak
# ** and puts the merged version into $PONAME
# **
# ** Usage:   mergepo <locale id>
# ** Example: mergepo es_ES
# **
# ** Philipe Mingo <mingo@rotedic.com>
# ** Konstantin Riabitsev <icon@duke.edu>
# **
# **  $Id$

if [ -z "$1" ]; then
 echo "USAGE: mergepo [localename]"
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

echo "Merging $POFILE"
mv $POFILE $POFILE.bak 
msgmerge $POFILE.bak $POTNAME > $POFILE

# msgmerge will split long lines, such as the RCS Id line. If it did split
# it, join the pieces back together.
ed -s $POFILE << END
/^"Project-Id-Version:/v/\\n"$/j\\
s/""//
wq
END

echo "Old po file renamed to $PONAME.bak"
