<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WaGatewayClient
{
    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['X-Gateway-Key' => config('services.wa_gateway.key')])
            ->timeout((int) config('services.wa_gateway.timeout', 45))
            ->retry(2, 500);
    }

    private function url(string $path): string
    {
        $base = rtrim((string) config('services.wa_gateway.url'), '/');
        if ($base === '') {
            throw new RuntimeException('WA_GATEWAY_URL belum diatur.');
        }
        return $base.'/'.ltrim($path, '/');
    }

    public function get(string $path): array
    {
        return $this->client()->get($this->url($path))->throw()->json();
    }

    public function post(string $path, array $payload = []): array
    {
        return $this->client()->post($this->url($path), $payload)->throw()->json();
    }
}
