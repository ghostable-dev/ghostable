# Ghostable Server Repository Context

This repository is the **Ghostable backend API and operations platform**.

It is the server-side core for Ghostable, responsible for:

- API surface used by external clients (CLI and desktop).
- User authentication and account/session handling.
- Organization/project/environment data persistence and orchestration.
- Zero-knowledge secret management for environments and variables.
- Secret rotation, sharing, and access-policy enforcement.
- Human-facing dashboard capabilities (when users log in to manage accounts).
- Billing and subscription workflows for user organizations.

## What this repo is not

- It is **not** the macOS desktop client.
- It is **not** the TypeScript CLI client.

## Repo ownership and usage model

- This API is consumed by two official clients:
  - `ghostable-cli` (TypeScript, published on npm)
  - `ghostable` desktop client (macOS)
- Clients are maintained in separate repositories.

## Primary architecture surface

- Laravel 12 application with domain-driven organization in `app/`.
- API versioned endpoints live under `app/Api/V2/...`.
- Crypto/signature validation, environment key handling, and secure envelope workflows are implemented in `app/Crypto` and `app/Environment`.
- Human-facing UI/pages and admin tooling also live here for management workflows.
- Activity and audit logging is implemented through model controllers/services in this repo.

## Operational context for agents

- For signing contract and verification details for environment key writes, see:
  - `agents/environment-key-signature-contract.md`
- If you need a quick high-level mental model, treat this as:
  - **Secret-control server**
  - **Account/billing backend**
  - **API gateway for desktop + CLI clients**

## Security model (critical)

- Ghostable is a **zero-knowledge** system across desktop, CLI, and automation clients.
- This API stores only encrypted secret material and metadata, never plaintext secret values.
- All secret encryption/decryption must occur on trusted clients:
  - Desktop: trusted linked Mac device session.
  - CLI: trusted linked workstation/runner session.
  - Automation: deploy tokens, not human sessions.
- Ghostable must never require or access client private keys to:
  - store encrypted data,
  - version environment keys/values,
  - audit usage,
  - or deliver encrypted payloads.
- Any feature or fix that weakens this boundary is out-of-scope for this server and should be treated as a hard rejection.

### Primary risk scenario to watch

- The most probable server-side compromise pattern to guard against is:
  1) account takeover,
  2) unauthorized device enrollment in an organization,
  3) access to encrypted secret material through trusted-client decryption flows.
- Even though encryption remains client-side, this flow can still result in practical secret exposure if trust assumptions around device authorization are bypassed.

### Hardening posture for future changes

- Treat any feature that touches:
  - device linking/authorization,
  - organization membership,
  - environment key handoff/reshare,
  - deployment token flow,
  - or sensitive audit/security logging
  as high-risk and requiring stronger review.
- Prefer minimizing privilege escalation paths that let a single credential unlock multiple devices without explicit re-approval.
- Favor explicit, auditable, user-visible security checks around critical device/secret operations.
- Keep failure modes clear and non-silent: avoid accepting suspicious authorization updates when invariants are ambiguous.

## External context

- For broader product and architecture context, see [ghostable.dev](https://ghostable.dev).
