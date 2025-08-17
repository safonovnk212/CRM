<?php
namespace App\Jobs;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLead implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Lead $lead) {}

    public function handle(): void
    {
        // помечаем "processing"
        $this->lead->status = 'processing';
        $this->lead->save();

        Log::info('ProcessLead started', [
            'lead_id'  => $this->lead->id,
            'click_id' => $this->lead->click_id,
        ]);

        // TODO: здесь будет отправка в партнёрку и постбек в Keitaro
        usleep(200000); // эмулируем задержку

        // помечаем "sent"
        $this->lead->status = 'sent';
        $this->lead->save();

        Log::info('ProcessLead job handled', ['lead_id' => $this->lead->id]);
    }
}
