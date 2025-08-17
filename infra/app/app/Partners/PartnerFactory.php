<?php
namespace App\Partners;

use App\Partners\Contracts\PartnerAdapterInterface;

class PartnerFactory
{
    /** @return array<string,class-string<PartnerAdapterInterface>> */
    public static function registry(): array
    {
        return (array) config("partners.adapters", []);
    }

    /** @return string[] список ключей доступных адаптеров */
    public static function keys(): array
    {
        return array_keys(self::registry());
    }

    /** @return PartnerAdapterInterface */
    public static function make(string $key): PartnerAdapterInterface
    {
        $registry = self::registry();
        if (!isset($registry[$key])) {
            throw new \InvalidArgumentException("Unknown partner adapter: {$key}");
        }
        $class = $registry[$key];
        $obj = app($class);
        if (!$obj instanceof PartnerAdapterInterface) {
            throw new \RuntimeException("$class must implement PartnerAdapterInterface");
        }
        return $obj;
    }
}
