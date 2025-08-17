<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Lead;

class LeadController extends Controller
{
    public function __invoke(Request $request)
    {
        $minutes = (int) config('lead.dedup_minutes', 1440);

        // Валидация входа
        $data = $request->validate([
            'phone'   => 'required|string|max:64',
            'name'    => 'nullable|string|max:128',
            'clickid' => 'nullable|string|max:128',
            'extra'   => 'nullable|array',
        ]);

        // Поиск последнего лида по телефону во временном окне
        $existing = Lead::where('phone', $data['phone'])
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->latest('created_at')
            ->first();

        if ($existing) {
            // Логируем с отдельным одноразовым каналом (без правки config/logging.php)
            Log::build([
                'driver' => 'single',
                'path'   => storage_path('logs/lead-dedup.log'),
                'level'  => 'info',
            ])->info('Lead deduplicated', [
                'phone'     => $existing->phone,
                'lead_id'   => $existing->id,
                'window_m'  => $minutes,
                'created_at'=> optional($existing->created_at)->toISOString(),
                'reason'    => 'phone',
            ]);

            return response()->json([
                'ok'            => 1,
                'id'            => $existing->id,
                'dedup'         => true,
                'dedup_reason'  => 'phone',
                'created_at'    => optional($existing->created_at)->toISOString(),
                'window_m'      => $minutes,
            ], 200);
        }

        // Создание нового лида
        $lead = Lead::create([
            'phone'      => $data['phone'],
            'name'       => $data['name']     ?? null,
            'status'     => 'new',
            'clickid'    => $data['clickid']  ?? null,
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
            'extra'      => $data['extra']    ?? null,
        ]);

        return response()->json(['ok' => 1, 'id' => $lead->id], 201);
    }
}
