<?php
namespace App\Partners\Adapters;

use App\Partners\Adapters\AbstractPartnerAdapter;
use App\Services\PartnerDispatcher; // существующий сервис, который логирует response и raw

class KmaAdapter extends AbstractPartnerAdapter
{
    public function key(): string { return "kma"; }
    public function displayName(): string { return "KMA"; }

    public function isConfigured(): bool
    {
        return !empty(env("PARTNER_ENDPOINT"))
            && !empty(env("KMA_ACCESS_TOKEN"))
            && !empty(env("KMA_CHANNELS"));
    }

    /**
     * @param array $lead ожидает как минимум: id, name, phone, ip/extra.ip, extra.stream
     * @return array{ok:bool,code:?int,body:?string,status:"sent"|"error",meta:array}
     */
    public function send(array $lead): array
    {
        // Адрес KMA из ENV
        $endpoint = (string) env("PARTNER_ENDPOINT", "https://api.kma.biz/lead/add");

        // Канал (stream) – уже проставляется раньше в extra.stream
        $stream = $this->extractStream($lead);

        // IP — берём из $lead["ip"] либо из extra["ip"], либо пусто
        $ip = $lead["ip"] ?? null;
        if (!$ip && !empty($lead["extra"])) {
            $extra = is_array($lead["extra"]) ? $lead["extra"] : json_decode((string)$lead["extra"], true);
            if (is_array($extra) && !empty($extra["ip"])) {
                $ip = $extra["ip"];
            }
        }

        // Собираем payload под KMA
        $payload = [
            "name"    => (string)($lead["name"] ?? ""),
            "phone"   => (string)($lead["phone"] ?? ""),
            "channel" => (string)$stream,
            "ip"      => (string)($ip ?? ""),
            // плюс — можно передать click_id, offer_id, и т.д., если нужно
        ];

        // Отправляем через общий диспетчер, чтобы получить единообразные логи
         $res = app(\App\Services\PartnerDispatcher::class)->postForm(
             $endpoint, $payload, ["Authorization" => "Bearer " . (string) env("KMA_ACCESS_TOKEN"), "X-Kma-Channel" => $payload["channel"]]
        );

        // Нормализуем статус:
        // - если HTTP прошёл (code 200), но в теле код 2/13 — считаем бизнес-ошибкой => status="error"
        // - если HTTP 200 и code 0 => "sent"
        $status = "error";
        if (!empty($res["ok"]) && (int)($res["code"] ?? 0) === 200) {
            $body = (string)($res["body"] ?? "");
            $kma = json_decode($body, true);
            if (is_array($kma) && isset($kma["code"])) {
                $status = ((int)$kma["code"] === 0) ? "sent" : "error";
            } else {
                // если не JSON, но HTTP 200 — оставим как "error", чтобы понять, что ответ нестандартный
                $status = "error";
            }
        }

        // Вернём в едином формате
        return [
            "ok"     => (bool)($res["ok"] ?? false),
            "code"   => $res["code"] ?? null,
            "body"   => $res["body"] ?? null,
            "status" => $status,
            "meta"   => [
                "url"     => $res["url"] ?? $endpoint,
                "payload" => $payload,
            ],
        ];
    }

    private function extractStream(array $lead): ?string
    {
        if (!empty($lead["stream"]) && is_string($lead["stream"])) {
            return $lead["stream"];
        }
        if (!empty($lead["extra"])) {
            $extra = is_array($lead["extra"]) ? $lead["extra"] : json_decode((string)$lead["extra"], true);
            if (is_array($extra) && !empty($extra["stream"]) && is_string($extra["stream"])) {
                return $extra["stream"];
            }
        }
        return null;
    }
}
