<?php

namespace Finalx\Webman\Middleware;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use JustSteveKing\StatusCode\Http;
use Tinywan\Jwt\Exception\JwtCacheTokenException;
use Tinywan\Jwt\Exception\JwtTokenException;
use Tinywan\Jwt\Exception\JwtTokenExpiredException;
use Tinywan\Jwt\JwtToken;
use UnexpectedValueException;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class JwtMiddleware implements MiddlewareInterface
{

    public function __construct(
        private ?array $errorMessages = [
            'missingToken' => [Http::UNAUTHORIZED->value, "token不存在~"],
            'signatureInvalid' => [401011, "身份验证令牌无效"],
            'beforeValid' => [401012, "身份验证令牌尚未生效"],
            'expired' => [401013, "身份验证会话已过期，请重新登录！"],
            'unexpectedValue' => [401014, "获取的扩展字段不存在"],
            'jwtCacheToken' => [401015, "身份验证会话已过期，请再次登录！"],
        ]
    ) {}

    public function process(Request $request, callable $handler): Response
    {
        if ($request?->route?->param('public', false)) return $handler($request);
        $token = substr($request->header('Authorization') ?? '', 7);
        if (!$token) {
            [$code, $message] = $this->errorMessages['signatureInvalid'];
            return new Response(
                Http::UNAUTHORIZED->value,
                ['Content-Type' => 'application/json'],
                json_encode(['code' => $code, 'message' => $message])
            );
        }

        try {
            JwtToken::verify(token: $token);
        } catch (SignatureInvalidException $signatureInvalidException) {
            [$code, $message] = $this->errorMessages['signatureInvalid'];
            throw new JwtTokenException($message, $code);
        } catch (BeforeValidException $beforeValidException) {
            [$code, $message] = $this->errorMessages['beforeValid'];
            throw new JwtTokenException($message, $code);
        } catch (ExpiredException $expiredException) {
            [$code, $message] = $this->errorMessages['expired'];
            throw new JwtTokenExpiredException($message, $code);
        } catch (UnexpectedValueException $unexpectedValueException) {
            [$code, $message] = $this->errorMessages['unexpectedValue'];
            throw new JwtTokenException($message, code: $code);
        } catch (JwtCacheTokenException | \Exception $exception) {
            [$code, $message] = $this->errorMessages['jwtCacheToken'];
            $message = $message ?: $exception->getMessage();
            throw new JwtTokenException($message, $code);
        }

        return $handler($request);
    }
}
