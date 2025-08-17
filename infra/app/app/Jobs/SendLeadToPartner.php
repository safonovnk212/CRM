<?php

namespace App\Jobs;

use App\Partners\PartnerRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendLeadToPartner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $leadId;

    public function __construct(int $leadId)
    {
        $this->leadId = $leadId;
        $this->onQueue("default");
    }

    public function handle(): void
    {
        // 1) достаём лида
        $lead = DB::table("leads")->where("id", $this->leadId)->first();
        if (!$lead) {
            Log::info("SendLeadToPartner:done", ["leadId" => $this->leadId, "status" => "error", "reason" => "lead_not_found"]);
            return;
        }

        // приводим к массиву
        $leadArr = (array) $lead;

        // extra может быть json-строкой — распарсим для удобства
        $extra = $leadArr["extra"] ?? null;
        if (is_string($extra)) {
            $decoded = json_decode($extra, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $leadArr["extra"] = $decoded;
            }
        }

        Log::info("SendLeadToPartner:start", ["leadId" => $this->leadId]);

        // 2) выбираем адаптер
        $router  = new PartnerRouter();
        $adapter = $router->resolveForLead($leadArr);

        if (!$adapter) {
            Log::info("SendLeadToPartner:done", ["leadId" => $this->leadId, "status" => "error", "reason" => "no_adapter"]);
            return;
        }

        // 3) отправляем (пока адаптер-заглушка вернёт stub-ответ)
        $res = $adapter->send($leadArr);

        // поддержим оба формата логов, к которым ты привык: короткий и raw
        $short = [
            "ok"   => (bool)($res["ok"]   ?? false),
            "code" => $res["code"] ?? null,
            "url"  => $res["url"]  ?? (env("PARTNER_ENDPOINT") ?: null),
        ];
        Log::info("PartnerDispatcher: response", $short);
        Log::info("PartnerDispatcher: response (raw)", [
            "ok"   => $res["ok"]   ?? null,
            "code" => $res["code"] ?? null,
            "url"  => $res["url"]  ?? (env("PARTNER_ENDPOINT") ?: null),
            "body" => $res["body"] ?? null,
        ]);

        // 4) финальный маркер
        $status = ($res["ok"] ?? false) ? "sent" : "error";
        Log::info("SendLeadToPartner:done", ["leadId" => $this->leadId, "status" => $status]);
    }
}
