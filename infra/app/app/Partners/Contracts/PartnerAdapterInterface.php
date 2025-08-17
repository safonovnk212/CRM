<?php
namespace App\Partners\Contracts;

interface PartnerAdapterInterface
{
    /** Машинный ключ адаптера, напр. "kma" */
    public function key(): string;

    /** Человекочитаемое имя для админки */
    public function displayName(): string;

    /** Достаточно ли настроек/ENV для работы адаптера */
    public function isConfigured(): bool;

    /**
     * Отправка лида партнёру.
     * Ожидается массив: [ok=>bool,code=>?int,body=>?string,status=>sent|error,meta=>array]
     * Бизнес-ошибки партнёра (напр., duplicate) не бросают исключения, а отражаются в ответе.
     */
    public function send(array $lead): array;
}
