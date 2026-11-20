#!/usr/bin/env bash
#
# Fail if vendor-prefixed/ is out of sync with composer.lock (needs `composer install`)

set -euo pipefail

cd "$(dirname "$0")/.."

composer prefix --quiet

# Embeds the commit SHA and is read by nothing
git checkout -- vendor-prefixed/composer/installed.php 2>/dev/null || true

# Against the index: staged changes count as in sync
changed="$(git diff --name-only -- vendor-prefixed; git ls-files --others --exclude-standard -- vendor-prefixed)"

if [[ -n "$changed" ]]; then
  echo "✘ vendor-prefixed/ is out of sync with composer.lock. Run 'composer prefix' and commit the result:" >&2
  echo "$changed" >&2
  exit 1
fi

echo "✔ vendor-prefixed/ is in sync with composer.lock"
