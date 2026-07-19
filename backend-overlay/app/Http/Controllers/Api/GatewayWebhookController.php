<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GatewayWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('services.wa_gateway.webhook_secret');
        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $provided = (string) $request->header('X-Gateway-Signature');

        abort_unless($secret !== '' && hash_equals($expected, $provided), 401, 'Invalid webhook signature');

        $data = $request->validate([
            'event' => ['required', 'string'],
            'session' => ['required', 'string'],
            'payload' => ['required', 'array'],
        ]);

        if ($data['event'] === 'message.received') {
            $payload = $data['payload'];
            DB::table('wa_messages')->insert([
                'session_key' => $data['session'],
                'wa_message_id' => $payload['id'] ?? null,
                'phone' => $payload['phone'] ?? '',
                'direction' => 'IN',
                'type' => strtoupper($payload['type'] ?? 'TEXT'),
                'body' => $payload['body'] ?? null,
                'status' => 'RECEIVED',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ($data['event'] === 'session.status') {
            DB::table('wa_devices')->where('session_key', $data['session'])->update([
                'status' => $data['payload']['status'] ?? 'UNKNOWN',
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
