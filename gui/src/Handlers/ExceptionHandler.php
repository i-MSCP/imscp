<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2019 by Laurent Declercq <l.declercq@nuxwin.com>
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

/** @noinspection
 * PhpUnhandledExceptionInspection
 * PhpDocMissingThrowsInspection
 * PhpUnusedParameterInspection
 * PhpIncludeInspection
 */

declare(strict_types=1);

namespace iMSCP\Handlers;

use iMSCP\Exception\BrowserExceptionWriter;
use iMSCP\Exception\ExceptionEvent;
use iMSCP\Exception\MailExceptionWriter;
use iMSCP\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\AbstractHandler;
use Slim\Http\Body;
use Throwable;
use UnexpectedValueException;

/**
 * Class ExceptionHandler
 * @package iMSCP\Handlers
 */
class ExceptionHandler extends AbstractHandler
{
    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     * @param Throwable $error The caught Throwable object
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        Throwable $error
    ): ResponseInterface
    {
        $event = new ExceptionEvent($error);
        $mailHandler = new MailExceptionWriter();
        $mailHandler->onUncaughtException($event);

        $contentType = $this->determineContentType($request);
        switch ($contentType) {
            case 'application/json':
                $output = $this->renderJsonErrorMessage($error);
                break;
            case 'text/xml':
            case 'application/xml':
                $output = $this->renderXmlErrorMessage($error);
                break;
            case 'text/html':
                $output = $this->renderHtmlErrorMessage($event);
                break;
            default:
                throw new UnexpectedValueException(
                    'Cannot render unknown content type ' . $contentType
                );
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response
            ->withStatus(500)
            ->withHeader('Content-type', $contentType)
            ->withBody($body);
    }

    /**
     * Render JSON error
     *
     * @param Throwable $error
     * @return string
     */
    protected function renderJsonErrorMessage(Throwable $error)
    {
        if (Registry::isRegistered('config')) {
            $debug = Registry::get('config')['DEBUG'];
        } else {
            $debug = 1;
        }

        $json = [
            'message' => 'i-MSCP - internet Multi Server Control Panel - Fatal Error',
        ];

        if ($debug) {
            $json['error'] = [];

            do {
                $json['error'][] = [
                    'type'    => get_class($error),
                    'code'    => $error->getCode(),
                    'message' => $error->getMessage(),
                    'file'    => $error->getFile(),
                    'line'    => $error->getLine(),
                    'trace'   => explode("\n", $error->getTraceAsString()),
                ];
            } while ($error = $error->getPrevious());
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }

    /**
     * Render XML error
     *
     * @param Throwable $error
     * @return string
     */
    protected function renderXmlErrorMessage(Throwable $error)
    {
        if (Registry::isRegistered('config')) {
            $debug = Registry::get('config')['DEBUG'];
        } else {
            $debug = 1;
        }

        $xml = "<error>\n  <message>i-MSCP - internet Multi Server Control Panel - Fatal Error</message>\n";

        if ($debug) {
            do {
                $xml .= "  <error>\n";
                $xml .= "    <type>" . get_class($error) . "</type>\n";
                $xml .= "    <code>" . $error->getCode() . "</code>\n";
                $xml .= "    <message>" . $this->createCdataSection($error->getMessage()) . "</message>\n";
                $xml .= "    <file>" . $error->getFile() . "</file>\n";
                $xml .= "    <line>" . $error->getLine() . "</line>\n";
                $xml .= "    <trace>" . $this->createCdataSection($error->getTraceAsString()) . "</trace>\n";
                $xml .= "  </error>\n";
            } while ($error = $error->getPrevious());
        }

        $xml .= "</error>";

        return $xml;
    }

    /**
     * Returns a CDATA section with the given content.
     *
     * @param string $content
     * @return string
     */
    private function createCdataSection($content)
    {
        return sprintf(
            '<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $content)
        );
    }

    /**
     * Render HTML error page
     *
     * @param ExceptionEvent $event
     * @return string
     */
    protected function renderHtmlErrorMessage(ExceptionEvent $event)
    {
        $browserHandler = new BrowserExceptionWriter();

        ob_start();

        $browserHandler->onUncaughtException($event);

        return ob_get_clean();
    }
}
