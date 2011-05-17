#!/bin/bash

# i-MSCP a internet Multi Server Control Panel
#
# Copyright (C) 2006-2010 by isp Control Panel - http://isp-control.net
# Copyright (C) 2010 by internet Multi Server Control Panel - http://i-mscp.net
#
# @author 		Jochen Manz <jochen.manz@gmx.de>
#
# @license
# The contents of this file are subject to the Mozilla Public License
# Version 1.1 (the "License"); you may not use this file except in
# compliance with the License. You may obtain a copy of the License at
# http://www.mozilla.org/MPL/
#
# Software distributed under the License is distributed on an "AS IS"
# basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
# License for the specific language governing rights and limitations
# under the License.

# Variables
CURRENTVERSION="1.0.0"
TARGETVERSION="1.0.0"
SVNLOCATION="branches"
SVNFOLDER="i-mscp-"${TARGETVERSION}
RELEASEFOLDER="i-mscp-"${SVNFOLDER}
FTPFOLDER="i-MSCP "${TARGETVERSION}
FTPUSER="" // Insert Sourceforge Username

SVNSTRING="https://i-mscp.svn.sourceforge.net/svnroot/i-mscp/"${SVNLOCATION}"/"${SVNFOLDER}

# Cleanup
rm -rf i-mscp-*

# Pull the code from svn
svn export $SVNSTRING

# Builddate
IMSCPCONF="${SVNFOLDER}\configs\debian\imscp.conf"
CURRENTBUILDDATE=$(grep BuildDate ${IMSCPCONF} | cut -d "=" -f 2 | sed 's/ //g')
TARGETBUILDDATE=$(date -u +"%Y%m%d")

echo ${CURRENTBUILDDATE}

mv ${SVNFOLDER} ${RELEASEFOLDER}

# Release preparations
#rpl -R "Version = ${CURRENTVERSION}" "Version = ${TARGETVERSION}" ${RELEASEFOLDER}/configs
#rpl -R "BuildDate = ${CURRENTBUILDDATE}" "BuildDate = ${TARGETBUILDDATE}" ${RELEASEFOLDER}/*/imscp.conf
#rpl -R "${CURRENTVERSION}" "${TARGETVERSION}" ${RELEASEFOLDER}/docs/*/INSTALL

# Create the needed Archives
tar cjf ${RELEASEFOLDER}.tar.bz2 ./${RELEASEFOLDER}
md5sum ${RELEASEFOLDER}.tar.bz2 > ${RELEASEFOLDER}.tar.bz2.sum

tar czf ${RELEASEFOLDER}.tar.gz ./${RELEASEFOLDER}
md5sum ${RELEASEFOLDER}.tar.gz > ${RELEASEFOLDER}.tar.gz.sum

zip -9r ${RELEASEFOLDER}.zip ./${RELEASEFOLDER}
md5sum ${RELEASEFOLDER}.zip > ${RELEASEFOLDER}.zip.sum

7zr a ${RELEASEFOLDER}.7z ./${RELEASEFOLDER}
md5sum ${RELEASEFOLDER}.7z > ${RELEASEFOLDER}.7z.sum

# Fill the batch file for sftp
if [ -e ./ftpbatch.sh ]; then
	rm -rf ./ftpbatch.sh
fi

touch ./ftpbatch.sh

echo -ne "cd /home/frs/project/i/i-/i-mscp/i-MSCP\n" >> ftpbatch.sh
echo -ne "mkdir i-MSCP\ ${TARGETVERSION}\n" >> ftpbatch.sh
echo -ne "cd i-MSCP\ ${TARGETVERSION}\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.zip\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.zip.sum\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.7z\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.7z.sum\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.tar.gz\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.tar.gz.sum\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.tar.bz2\n" >> ftpbatch.sh
echo -ne "put ${RELEASEFOLDER}.tar.bz2.sum\n" >> ftpbatch.sh
echo -ne "quit\n" >> ftpbatch.sh

# you will be promted for a login!
sftp -o "batchmode no" -b ./ftpbatch.sh ${FTPUSER},i-mscp@frs.sourceforge.net
