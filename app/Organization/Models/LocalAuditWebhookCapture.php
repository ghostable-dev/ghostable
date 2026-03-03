<?php

declare(strict_types=1);

namespace App\Organization\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property \Illuminate\Support\Carbon $received_at
 * @property string|null $event_id
 * @property string|null $event_type
 * @property string|null $organization_id
 * @property string $http_method
 * @property string $request_url
 * @property array<string, mixed> $headers_json
 * @property array<string, mixed>|null $payload_json
 * @property string|null $payload_raw
 * @property string|null $signature_header
 * @property string|null $timestamp_header
 * @property string $mode
 * @property int $response_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
final class LocalAuditWebhookCapture extends Model
{
    use HasUuids;

    protected $fillable = [
        'received_at',
        'event_id',
        'event_type',
        'organization_id',
        'http_method',
        'request_url',
        'headers_json',
        'payload_json',
        'payload_raw',
        'signature_header',
        'timestamp_header',
        'mode',
        'response_status',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'headers_json' => 'array',
        'payload_json' => 'array',
        'response_status' => 'integer',
    ];
}
