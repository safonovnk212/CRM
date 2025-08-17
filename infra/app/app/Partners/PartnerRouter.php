<?php
namespace App\Partners;

use App\Partners\Contracts\PartnerAdapterInterface;

class PartnerRouter
{
    public function __construct(private readonly PartnerRegistry $registry = new PartnerRegistry())
    {
    }

    /**
     * Простая маршрутизация: по extra.stream и ENV KMA_CHANNELS.
     * Возвращает адаптер или null, если ничего не подошло.
     *
     * @param array $lead (ключи: id, extra и т.п.)
     */
    public function resolveForLead(array $lead): ?PartnerAdapterInterface
    {
        $stream = $this->extractStream($lead);

        // KMA: если stream входит в список каналов из ENV — берём kma
        $kmaChannels = array_filter(array_map("trim", explode(",", (string) env("KMA_CHANNELS"))));
        if ($stream && in_array($stream, $kmaChannels, true)) {
            return $this->registry->get("kma");
        }

        // TODO: добавить правила для других партнёрок
        return null;
    }

    private function extractStream(array $lead): ?string
    {
        // вариант 1: stream в корне массива
        if (!empty($lead["stream"]) && is_string($lead["stream"])) {
            return $lead["stream"];
        }

        // вариант 2: stream в JSON app/DB поле extra
        if (!empty($lead["extra"])) {
            $extra = is_array($lead["extra"]) ? $lead["extra"] : json_decode((string) $lead["extra"], true);
            if (is_array($extra) && !empty($extra["stream"]) && is_string($extra["stream"])) {
                return $extra["stream"];
            }
        }

        return null;
    }
}
