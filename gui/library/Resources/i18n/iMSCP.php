<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @noinspection PhpUnhandledExceptionInspection
 */

// Note this file is not intended to be loaded. It will simply be parsed by the gettext
// tools when updating translation files.

tr('_: Localised language');
tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.');

// iMSCP/Validate/File/Plugin.php
tr("File '%value%' doesn't look like an i-MSCP plugin archive.");
tr("File '%value%' is not readable or does not exist.");
tr("Plugin '%value%' is not compatible with this i-MSCP version.");
tr("Plugin '%value%' cannot be updated because it is protected.");
tr("Plugin '%value%' cannot be updated due to pending task.");
tr("Plugin '%value%' file is invalid.");
tr("Plugin '%value%' info field is invalid.");
tr("Plugin '%value%' info field is missing.");

// Zend/Validate/File/Count.php
tr("Too many files, maximum '%max%' are allowed but '%count%' are given");
tr("Too few files, minimum '%min%' are expected but '%count%' are given");

// Zend/Validate/File/Size.php
tr("Maximum allowed size for file '%value%' is '%max%' but '%size%' detected");
tr("Minimum expected size for file '%value%' is '%min%' but '%size%' detected");
tr("File '%value%' is not readable or does not exist");

// Zend/Validate/File/Upload.php
tr("File '%value%' exceeds the defined ini size");
tr("File '%value%' exceeds the defined form size");
tr("File '%value%' was only partially uploaded");
tr("File '%value%' was not uploaded");
tr("No temporary directory was found for file '%value%'");
tr("File '%value%' can't be written");
tr("A PHP extension returned an error while uploading the file '%value%'");
tr("File '%value%' was illegally uploaded. This could be a possible attack");
tr("File '%value%' was not found");
tr("Unknown error while uploading file '%value%'");
