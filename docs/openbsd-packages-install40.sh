#!/bin/sh

export
PKG_PATH=http://mirror.paranoidbsd.org/pub/OpenBSD/4.0/packages/i386/

for a in `cat openbsd-packages40.txt`
do

	pkg_add -v $a

done