#!/bin/sh
#
# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010 - 2011 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    iMSCP
# @package     iMSCP_Scripts
# @subpackage  i18n
# @copyright   2010 - 2011 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <laurent.declercq@i-mscp.net>
# @version     SVN: $Id$
# @link        http://www.i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
#

########################################################################################################################
# Script short description
#
# This script create a new POT file by retrieving all messages from the Front-end files (*.php, *.phtml and *.xml files)
#
# Note to developers:
#
# I. PHP and PHTML files:
#
# When you add translation strings in files, don't forget to check that the directories that contain your files are
# correctly listed in the xgettext command of the PHP Section bellow.
#
# II. XML files
#
# When you add translation strings in files, don't forget to check that the directories that contain your files are
# correctly listed in the xgettext command of the XML Section bellow. Also, don't forget to add all needed keywords.
#
#
########################################################################################################################

set -e

LANGUAGE_DIRECTORY="../../application/languages"
APPLICATION_DIRECTORY="../../application"

# PHP Section
/usr/bin/xgettext --language=PHP \
-d "i-MSCP" \
--keyword="translate" \
--keyword="plural:1,2" \
${APPLICATION_DIRECTORY}/layouts/*.phtml \
${APPLICATION_DIRECTORY}/modules/*/controllers/*.php \
${APPLICATION_DIRECTORY}/modules/*/views/scripts/*/*.phtml \
--from-code=utf-8 \
--no-wrap \
-p ${LANGUAGE_DIRECTORY}/po \
-o "iMSCP.pot"

# XML Section
/usr/bin/xgettext --language=Glade \
--package-name="i-MSCP" \
--package-version="1.0.0" \
--copyright-holder="i-MSCP Team" \
--msgid-bugs-address="i18n@i-mscp.net" \
-d "i-MSCP" \
--keyword="label" \
--keyword="plural:1,2" \
${APPLICATION_DIRECTORY}/configs/menus/*.xml \
--from-code=utf-8 \
--no-wrap \
-p ${LANGUAGE_DIRECTORY}/po \
-o "iMSCP.pot" -j -s

exit 0
