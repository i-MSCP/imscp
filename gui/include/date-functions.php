<?php
/**
 * ispCP ω (OMEGA) a Virtual Hosting Control System
 *
 * @copyright 	2001-2006 by moleSoftware GmbH
 * @copyright 	2006-2009 by ispCP | http://isp-control.net
 * @version 	SVN: $Id$
 * @link 		http://isp-control.net
 * @author 		ispCP Team
 *
 * @license
 *   This program is free software; you can redistribute it and/or modify it under
 *   the terms of the MPL General Public License as published by the Free Software
 *   Foundation; either version 1.1 of the License, or (at your option) any later
 *   version.
 *   You should have received a copy of the MPL Mozilla Public License along with
 *   this program; if not, write to the Open Source Initiative (OSI)
 *   http://opensource.org | osi@opensource.org
 */

function check_day($day) {

    if ($day === '') return 0;
    if (preg_match("/^0?[1-9]$/D", $day)) return 1;
    if (preg_match("/^[1-9][0-9]$/D", $day) && (9 < $day) && ($day < 32)) return 1;

    return 0;
}

function check_month($month) {

    if ($month === '') return 0;
    if (preg_match("/^0?[1-9]$/D", $month)) return 1;
    if (preg_match("/^[1-9][0-9]$/D", $month) && (9 < $month) && ($month < 13)) return 1;

    return 0;
}

function check_year($year) {

    $current_year = date("Y", time());
    if ($year === '') return 0;
    if (preg_match("/^[1-9][0-9][0-9][0-9]$/D", $year) && (1899 < $year) && ($year < ($current_year + 1))) return 1;

    return 0;
}

function check_date($date) {

    if ($date === '') return 0;
    $res = preg_match_all("/^([^\.]+)\.([^\.]+)\.([^\n]+)\n/D", "$date\n", $parts, PREG_PATTERN_ORDER);
    if ($res != 1) return 0;
    if (check_day($parts[1][0]) && check_month($parts[2][0]) && check_year($parts[3][0])) return 1;

    return 0;
}

function split_date($date) {

    if (check_date($date)) {
        $res = preg_match_all("/^([^\.]+)\.([^\.]+)\.([^\n]+)\n/D", "$date\n", $parts, PREG_PATTERN_ORDER);
        return array($parts[1][0], $parts[2][0], $parts[3][0]);
    }

    return '';
}

?>