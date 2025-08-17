<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * Доверенные прокси:
     * "*" — доверять всем (подходит для docker-compose в приватной сети).
     * Лучше указать точные CIDR ниже (см. вариант Б).
     */
    protected $proxies = "*";

    /**
     * Заголовки, по которым определяется исходный IP/протокол.
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
