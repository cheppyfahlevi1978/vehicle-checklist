<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WaGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function __construct(private readonly WaGatewayClient $gateway) {}

    public function index(): JsonResponse
    {
        $rows = DB::table('wa_messages')->latest()->paginate(50);
        return response()->json(['success' => true, 'data' => $rows]);
    }

    public function sendText(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^[0-9]{8,16}$/'],
            'message' => ['required', 'string', 'max:4096'],
            'consent_confirmed' => ['accepted'],
        ]);

        $result = $this->gateway->post('/sessions/'.rawurlencode($data['session']).'/send-text', [
            'phone' => $data['phone'],
            'message' => $data['message'],
            'consentConfirmed' => true,
        ]);

        DB::table('wa_messages')->insert([
            'session_key' => $data['session'],
            'wa_message_id' => $result['messageId'] ?? null,
            'phone' => $data['phone'],
            'direction' => 'OUT',
            'type' => 'TEXT',
            'body' => $data['message'],
            'status' => $result['status'] ?? 'SENT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Pesan dikirim.', 'data' => $result]);
    }
}
