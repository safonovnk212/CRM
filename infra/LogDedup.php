<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\JsonResponse;

class LogDedup
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        // Логируем только JSON-ответы и только если там явно указано dedup=true
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);

            if (is_array($payload) && !empty($payload['dedup'])) {
                $ctx = [
                    'dedup'        => $payload['dedup'],
                    'reason'       => $payload['dedup_reason'] ?? null,
                    'lead_id'      => $payload['id'] ?? null,
                    'created_at'   => $payload['created_at'] ?? null,
                    'window_m'     => $payload['window_m'] ?? null,
                    'phone'        => $request->input('phone'),
                    'ip'           => $request->ip(),
                    'user_agent'   => $request->userAgent(),
                    'path'         => $request->path(),
                ];

                Log::channel('lead_dedup')->info('lead_dedup', $ctx);
            }
        }

        return $response;
    }
}
