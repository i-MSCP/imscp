<?php
/**
 * Language configuration file
 *
 * Copyright (c) 2005-2006 The SquirrelMail Project Team
 *
 * This file is part of SquirrelMail webmail interface. It is distributed
 * together with other translation files and is used to enable 
 * translation.
 *
 * SquirrelMail is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * SquirrelMail is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with SquirrelMail; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @version $Id: setup.php,v 1.2 2006/07/19 12:38:10 tokul Exp $
 * @package locales
 */

$languages['ko_KR']['NAME']    = 'Korean';
$languages['ko_KR']['CHARSET'] = 'euc-KR';
$languages['ko_KR']['LOCALE']  = 'ko_KR.EUC-KR';
$languages['ko_KR']['XTRA_CODE'] = 'korean_xtra';
$languages['ko']['ALIAS'] = 'ko_KR';

/********************************
 * Korean charset extra functions
 ********************************/

/**
 * Korean downloaded filename processing functions
 *
 * @param string default return value
 * @return string
 * @since 1.5.1
 */
function korean_xtra_downloadfilename($ret) {
    $ret = str_replace("\x0D\x0A", '', $ret);  /* Hanmail's CR/LF Clear */
    for ($i=0;$i<strlen($ret);$i++) {
        if ($ret[$i] >= "\xA1" && $ret[$i] <= "\xFE") {   /* 0xA1 - 0XFE are Valid */
            $i++;
            continue;
        } else if (($ret[$i] >= 'a' && $ret[$i] <= 'z') || /* From Original ereg_replace in download.php */
                   ($ret[$i] >= 'A' && $ret[$i] <= 'Z') ||
                   ($ret[$i] == '.') || ($ret[$i] == '-')) {
            continue;
        } else {
            $ret[$i] = '_';
        }
    }
    return $ret;
}
