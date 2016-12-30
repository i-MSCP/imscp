<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
 */

use iMSCP_Events_Event as Event;
use iMSCP_Authentication_Result as AuthResult;

/**
 * Class iMSCP_Authentication_AuthEvent
 */
class iMSCP_Authentication_AuthEvent  extends Event
{
    /**
     * @var string Event name
     */
    protected $name = iMSCP_Events::onAuthentication;
    
    /** @var  iMSCP_Authentication */
    protected $authentication;

    /**
     * @var iMSCP_Authentication_Result
     */
    protected $authenticationResult = null;

    public function getAuthenticationService()
    {
        return $this->authentication;
    }
    
    /**
     * Has authentication result?
     * 
     * @return bool
     */
    public function hasAuthenticationResult()
    {
        return $this->authenticationResult !== null;
    }

    /**
     * Set authentication result
     * 
     * @param iMSCP_Authentication_Result $authResult
     */
    public function setAuthenticationResult(AuthResult $authResult)
    {
        $this->authenticationResult = $authResult;
    }

    /**
     * Get authentication result
     * 
     * @return iMSCP_Authentication_Result
     */
    public function getAuthenticationResult()
    {
        return $this->authenticationResult;
    }
}
