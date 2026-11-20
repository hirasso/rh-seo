#!/usr/bin/env bash
set -e

cd "$(dirname "$0")/.."

# Empty POT-Creation-Date keeps the file diff-stable
./vendor/bin/wp i18n make-pot . lang/rhseo.pot \
  --include="lib/rah,rh-seo.php" \
  --exclude="build,vendor-prefixed" \
  --domain="rhseo" \
  --package-name="RH SEO" \
  --skip-js \
  --headers='{"Report-Msgid-Bugs-To":"https://github.com/hirasso/rh-seo/","POT-Creation-Date":""}'
