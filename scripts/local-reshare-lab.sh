#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
GHOSTABLE_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
WORKSPACE_DIR="$(cd "${GHOSTABLE_DIR}/.." && pwd)"
CLI_DIR_DEFAULT="${WORKSPACE_DIR}/cli"

CLI_DIR="${CLI_DIR_DEFAULT}"
API_BASE="${API_BASE:-${GHOSTABLE_API:-https://ghostable.test/api/v2}}"
RUN_ID="${RUN_ID:-$(date +%Y%m%d%H%M%S)}"
ACTOR_EMAIL="${ACTOR_EMAIL:-rucci.joe@gmail.com}"
RECIPIENT_EMAIL="${RECIPIENT_EMAIL:-nick@gmail.com}"
ORGANIZATION_NAME="${ORGANIZATION_NAME:-Ghostable}"
PROJECT_NAME="${PROJECT_NAME:-Primary Server}"
DEFAULT_ENV_NAME="${ENV_NAME:-local}"
ENV_NAMES=()
LAB_ROOT="${LAB_ROOT:-${WORKSPACE_DIR}/.ghostable-reshare-lab}"
ACTOR_PREFIX="${ACTOR_PREFIX:-local.ghostable.reshare.actor.${RUN_ID}}"
RECIPIENT_PREFIX="${RECIPIENT_PREFIX:-local.ghostable.reshare.recipient.${RUN_ID}}"
ACTOR_DEVICE_NAME="${ACTOR_DEVICE_NAME:-}"
RECIPIENT_DEVICE_NAME="${RECIPIENT_DEVICE_NAME:-}"
SECRET_COUNT="${SECRET_COUNT:-5}"
SKIP_APP_SETUP=0
SKIP_CLI_BUILD=0
PREPARE_ONLY=0

usage() {
	cat <<EOF
Usage: $(basename "$0") [options]

Runs a deterministic local key re-share lab flow across server + CLI:
1) Optional fresh app:setup
2) Create/get dedicated lab environment(s)
3) Link actor device and push an encrypted secret
4) Link recipient device (creates pending key re-share request)
5) Optionally fulfill request from actor
6) Optionally pull as recipient and verify decrypted value(s)

Options:
  --skip-app-setup            Do not run php artisan app:setup --force
  --skip-cli-build            Do not run npm run build in the CLI repo
  --prepare-only              Stop after creating a pending request (no fulfill/pull)
  --cli-dir <path>            Path to cli repo (default: ../cli from ghostable)
  --api-base <url>            Ghostable API base URL (default: https://ghostable.test/api/v2)
  --run-id <id>               Stable identifier used for env + prefixes
  --org-name <name>           Organization name (default: Ghostable)
  --project-name <name>       Project name (default: Primary Server)
  --env-name <name>           Environment name. Repeat for multiple environments.
  --actor-email <email>       Actor account email (default: rucci.joe@gmail.com)
  --recipient-email <email>   Recipient account email (default: nick@gmail.com)
  --secret-count <count>      Number of decryptable secrets to push (default: 5)
  --actor-prefix <prefix>     Keychain prefix for actor identity
  --recipient-prefix <prefix> Keychain prefix for recipient identity
  --lab-root <path>           Root directory for generated actor/recipient workdirs
  --help                      Show this help
EOF
}

log() {
	printf '\n[%s] %s\n' "$(date +%H:%M:%S)" "$1"
}

write_env_fixture() {
	local destination="$1"
	local environment_name="$2"

	case "$environment_name" in
	production)
		cat > "$destination" <<EOF
APP_NAME="Primary Server"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:Pqk2Jdr9cYJ4mY5eL1sR0bZ7uD8aW6nF3xC9vT2mH4Q=
APP_URL=https://primary.ghostable.com
LOG_CHANNEL=stack
LOG_LEVEL=info
DB_CONNECTION=mysql
DB_HOST=prod-db.use1.internal
DB_PORT=3306
DB_DATABASE=primary_server
DB_USERNAME=primary_server_app
DB_PASSWORD=prod_db_ghostable_01
SESSION_DRIVER=database
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=prod-cache.use1.internal
REDIS_PASSWORD=prod_cache_ghostable_01
REDIS_PORT=6379
MAIL_MAILER=ses
MAIL_FROM_ADDRESS=deploys@primary.ghostable.com
AWS_ACCESS_KEY_ID=AKIAPRIMARYSERVERPROD01
AWS_SECRET_ACCESS_KEY=primary-server-prod-secret-01
AWS_DEFAULT_REGION=us-east-1
STRIPE_SECRET_KEY=sk_live_primary_server_prod_01
EOF
		;;
	staging)
		cat > "$destination" <<EOF
APP_NAME="Primary Server"
APP_ENV=staging
APP_DEBUG=false
APP_KEY=base64:Vn4Lq2gJd8T1mC7pS9wF6kR3xY0bH5nN2zQ8uE1tM6A=
APP_URL=https://staging.primary.ghostable.com
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=stage-db.use1.internal
DB_PORT=3306
DB_DATABASE=primary_server_staging
DB_USERNAME=primary_server_stage
DB_PASSWORD=stage_db_ghostable_01
SESSION_DRIVER=database
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=stage-cache.use1.internal
REDIS_PASSWORD=stage_cache_ghostable_01
REDIS_PORT=6379
MAIL_MAILER=log
MAIL_FROM_ADDRESS=staging@primary.ghostable.com
AWS_ACCESS_KEY_ID=AKIAPRIMARYSERVERSTAGE01
AWS_SECRET_ACCESS_KEY=primary-server-stage-secret-01
AWS_DEFAULT_REGION=us-east-1
STRIPE_SECRET_KEY=sk_test_primary_server_stage_01
EOF
		;;
	*)
		cat > "$destination" <<EOF
APP_NAME="Primary Server"
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:Rf6Mk3qLd1sP9vB4hT7nW2xC8yJ5uE0aN3zG1rH6mK2=
APP_URL=http://primary-server.ghostable.test
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=primary_server_local
DB_USERNAME=primary_server
DB_PASSWORD=password
SESSION_DRIVER=database
CACHE_STORE=redis
QUEUE_CONNECTION=database
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
MAIL_MAILER=log
MAIL_FROM_ADDRESS=local@primary-server.ghostable.test
AWS_ACCESS_KEY_ID=AKIAPRIMARYSERVERLOCAL01
AWS_SECRET_ACCESS_KEY=primary-server-local-secret-01
AWS_DEFAULT_REGION=us-east-1
STRIPE_SECRET_KEY=sk_test_primary_server_local_01
EOF
		;;
	esac
}

fail() {
	printf 'ERROR: %s\n' "$1" >&2
	exit 1
}

require_cmd() {
	command -v "$1" >/dev/null 2>&1 || fail "Missing required command: $1"
}

human_name_from_email() {
	local email="$1"
	local localpart token cleaned

	localpart="${email%%@*}"
	token="${localpart##*.}"
	token="${token//[_-]/ }"
	cleaned="$(printf '%s' "$token" | sed -E 's/[^A-Za-z0-9 ]+/ /g; s/[[:space:]]+/ /g; s/^ //; s/ $//')"

	if [[ -z "$cleaned" ]]; then
		cleaned="User"
	fi

	printf '%s' "$cleaned" | awk '{
		for (i = 1; i <= NF; i++) {
			$i = toupper(substr($i, 1, 1)) tolower(substr($i, 2));
		}
		print;
	}'
}

append_env_name() {
	local value="$1"
	local parsed trimmed existing

	if [[ -z "$value" ]]; then
		return
	fi

	IFS=',' read -r -a parsed <<< "$value"
	for trimmed in "${parsed[@]}"; do
		trimmed="$(printf '%s' "$trimmed" | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//')"
		if [[ -z "$trimmed" ]]; then
			continue
		fi

		for existing in "${ENV_NAMES[@]:-}"; do
			if [[ "$existing" == "$trimmed" ]]; then
				trimmed=''
				break
			fi
		done

		if [[ -n "$trimmed" ]]; then
			ENV_NAMES+=("$trimmed")
		fi
	done
}

run_tinker() {
	local code="$1"
	php artisan tinker --execute="$code"
}

run_cli() {
	local workdir="$1"
	local keychain_prefix="$2"
	local token="$3"
	shift 3
	(
		cd "$workdir"
		GHOSTABLE_KEYCHAIN_PREFIX="$keychain_prefix" \
		GHOSTABLE_API="$API_BASE" \
		GHOSTABLE_TOKEN="$token" \
		node "${CLI_DIR}/bin/ghostable.mjs" "$@"
	)
}

while (($# > 0)); do
	case "$1" in
	--skip-app-setup)
		SKIP_APP_SETUP=1
		;;
	--skip-cli-build)
		SKIP_CLI_BUILD=1
		;;
	--prepare-only)
		PREPARE_ONLY=1
		;;
	--cli-dir)
		CLI_DIR="${2:-}"
		shift
		;;
	--api-base)
		API_BASE="${2:-}"
		shift
		;;
	--run-id)
		RUN_ID="${2:-}"
		shift
		;;
	--org-name)
		ORGANIZATION_NAME="${2:-}"
		shift
		;;
	--project-name)
		PROJECT_NAME="${2:-}"
		shift
		;;
	--env-name)
		append_env_name "${2:-}"
		shift
		;;
	--actor-email)
		ACTOR_EMAIL="${2:-}"
		shift
		;;
	--recipient-email)
		RECIPIENT_EMAIL="${2:-}"
		shift
		;;
	--secret-count)
		SECRET_COUNT="${2:-}"
		shift
		;;
	--actor-prefix)
		ACTOR_PREFIX="${2:-}"
		shift
		;;
	--recipient-prefix)
		RECIPIENT_PREFIX="${2:-}"
		shift
		;;
	--lab-root)
		LAB_ROOT="${2:-}"
		shift
		;;
	--help|-h)
		usage
		exit 0
		;;
	*)
		fail "Unknown option: $1"
		;;
	esac
	shift
done

if [[ "${#ENV_NAMES[@]}" -eq 0 ]]; then
	append_env_name "$DEFAULT_ENV_NAME"
fi

if [[ -z "$RUN_ID" ]]; then
	fail "--run-id cannot be empty"
fi

if [[ -z "$CLI_DIR" ]]; then
	fail "--cli-dir cannot be empty"
fi

if [[ -z "$API_BASE" ]]; then
	fail "--api-base cannot be empty"
fi

if [[ "${#ENV_NAMES[@]}" -eq 0 ]]; then
	fail "At least one environment name is required"
fi

if ! [[ "$SECRET_COUNT" =~ ^[0-9]+$ ]] || ((SECRET_COUNT < 1)); then
	fail "--secret-count must be an integer >= 1"
fi

if [[ -z "$ACTOR_DEVICE_NAME" ]]; then
	ACTOR_DEVICE_NAME="$(human_name_from_email "$ACTOR_EMAIL") MacBook Pro"
fi

if [[ -z "$RECIPIENT_DEVICE_NAME" ]]; then
	RECIPIENT_DEVICE_NAME="$(human_name_from_email "$RECIPIENT_EMAIL") MacBook Pro"
fi

if [[ ! -d "$CLI_DIR" ]]; then
	fail "CLI directory does not exist: $CLI_DIR"
fi

if [[ ! -f "${CLI_DIR}/bin/ghostable.mjs" ]]; then
	fail "CLI binary not found at ${CLI_DIR}/bin/ghostable.mjs"
fi

require_cmd php
require_cmd node
require_cmd npm

NODE_MAJOR="$(node -p 'parseInt(process.versions.node.split(".")[0], 10)')"
if ((NODE_MAJOR < 20)); then
	fail "Node ${NODE_MAJOR} detected. Ghostable CLI requires Node 20+."
fi

cd "$GHOSTABLE_DIR"

log "Configuration"
printf 'Ghostable repo: %s\n' "$GHOSTABLE_DIR"
printf 'CLI repo:       %s\n' "$CLI_DIR"
printf 'API base:       %s\n' "$API_BASE"
printf 'Run ID:         %s\n' "$RUN_ID"
printf 'Org/Project:    %s / %s\n' "$ORGANIZATION_NAME" "$PROJECT_NAME"
printf 'Environments:   %s\n' "$(IFS=', '; printf '%s' "${ENV_NAMES[*]}")"
printf 'Actor email:    %s\n' "$ACTOR_EMAIL"
printf 'Recipient email:%s\n' "$RECIPIENT_EMAIL"

if [[ "$SKIP_APP_SETUP" -eq 0 ]]; then
	log "Running app:setup --force"
	php artisan app:setup --force
else
	log "Skipping app:setup"
fi

if [[ "$SKIP_CLI_BUILD" -eq 0 ]]; then
	log "Building CLI (npm run build)"
	(
		cd "$CLI_DIR"
		npm run build >/dev/null
	)
else
	log "Skipping CLI build"
fi

log "Resolving actor + recipient access tokens"
ACTOR_TOKEN="$(
	ACTOR_EMAIL="$ACTOR_EMAIL" run_tinker '$user = \App\Account\Models\User::query()->where("email", getenv("ACTOR_EMAIL"))->firstOrFail(); echo $user->createToken("reshare-lab-actor")->plainTextToken;'
)"
RECIPIENT_TOKEN="$(
	RECIPIENT_EMAIL="$RECIPIENT_EMAIL" run_tinker '$user = \App\Account\Models\User::query()->where("email", getenv("RECIPIENT_EMAIL"))->firstOrFail(); echo $user->createToken("reshare-lab-recipient")->plainTextToken;'
)"

[[ -n "$ACTOR_TOKEN" ]] || fail "Failed to create actor token"
[[ -n "$RECIPIENT_TOKEN" ]] || fail "Failed to create recipient token"

log "Resolving organization + project IDs"
ORG_ID="$(
	ORGANIZATION_NAME="$ORGANIZATION_NAME" run_tinker 'echo (string) (\App\Organization\Models\Organization::query()->where("name", getenv("ORGANIZATION_NAME"))->value("id") ?? "");'
)"
[[ -n "$ORG_ID" ]] || fail "Organization not found: $ORGANIZATION_NAME"

PROJECT_ID="$(
	ORGANIZATION_NAME="$ORGANIZATION_NAME" PROJECT_NAME="$PROJECT_NAME" run_tinker '$orgId = \App\Organization\Models\Organization::query()->where("name", getenv("ORGANIZATION_NAME"))->value("id"); if (! $orgId) { echo ""; } else { echo (string) (\App\Project\Models\Project::query()->where("organization_id", $orgId)->where("name", getenv("PROJECT_NAME"))->value("id") ?? ""); }'
)"
[[ -n "$PROJECT_ID" ]] || fail "Project not found: $PROJECT_NAME in org $ORGANIZATION_NAME"

log "Ensuring lab environments exist"
ENV_IDS=()
for environment_name in "${ENV_NAMES[@]}"; do
	environment_id="$(
		PROJECT_ID="$PROJECT_ID" ENV_NAME="$environment_name" run_tinker '$project = \App\Project\Models\Project::query()->findOrFail(getenv("PROJECT_ID")); $existing = $project->environments()->where("name", getenv("ENV_NAME"))->first(); if ($existing) { echo (string) $existing->getKey(); } else { $type = match (strtolower((string) getenv("ENV_NAME"))) { "production" => \App\Environment\Enums\EnvironmentType::PRODUCTION, "staging" => \App\Environment\Enums\EnvironmentType::STAGING, default => \App\Environment\Enums\EnvironmentType::LOCAL, }; $env = app(\App\Environment\Actions\CreateEnv::class)->handle(name: getenv("ENV_NAME"), type: $type, project: $project); echo (string) $env->getKey(); }'
	)"
	[[ -n "$environment_id" ]] || fail "Failed to create or resolve environment: $environment_name"
	ENV_IDS+=("$environment_id")
done

ACTOR_DIR="${LAB_ROOT}/actor-${RUN_ID}"
RECIPIENT_DIR="${LAB_ROOT}/recipient-${RUN_ID}"
mkdir -p "${ACTOR_DIR}/.ghostable" "${RECIPIENT_DIR}/.ghostable"

log "Writing actor + recipient manifests"
cat > "${ACTOR_DIR}/.ghostable/ghostable.yaml" <<EOF
id: ${PROJECT_ID}
name: ${PROJECT_NAME}
environments:
EOF

for environment_name in "${ENV_NAMES[@]}"; do
	printf '  %s: {}\n' "$environment_name" >> "${ACTOR_DIR}/.ghostable/ghostable.yaml"
done

cp "${ACTOR_DIR}/.ghostable/ghostable.yaml" "${RECIPIENT_DIR}/.ghostable/ghostable.yaml"

actor_local_part="${ACTOR_EMAIL%%@*}"
recipient_local_part="${RECIPIENT_EMAIL%%@*}"
actor_key="$(printf '%s' "$actor_local_part" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9._-]+/-/g; s/^-+//; s/-+$//')"
recipient_key="$(printf '%s' "$recipient_local_part" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9._-]+/-/g; s/^-+//; s/-+$//')"
[[ -n "$actor_key" ]] || actor_key="actor-user"
[[ -n "$recipient_key" ]] || recipient_key="recipient-user"
if [[ "$recipient_key" == "$actor_key" ]]; then
	recipient_key="${recipient_key}-secondary"
fi

PERSONAS_FILE="${LAB_ROOT}/personas.env"
log "Writing persona mappings for local:cli (${PERSONAS_FILE})"
cat > "${PERSONAS_FILE}" <<EOF
# key=email|prefix|workdir
${actor_key}=${ACTOR_EMAIL}|${ACTOR_PREFIX}|${ACTOR_DIR}
${recipient_key}=${RECIPIENT_EMAIL}|${RECIPIENT_PREFIX}|${RECIPIENT_DIR}
EOF

log "Preparing actor env files"
for environment_name in "${ENV_NAMES[@]}"; do
	write_env_fixture "${ACTOR_DIR}/.env.${environment_name}" "$environment_name"
done

log "Linking actor device"
run_cli "$ACTOR_DIR" "$ACTOR_PREFIX" "$ACTOR_TOKEN" \
	device link \
	--name "$ACTOR_DEVICE_NAME" \
	--platform "macos-reshare-lab"

for environment_name in "${ENV_NAMES[@]}"; do
	log "Actor pushing encrypted secret to ${environment_name} (creates environment key)"
	run_cli "$ACTOR_DIR" "$ACTOR_PREFIX" "$ACTOR_TOKEN" \
		env push \
		--env "$environment_name" \
		--file ".env.${environment_name}" \
		--conflict-mode warn \
		--assume-yes
done

log "Linking recipient device (should trigger pending re-share request)"
run_cli "$RECIPIENT_DIR" "$RECIPIENT_PREFIX" "$RECIPIENT_TOKEN" \
	device link \
	--name "$RECIPIENT_DEVICE_NAME" \
	--platform "macos-reshare-lab"

RECIPIENT_DEVICE_ID="$(
	RECIPIENT_EMAIL="$RECIPIENT_EMAIL" RECIPIENT_DEVICE_NAME="$RECIPIENT_DEVICE_NAME" run_tinker '$userId = \App\Account\Models\User::query()->where("email", getenv("RECIPIENT_EMAIL"))->value("id"); if (! $userId) { echo ""; } else { echo (string) (\App\Crypto\Models\Device::query()->where("user_id", $userId)->where("name", getenv("RECIPIENT_DEVICE_NAME"))->latest("created_at")->value("id") ?? ""); }'
)"
[[ -n "$RECIPIENT_DEVICE_ID" ]] || fail "Failed to resolve recipient device ID"

log "Resolving pending re-share requests"
REQUEST_IDS=()
MISSING_REQUEST_INDEXES=()

for index in "${!ENV_NAMES[@]}"; do
	environment_name="${ENV_NAMES[$index]}"
	environment_id="${ENV_IDS[$index]}"
	request_id="$(
		ORG_ID="$ORG_ID" ENV_ID="$environment_id" RECIPIENT_DEVICE_ID="$RECIPIENT_DEVICE_ID" run_tinker 'echo (string) (\App\Environment\Models\EnvironmentKeyReshareRequest::query()->where("organization_id", getenv("ORG_ID"))->where("environment_id", getenv("ENV_ID"))->where("target_device_id", getenv("RECIPIENT_DEVICE_ID"))->where("status", "pending")->latest("created_at")->value("id") ?? "");'
	)"

	REQUEST_IDS+=("$request_id")
	if [[ -z "$request_id" ]]; then
		MISSING_REQUEST_INDEXES+=("$index")
		log "No pending request resolved yet for ${environment_name}"
	fi
done

if [[ "${#MISSING_REQUEST_INDEXES[@]}" -gt 0 ]]; then
	log "Running reconcile without notifications for unresolved environments"
	php artisan environment:key-reshare:reconcile --organization="$ORG_ID" --no-notify >/dev/null

	for index in "${MISSING_REQUEST_INDEXES[@]}"; do
		environment_id="${ENV_IDS[$index]}"
		request_id="$(
			ORG_ID="$ORG_ID" ENV_ID="$environment_id" RECIPIENT_DEVICE_ID="$RECIPIENT_DEVICE_ID" run_tinker 'echo (string) (\App\Environment\Models\EnvironmentKeyReshareRequest::query()->where("organization_id", getenv("ORG_ID"))->where("environment_id", getenv("ENV_ID"))->where("target_device_id", getenv("RECIPIENT_DEVICE_ID"))->where("status", "pending")->latest("created_at")->value("id") ?? "");'
		)"
		REQUEST_IDS[$index]="$request_id"
	done
fi

for index in "${!ENV_NAMES[@]}"; do
	if [[ -z "${REQUEST_IDS[$index]}" ]]; then
		fail "Failed to resolve pending key re-share request for ${ENV_NAMES[$index]}"
	fi
done

if [[ "$PREPARE_ONLY" -eq 1 ]]; then
	log "Prepared pending key re-share requests (manual fulfill mode)"
	printf '\nSummary\n'
	printf '  Organization ID:   %s\n' "$ORG_ID"
	printf '  Project ID:        %s\n' "$PROJECT_ID"
	for index in "${!ENV_NAMES[@]}"; do
		printf '  Environment:       %s (%s)\n' "${ENV_NAMES[$index]}" "${ENV_IDS[$index]}"
		printf '  Request ID:        %s\n' "${REQUEST_IDS[$index]}"
	done
	printf '  Actor prefix:      %s\n' "$ACTOR_PREFIX"
	printf '  Recipient prefix:  %s\n' "$RECIPIENT_PREFIX"
	printf '  Actor workspace:   %s\n' "$ACTOR_DIR"
	printf '  Recipient workspace: %s\n' "$RECIPIENT_DIR"
	printf '  Persona file:      %s\n' "$PERSONAS_FILE"
	printf '  Persona keys:      %s, %s\n' "$actor_key" "$recipient_key"
	printf '\nNext steps\n'
	printf '  1) Login to desktop as target user/device and trigger pending state.\n'
	printf '  2) Run: cd %s && php artisan local:cli\n' "$GHOSTABLE_DIR"
	printf '     - Pick %s (or another user with access), choose "Fulfill a pending key re-share request".\n' "$actor_key"
	exit 0
fi

for index in "${!REQUEST_IDS[@]}"; do
	log "Fulfilling request for ${ENV_NAMES[$index]} as actor"
	run_cli "$ACTOR_DIR" "$ACTOR_PREFIX" "$ACTOR_TOKEN" \
		env reshare fulfill "${REQUEST_IDS[$index]}" \
		--organization "$ORG_ID"
done

for environment_name in "${ENV_NAMES[@]}"; do
	log "Pulling ${environment_name} as recipient (must decrypt successfully)"
	run_cli "$RECIPIENT_DIR" "$RECIPIENT_PREFIX" "$RECIPIENT_TOKEN" \
		env pull \
		--env "$environment_name" \
		--file ".env.${environment_name}" \
		--replace \
		--format alphabetical

	expected_env_name="$environment_name"
	if ! grep -q "^APP_ENV=${expected_env_name}$" "${RECIPIENT_DIR}/.env.${environment_name}"; then
		fail "Recipient pull succeeded but expected APP_ENV=${expected_env_name} was not found in ${RECIPIENT_DIR}/.env.${environment_name}"
	fi
done

log "Success: local re-share flow completed"
printf '\nSummary\n'
printf '  Organization ID:   %s\n' "$ORG_ID"
printf '  Project ID:        %s\n' "$PROJECT_ID"
for index in "${!ENV_NAMES[@]}"; do
	printf '  Environment:       %s (%s)\n' "${ENV_NAMES[$index]}" "${ENV_IDS[$index]}"
	printf '  Request ID:        %s\n' "${REQUEST_IDS[$index]}"
done
printf '  Actor prefix:      %s\n' "$ACTOR_PREFIX"
printf '  Recipient prefix:  %s\n' "$RECIPIENT_PREFIX"
printf '  Actor workspace:   %s\n' "$ACTOR_DIR"
printf '  Recipient workspace: %s\n' "$RECIPIENT_DIR"
printf '  Persona file:      %s\n' "$PERSONAS_FILE"
printf '  Persona keys:      %s, %s\n' "$actor_key" "$recipient_key"
