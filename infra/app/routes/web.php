<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get("/__ip", function (\Illuminate\Http\Request $r) {
    return response()->json([
        "ip"     => $r->ip(),
        "xff"    => $r->header("X-Forwarded-For"),
        "proto"  => $r->header("X-Forwarded-Proto"),
        "remote" => $r->server("REMOTE_ADDR"),
    ]);
});
