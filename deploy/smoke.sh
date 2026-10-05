#!/usr/bin/env bash
# Rauchtest: meldet sich als Testkonto an und ruft alle Portalseiten auf.
# Aufruf: deploy/smoke.sh <benutzername> <passwort>
set -uo pipefail
BASE="https://flash.profimedien.com"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

TOKEN=$(curl -s --http1.1 -c "$JAR" -b "$JAR" "$BASE/anmelden" | grep -o 'name="_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"$//')
CODE=$(curl -s --http1.1 -o /dev/null -w "%{http_code} %{redirect_url}" -c "$JAR" -b "$JAR" \
  --data-urlencode "_token=$TOKEN" --data-urlencode "login=$1" --data-urlencode "password=$2" "$BASE/anmelden")
echo "Login $1: $CODE"

for p in / /news /news/1 /kalender /faq /ansprechpartner /galerien /galerien/1 /organigramm /onboarding \
         /rueckmeldungen /rueckmeldungen/1 /rueckmeldungen/2 /mitmachen /kleinanzeigen /verzeichnis /verzeichnis/1 \
         /medigram /medigram/1 /mitteilungen /profil; do
  OUT=$(curl -s --http1.1 -b "$JAR" -w "\n%{http_code}" "$BASE$p")
  STATUS=$(tail -1 <<< "$OUT")
  ERR=$(grep -o -m1 -E '(Internal Server Error|Exception)[^<"]{0,200}' <<< "$OUT")
  printf "%-22s %s %s\n" "$p" "$STATUS" "$ERR"
done
