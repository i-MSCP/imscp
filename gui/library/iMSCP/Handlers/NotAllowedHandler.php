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

use iMSCP_Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\AbstractHandler;
use Slim\Http\Body;
use UnexpectedValueException;

/**
 * Class NotAllowedHandler
 * @package iMSCP\Handlers
 */
class NotAllowedHandler extends AbstractHandler
{
    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     * @param string[] $methods Allowed HTTP methods
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $methods
    ): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            $status = 200;
            $contentType = 'text/plain';
            $output = $this->renderPlainOptionsMessage($methods);
        } else {
            $status = 405;
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonNotAllowedMessage($methods);
                    break;
                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlNotAllowedMessage($methods);
                    break;
                case 'text/html':
                    $output = $this->renderHtmlNotAllowedMessage($methods);
                    break;
                default:
                    throw new UnexpectedValueException(
                        'Cannot render unknown content type ' . $contentType
                    );
            }
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);
        $allow = implode(', ', $methods);

        return $response
            ->withStatus($status)
            ->withHeader('Content-type', $contentType)
            ->withHeader('Allow', $allow)
            ->withBody($body);
    }

    /**
     * Render PLAIN message for OPTIONS response
     *
     * @param array $methods
     * @return string
     */
    protected function renderPlainOptionsMessage(array $methods): string
    {
        return 'Method Not Allowed';
    }

    /**
     * Render JSON not allowed message
     *
     * @param array $methods
     * @return string
     */
    protected function renderJsonNotAllowedMessage(array $methods): string
    {
        return '{"code":405,"message":"Method Not Allowed"}';
    }

    /**
     * Render XML not allowed message
     *
     * @param array $methods
     * @return string
     */
    protected function renderXmlNotAllowedMessage(array $methods): string
    {
        return '<response><code>405</code><message>Method Not Allowed</message></response>';
    }

    /**
     * Render HTML not allowed message
     *
     * @param array $methods
     * @return string
     */
    protected function renderHtmlNotAllowedMessage(array $methods): string
    {
        ob_start();

        include(
            iMSCP_Registry::get('config')['GUI_ROOT_DIR']
            . '/public/errordocs/404.html'
        );

        return ob_get_clean();
    }
}
