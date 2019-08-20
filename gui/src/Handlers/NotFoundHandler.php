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

use iMSCP\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Handlers\AbstractHandler;
use Slim\Http\Body;
use UnexpectedValueException;

/**
 * Class NotFoundHandler
 * @package iMSCP\Handlers
 */
class NotFoundHandler extends AbstractHandler
{
    /**
     * Invoke not found handler
     *
     * @param ServerRequestInterface $request The most recent Request object
     * @param ResponseInterface $response The most recent Response object
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface
    {
        if ($request->getMethod() === 'OPTIONS') {
            $contentType = 'text/plain';
            $output = $this->renderPlainNotFoundOutput();
        } else {
            $contentType = $this->determineContentType($request);
            switch ($contentType) {
                case 'application/json':
                    $output = $this->renderJsonNotFoundOutput();
                    break;
                case 'text/xml':
                case 'application/xml':
                    $output = $this->renderXmlNotFoundOutput();
                    break;
                case 'text/html':
                    $output = $this->renderHtmlNotFoundOutput($request);
                    break;
                default:
                    throw new UnexpectedValueException(
                        'Cannot render unknown content type ' . $contentType
                    );
            }
        }

        $body = new Body(fopen('php://temp', 'r+'));
        $body->write($output);

        return $response->withStatus(404)
            ->withHeader('Content-Type', $contentType)
            ->withBody($body);
    }

    /**
     * Render plain not found message
     *
     * @return string
     */
    protected function renderPlainNotFoundOutput(): string
    {
        return 'Not found';
    }

    /**
     * Return a response for application/json content not found
     *
     * @return string
     */
    protected function renderJsonNotFoundOutput(): string
    {
        return '{"code":404,"message":"Not found"}';
    }

    /**
     * Return a response for xml content not found
     *
     * @return string
     */
    protected function renderXmlNotFoundOutput(): string
    {
        return '<response><code>404</code><message>Not Found</message></response>';
    }

    /**
     * Return a response for text/html content not found
     *
     * @param ServerRequestInterface $request The most recent Request object
     * @return string
     */
    protected function renderHtmlNotFoundOutput(
        ServerRequestInterface $request
    ): string
    {
        ob_start();

        include(
            Registry::get('config')['GUI_ROOT_DIR']
            . '/public/errordocs/404.html'
        );

        return ob_get_clean();
    }
}
