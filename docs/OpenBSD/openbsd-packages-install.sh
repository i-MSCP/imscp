#!/bin/sh

export PKG_PATH=ftp://ftp.ca.openbsd.org/pub/OpenBSD/`uname -r`/packages/`uname -m`/

for a in `cat openbsd-packages.txt`
do

	pkg_add -v $a

done
