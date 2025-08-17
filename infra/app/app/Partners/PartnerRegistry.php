<?php
namespace App\Partners;

use App\Partners\Contracts\PartnerAdapterInterface;

class PartnerRegistry
{
    /** @var array<string,class-string<PartnerAdapterInterface>> */
    private array $map;

    /** @var array<string,PartnerAdapterInterface> */
    private array $instances = [];

    public function __construct(?array $map = null)
    {
        // берём карту адаптеров из конфига, если не передали вручную
        $this->map = $map ?? (config("partners.adapters") ?? []);
        if (!is_array($this->map)) {
            $this->map = [];
        }
    }

    /** Список доступных ключей */
    public function keys(): array
    {
        return array_keys($this->map);
    }

    /** Получить адаптер по ключу, либо null */
    public function get(string $key): ?PartnerAdapterInterface
    {
        if (!isset($this->map[$key])) {
            return null;
        }
        if (!isset($this->instances[$key])) {
            $cls = $this->map[$key];
            if (!class_exists($cls)) {
                return null;
            }
            $this->instances[$key] = new $cls();
        }
        return $this->instances[$key];
    }
}
