<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2018 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * @noinspection PhpUnhandledExceptionInspection PhpDocMissingThrowsInspection
 */

/**
 * Class iMSCP_Plugin_Bruteforce
 */
class iMSCP_Plugin_Bruteforce extends iMSCP_Plugin_Action
{
    /**
     * @var int Max. login/captcha attempts before blocking time is taking effect
     */
    protected $maxAttemptsBeforeBlocking = 0;

    /**
     * @var string Client IP address
     */
    protected $clientIpAddr;

    /**
     * @var string Target form (login|captcha)
     */
    protected $target = 'login';

    /**
     * @var int Time during which an IP address is blocked
     */
    protected $isBlockedFor = 0;

    /**
     * @var int Time to wait before a new login|captcha attempts
     */
    protected $isWaitingFor = 0;

    /**
     * @var bool Tells whether or not a login attempt has been recorded
     */
    protected $recordExists = false;

    /**
     * @var string Session unique identifier
     */
    protected $sessionId;

    /**
     * @var string Last message raised
     */
    protected $message;

    /**
     * @inheritdoc
     */
    public function __construct(iMSCP_Plugin_Manager $pm, $target = 'login')
    {
        $target = (string)$target;

        if (!in_array($target, ['login', 'captcha'])) {
            throw new iMSCP_Plugin_Exception(tr('Unknown bruteforce detection type: %s', $target));
        }

        $this->maxAttemptsBeforeBlocking = $this->getConfigParam($target == 'login' ? 'BRUTEFORCE_MAX_LOGIN' : 'BRUTEFORCE_MAX_CAPTCHA', 5);
        $this->clientIpAddr = getIpAddr();
        $this->target = $target;
        $this->sessionId = session_id();
        exec_query('DELETE FROM login WHERE UNIX_TIMESTAMP() > (lastaccess + ?)', $this->getConfigParam('BRUTEFORCE_BLOCK_TIME', 15) * 60);
        parent::__construct($pm);
    }

    /**
     * @inheritdoc
     */
    protected function init()
    {
        $stmt = exec_query('SELECT lastaccess, login_count, captcha_count FROM login WHERE ipaddr = ? AND user_name IS NULL', $this->clientIpAddr);

        if (!$stmt->rowCount()) {
            return;
        }

        $row = $stmt->fetchRow();
        $this->recordExists = true;

        if ($row[$this->target . '_count'] >= $this->maxAttemptsBeforeBlocking) {
            $this->isBlockedFor = $row['lastaccess'] + ($this->getConfigParam('BRUTEFORCE_BLOCK_TIME', 15) * 60);
            return;
        }

        if ($this->getConfigParam('BRUTEFORCE_BETWEEN', 1)
            && $row[$this->target . '_count'] >= $this->getConfigParam('BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT', 2)
        ) {
            $this->isWaitingFor = $row['lastaccess'] + $this->getConfigParam('BRUTEFORCE_BETWEEN_TIME', 30);
            return;
        }
    }

    /**
     * @inheritdoc
     */
    public function getInfoFromFile()
    {
        if (NULL === $this->info) {
            $this->info = [
                'name'        => 'Bruteforce',
                'desc'        => 'Provides countermeasures against brute-force and dictionary attacks.',
                'url'         => 'http://www.i-mscp.net',
                'version'     => '1.0.0',
                'build'       => '2018122300',
                'require_api' => '1.0.0',
                'author'      => 'Laurent Declercq',
                'email'       => 'l.declercq@nuxwin.com',
                'priority'    => 0
            ];
        }

        return $this->info;
    }

    /**
     * @inheritdoc
     */
    public function &getInfo()
    {
        return $this->getInfoFromFile();
    }

    /**
     * @inheritdoc
     */
    public function &getConfig()
    {
        if (NULL === $this->config) {
            $config = iMSCP_Registry::get('config');
            $this->config = [
                'BRUTEFORCE'                          => $config['BRUTEFORCE'],
                'BRUTEFORCE_BETWEEN'                  => $config['BRUTEFORCE_BETWEEN'],
                'BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT' => $config['BRUTEFORCE_MAX_ATTEMPTS_BEFORE_WAIT'],
                'BRUTEFORCE_BETWEEN_TIME'             => $config['BRUTEFORCE_BETWEEN_TIME'],
                'BRUTEFORCE_BLOCK_TIME'               => $config['BRUTEFORCE_BLOCK_TIME'],
                'BRUTEFORCE_MAX_LOGIN'                => $config['BRUTEFORCE_MAX_LOGIN'],
                'BRUTEFORCE_MAX_CAPTCHA'              => $config['BRUTEFORCE_MAX_CAPTCHA']
            ];
        }

        return $this->config;
    }

    /**
     * @inheritdoc
     */
    public function register(iMSCP_Events_Manager_Interface $eventsManager)
    {
        // That plugin must acts early in the authentication process
        $eventsManager->registerListener(iMSCP_Events::onBeforeAuthentication, $this, 100);
    }

    /**
     * onBeforeAuthentication event listener
     *
     * @param iMSCP_Events_Event $event
     * @return null|string
     * @throws Zend_Exception
     * @throws iMSCP_Exception_Database
     * @throws iMSCP_Events_Exception
     */
    public function onBeforeAuthentication($event)
    {
        if ($this->isWaiting() || $this->isBlocked()) {
            $event->stopPropagation();
            return $this->getLastMessage();
        }

        $this->logAttempt();
        return NULL;
    }

    /**
     * Is blocked IP address?
     *
     * @return bool TRUE if the client is blocked
     * @throws Zend_Exception
     */
    public function isBlocked()
    {
        if ($this->isBlockedFor == 0) {
            return false;
        }

        $time = time();
        if ($time < $this->isBlockedFor) {
            $this->message = tr('You have been blocked for %s minutes.', strftime('%M:%S', $this->isBlockedFor - $time));
            return true;
        }

        return false;
    }

    /**
     * Is waiting IP address?
     *
     * @return bool TRUE if the client have to wait for a next login/captcha attempts, FALSE otherwise
     * @throws Zend_Exception
     */
    public function isWaiting()
    {
        if ($this->isWaitingFor == 0) {
            return false;
        }

        $time = time();
        if ($time < $this->isWaitingFor) {
            $this->message = tr('You must wait %s minutes before the next attempt.', strftime('%M:%S', $this->isWaitingFor - $time));
            return true;
        }

        return false;
    }

    /**
     * Log a login or captcha attempt
     *
     * @return void
     * @throws iMSCP_Exception_Database
     * @throws iMSCP_Events_Exception
     */
    public function logAttempt()
    {
        if (!$this->recordExists) {
            $this->createRecord();
            return;
        }

        $this->updateRecord();
    }

    /**
     * Returns last message raised
     *
     * @return string
     */
    public function getLastMessage()
    {
        return $this->message;
    }

    /**
     * Increase login|captcha attempts by 1 for $_ipAddr
     *
     * @return void
     * @throws iMSCP_Events_Exception
     * @throws iMSCP_Exception_Database
     */
    protected function updateRecord()
    {
        exec_query(
            "
                UPDATE login SET lastaccess = UNIX_TIMESTAMP(), {$this->target}_count = {$this->target}_count + 1
                WHERE ipaddr= ? AND user_name IS NULL
            ",
            $this->clientIpAddr
        );
    }

    /**
     * Create bruteforce detection record
     *
     * @return void
     * @throws iMSCP_Events_Exception
     * @throws iMSCP_Exception_Database
     */
    protected function createRecord()
    {
        exec_query(
            "REPLACE INTO login (session_id, ipaddr, {$this->target}_count, user_name, lastaccess) VALUES (?, ?, 1, NULL, UNIX_TIMESTAMP())",
            [$this->sessionId, $this->clientIpAddr]
        );
    }
}
