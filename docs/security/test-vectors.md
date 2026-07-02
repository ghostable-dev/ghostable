# Security Test Vectors

The fixture file `docs/security/test-vectors.json` contains deterministic
vectors for security-sensitive compatibility checks. These vectors are public
evidence for developers reviewing the beta security design; they are not an
external audit.

The vectors cover:

- Canonical JSON ordering and null-field omission before signing.
- HKDF-SHA256 value-key derivation for an environment scope.
- XChaCha20-Poly1305 decryption with explicit key, nonce, plaintext, and AAD.
- Ed25519 signatures over canonical Ghostable records.

The focused unit tests load this fixture and assert that the current
implementation still matches it. If a vector changes, the pull request should
explain whether the change is intentional compatibility movement, a bug fix, or
only fixture maintenance.

Run:

```sh
go test -cover ./internal/crypto ./internal/security ./internal/userpresence
make security-check
```

The fixture intentionally exercises deterministic decrypt and verify paths.
Normal value and envelope writes still use random nonces and fresh keys.
