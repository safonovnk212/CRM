<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

class PartnerDispatcher
{
    /**
     * @param array $lead toArray() модели Lead
     * @return array{ok:bool,skip:bool,code:int|null,body:?string,url:?string}
     */
    public static function send(array $lead): array
    {
        $endpoint = trim((string) env("PARTNER_ENDPOINT", ""));
        $method   = strtoupper((string) env("PARTNER_METHOD", "POST"));
        $authType = (string) env("PARTNER_AUTH_TYPE", "none");   // none|header|query
        $authKey  = (string) env("PARTNER_AUTH_KEY", "");
        $authVal  = (string) env("PARTNER_AUTH_VALUE", "");
        $clickKey = (string) env("PARTNER_CLICK_PARAM", "subid");

        if ($endpoint === "") {
            Log::info("PartnerDispatcher: skip (no endpoint)");
            return ["ok"=>false,"skip"=>true,"code"=>null,"body"=>null,"url"=>null];
        }

        // ---- extra из лида
        $extra = [];
        if (isset($lead["extra"]) && is_string($lead["extra"])) {
            $extra = json_decode($lead["extra"], true) ?: [];
        } elseif (isset($lead["extra"]) && is_array($lead["extra"])) {
            $extra = $lead["extra"];
        }

        // ---- KMA: токен и список каналов
        $kmaToken      = trim((string) env("KMA_ACCESS_TOKEN", ""));
        $kmaChannels   = array_values(array_filter(array_map("trim", explode(",", (string) env("KMA_CHANNELS", "")))));
        $streamIn      = (string) ($extra["stream"] ?? "");
        $chosenChannel = null;
        if ($streamIn !== "" && in_array($streamIn, $kmaChannels, true)) {
            $chosenChannel = $streamIn;
        } elseif (!empty($kmaChannels)) {
            $chosenChannel = $kmaChannels[0]; // дефолт — первый из списка
        }

        // ---- базовый payload (привычные поля)
        $payload = array_filter([
            "name"         => $lead["name"]      ?? null,
            "phone"        => $lead["phone"]     ?? null,
            $clickKey      => $lead["click_id"]  ?? null,
            "offer_id"     => $lead["offer_id"]  ?? null,

            // UTM / FB
            "utm_source"   => $extra["utm_source"]    ?? null,
            "utm_medium"   => $extra["utm_medium"]    ?? null,
            "utm_campaign" => $extra["utm_campaign"]  ?? null,
            "utm_content"  => $extra["utm_content"]   ?? null,
            "utm_term"     => $extra["utm_term"]      ?? null,
            "fbclid"       => $extra["fbclid"]        ?? null,
            "fbp"          => $extra["fbp"]           ?? null,
            "fbc"          => $extra["fbc"]           ?? null,
        ], fn($v) => $v !== null && $v !== "");

        // ---- доп. поля под KMA (требуются по их сообщению)
        $kmaPayload = array_filter([
            "channel"      => $chosenChannel,          // ОБЯЗАТЕЛЕН
            "ip"           => $lead["ip"] ?? null,     // ОБЯЗАТЕЛЕН (мы его сохраняем в leads.ip)
            // сабы в KMA-формате:
            "data1"        => $extra["sub1"] ?? null,
            "data2"        => $extra["sub2"] ?? null,
            "data3"        => $extra["sub3"] ?? null,
            "data4"        => $extra["sub4"] ?? null,
            "data5"        => $extra["sub5"] ?? null,
            // на всякий — пробросим и наш click_id как subid
            "subid"        => $lead["click_id"] ?? null,
        ], fn($v) => $v !== null && $v !== "");

        // ---- авторизация + спец-заголовок канала KMA
        $headers = [];
        $query   = [];
        if ($authType === "header" && $authKey !== "" && $authVal !== "") {
            $headers[$authKey] = trim($authVal, "\"");
        } elseif ($authType === "query" && $authKey !== "" && $authVal !== "") {
            $query[$authKey] = trim($authVal, "\"");
        }

        // KMA: если есть токен — кладём его в Authorization, а канал — в X-Kma-Channel
        if ($kmaToken !== "") {
            $headers["Authorization"] = "Bearer ".$kmaToken;
        }
        if ($chosenChannel !== null && $chosenChannel !== "") {
            $headers["X-Kma-Channel"] = $chosenChannel;
        }

        try {
            $req  = Http::timeout(20)->withHeaders($headers);
            $body = array_merge($payload, $kmaPayload, $query);

            $resp = $method === "GET"
                ? $req->get($endpoint, $body)
                : $req->asForm()->post($endpoint, $body);

            $ok   = $resp->successful();
            $code = $resp->status();
            $rbody= $resp->body();
            Log::info("PartnerDispatcher: response", ["code"=>$code, "ok"=>$ok, "url"=>$endpoint]);

            return ["ok"=>$ok, "skip"=>false, "code"=>$code, "body"=>$rbody, "url"=>$endpoint];
        } catch (\Throwable $e) {
            Log::error("PartnerDispatcher: exception ".$e->getMessage());
            return ["ok"=>false, "skip"=>false, "code"=>null, "body"=>null, "url"=>$endpoint];
        }
    }

    /**
     * Унифицированный POST JSON с логами в стиле PartnerDispatcher.
     * Возвращает: ["ok"=>bool, "code"=>?int, "url"=>string, "body"=>?string]
     */
    /**
     * Унифицированный POST JSON с логами в стиле PartnerDispatcher.
     * Возвращает: ["ok"=>bool, "code"=>?int, "url"=>string, "body"=>?string]
     */


    /**
     * Унифицированный POST JSON с логами в стиле PartnerDispatcher.
     * Возвращает: ["ok"=>bool, "code"=>?int, "url"=>string, "body"=>?string]
     */
    public function postJson(string $url, array $payload, array $headers = []): array
    {
        try {
            $resp = \Illuminate\Support\Facades\Http::timeout(15)
                ->withHeaders(array_merge([
                    "Accept" => "application/json",
                    "Content-Type" => "application/json",
                ], $headers))
                ->post($url, $payload);

            $code = $resp->status();
            $body = $resp->body();

            \Log::info("PartnerDispatcher: response", ["code"=>$code,"ok"=>$resp->successful(),"url"=>$url]);
            \Log::info("PartnerDispatcher: response (raw)", ["ok"=>true,"code"=>$code,"url"=>$url,"body"=>$body]);

            return ["ok"=>$resp->successful(),"code"=>$code,"url"=>$url,"body"=>$body];
        } catch (\Throwable $e) {
            \Log::info("PartnerDispatcher: response", ["code"=>null,"ok"=>false,"url"=>$url,"error"=>$e->getMessage()]);
            return ["ok"=>false,"code"=>null,"url"=>$url,"body"=>null];
        }
    }



    public function postForm(string $url, array $form, array $headers = []): array
    {
        $resp = Http::asForm()->withHeaders($headers)->post($url, $form);

        \Log::info('PartnerDispatcher: response', [
            'code' => $resp->status(),
            'ok'   => $resp->successful(),
            'url'  => $url,
        ]);
        \Log::info('PartnerDispatcher: response (raw)' , [
            'ok'   => $resp->successful(),
            'code' => $resp->status(),
            'url'  => $url,
            'body' => $resp->body(),
        ]);

        return [
            'ok'   => $resp->successful(),
            'code' => $resp->status(),
            'body' => $resp->body(),
            'url'  => $url,
        ];
    }
}
