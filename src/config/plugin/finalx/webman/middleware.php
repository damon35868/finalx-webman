<?php

use JustSteveKing\StatusCode\Http;

return [
    'jwt' => [
        'missingToken'     => [Http::UNAUTHORIZED->value, "token不存在~"],
        'signatureInvalid' => [401011, "身份验证令牌无效"],
        'beforeValid'      => [401012, "身份验证令牌尚未生效"],
        'expired'          => [401013, "身份验证会话已过期，请重新登录！"],
        'unexpectedValue'  => [401014, "扩展字段不存在"],
        'jwtCacheToken'    => [401015, "您的账号已在其他地方登录了，请重新登录哦～"],
    ],
    'response' => [
        'camel' => true,
        'message' => 'success',
    ]
];
