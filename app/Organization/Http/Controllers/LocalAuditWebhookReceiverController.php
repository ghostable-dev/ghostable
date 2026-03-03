<?php

declare(strict_types=1);

namespace App\Organization\Http\Controllers;

use App\Core\Http\Controllers\Controller;
use App\Organization\Models\LocalAuditWebhookCapture;
use App\Organization\Support\LocalAuditWebhookCaptureManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;

final class LocalAuditWebhookReceiverController extends Controller
{
    public function ingest(Request $request, LocalAuditWebhookCaptureManager $captures): JsonResponse
    {
        $this->abortIfDisabled();

        $expectedToken = trim((string) config('audit_webhook_receiver.token', ''));
        $providedToken = trim((string) $request->query('token', ''));

        if ($expectedToken !== '' && ! hash_equals($expectedToken, $providedToken)) {
            abort(403);
        }

        $mode = $this->resolveMode($request);
        $status = $this->responseStatusForMode($mode);

        if ($mode === 'slow') {
            $delayMs = max(0, min(10_000, (int) $request->query('delay_ms', 0)));
            if ($delayMs > 0) {
                usleep($delayMs * 1000);
            }
        }

        $raw = $request->getContent();
        $decoded = json_decode($raw, true);
        $payload = is_array($decoded) ? $decoded : null;

        try {
            $captures->capture([
                'received_at' => now(),
                'event_id' => $this->payloadString($payload, 'id'),
                'event_type' => $this->resolveEventType($request, $payload),
                'organization_id' => $this->resolveOrganizationId($payload),
                'http_method' => strtoupper($request->method()),
                'request_url' => $request->fullUrl(),
                'headers_json' => $request->headers->all(),
                'payload_json' => $payload,
                'payload_raw' => $raw !== '' ? $raw : null,
                'signature_header' => $request->header('X-Ghostable-Signature'),
                'timestamp_header' => $request->header('X-Ghostable-Timestamp'),
                'mode' => $mode,
                'response_status' => $status,
            ]);
        } catch (RuntimeException $exception) {
            return response()->json([
                'status' => 'capture_unavailable',
                'detail' => $exception->getMessage(),
            ], 503);
        }

        return response()->json([
            'status' => $status >= 400 ? 'simulated_failure' : 'accepted',
            'mode' => $mode,
            'driver' => $captures->driverName(),
        ], $status);
    }

    public function inbox(LocalAuditWebhookCaptureManager $captures): View
    {
        $this->abortIfDisabled();

        $driver = $captures->driverName();
        $hasCaptureTable = Schema::hasTable('local_audit_webhook_captures');
        $capturesList = $driver === 'database' && $hasCaptureTable
            ? LocalAuditWebhookCapture::query()->latest('received_at')->limit(200)->get()
            : collect();

        return view('organization.local-audit-webhook-inbox', [
            'driver' => $driver,
            'captures' => $capturesList,
            'hasCaptureTable' => $hasCaptureTable,
        ]);
    }

    public function clear(LocalAuditWebhookCaptureManager $captures): RedirectResponse
    {
        $this->abortIfDisabled();

        if ($captures->driverName() !== 'database') {
            return redirect()
                ->route('local.audit-webhooks.inbox')
                ->with('status', 'Capture clear is only available when AUDIT_WEBHOOK_RECEIVER_DRIVER=database.');
        }

        if (! Schema::hasTable('local_audit_webhook_captures')) {
            return redirect()
                ->route('local.audit-webhooks.inbox')
                ->with(
                    'status',
                    'Capture table is missing. Run `php artisan local:audit-webhooks:install-captures-table` first.'
                );
        }

        LocalAuditWebhookCapture::query()->delete();

        return redirect()
            ->route('local.audit-webhooks.inbox')
            ->with('status', 'Local audit webhook captures cleared.');
    }

    private function resolveMode(Request $request): string
    {
        $mode = strtolower(trim((string) $request->query('mode', 'ok')));

        return in_array($mode, ['ok', 'fail', 'slow'], true) ? $mode : 'ok';
    }

    private function responseStatusForMode(string $mode): int
    {
        return $mode === 'fail' ? 500 : 202;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveEventType(Request $request, ?array $payload): ?string
    {
        $header = $request->header('X-Ghostable-Event');
        if (is_string($header) && $header !== '') {
            return $header;
        }

        $event = $this->payloadString($payload, 'event');
        if ($event !== null) {
            return $event;
        }

        return $this->payloadString($payload, 'type');
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function resolveOrganizationId(?array $payload): ?string
    {
        $id = $this->payloadString($payload, 'organization_id');
        if ($id !== null) {
            return $id;
        }

        if (! $payload || ! isset($payload['organization']) || ! is_array($payload['organization'])) {
            return null;
        }

        $nested = $payload['organization']['id'] ?? null;
        if (! is_scalar($nested)) {
            return null;
        }

        $normalized = trim((string) $nested);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function payloadString(?array $payload, string $key): ?string
    {
        if (! $payload || ! array_key_exists($key, $payload)) {
            return null;
        }

        if (! is_scalar($payload[$key])) {
            return null;
        }

        $value = trim((string) $payload[$key]);

        return $value !== '' ? $value : null;
    }

    private function abortIfDisabled(): void
    {
        if (! config('audit_webhook_receiver.local_routes_enabled', false)) {
            abort(404);
        }
    }
}
