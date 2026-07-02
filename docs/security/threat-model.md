# Ghostable Threat Model

This threat model documents the current beta security design. It is not a
substitute for an external audit.

## Assets

- Plaintext environment values decrypted during local, CI, or deploy workflows.
- Local device identity material used for signatures and access grants.
- Environment keys and per-device access grants stored under `.ghostable/`.
- Signed policy, device, value, key metadata, suppression, and activity records.
- Repository history containing encrypted Ghostable state.

## Trust Boundaries

- Local machine boundary: private device identities live outside the repository
  in the platform secret store when available, or in restrictive local files.
- Repository boundary: `.ghostable/` records are intended to be committed and
  reviewed, but repository writers can propose policy, device, grant, and value
  changes.
- Automation boundary: `GHOSTABLE_CI_TOKEN` credentials are scoped automation
  secrets and are trusted only for their configured grants.
- Deploy-provider boundary: Forge, Vapor, Cloud, local `.env`, and process
  injection workflows receive plaintext values after Ghostable decrypts them.

## Attacker Model

Ghostable is designed to resist:

- Passive repository readers who can inspect committed `.ghostable/` files but
  do not have a valid local identity, environment key, or automation credential.
- Accidental or malicious modification of signed repository metadata, value
  records, device records, access grants, and policy records.
- Stale or revoked grants after environment key rotation when the repository
  state is reviewed and up to date.
- Non-interactive local use of protected production-like environments without a
  scoped automation credential.

Ghostable is not designed to fully resist:

- A compromised local device that can read local identity material or decrypted
  process memory.
- A compromised terminal, shell history, editor, deploy provider, CI runner, or
  local plaintext `.env` file after values are decrypted.
- Reviewers accepting malicious repository changes to policy, devices, grants,
  or encrypted records.
- Users placing secrets in plaintext metadata such as annotations, schema
  descriptions, comments, commit messages, or issue trackers.

## Entry Points

- CLI commands that write, read, inject, deploy, or review environment values.
- `.ghostable/` record changes submitted through git.
- Local identity store reads and writes.
- Automation credentials supplied through `GHOSTABLE_CI_TOKEN`.
- Provider deploy integrations that receive decrypted values.

## Controls

- Secret values are encrypted with XChaCha20-Poly1305 using keys derived with
  HKDF-SHA256 from per-environment material.
- Device identity uses Ed25519 signatures and X25519 access-grant envelopes.
- Signed records bind devices, policy, access grants, key metadata, activity,
  and value payloads to the signer.
- Production-like local-device operations require interactive OS user
  confirmation unless a scoped automation credential is used.
- Local plaintext cleanup, review scanning, validation, and hygiene commands
  help detect operational drift but do not replace code review.

## Residual Risks

- The project has not completed an external audit.
- Local plaintext can still exist during legitimate pull, run, deploy, and
  debugging workflows.
- Repository metadata is intentionally public to collaborators and can leak
  names, environments, device labels, change reasons, annotations, and timing.
- Security depends on prompt revocation and key rotation after device or token
  compromise.

## Review Cadence

- Run `make security-check` before security-sensitive releases.
- Review this threat model when changing cryptographic formats, access control,
  local identity storage, CI token behavior, deploy integrations, or protected
  environment behavior.
- Revisit the model after an external audit and after any validated security
  vulnerability.
