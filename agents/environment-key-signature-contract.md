# Environment Key Signing Contract

This document defines the expected request shape for environment key operations that require
`client_sig` in the Ghostable API.

## Endpoints

- `POST /api/v2/projects/{project}/environments/{name}/key`
- `POST /api/v2/projects/{project}/environments/{name}/key-envelope`

## Required base fields

- `device_id` (string, UUID): signing device.
- `fingerprint` (string): target key fingerprint.
- `envelope` (object): encrypted payload to persist.
- `client_sig` (string, Base64): Ed25519 detached signature over the signed payload.

## Key endpoint optional fields

- `version` (integer, optional)
- `created_by_device_id` (UUID, optional)
- `rotated_at` (ISO 8601 datetime, optional)

## Envelope endpoint optional fields

- `request_ids` (array of UUIDs, optional)

## Envelope payload fields

`envelope` object:

- `ciphertext_b64` (string, required)
- `nonce_b64` (string, required)
- `alg` (string, optional)
- `version` (string, optional)
- `aad_b64` (string, optional)
- `recipients` (array, optional)

## Signature verification behavior

### Canonical signed payload (primary path)

Server builds a normalized payload array and signs it as UTF-8 JSON using:

- `JSON_UNESCAPED_SLASHES`
- `JSON_UNESCAPED_UNICODE`
- `JSON_THROW_ON_ERROR`

`client_sig` is not included in signed payload.

### Key endpoint signed payload shape

```json
{
  "device_id": "...",
  "fingerprint": "...",
  "version": 2,
  "created_by_device_id": "...", // optional
  "rotated_at": "2026-04-14T12:00:00Z", // optional
  "envelope": {
    "ciphertext_b64": "...",
    "nonce_b64": "...",
    "alg": "xchacha20-poly1305", // optional
    "aad_b64": "..."          // optional
  }
}
```

Optional fields are only included if present in the request.

### Envelope endpoint signed payload shape

```json
{
  "device_id": "...",
  "fingerprint": "...",
  "envelope": {
    "ciphertext_b64": "...",
    "nonce_b64": "...",
    "alg": "xchacha20-poly1305", // optional
    "aad_b64": "..."             // optional
  },
  "request_ids": ["..."]
}
```

### Raw-body fallback (compatibility)

If canonical verification fails, server attempts a fallback by removing `client_sig` from raw request bytes and
verifying the remaining JSON string exactly. The raw fallback supports clients that may already sign a serialized payload
that still contains all JSON formatting details.

### Where this is enforced

- `app/Crypto/Actions/VerifyClientPayloadSignature.php`
- `app/Api/V2/Http/Controllers/Environment/CreateEnvironmentKey.php`
- `app/Api/V2/Http/Controllers/Environment/CreateEnvironmentKeyEnvelope.php`

## Troubleshooting signature errors

`GHO-VAL-0001: Invalid signature detected for secret "environment key"` means the bytes verified by the server do not match the bytes used during signature generation.

Most common causes:

- The client signed a payload with a different key field shape/order or key omission.
- `client_sig` was included in the signed bytes.
- Optional fields/arrays were dropped or added inconsistently.
- JSON whitespace/escaping changes (especially in transport middleware layers).

Use the debug logging around duplication flow (especially variable push payload composition) to compare the exact
`device_id`, `fingerprint`, and `envelope` fields sent before signing against the server’s expected canonical payload.
