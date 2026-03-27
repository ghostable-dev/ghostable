#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GHOSTABLE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
WORKSPACE_DIR="$(cd "${GHOSTABLE_DIR}/.." && pwd)"
CLI_DIR_DEFAULT="${WORKSPACE_DIR}/cli"

CLI_DIR="${CLI_DIR_DEFAULT}"
API_BASE="${API_BASE:-${GHOSTABLE_API:-https://ghostable.test/api/v2}}"
EMAIL=""
PREFIX=""
PERSONA=""
PERSONA_FILE="${PERSONA_FILE:-${WORKSPACE_DIR}/.ghostable-reshare-lab/personas.env}"
TOKEN_NAME="local-cli-as"
WORKDIR="${PWD}"
EMAIL_SET=0
PREFIX_SET=0
WORKDIR_SET=0
PRINT_ENV=0

usage() {
	cat <<EOF
Usage: $(basename "$0") [--persona <name> | --email <email>] [options] -- <ghostable-cli args...>

Run the Ghostable CLI as a local persona by minting a token for a user and
selecting a keychain prefix (device identity profile).

Identity:
  --persona <name>            Persona key from personas file
  --email, -e <email>         User email to act as (required when --persona is not set)

Options:
  --prefix, -p <prefix>       Keychain prefix/device persona
                              (default: local.ghostable.local.<email-localpart>)
  --persona-file <path>       Persona mapping file (default: .ghostable-reshare-lab/personas.env)
  --api-base <url>            API base URL (default: https://ghostable.test/api/v2)
  --cli-dir <path>            Path to CLI repo (default: ../cli from ghostable)
  --token-name <name>         Personal access token name (default: local-cli-as)
  --workdir <path>            Directory to run CLI command from (default: current dir)
  --print-env                 Print resolved env vars instead of running CLI
  --help, -h                  Show help

Persona file format (key=email|prefix|workdir):
  joe=rucci.joe@gmail.com|local.ghostable.reshare.actor.main|/Users/you/Herd/ghostable
  nick=nick@gmail.com|local.ghostable.reshare.recipient.main

Examples:
  bash scripts/local-cli-as.sh \\
    --persona joe \\
    -- env reshare pending --role actor

  bash scripts/local-cli-as.sh \\
    --email rucci.joe@gmail.com \\
    --prefix local.ghostable.reshare.actor.main \\
    -- organization list

  bash scripts/local-cli-as.sh \\
    --email rucci.joe@gmail.com \\
    --prefix local.ghostable.reshare.actor.main \\
    -- env reshare fulfill <request-id> --organization <org-id>
EOF
}

fail() {
	printf 'ERROR: %s\n' "$1" >&2
	exit 1
}

require_cmd() {
	command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

trim() {
	local value="$1"
	value="${value#"${value%%[![:space:]]*}"}"
	value="${value%"${value##*[![:space:]]}"}"
	printf '%s' "$value"
}

default_prefix_for_email() {
	local localpart="$1"
	local normalized
	normalized="$(printf '%s' "$localpart" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+//; s/-+$//')"
	if [[ -z "$normalized" ]]; then
		normalized="user"
	fi
	printf 'local.ghostable.local.%s\n' "$normalized"
}

load_persona() {
	local persona="$1"
	local file="$2"
	local line
	local parsed_email
	local parsed_prefix
	local parsed_workdir

	if [[ ! -f "$file" ]]; then
		fail "Persona file not found: ${file} (copy scripts/local-cli-personas.example.env to this path first)"
	fi
	[[ "$persona" =~ ^[A-Za-z0-9._-]+$ ]] || fail "Invalid persona name: $persona"

	line="$(grep -E "^${persona}=" "$file" | tail -n1 || true)"
	[[ -n "$line" ]] || fail "Persona '${persona}' not found in ${file}"

	IFS='|' read -r parsed_email parsed_prefix parsed_workdir <<<"${line#*=}"
	parsed_email="$(trim "$parsed_email")"
	parsed_prefix="$(trim "$parsed_prefix")"
	parsed_workdir="$(trim "$parsed_workdir")"

	[[ -n "$parsed_email" ]] || fail "Persona '${persona}' is missing email in ${file}"

	if [[ "$EMAIL_SET" -eq 0 ]]; then
		EMAIL="$parsed_email"
	fi

	if [[ "$PREFIX_SET" -eq 0 && -n "$parsed_prefix" ]]; then
		PREFIX="$parsed_prefix"
	fi

	if [[ "$WORKDIR_SET" -eq 0 && -n "$parsed_workdir" ]]; then
		WORKDIR="$parsed_workdir"
	fi
}

while (($# > 0)); do
	case "$1" in
	--persona)
		PERSONA="${2:-}"
		shift
		;;
	--persona-file)
		PERSONA_FILE="${2:-}"
		shift
		;;
	--email|-e)
		EMAIL="${2:-}"
		EMAIL_SET=1
		shift
		;;
	--prefix|-p)
		PREFIX="${2:-}"
		PREFIX_SET=1
		shift
		;;
	--api-base)
		API_BASE="${2:-}"
		shift
		;;
	--cli-dir)
		CLI_DIR="${2:-}"
		shift
		;;
	--token-name)
		TOKEN_NAME="${2:-}"
		shift
		;;
	--workdir)
		WORKDIR="${2:-}"
		WORKDIR_SET=1
		shift
		;;
	--print-env)
		PRINT_ENV=1
		;;
	--help|-h)
		usage
		exit 0
		;;
	--)
		shift
		break
		;;
	*)
		fail "Unknown option: $1"
		;;
	esac
	shift
done

if [[ -n "$PERSONA" ]]; then
	load_persona "$PERSONA" "$PERSONA_FILE"
fi

if [[ -z "$EMAIL" ]]; then
	fail "--email is required"
fi

if [[ "$EMAIL" != *"@"* ]]; then
	fail "--email must be a valid email address"
fi

if [[ -z "$PREFIX" ]]; then
	PREFIX="$(default_prefix_for_email "${EMAIL%%@*}")"
fi

if [[ -z "$API_BASE" ]]; then
	fail "--api-base cannot be empty"
fi

if [[ -z "$CLI_DIR" ]]; then
	fail "--cli-dir cannot be empty"
fi

if [[ -z "$TOKEN_NAME" ]]; then
	fail "--token-name cannot be empty"
fi

if [[ -z "$WORKDIR" ]]; then
	fail "--workdir cannot be empty"
fi

if [[ ! -d "$CLI_DIR" ]]; then
	fail "CLI directory does not exist: $CLI_DIR"
fi

if [[ ! -f "${CLI_DIR}/bin/ghostable.mjs" ]]; then
	fail "CLI binary not found at ${CLI_DIR}/bin/ghostable.mjs"
fi

if [[ ! -d "$WORKDIR" ]]; then
	fail "Workdir does not exist: $WORKDIR"
fi

if [[ "$PRINT_ENV" -eq 0 && $# -eq 0 ]]; then
	fail "Missing command arguments after -- (for example: -- organization list)"
fi

require_cmd php
require_cmd node

cd "$GHOSTABLE_DIR"

TOKEN="$(
	EMAIL="$EMAIL" TOKEN_NAME="$TOKEN_NAME" php artisan tinker --execute='$user = \App\Account\Models\User::query()->where("email", getenv("EMAIL"))->firstOrFail(); $name = getenv("TOKEN_NAME"); $user->tokens()->where("name", $name)->delete(); echo $user->createToken($name)->plainTextToken;'
)"

[[ -n "$TOKEN" ]] || fail "Failed to mint token for ${EMAIL}"

if [[ "$PRINT_ENV" -eq 1 ]]; then
	printf 'export GHOSTABLE_API=%q\n' "$API_BASE"
	printf 'export GHOSTABLE_KEYCHAIN_PREFIX=%q\n' "$PREFIX"
	printf 'export GHOSTABLE_TOKEN=%q\n' "$TOKEN"
	exit 0
fi

printf 'Persona email:      %s\n' "$EMAIL"
if [[ -n "$PERSONA" ]]; then
	printf 'Persona key:        %s\n' "$PERSONA"
fi
printf 'Keychain prefix:    %s\n' "$PREFIX"
printf 'API base:           %s\n' "$API_BASE"
printf 'CLI command:        ghostable %s\n' "$*"
printf '\n'

(
	cd "$WORKDIR"
	GHOSTABLE_API="$API_BASE" \
	GHOSTABLE_KEYCHAIN_PREFIX="$PREFIX" \
	GHOSTABLE_TOKEN="$TOKEN" \
	node "${CLI_DIR}/bin/ghostable.mjs" "$@"
)
