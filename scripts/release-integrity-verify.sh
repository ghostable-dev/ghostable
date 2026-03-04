#!/usr/bin/env bash

set -euo pipefail

ARTIFACT_DIR="${1:-artifacts}"

if [[ ! -d "$ARTIFACT_DIR" ]]; then
  echo "❌ Artifact directory not found: $ARTIFACT_DIR" >&2
  exit 1
fi

cd "$ARTIFACT_DIR"

if [[ ! -f "SHA256SUMS" ]]; then
  echo "❌ Missing SHA256SUMS in $ARTIFACT_DIR" >&2
  exit 1
fi

shopt -s nullglob

archive_files=( *.tar.gz )
if [[ ${#archive_files[@]} -eq 0 ]]; then
  echo "❌ No archive artifacts (*.tar.gz) found in $ARTIFACT_DIR" >&2
  exit 1
fi

if [[ ! -f "ghostable-sbom.spdx.json" ]]; then
  echo "❌ Missing SBOM file ghostable-sbom.spdx.json in $ARTIFACT_DIR" >&2
  exit 1
fi

for file in "${archive_files[@]}"; do
  if ! grep -Fq "  $file" "SHA256SUMS" && ! grep -Fq " $file" "SHA256SUMS"; then
    echo "❌ Missing checksum entry for $file in SHA256SUMS" >&2
    exit 1
  fi
done

echo "Verifying SHA256 checksums..."
shasum -a 256 -c SHA256SUMS

echo "Validating server SBOM JSON payload..."
if ! python3 -m json.tool ghostable-sbom.spdx.json >/tmp/ghostable-sbom-check.json 2>&1; then
  echo "❌ ghostable-sbom.spdx.json is not valid JSON" >&2
  exit 1
fi

if [[ ! -s "ghostable-sbom.spdx.json" ]]; then
  echo "❌ ghostable-sbom.spdx.json is empty" >&2
  exit 1
fi

for file in "${archive_files[@]}"; do
  if [[ ! -s "$file" ]]; then
    echo "❌ Archive file is empty: $file" >&2
    exit 1
  fi
done

echo "✅ Server release integrity files verified."
