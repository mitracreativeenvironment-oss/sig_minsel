#!/usr/bin/env bash
# ==============================================================================
# Script Otomasi Release & Tagging (Bash / Linux / CI)
# SIG Minahasa Selatan
# ==============================================================================

set -e

TYPE="${1:-patch}"
VERSION_ARG="$2"
PUSH_FLAG=false

for arg in "$@"; do
  if [ "$arg" == "--push" ]; then
    PUSH_FLAG=true
  fi
done

VERSION_FILE="VERSION"

if [ ! -f "$VERSION_FILE" ]; then
  echo "0.1.0" > "$VERSION_FILE"
fi

CURRENT_VERSION=$(tr -d '[:space:]' < "$VERSION_FILE")
IFS='.' read -r MAJOR MINOR PATCH <<< "$CURRENT_VERSION"

case "$TYPE" in
  patch)
    PATCH=$((PATCH + 1))
    NEXT_VERSION="${MAJOR}.${MINOR}.${PATCH}"
    ;;
  minor)
    MINOR=$((MINOR + 1))
    PATCH=0
    NEXT_VERSION="${MAJOR}.${MINOR}.${PATCH}"
    ;;
  major)
    MAJOR=$((MAJOR + 1))
    MINOR=0
    PATCH=0
    NEXT_VERSION="${MAJOR}.${MINOR}.${PATCH}"
    ;;
  custom)
    if [ -z "$VERSION_ARG" ]; then
      echo "Error: Versi custom harus disertakan (contoh: ./scripts/release.sh custom 1.0.0)"
      exit 1
    fi
    NEXT_VERSION="$VERSION_ARG"
    ;;
  *)
    echo "Penggunaan: ./scripts/release.sh [patch|minor|major|custom] [version] [--push]"
    exit 1
    ;;
esac

TAG_NAME="v${NEXT_VERSION}"
TODAY=$(date +%Y-%m-%d)

echo "Memproses rilis: $CURRENT_VERSION -> $NEXT_VERSION ($TAG_NAME)"

# 1. Update VERSION file
printf "%s" "$NEXT_VERSION" > "$VERSION_FILE"

# 2. Update package.json jika ada
if [ -f "package.json" ]; then
  node -e "
    const fs = require('fs');
    const p = JSON.parse(fs.readFileSync('package.json', 'utf8'));
    p.version = '$NEXT_VERSION';
    fs.writeFileSync('package.json', JSON.stringify(p, null, 2) + '\n');
  " 2>/dev/null || sed -i "s/\"version\": \".*\"/\"version\": \"$NEXT_VERSION\"/" package.json
fi

# 3. Git commit & tag
git add -A
git commit -m "chore(release): ${TAG_NAME}"
git tag -a "${TAG_NAME}" -m "Release ${TAG_NAME}"

echo "Commit dan tag ${TAG_NAME} berhasil dibuat!"

if [ "$PUSH_FLAG" = true ]; then
  CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
  echo "Melakukan git push ke origin ${CURRENT_BRANCH}..."
  git push origin "$CURRENT_BRANCH"
  git push origin "$TAG_NAME"
  echo "Berhasil di-push ke GitHub!"
else
  echo "Untuk melakukan push manual, jalankan:"
  echo "  git push origin \$(git rev-parse --abbrev-ref HEAD)"
  echo "  git push origin $TAG_NAME"
fi
