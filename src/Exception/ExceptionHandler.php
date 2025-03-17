<?php

namespace Finalx\Webman\Exception;

use JustSteveKing\StatusCode\Http;
use Throwable;
use Webman\Exception\ExceptionHandlerInterface;
use Webman\Http\Request;
use Webman\Http\Response;

class ExceptionHandler implements ExceptionHandlerInterface
{
    public function report(Throwable $e) {}


    public function render(Request $request, Throwable $e): Response
    {
        $code = $e->getCode() <= http::NETWORK_AUTHENTICATION_REQUIRED->value && $e->getCode() !== 0  ? $e->getCode() : http::INTERNAL_SERVER_ERROR->value;

        return new Response(
            $code,
            ['Content-Type' => 'application/json'],
            json_encode([
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ])
        );
    }
}
