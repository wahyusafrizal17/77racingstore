<?php

namespace App\Http\Controllers;

use App\Services\LeaveRequestService;

class LeaveVerifyController extends Controller
{
    public function __construct(private LeaveRequestService $leaves) {}

    public function __invoke(string $token)
    {
        $result = $this->leaves->findByVerifyToken($token);
        abort_unless($result && $result['signed_at'], 404);

        return view('izin.verify', [
            'result' => $result,
        ]);
    }
}
