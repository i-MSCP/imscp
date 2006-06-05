#!/bin/sh

#export PKG_PATH=ftp://ftp.de.openbsd.org/pub/OpenBSD/3.8/packages/sparc64/
export PKG_PATH=ftp://ftp.de.openbsd.org/pub/OpenBSD/3.8/packages/i386/

for a in `cat openbsd-packages.txt`
do

	pkg_add -v $a 

done
