<?php
namespace App\Http\Controllers;

use App\Http\Requests\IntakeRequest;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class IntakeController extends Controller
{
    public function store(IntakeRequest $request)
    {
        // Honeypot
        if ($request->filled("hp_field")) {
            return response()->json(["ok" => false, "error" => "bot detected"], 400);
        }

        // Собираем extra
        $extra = array_filter([
            "utm_source"   => $request->input("utm_source"),
            "utm_medium"   => $request->input("utm_medium"),
            "utm_campaign" => $request->input("utm_campaign"),
            "utm_content"  => $request->input("utm_content"),
            "utm_term"     => $request->input("utm_term"),
            "sub1"         => $request->input("sub1"),
            "sub2"         => $request->input("sub2"),
            "sub3"         => $request->input("sub3"),
            "sub4"         => $request->input("sub4"),
            "sub5"         => $request->input("sub5"),
            "fbclid"       => $request->input("fbclid"),
            "fbp"          => $request->input("fbp"),
            "fbc"          => $request->input("fbc"),
        "stream"       => $request->input("stream"),
        ], static fn($v) => $v !== null && $v !== "");

        // Сохраняем лид
        $lead = Lead::create([
            "name"       => $request->input("name"),
            "phone"      => $request->input("phone"),
            "offer_id"   => $request->input("offer_id"),
            "click_id"   => $request->input("click_id"),
            "status"     => "new",
            "ip"         => $request->ip(),
            "user_agent" => substr((string) $request->userAgent(), 0, 500),
            "extra"      => $extra,
        ]);

        // Лог и отправка в очередь
        Log::info("IntakeController:dispatch", ["leadId" => $lead->id]);
        Bus::dispatch(new \App\Jobs\SendLeadToPartner($lead->id));

        return response()->json(["ok" => true, "lead_id" => $lead->id]);
    }
}
