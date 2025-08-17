<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class HealthController
{
    public function __invoke(Request $request)
    {
        return response()->json(['ok' => 1]);
    }
}
