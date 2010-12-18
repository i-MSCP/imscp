<?php
/**
 * iMSCP complete domain backup/restore tool
 * Restore application
 *
 * @copyright 	2010 Thomas Wacker
 * @author 		Thomas Wacker <zuhause@thomaswacker.de>
 * @version 	SVN: $Id$
 *
 * @license
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

function delTree($dir)
{
    $files = glob($dir . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (substr($file, -1) == '/')
			delTree($file);
        else
			unlink($file);
    }

    if (is_dir($dir)) rmdir($dir);
}
