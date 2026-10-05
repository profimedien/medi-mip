#!/usr/bin/env bash
# Rauchtest der Redaktion: holt einen signierten Test-Login und ruft alle Admin-Seiten auf.
# Aufruf: deploy/smoke-admin.sh [benutzername]   (Standard: profimedien)
set -uo pipefail
USER_NAME="${1:-profimedien}"
BASE="https://flash.profimedien.com"
JAR="$(mktemp)"
trap 'rm -f "$JAR"' EXIT

LINK=$(ssh -i "$HOME/.ssh/allinkl_preview" ssh-w021a780@v1036236.kasserver.com \
  "cd /www/htdocs/w021a780/flash && php84 artisan mip:login-link $USER_NAME --minutes=2")
curl -s --http1.1 -o /dev/null -c "$JAR" -b "$JAR" -L "$LINK"

for p in /admin /admin/users /admin/users/create /admin/users/1/edit /admin/posts /admin/posts/create /admin/posts/1/edit \
         /admin/forms /admin/forms/create /admin/forms/1/edit /admin/events /admin/galleries /admin/chat-groups \
         /admin/faqs /admin/faq-categories /admin/faq-questions /admin/contacts /admin/org-units /admin/onboarding-sections \
         /admin/links /admin/suggestions /admin/material-requests /admin/referrals /admin/classifieds \
         /admin/locations /admin/departments /admin/settings /admin/organigramm; do
  OUT=$(curl -s --http1.1 -b "$JAR" -w "\n%{http_code}" "$BASE$p")
  STATUS=$(tail -1 <<< "$OUT")
  ERR=$(grep -o -m1 -E '(Internal Server Error|Exception)[^<"]{0,200}' <<< "$OUT")
  printf "%-28s %s %s\n" "$p" "$STATUS" "$ERR"
done
