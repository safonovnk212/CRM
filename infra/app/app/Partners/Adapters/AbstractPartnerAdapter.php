<?php
namespace App\Partners\Adapters;

use App\Partners\Contracts\PartnerAdapterInterface;

abstract class AbstractPartnerAdapter implements PartnerAdapterInterface
{
    public function displayName(): string { return ucfirst($this->key()); }
    public function isConfigured(): bool { return true; }
}
