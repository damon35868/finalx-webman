<?php

namespace Finalx\Webman\Middleware;

use Illuminate\Support\Str;
use JustSteveKing\StatusCode\Http;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ResponseMiddleware implements MiddlewareInterface
{

    public function __construct(
        private ?string $message = "请求成功",
        private ?bool $camel = true
    ) {}

    protected function responseKeysToCamelCase($data)
    {
        if (is_object($data))  $data = get_object_vars($data);
        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $newKey = Str::camel($key);
                if (is_object($value) || is_array($value)) {
                    $value = $this->responseKeysToCamelCase($value);
                }
                $result[$newKey] = $value;
            }
            return is_object($data) ? (object) $result : $result;
        }
        return $data;
    }
    public function process(Request $request, callable $handler): Response
    {
        $res =  $handler($request);
        if ($res->getStatusCode() !== Http::OK->value) {
            return $res;
        }

        $data = json_decode($res->rawBody());
        return json([
            'code' => Http::OK->value,
            'message' => $this->message,
            'data' => $this->camel ? $this->responseKeysToCamelCase($data) : $data
        ]);
    }
}
