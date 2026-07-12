<?php

namespace App\Jobs;

use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendMetaConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int> exponential backoff in seconds */
    public array $backoff = [30, 120, 600];

    /** @param array<string, mixed> $payload prepared by MetaConversionsService */
    public function __construct(public array $payload)
    {
    }

    public function handle(): void
    {
        // Token is read at run time (not enqueue time) so a rotated token applies immediately.
        $accessToken = SiteSetting::get('meta_capi_access_token', '');
        if (!$accessToken || empty($this->payload['pixel_id'])) {
            return;
        }

        $event = [
            'event_name'       => $this->payload['event_name'],
            'event_time'       => $this->payload['event_time'],
            'action_source'    => 'website',
            'user_data'        => (object) ($this->payload['user_data'] ?? []),
            'custom_data'      => (object) ($this->payload['custom_data'] ?? []),
        ];
        if (!empty($this->payload['event_id'])) {
            $event['event_id'] = $this->payload['event_id'];
        }
        if (!empty($this->payload['event_source_url'])) {
            $event['event_source_url'] = $this->payload['event_source_url'];
        }

        $body = ['data' => [$event]];

        $testEventCode = SiteSetting::get('meta_capi_test_event_code', '');
        if ($testEventCode) {
            $body['test_event_code'] = $testEventCode;
        }

        $response = Http::timeout(10)
            ->withToken($accessToken)
            ->post("https://graph.facebook.com/v21.0/{$this->payload['pixel_id']}/events", $body);

        if ($response->failed()) {
            throw new \RuntimeException(
                'Meta CAPI request failed: ' . $response->status() . ' ' . $response->body()
            );
        }
    }

    public function failed(\Throwable $e): void
    {
        // FR-011: conversion tracking must never surface as a business error.
        Log::warning('SendMetaConversionJob failed permanently', [
            'event_name' => $this->payload['event_name'] ?? null,
            'event_id'   => $this->payload['event_id'] ?? null,
            'error'      => $e->getMessage(),
        ]);
    }
}
