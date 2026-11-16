#!/bin/sh
# Lance TOUTES les suites de tests simweb AVANT tout deploiement ou build.
# Appele par le hook git pre-push (githooks/pre-push) et par la CI
# (.github/workflows/test.yml).
#
# Exit 0 = tout est vert, 1 = au moins un echec -> bloque le push.
# Les suites UDP tournent SEQUENTIELLEMENT (jamais deux en parallele :
# ports 49191+ reserves aux tests, 49180 = instance reelle eventuelle).
set -e
cd "$(dirname "$0")/../.."

echo "== 1. Tests client C# SimBridge (schema des trames, parsing) =="
dotnet run -c Release --project simbridge/tests/csharp-client-tests

echo "== 2. Loopback protocole SimBridge (mock plugin UDP, sans X-Plane) =="
(cd simbridge/tests && SIMBRIDGE_TEST_PORT="${SIMBRIDGE_TEST_PORT:-49191}" python3 test_loopback.py)

echo "== 3. Lint PHP (api/ + includes/) =="
if command -v php >/dev/null 2>&1; then
    lint_out=$(find api includes -name '*.php' -type f -print0 | xargs -0 -n 20 php -l 2>&1 || true)
    if echo "$lint_out" | grep -qiE "parse error|fatal error"; then
        echo "$lint_out" | grep -iE "parse error|fatal error"
        echo "LINT PHP EN ECHEC"
        exit 1
    fi
    echo "  php -l OK ($(find api includes -name '*.php' -type f | wc -l) fichiers)"
else
    echo "  php absent sur cette machine : lint ignore (fait en CI)"
fi

echo "== TOUTES LES SUITES SIMWEB SONT VERTES =="
