<?php

/*
 * This file is part of asm89/stack-cors.
 *
 * (c) Alexander <iam.asm89@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Asm89\Stack;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors implements HttpKernelInterface
{
    /**
     * @var \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    private $app;

    /**
     * @var \Asm89\Stack\CorsService
     */
    private $cors;

    private $defaultOptions = array(
        'allowedHeaders'         => array(),
        'allowedMethods'         => array(),
        'allowedOrigins'         => array(),
        'allowedOriginsPatterns' => array(),
        'exposedHeaders'         => false,
        'maxAge'                 => false,
        'supportsCredentials'    => false,
        'enforceVaryOrigin'      => false,
    );

    public function __construct(HttpKernelInterface $app, array $options = array())
    {
        $this->app  = $app;
        $this->cors = new CorsService(array_merge($this->defaultOptions, $options));
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $finalResponse;

        if (!$this->cors->isCorsRequest($request)) {
            $finalResponse = $this->app->handle($request, $type, $catch);
        }

        else if ($this->cors->isPreflightRequest($request)) {
            $finalResponse = $this->cors->handlePreflightRequest($request);
        }

        else if (!$this->cors->isActualRequestAllowed($request)) {
            $finalResponse = new Response('Not allowed.', 403);
        }

        else {
            $response = $this->app->handle($request, $type, $catch);
            $finalResponse = $this->cors->addActualRequestHeaders($response, $request);
        }

        $finalResponse = $this->cors->enforceVaryOrigin($finalResponse);
        return $finalResponse;
    }
}
