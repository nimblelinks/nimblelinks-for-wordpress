#!/usr/bin/env bash
set -euo pipefail

SLUG="nimble-links"
SVN_URL="https://plugins.svn.wordpress.org/$SLUG"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
STAGE_DIR="$DIST_DIR/$SLUG"
SVN_DIR="$DIST_DIR/svn"
ASSETS_SRC="$ROOT_DIR/assets"

cd "$ROOT_DIR"

echo "==> Checking prerequisites"
command -v svn >/dev/null || { echo "svn not found. Install with: brew install svn"; exit 1; }

VERSION="$(grep -E '^[[:space:]]*\*[[:space:]]*Version:' nimble-links.php | head -n1 | awk -F: '{print $2}' | tr -d ' ')"
if [[ -z "$VERSION" ]]; then
    echo "Could not determine version from nimble-links.php"
    exit 1
fi
echo "    Version: $VERSION"

README_VERSION="$(grep -E '^Stable tag:' readme.txt | awk -F: '{print $2}' | tr -d ' ')"
if [[ "$README_VERSION" != "$VERSION" ]]; then
    echo "    Mismatch: nimble-links.php Version=$VERSION, readme.txt Stable tag=$README_VERSION"
    exit 1
fi

echo "==> Building plugin (delegates to bin/build-zip.sh)"
"$ROOT_DIR/bin/build-zip.sh" >/dev/null

if [[ ! -d "$STAGE_DIR" ]]; then
    echo "Build did not produce $STAGE_DIR"
    exit 1
fi

echo "==> Preparing SVN working copy at $SVN_DIR"
if [[ -d "$SVN_DIR/.svn" ]]; then
    svn update --quiet "$SVN_DIR"
else
    rm -rf "$SVN_DIR"
    svn checkout --quiet "$SVN_URL" "$SVN_DIR"
fi

mkdir -p "$SVN_DIR/trunk" "$SVN_DIR/tags" "$SVN_DIR/branches" "$SVN_DIR/assets"

echo "==> Syncing build into trunk/"
rsync -a --delete --exclude='.svn' "$STAGE_DIR/" "$SVN_DIR/trunk/"

echo "==> Syncing assets/ into svn assets/"
shopt -s nullglob dotglob
asset_files=("$ASSETS_SRC"/*)
shopt -u nullglob dotglob
asset_files=("${asset_files[@]/*\/.gitkeep/}")

if [[ ${#asset_files[@]} -eq 0 || ( ${#asset_files[@]} -eq 1 && -z "${asset_files[0]}" ) ]]; then
    echo "    (no files in $ASSETS_SRC — skipping)"
else
    rsync -a --delete --exclude='.svn' --exclude='.gitkeep' "$ASSETS_SRC/" "$SVN_DIR/assets/"
fi

echo "==> Reconciling SVN add/remove"
cd "$SVN_DIR"
svn status trunk assets | awk '/^!/ { print $2 }' | xargs -I{} svn rm --quiet {} || true
svn add --force --quiet trunk assets

if svn info "$SVN_URL/tags/$VERSION" >/dev/null 2>&1; then
    echo "    Tag $VERSION already exists on the server. Skipping tag step."
    TAG_CREATED=0
else
    if [[ -e "tags/$VERSION" ]]; then
        echo "    Local tags/$VERSION exists from a prior run. Removing and recreating."
        rm -rf "tags/$VERSION"
    fi
    echo "==> Tagging release $VERSION"
    svn cp --quiet trunk "tags/$VERSION"
    TAG_CREATED=1
fi

echo
echo "==> Pending SVN changes:"
svn status

echo
if [[ "$TAG_CREATED" -eq 1 ]]; then
    COMMIT_MSG="Release $VERSION"
else
    COMMIT_MSG="Update for $VERSION"
fi

cat <<EOF
==> Ready to commit.

Working copy:  $SVN_DIR
SVN URL:       $SVN_URL
Version:       $VERSION

Run the following to publish:

    cd "$SVN_DIR" && svn ci -m "$COMMIT_MSG" --username mattdaneshvar

You will be prompted for your WordPress.org SVN password
(generate at https://profiles.wordpress.org/me/profile/edit/group/3/?screen=svn-password
— this is *not* your wordpress.org login password).
EOF
