<?php

/**
 * Disposition.class.php
 *
 * This file contains functions needed to handle content disposition headers 
 * in mime messages. See RFC 2183.
 *
 * @copyright &copy; 2003-2009 The SquirrelMail Project Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version $Id: Disposition.class.php 13549 2009-04-15 22:00:49Z jervfors $
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.2
 */

/**
 * Class that handles content disposition header
 * @package squirrelmail
 * @subpackage mime
 * @since 1.3.0
 * @todo FIXME: do we have to declare vars ($name and $properties)?
 */
class Disposition {
    /**
     * Constructor function
     * @param string $name
     */
    function Disposition($name) {
       $this->name = $name;
       $this->properties = array();
    }

    /**
     * Returns value of content disposition property
     * @param string $par content disposition property name
     * @return string
     * @since 1.3.1
     */
    function getProperty($par) {
        $value = strtolower($par);
        if (isset($this->properties[$par])) {
            return $this->properties[$par];
        }
        return '';
    }
}

