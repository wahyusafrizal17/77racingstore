<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionCsrfController extends Controller
{
    /**
     * Perbarui token CSRF + sentuh session agar tidak kedaluwarsa saat tab terbuka lama.
     */
    public function show(Request $request): JsonResponse
    {
        // Touch session lifetime.
        $request->session()->put('_csrf_keepalive_at', now()->timestamp);

        return response()->json([
            'token' => csrf_token(),
        ]);
    }
}
