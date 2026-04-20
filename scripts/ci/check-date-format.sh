#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

if ! command -v rg >/dev/null 2>&1; then
  echo "ERROR: ripgrep (rg) is required for date format guard."
  exit 1
fi

echo "Running date format guard..."

FAIL=0

run_check() {
  local label="$1"
  local pattern="$2"
  shift 2
  local paths=("$@")

  if rg -n -S --glob '!vendor/**' --glob '!node_modules/**' --glob '!storage/**' --glob '!public/build/**' -- "$pattern" "${paths[@]}"; then
    echo ""
    echo "ERROR: $label"
    echo ""
    FAIL=1
  fi
}

# 1) Prevent human-relative time output in UI.
run_check \
  "Use absolute format (YYYY-MM-DD / YYYY-MM-DD (HH:ii)), do not use diffForHumans()." \
  "diffForHumans\\s*\\(" \
  resources/views app/Http/Controllers

# 2) Prevent non-standard PHP date output patterns in UI/PDF layer.
run_check \
  "Found non-standard PHP date format (example: d M Y, M Y, l, j F Y)." \
  "->format\\('(?:d\\s*M(?:,\\s*Y|\\s*Y)?|M\\s*Y|l,\\s*j\\s*F\\s*Y)'\\)" \
  resources/views resources/views/pdf app/Http/Controllers

# 3) Prevent locale-dependent datetime string rendering in JS for UI dates.
run_check \
  "Found locale-dependent JS datetime rendering with hardcoded locale (toLocaleString / toLocaleDateString)." \
  "(toLocaleString\\s*\\(\\s*['\\\"]|toLocaleDateString\\s*\\(\\s*['\\\"])" \
  resources/views

# 4) Prevent locale-implicit Intl datetime format for timestamps.
run_check \
  "Found Intl.DateTimeFormat(undefined, ...) for datetime rendering. Use en-CA parts mapping to YYYY-MM-DD (HH:ii)." \
  "Intl\\.DateTimeFormat\\(undefined\\s*," \
  resources/views

if [[ "$FAIL" -ne 0 ]]; then
  echo "Date format guard FAILED."
  exit 1
fi

echo "Date format guard PASSED."
