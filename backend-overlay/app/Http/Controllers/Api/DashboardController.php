<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = now()->toDateString();
        return response()->json([
            'success' => true,
            'data' => [
                'devices' => DB::table('wa_devices')->count(),
                'connected_devices' => DB::table('wa_devices')->where('status', 'CONNECTED')->count(),
                'contacts' => DB::table('wa_contacts')->count(),
                'messages_in_today' => DB::table('wa_messages')->where('direction', 'IN')->whereDate('created_at', $today)->count(),
                'messages_out_today' => DB::table('wa_messages')->where('direction', 'OUT')->whereDate('created_at', $today)->count(),
                'failed_today' => DB::table('wa_messages')->where('status', 'FAILED')->whereDate('created_at', $today)->count(),
                'campaigns' => DB::table('wa_campaigns')->count(),
            ],
        ]);
    }
}
