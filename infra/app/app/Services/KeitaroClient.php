<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;

class KeitaroClient {
    public static function sendConversion(string $subid, string $status, $payout=null): array {
        $base = rtrim(env("KEITARO_POSTBACK_BASE",""),"/");
        if(!$base) return ["ok"=>false,"error"=>"no_base"];
        $params = array_filter([
            "subid"  => $subid,
            "status" => $status,
            "payout" => $payout,
        ], fn($v)=>$v!==null && $v!=="");
        $resp = Http::timeout(10)->get($base, $params);
        return ["ok"=>$resp->successful(), "code"=>$resp->status(), "body"=>$resp->body(), "url"=>$base, "params"=>$params];
    }
}
