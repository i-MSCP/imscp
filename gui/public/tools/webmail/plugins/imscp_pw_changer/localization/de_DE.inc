<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2011 by i-MSCP team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP Roundcube password changer
 * @copyright   2010-2011 by i-MSCP team
 * @author 		Sascha Bay
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */
 
$labels = array();
$labels['changepasswd']  = 'Passwort Ändern';
$labels['newpasswd']  = 'Neues Passwort';
$labels['confpasswd']  = 'Passwort Wiederholung';

$messages = array();
$messages['nopassword'] = 'Bitte geben Sie ein neues Passwort ein';
$messages['passwordinconsistency'] = 'Das neue Passwort und dessen Wiederholung stimmen nicht überein';
$messages['connecterror'] = 'Neues Passwort nicht gespeichert: Verbindungsfehler';
$messages['internalerror'] = 'Neues Passwort nicht gespeichert';
$messages['passwordlenght'] = 'Password hat zu wenig Zeichen! Vorgabe sind: ';
?>
