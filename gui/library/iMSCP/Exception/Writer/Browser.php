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
 */

use iMSCP_Registry as Registry;
use iMSCP\TemplateEngine;

/**
 * iMSCP_Exception_Writer_Browser
 *
 * This exception writer writes an exception messages to the client browser.
 */
class iMSCP_Exception_Writer_Browser extends iMSCP_Exception_Writer_Abstract
{
    /**
     * @var TemplateEngine
     */
    protected $templateEngine;

    /**
     * @var string Template file path
     */
    protected $templateFile;

    /** @var  string message */
    protected $message;

    /**
     * Constructor
     *
     * @param string $templateFile Template file path
     */
    public function __construct($templateFile = 'message.tpl')
    {
        $this->templateFile = (string)$templateFile;
    }

    /**
     * onUncaughtException event listener
     *
     * @param iMSCP_Exception_Event $event
     * @return void
     */
    public function onUncaughtException(iMSCP_Exception_Event $event)
    {
        $exception = $event->getException();

        if (Registry::isRegistered('config')) {
            $debug = Registry::get('config')['DEBUG'];
        } else {
            $debug = 1;
        }

        if ($debug) {
            $exception = $event->getException();
            $this->message .= sprintf(
                "An exception has been thrown in file %s at line %s:\n\n",
                $exception->getFile(),
                $exception->getLine()
            );

            $this->message .= preg_replace('#([\t\n]+|<br \/>)#', ' ', $exception->getMessage());

            /** @var $exception iMSCP_Exception_Database */
            if ($exception instanceof iMSCP_Exception_Database) {
                $query = $exception->getQuery();
                if ($query !== '') {
                    $this->message .= sprintf("<br><br><strong>Query was:</strong><br><br>%s", $exception->getQuery());
                }
            }
        } else {
            $exception = new iMSCP_Exception_Production($exception->getMessage(), $exception->getCode(), $exception);
            $this->message = $exception->getMessage();
        }


        if ($this->templateFile) {
            $this->render();
        }

        $tpl = $this->templateEngine;

        # Fallback to inline template in case something goes wrong with template engine
        if (NULL === $tpl) {
            echo <<<HTML
<!DOCTYPE html>
<html>
    <head>
    <title>i-MSCP - internet Multi Server Control Panel - Fatal Error</title>
    <meta charset="UTF-8">
    <meta name="robots" content="nofollow, noindex">
    <link rel="icon" href="/themes/default/assets/images/favicon.ico">
    <link rel="stylesheet" href="/themes/default/assets/css/jquery-ui-black.css">
    <link rel="stylesheet" href="/themes/default/assets/css/simple.css">
    <!--[if (IE 7)|(IE 8)]>
        <link href="/themes/default/assets/css/ie78overrides.css?v=1425280612" rel="stylesheet">
    <![endif]-->
    <script src="/themes/default/assets/js/jquery/jquery.js"></script>
    <script src="/themes/default/assets/js/jquery/jquery-ui.js"></script>
    <script src="/themes/default/assets/js/imscp.js"></script>
    <script>
        $(function () { iMSCP.initApplication('simple'); });
    </script>
    </head>
    <body class="black">
        <div class="wrapper">
            <div id="content">
                <div id="message_container">
                    <h1>An unexpected error occurred</h1>
                    <pre>{$this->message}</pre>
                    <div class="buttons">
                        <a class="link_as_button" href="javascript:history.go(-1)" target="_self">Back</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
HTML;
        } else {
            $event->setParam('templateEngine', $tpl);
            layout_init($event);
            $tpl->parse('LAYOUT', 'layout');
            $tpl->prnt();
        }
    }

    /**
     * Render exception template file
     *
     * @return void
     */
    protected function render()
    {
        if (!Registry::isRegistered('db')) {
            return;
        }

        $tpl = new TemplateEngine();
        $tpl->define([
            'layout'         => 'shared/layouts/simple.tpl',
            'page'           => $this->templateFile,
            'page_message'   => 'layout',
            'backlink_block' => 'page'
        ]);

        if (Registry::isRegistered('backButtonDestination')) {
            $backButtonDestination = Registry::get('backButtonDestination');
        } else {
            $backButtonDestination = 'javascript:history.go(-1)';
        }

        $tpl->assign([
            'TR_PAGE_TITLE'           => 'i-MSCP - internet Multi Server Control Panel - Fatal Error',
            'HEADER_BLOCK'            => '',
            'BOX_MESSAGE_TITLE'       => 'An unexpected error occurred',
            'PAGE_MESSAGE'            => '',
            'BOX_MESSAGE'             => $this->message,
            'BACK_BUTTON_DESTINATION' => $backButtonDestination,
            'TR_BACK'                 => 'Back'
        ]);
        $tpl->parse('LAYOUT_CONTENT', 'page');
        $this->templateEngine = $tpl;
    }
}
