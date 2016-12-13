<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Authentication class
 *
 * This class is responsible to authenticate users using authentication handlers. An authentication handler is
 * implemented as an event listener that listen on the onAuthenticate event.
 *
 * An authentication handler which was successful must short-circuit the execution of any other authentication handlers
 * by stopping the propagation of the onAuthentication event.
 *
 * On success, an authentication handler should always return an iMSCP_Authentication_Result object, excepted when it is
 * part of a multi-factor authentication process, in which case it can return NULL.
 */
class iMSCP_Authentication
{
    /**
     * Singleton instance
     *
     * @var iMSCP_Authentication
     */
    protected static $instance = NULL;

    /**
     * @var iMSCP_Events_Manager_Interface
     */
    protected $eventManager = NULL;

    /**
     * Singleton pattern implementation -  makes "new" unavailable
     */
    protected function __construct()
    {

    }

    /**
     * Singleton pattern implementation -  makes "clone" unavailable
     *
     * @return void
     */
    protected function __clone()
    {

    }

    /**
     * Implements singleton design pattern
     *
     * @return iMSCP_Authentication Provides a fluent interface, returns self
     */
    public static function getInstance()
    {
        if (NULL === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Return an iMSCP_Events_Manager instance
     *
     * @param iMSCP_Events_Manager_Interface $events
     * @return iMSCP_Events_Manager_Interface
     */
    public function getEventManager(iMSCP_Events_Manager_Interface $events = NULL)
    {
        if (NULL !== $events) {
            $this->eventManager = $events;
            return $events;
        }

        if (NULL === $this->eventManager) {
            $this->eventManager = iMSCP_Events_Aggregator::getInstance();
        }

        return $this->eventManager;
    }

    /**
     * Process authentication
     *
     * @trigger onBeforeAuthentication
     * @trigger onAuthentication
     * @trigger onAfterAuthentication
     * @return iMSCP_Authentication_Result
     */
    public function authenticate()
    {
        $em = $this->getEventManager();
        $response = $em->dispatch(iMSCP_Events::onBeforeAuthentication, array('context' => $this));

        if (!$response->isStopped()) {
            // Process authentication through registered handlers
            $response = $em->dispatch(iMSCP_Events::onAuthentication, array('context' => $this));

            if (!($resultAuth = $response->last()) instanceof iMSCP_Authentication_Result) {
                $resultAuth = new iMSCP_Authentication_Result(
                    iMSCP_Authentication_Result::FAILURE_UNCATEGORIZED, tr('Unknown reason.')
                );
            }

            if ($resultAuth->isValid()) {
                $this->unsetIdentity(); // Prevent multiple successive calls from storing inconsistent results
                $this->setIdentity($resultAuth->getIdentity());
            }
        } else {
            $resultAuth = new iMSCP_Authentication_Result(
                iMSCP_Authentication_Result::FAILURE_UNCATEGORIZED, NULL, $response->last()
            );
        }

        $em->dispatch(iMSCP_Events::onAfterAuthentication, array('context' => $this, 'authResult' => $resultAuth));
        return $resultAuth;
    }

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return boolean
     */
    public function hasIdentity()
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        $stmt = exec_query('SELECT COUNT(session_id) AS cnt FROM login WHERE session_id = ? AND ipaddr = ?', array(
            session_id(), getipaddr()
        ));

        return (bool)$stmt->fetchRow(PDO::FETCH_COLUMN);
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return stdClass|null
     */
    public function getIdentity()
    {
        $identity = NULL;

        if (!$this->hasIdentity()) {
            return $identity;
        }

        $identity = new stdClass();
        $identity->admin_id = $_SESSION['user_id'];
        $identity->admin_name = $_SESSION['user_logged'];
        $identity->admin_type = $_SESSION['user_type'];
        $identity->email = $_SESSION['user_email'];
        $identity->created_by = $_SESSION['user_created_by'];

        return $identity;
    }

    /**
     * Set the given identity
     *
     * @trigger onBeforeSetIdentity
     * @trigger onAfterSetIdentify
     * @param stdClass $identity Identity data
     */
    public function setIdentity($identity)
    {
        $this->getEventManager()->dispatch(
            iMSCP_Events::onBeforeSetIdentity, array('context' => $this, 'identity' => $identity)
        );

        session_regenerate_id();
        $lastAccess = time();

        exec_query('INSERT INTO login (session_id, ipaddr, lastaccess, user_name) VALUES (?, ?, ?, ?)', array(
            session_id(), getIpAddr(), $lastAccess, $identity->admin_name
        ));

        $_SESSION['user_logged'] = $identity->admin_name;
        $_SESSION['user_type'] = $identity->admin_type;
        $_SESSION['user_id'] = $identity->admin_id;
        $_SESSION['user_email'] = $identity->email;
        $_SESSION['user_created_by'] = $identity->created_by;
        $_SESSION['user_login_time'] = $lastAccess;
        $_SESSION['user_identity'] = $identity;

        $this->getEventManager()->dispatch(iMSCP_Events::onAfterSetIdentity, array('context' => $this));
    }

    /**
     * Unset the current identity
     *
     * @trigger onBeforeUnsetIdentity
     * @trigger onAfterUnserIdentity
     * @return void
     */
    public function unsetIdentity()
    {
        $this->getEventManager()->dispatch(iMSCP_Events::onBeforeUnsetIdentity, array('context' => $this));

        exec_query('DELETE FROM login WHERE session_id = ?', session_id());

        $preserveList = array(
            'user_def_lang', 'user_theme', 'user_theme_color', 'show_main_menu_labels', 'pageMessages'
        );

        foreach (array_keys($_SESSION) as $sessionVariable) {
            if (!in_array($sessionVariable, $preserveList)) {
                unset($_SESSION[$sessionVariable]);
            }
        }

        $this->getEventManager()->dispatch(iMSCP_Events::onAfterUnsetIdentity, array('context' => $this));
    }
}
