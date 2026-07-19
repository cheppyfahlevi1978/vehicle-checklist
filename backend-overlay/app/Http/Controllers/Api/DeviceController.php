<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WaGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    public function __construct(private readonly WaGatewayClient $gateway) {}

    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => DB::table('wa_devices')->orderBy('name')->get()]);
    }

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session' => ['required', 'regex:/^[a-zA-Z0-9_-]+$/', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
        ]);

        $result = $this->gateway->post('/sessions', ['session' => $data['session']]);
        DB::table('wa_devices')->updateOrInsert(
            ['session_key' => $data['session']],
            ['name' => $data['name'], 'status' => $result['status'] ?? 'STARTING', 'updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function status(string $session): JsonResponse
    {
        $result = $this->gateway->get('/sessions/'.rawurlencode($session).'/status');
        DB::table('wa_devices')->where('session_key', $session)->update(['status' => $result['status'] ?? 'UNKNOWN', 'last_seen_at' => now(), 'updated_at' => now()]);
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function qr(string $session): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->gateway->get('/sessions/'.rawurlencode($session).'/qr')]);
    }

    public function logout(string $session): JsonResponse
    {
        $result = $this->gateway->post('/sessions/'.rawurlencode($session).'/logout');
        DB::table('wa_devices')->where('session_key', $session)->update(['status' => 'DISCONNECTED', 'updated_at' => now()]);
        return response()->json(['success' => true, 'data' => $result]);
    }
}
