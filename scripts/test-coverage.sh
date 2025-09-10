#!/usr/bin/env bash
set -euo pipefail

# Defaults (overridable via env)
XDEBUG_EXT="${XDEBUG_EXT:-/Applications/Herd.app/Contents/Resources/xdebug/xdebug-83-arm64.so}"
XDEBUG_MODE="${XDEBUG_MODE:-coverage}"
MEMORY_LIMIT="${MEMORY_LIMIT:-512M}"
PEST_ARGS="${PEST_ARGS:---coverage --compact --min=90}"

php \
  -d zend_extension="$XDEBUG_EXT" \
  -d xdebug.mode="$XDEBUG_MODE" \
  -d memory_limit="$MEMORY_LIMIT" \
  ./vendor/bin/pest $PEST_ARGS