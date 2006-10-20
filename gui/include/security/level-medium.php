<?php
/************************************************************************
 * IP-Filter v0.5                                     Start: 10/20/2006 *
 * ==============                               Last change: 10/20/2006 *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * File              : level-medium.php                                 *
 * -------------------------------------------------------------------- *
 * Short description : Medium security checks, a little harder to break *
 * -------------------------------------------------------------------- *
 *                                                                      *
 * -------------------------------------------------------------------- *
 * Copyright (c) 2006 by Roland Haeder                                  *
 * For more information visit: http://blog.mxchange.org                 *
 *                                                                      *
 * This program is free software. You can redistribute it and/or modify *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2 of the License.       *
 *                                                                      *
 * This program is distributed in the hope that it will be useful,      *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        *
 * GNU General Public License for more details.                         *
 *                                                                      *
 * You should have received a copy of the GNU General Public License    *
 * along with this program; if not, write to the Free Software          *
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               *
 * MA  02110-1301  USA                                                  *
 ************************************************************************/

// Check selected security level
if ($cfg['SECURITY_LEVEL'] == "none" || $cfg['SECURITY_LEVEL'] == "easy") {
	// Abort on "none" and "easy"
	return;
}

// Check REQUEST_METHOD
if (!in_array($_SERVER['REQUEST_METHOD'], explode(",", $cfg['SEC_REQUEST_METHODS']))) {
	// Block not allowed methods
	ipfilter_send(0);
	ipfilter_die();
}

// Check SERVER_PROTOCOL
if (!in_array($_SERVER['SERVER_PROTOCOL'], explode(",", $cfg['SEC_SERVER_PROTOCOLS']))) {
	// Block not allowed / misssing protocols
	ipfilter_send(10);
	ipfilter_die();
}

//
?>
