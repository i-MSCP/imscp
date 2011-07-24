<?php
/**
 * Language configuration file
 *
 * Copyright (c) 2005-2009 The SquirrelMail Project Team
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
 * @version $Id: setup.php 13693 2009-05-14 00:26:43Z jervfors $
 * @package locales
 */

$languages['ja_JP']['NAME']    = 'Japanese';
$languages['ja_JP']['ALTNAME'] = '&#26085;&#26412;&#35486;';
$languages['ja_JP']['CHARSET'] = 'iso-2022-jp';
$languages['ja_JP']['LOCALE'] = 'ja_JP.EUC-JP';
$languages['ja_JP']['XTRA_CODE'] = 'japanese_xtra';
$languages['ja']['ALIAS'] = 'ja_JP';

/**************************
 * Japanese extra functions
 **************************/

/**
 * Japanese decoding function
 *
 * converts string to euc-jp, if string uses JIS, EUC-JP, ShiftJIS or UTF-8
 * charset. Needs mbstring support in php.
 * @param string $ret text, that has to be converted
 * @return string converted string
 * @since 1.5.1
 */
function japanese_xtra_decode($ret) {
    if (function_exists('mb_detect_encoding')) {
        $detect_encoding = @mb_detect_encoding($ret);
        if ($detect_encoding == 'JIS' ||
            $detect_encoding == 'EUC-JP' ||
            $detect_encoding == 'SJIS' ||
            $detect_encoding == 'UTF-8') {

            $ret = mb_convert_kana(mb_convert_encoding($ret, 'EUC-JP', 'AUTO'), "KV");
        }
    }
    return $ret;
}

/**
 * Japanese encoding function
 *
 * converts string to jis, if string uses JIS, EUC-JP, ShiftJIS or UTF-8
 * charset. Needs mbstring support in php.
 * @param string $ret text, that has to be converted
 * @return string converted text
 * @since 1.5.1
 */
function japanese_xtra_encode($ret) {
    if (function_exists('mb_detect_encoding')) {
        $detect_encoding = @mb_detect_encoding($ret);
        if ($detect_encoding == 'JIS' ||
            $detect_encoding == 'EUC-JP' ||
            $detect_encoding == 'SJIS' ||
            $detect_encoding == 'UTF-8') {

            $ret = mb_convert_encoding(mb_convert_kana($ret, "KV"), 'JIS', 'AUTO');
        }
    }
    return $ret;
}

/**
 * Japanese header encoding function
 *
 * creates base64 encoded header in iso-2022-jp charset
 * @param string $ret text, that has to be converted
 * @return string mime base64 encoded string
 * @since 1.5.1
 */
function japanese_xtra_encodeheader($ret) {
    if (function_exists('mb_detect_encoding')) {
        /**
         * First argument ($ret) contains header string.
         * SquirrelMail ja_JP translation uses euc-jp as internal encoding.
         * euc-jp stores Japanese letters in 0xA1-0xFE block (source:
         * JIS X 0208 unicode.org mapping. see euc_jp.php in extra decoding
         * library). Standard SquirrelMail 8bit test should detect if text
         * is in euc or in ascii.
         */
        if (sq_is8bit($ret)) {
            /**
             * Minimize dependency on mb_mime_encodeheader(). PHP 4.4.1 bug
             * and maybe other bugs.
             *
             * Convert text from euc-jp (internal encoding) to iso-2022-jp
             * (commonly used Japanese encoding) with mbstring functions.
             *
             * Use SquirrelMail internal B encoding function. 'encodeheader'
             * XTRA_CODE is executed in encodeHeader() function, so
             * functions/mime.php (encodeHeaderBase64) and functions/strings.php
             * (sq_is8bit) are already loaded.
             */
            $ret = encodeHeaderBase64(mb_convert_encoding($ret,'ISO-2022-JP','EUC-JP'),
                                      'iso-2022-jp');
        }
        /**
         * if text is in ascii, we leave it unchanged. If some ASCII
         * chars must be encoded, add code here in else statement.
         */
    }
    return $ret;
}

/**
 * Japanese header decoding function
 *
 * return human readable string from mime header. string is returned in euc-jp
 * charset.
 * @param string $ret header string
 * @return string decoded header string
 * @since 1.5.1
 */
function japanese_xtra_decodeheader($ret) {
    if (function_exists('mb_detect_encoding')) {
        $ret = str_replace("\t", "", $ret);
        if (eregi('=\\?([^?]+)\\?(q|b)\\?([^?]+)\\?=', $ret))
            $ret = @mb_decode_mimeheader($ret);
        $ret = @mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
    }
    return $ret;
}

/**
 * Japanese downloaded filename processing function
 *
 * Returns shift-jis or euc-jp encoded file name
 * @param string $ret string
 * @param string $useragent browser
 * @return string converted string
 * @since 1.5.1
 */
function japanese_xtra_downloadfilename($ret,$useragent) {
    if (function_exists('mb_detect_encoding')) {
        if (strstr($useragent, 'Windows') !== false ||
            strstr($useragent, 'Mac_') !== false) {
            $ret = mb_convert_encoding($ret, 'SJIS', 'AUTO');
        } else {
            $ret = mb_convert_encoding($ret, 'EUC-JP', 'AUTO');
        }
    }
    return $ret;
}

/**
 * Japanese wordwrap function
 *
 * wraps text at set number of symbols
 * @param string $ret text
 * @param integer $wrap number of symbols per line
 * @return string wrapped text
 * @since 1.5.1
 */
function japanese_xtra_wordwrap($ret,$wrap) {
    if (function_exists('mb_detect_encoding')) {
        $no_begin = "\x21\x25\x29\x2c\x2e\x3a\x3b\x3f\x5d\x7d\xa1\xf1\xa1\xeb\xa1" .
            "\xc7\xa1\xc9\xa2\xf3\xa1\xec\xa1\xed\xa1\xee\xa1\xa2\xa1\xa3\xa1\xb9" .
            "\xa1\xd3\xa1\xd5\xa1\xd7\xa1\xd9\xa1\xdb\xa1\xcd\xa4\xa1\xa4\xa3\xa4" .
            "\xa5\xa4\xa7\xa4\xa9\xa4\xc3\xa4\xe3\xa4\xe5\xa4\xe7\xa4\xee\xa1\xab" .
            "\xa1\xac\xa1\xb5\xa1\xb6\xa5\xa1\xa5\xa3\xa5\xa5\xa5\xa7\xa5\xa9\xa5" .
            "\xc3\xa5\xe3\xa5\xe5\xa5\xe7\xa5\xee\xa5\xf5\xa5\xf6\xa1\xa6\xa1\xbc" .
            "\xa1\xb3\xa1\xb4\xa1\xaa\xa1\xf3\xa1\xcb\xa1\xa4\xa1\xa5\xa1\xa7\xa1" .
            "\xa8\xa1\xa9\xa1\xcf\xa1\xd1";
        // This don't appear to be used... is it safe to remove?
        $no_end = "\x5c\x24\x28\x5b\x7b\xa1\xf2\x5c\xa1\xc6\xa1\xc8\xa1\xd2\xa1" .
            "\xd4\xa1\xd6\xa1\xd8\xa1\xda\xa1\xcc\xa1\xf0\xa1\xca\xa1\xce\xa1\xd0\xa1\xef";

        if (strlen($ret) >= $wrap &&
            substr($ret, 0, 1) != '>' &&
            strpos($ret, 'http://') === FALSE &&
            strpos($ret, 'https://') === FALSE &&
            strpos($ret, 'ftp://') === FALSE) {

            $ret = mb_convert_kana($ret, "KV");

            $line_new = '';
            $ptr = 0;

            while ($ptr < strlen($ret) - 1) {
                $l = mb_strcut($ret, $ptr, $wrap);
                $ptr += strlen($l);
                $tmp = $l;

                $l = mb_strcut($ret, $ptr, 2);
                while (strlen($l) != 0 && mb_strpos($no_begin, $l) !== FALSE ) {
                    $tmp .= $l;
                    $ptr += strlen($l);
                    $l = mb_strcut($ret, $ptr, 1);
                }
                $line_new .= $tmp;
                if ($ptr < strlen($ret) - 1)
                    $line_new .= "\n";
            }
            $ret = $line_new;
        }
    }
    return $ret;
}

/**
 * Japanese imap folder name encoding function
 *
 * converts folder name from euc-jp to utf7-imap
 * @param string $ret folder name
 * @return string converted folder name
 * @since 1.5.1
 */
function japanese_xtra_utf7_imap_encode($ret){
    if (function_exists('mb_detect_encoding')) {
        $ret = mb_convert_encoding($ret, 'UTF7-IMAP', 'EUC-JP');
    }
    return $ret;
}

/**
 * Japanese imap folder name decoding function
 *
 * converts folder name from utf7-imap to euc-jp.
 * @param string $ret folder name in utf7-imap
 * @return string converted folder name
 * @since 1.5.1
 */
function japanese_xtra_utf7_imap_decode($ret) {
    if (function_exists('mb_detect_encoding')) {
        $ret = mb_convert_encoding($ret, 'EUC-JP', 'UTF7-IMAP');
    }
    return $ret;
}

/**
 * Japanese string trimming function
 *
 * trims string to defined number of symbols
 * @param string $ret string
 * @param integer $width number of symbols
 * @return string trimmed string
 * @since 1.5.1
 */
function japanese_xtra_strimwidth($ret,$width) {
    if (function_exists('mb_detect_encoding')) {
        $ret = mb_strimwidth($ret, 0, $width, '...');
    }
    return $ret;
}
